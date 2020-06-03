<script type="text/javascript">
    dataTable{{ $view->tableId }}();
    
    //create a parent function to prevent duplicate vars
    //makes it stable to use multiple tables on the same page.
    function dataTable{{ $view->tableId }}()
    {
        /**
         * Get a parameter from the url
         * 
         * @param {type} param
         * @returns {unresolved}
         */
        function getParameterFromUrl(param)
        {
            return new URLSearchParams(window.location.search).get(param);
        }

        /**
         * Generate unique ID
         *
         * @param {String} prefix
         * @returns {String}
         */
        function uniqueId(prefix = "_")
        {
            return prefix + Math.random().toString(36).substr(2, 9);
        }

        //CONFIGS
        let uri = location.href;
        let mark = uri.includes('?') ? '&' : '?';
        let filters = ``;
        let filterSearch = ``;
        let table{{ $view->tableId }};
        let exportableColumns = [];

        $('th.col-exportable').each(function(idx) {
            exportableColumns.push(idx);
        });
        //END CONFIGS

        //FILTERS
        @foreach($view->filters as $filter)

        $(`#{{ $view->tableId }}datatable-filters`).append(`{!! $filter->build !!}`);

        $(document).on('{{ $filter->getTrigger() }}', `#{{ $filter->getID() }}`, () => {
            triggerFilters({{ $filter->getMultiple() }});
        });

        @endforeach
        //END FILTERS
        
        //Set searchColumns        
        $(document).on('input', '.search-filter', (e) => {
            filterSearch = `&filtersearch=`;
            let input = $(e.currenttarget);
            $(`#{{ $view->tableId }} .search-filter`).each((i, input) => {
                if($(input).val().length > 0){
                    filterSearch += `${$(input).attr('name')};${$(input).val()}|`;
                }
            });
            reloadTable();
        });
        //END searchColumns 

        /**
         * Trigger the filters
         * 
         */
        function triggerFilters(multiple = false)
        {
            filters = `&filter=`;
            $(`.datatable-filter`).each((index, e) => {
                if(!$(e).val() || $(e).val() === '**' || !$(e).attr('id')){
                    return;
                }
                let type = multiple ? 'm' : 's';

                filters += `${$(e).attr('name')};${type}*${$(e).val()}|`;
            });

            reloadTable();
        }

        /**
         * Reload the table
         * 
         */
        function reloadTable()
        {
            table{{ $view->tableId }}.ajax.url(`${uri}${mark}laravel-datatables=active&id={{ $view->id }}&${filters}&${filterSearch}`).load();
        }

        @if($view->autoReload)
        setInterval(() => {
            table{{ $view->tableId }}.ajax.reload( null, false );
        },10000);
        @endif

        @if(__("datatables") === 'datatables')
            const locale = @json(__("datatables::datatables"));
        @else
            const locale = @json(__("datatables"));
        @endif
        
        let Json{{ $view->tableId }} = {
            "language" : locale,
            "paging": true,
            "processing": true,
            "serverSide": true,
            "stateSave": {!! $view->stateSave !!},
            "responsive": {!! $view->responsiveTable !!},
            "dom" : "{!! $view->dom !!}",
            "ajax": `${uri}${mark}laravel-datatables=active&tableId={{ $view->tableId }}&id={{ $view->id }}${filters}${filterSearch}`,
            "pageLength" : {{ $view->pageLength }},
            "order" : [
                @foreach($view->order as $order)
                [
                    {{ $order[0] }},
                    "{{ $order[1] }}"
                ],
                @endforeach
            ],
            "columns": @json($view->columns),
            "columnDefs": [
                @foreach($view->defs as $def)
                {
                    "class" : "{{ isset($def["class"]) ? $def["class"] : '' }}",
                    "render": function ( data, type, row ) {
                        let output = "";
                        @foreach($def['rendered'] as $index => $render)

                            @php
                                $class = $def["def"][$index];
                            @endphp

                            function {{$def['id']}}{{ $index }}(data, type, row) {
                                
                                @if($class->condition)
                                let condition = row.{!! $class->condition !!};

                                if(!condition){
                                    return "{!! $class->before !!} {{ $class->returnWhenEmpty }} {!! $class->after !!}";
                                }
                                @endif
                                
                                @if($class->overwrite)
                                    @if(strlen($class->columnPath())> 0)
                                    if(!row.{{ $class->columnPath() }}){
                                        return "{!! $class->before !!} {{ $class->returnWhenEmpty }} {!! $class->after !!}";
                                    }
                                    @endif
                                    data = row.{{ $class->overwrite }};
                                @endif
                                //empty check
                                @if($class->emptyCheck)

                                if(!data @if(strlen($class->columnPath())> 0) || !row.{{ $class->columnPath() }} @endif){
                                    return "{!! $class->before !!} {{ $class->returnWhenEmpty }} {!! $class->after !!}";
                                }
                                @endif

                                {!! $render !!}
                            };

                            output += {{$def['id']}}{{ $index }}(data, type, row);
                        @endforeach

                        return output;
                    },
                    "targets": {{ $def["target"] }}
                },
                @endforeach
            ],
            "buttons": [
                {
                    extend: 'reload',
                    text: 'Limpar Filtro',
                    action: function ( e, dt, node, config ) {
                        dt.search('').draw();
                    }
                }, {
                    extend: 'print',
                    text: 'Imprimir',
                    exportOptions: {
                        columns: exportableColumns,
                    }
                }, {
                    extend: 'excelHtml5',
                    text: 'Excel',
                    exportOptions: {
                        columns: exportableColumns,
                    }
                }, {
                    extend: 'csvHtml5',
                    text: 'Csv',
                    exportOptions: {
                        columns: exportableColumns
                    }
                }, {
                    extend: 'pdfHtml5',
                    text: 'Pdf',
                    orientation: 'landscape',
                    customize: function (doc) {
                        doc.content[1].table.widths =
                            Array(doc.content[1].table.body[0].length + 1).join('*').split('');
                    },
                    exportOptions: {
                        columns: exportableColumns
                    }
                }
            ],
        };
        @if($view->rememberPage)
            if(getParameterFromUrl('datatables-page')){
                Json{{ $view->tableId }}.displayStart = (getParameterFromUrl('datatables-page') -1) * Json{{ $view->tableId }}.pageLength;
            }
        @endif

        /**
         * Init the datatable
         *
         */
        function initDatatable{{ $view->tableId }}()
        {
            table{{ $view->tableId }} = $('#{{ $view->tableId }}').DataTable(Json{{ $view->tableId }});
            @if($view->rememberPage)
            $(document).on('click', '#{{ $view->tableId }}_wrapper .paginate_button', () => {
               
               let url = location.href.split(`datatables-page=`)[0];
               if(url.slice(-1) === '?' || url.slice(-1) === '&'){
                   url = url.slice(0, -1);
               }
               let mark = url.includes('?') ? "&" : "?";
               
               window.history.pushState('laravel-datatable', 'pagination', `${url}${mark}datatables-page=${table{{ $view->tableId }}.page.info().page + 1}`);
            });
            @endif
        }

        /**
         * On document ready
         *
         */
        $(document).ready(() => {
            @if($view->autoLoadScripts)
            if($.fn.DataTable){
                initDatatable{{ $view->tableId }}();
            }
            @else
            beforeInit();

            function beforeInit(retry = 0)
            {
                if(!$.fn.DataTable){
                    if(retry >= 10){
                        return console.error('Laravel Datatable could not be loaded!');
                    }
                    console.info('Laravel Datatable not loaded. Check again...');
                    setTimeout(() => {
                        beforeInit(retry + 1);
                    },300);
                }else{
                    console.info('Laravel Datatable loaded. Init table...');
                    initDatatable{{ $view->tableId }}();
                }
            }

            @endif
            
            //Set searchColumns
            @foreach($view->columns as $column)
            @if($column['columnSearch'])
                $('#{{ $view->tableId }}').find('thead th:nth-child({{ $loop->index + 1 }})').append(`
                    <input type="text" name="{{ $column['data'] }}" class="search-filter" placeholder="${locale.columnSearchLabel}" />
                `);
            @endif
            @endforeach            
            //END searchColumns 
            
        });
    };
    
    
</script>
