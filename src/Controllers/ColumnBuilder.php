<?php
namespace SingleQuote\DataTables\Controllers;

/**
 * Description of ColumnBuilder
 *
 * @author Wim Pruiksma <wim.pruiksma@nugtr.nl>
 */
abstract class ColumnBuilder
{
    /**
     * Protected function set fields
     *
     */
    abstract protected function fields();
    
    /**
     * The method used by the elequent builder.
     * For example : ->get() or if changed : ->remember()
     *
     * @var string
     */
    public $elequentMethod = "get";
    
    /**
     * The prefix string used by the cacher.
     * Default it generates an unique query string
     *
     * @var string
     */
    public $elequentPrefix;
    
    /**
     * The filters parsed
     *
     * @var mixed
     */
    public $filtered = [];
    
    /**
     * Set the table columns
     *
     * @var array
     */
    public $defs = [];

    /**
     * Set the table columns
     *
     * @var array
     */
    public $columns = [];

    /**
     * Set the searchable keys
     *
     * @var array
     */
    public $searchable;

    /**
     * Set the table id
     *
     * @var mixed
     */
    public $tableId;

    /**
     * Set the table classes
     *
     * @var string
     */
    public $tableClass;

    /**
     * Set the encrypted keys
     *
     * @var array
     */
    public $encrypt = [];

    /**
     * Remember which data page the user is on
     *
     * @var bool
     */
    public $rememberPage = true;

    /**
     * Save the current state of the page
     *
     * @var bool
     */
    public $stateSave = false;

    /**
     * Set the table responsitivy
     *
     * @var bool
     */
    public $responsiveTable = false;

    /**
     * When set to true.
     * The package will load the required files when datatables is not loaded.
     *
     * @var bool
     */
    public $autoLoadScripts = false;
    
    /**
     * When set to true.
     * The package will auto reload the content of the current table page
     * @var bool
     */
    public $autoReload = true;

    /**
     * The default pagelength
     *
     * @var int
     */
    public $pageLength = 10;

    /**
     * Set order
     *
     * @var mixed
     */
    public $order;

    /**
     * Set the dom
     *
     * @var string
     */
    public $dom = "<'row'<'col-sm-3'l><'col-sm-3'f>> <'row'<'col-sm-12'tr>> <'row'<'col-sm-5'i><'col-sm-7'p>>";

    /**
     * Set the translations for the header
     *
     * @return array
     */
    public function translate() : array
    {
        return [];
    }
    
    public function filter() : array
    {
        return [];
    }

    /**
     * Perform a query on the model resource
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return \Illuminate\Database\Query\Builder
     */
    public function query($query)
    {
        return $query;
    }

    /**
     * Make the class
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return $this
     */
    public function make(... $params)
    {
        $this->fields       = $this->fields();
        $this->filters      = $this->filter();
        $this->tableId      = $this->tableId ?? uniqid('laravelDataTable');
        $this->query        = call_user_func_array([$this, 'query'], $params);
        $this->order        = $this->order ?? [[ 0, "asc" ]];

        foreach ($this->translate() as $index => $translate) {
            $this->translate[$this->toLower($index)] = $translate;
        }

        return $this;
    }

    /**
     * Translate someVariable to some_variable
     * Needed for relations
     *
     * @param string $string
     * @return string
     */
    private function toLower(string $string) : string
    {
        return strtolower(preg_replace("/(?<=[a-zA-Z])(?=[A-Z])/", "_", $string));
    }
    
    /**
     * Find filter by name
     * Returns null when not found
     *
     * @param string $name
     * @return mixed
     */
    public function getFilter(string $name)
    {
        if (!isset($this->filtered[$name])) {
            return null;
        }
        
        $filter = $this->filtered[$name];
        
        if ($filter->multiple) {
            return explode(',', $filter->value);
        }
        
        return $filter->value;
    }
}
