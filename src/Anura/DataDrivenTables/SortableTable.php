<?php

namespace Anura\DataDrivenTables;

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
        parent::printScript();
        ?>
        <script>
            <?php require "ressources/Table.js"; ?>
            setupSortable("<?php echo $this->id; ?>", "<?php echo json_encode($this->sortables); ?>");
        </script>
        <?php
    }

}
