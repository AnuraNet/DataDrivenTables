<?php

require_once 'classes/class.table.php';

abstract class SortableTable extends Table {

    protected $sortables = NULL;
    protected $sortByDefault = "id";
    protected $sortDirDefault = "ASC";
    protected $sortBy;
    protected $sortDir;

    public function __construct($id, $sqlQuery, $sqlArray, $nameArray, $emptyMsg) {
        $this->sortBy = $this->sortByDefault;
        $this->sortDir = $this->sortDirDefault;
        if ($this->sortables === NULL) {
            $this->sortables = $sqlArray;
        }
        if (filter_has_var(INPUT_GET, "tableSortBy")) {
            $this->sortBy = filter_input(INPUT_GET, "tableSortBy");
        }
        if (filter_has_var(INPUT_GET, "tableSortDir")) {
            $this->sortDir = filter_input(INPUT_GET, "tableSortDir");
        }
        $this->additionalScriptParameters['tableSortBy'] = $this->sortBy;
        $this->additionalScriptParameters['tableSortDir'] = $this->sortDir;
        //Contains checkAjax()!
        parent::__construct($id, $sqlQuery, $sqlArray, $nameArray, $emptyMsg);
    }

    protected function sqlQuery() {
        return "{$this->sqlQuery} {$this->getOrderClause()}";
    }

    protected function getOrderClause() {
        global $DB;
        return "ORDER BY {$DB->escapeString($this->sortBy)} {$DB->escapeString($this->sortDir)}, {$DB->escapeString($this->sortByDefault)} {$DB->escapeString($this->sortDirDefault)}";
    }

    protected function printScript() {
        if (!empty($this->sortables)) {
            parent::printScript();
            ?>
            <script>
            {
                var table = document.getElementById("<?php echo $this->id; ?>");
                var all = JSON.parse('<?php echo json_encode($this->sortables);?>');
                var data_id = table.querySelectorAll("th[data-id]");
                for (var i = 0; i < data_id.length; i++) {
                    var thiz = data_id[i];
                    if (all.indexOf(thiz.dataset.id) !== -1) {
                        thiz.addEventListener("click", function() {
                            var table = document.getElementById(this.dataset.table);
                            var obj = JSON.parse(table.dataset.additionalParameters);
                            var data_id_div = table.querySelectorAll("th[data-id] div");
                            for (var j = 0; j < data_id_div.length; j++) {
                                data_id_div[j].className = "";
                            }
                            if (obj.tableSortBy === this.dataset.id) {
                                if (obj.tableSortDir === "DESC") {
                                    obj.tableSortDir = "ASC";
                                    this.querySelector("div").classList.add("arrowUp");
                                } else {
                                    obj.tableSortDir = "DESC";
                                    this.querySelector("div").classList.add("arrowDown");
                                }
                            } else {
                                obj.tableSortBy = this.dataset.id;
                                obj.tableSortDir = "ASC";
                                this.querySelector("div").classList.add("arrowUp");
                            }
                            table.dataset.additionalParameters = JSON.stringify(obj);
                            updateTable(table.id);
                        });
                        thiz.dataset.table = table.id;
                        thiz.classList.add("clickCursor");
                        var obj = JSON.parse(table.dataset.additionalParameters);
                        if (obj.tableSortBy === thiz.dataset.id) {
                            if (obj.tableSortDir === "ASC") {
                                thiz.querySelector("div").classList.add("arrowUp");
                            } else {
                                thiz.querySelector("div").classList.add("arrowDown");
                            }
                        }
                    }
                }
            }
            </script>
            <?php
        }
    }
}
