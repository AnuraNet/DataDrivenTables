<?php

namespace Anura\DataDrivenTables;

class SortableTable extends Table {

    protected $sortables = NULL;
    protected $sortByDefault = "id";
    protected $sortDirDefault = "ASC";
    protected $sortBy;
    protected $sortDir;

    public function __construct($id, $db, $sqlQuery, $sqlArray, $nameArray, $emptyMsg = "", $rowsPerPage = -1, $type = "") {
        $this->sortBy = $this->sortByDefault;
        $this->sortDir = $this->sortDirDefault;

        if (filter_has_var(INPUT_GET, "tableSortBy")) {
            $this->sortBy = filter_input(INPUT_GET, "tableSortBy");
        }

        if (filter_has_var(INPUT_GET, "tableSortDir")) {
            $this->sortDir = filter_input(INPUT_GET, "tableSortDir");
        }

        if ($this->sortables === NULL) {
            $this->sortables = $sqlArray;
        }

        $this->additionalScriptParameters['tableSortBy'] = $this->sortBy;
        $this->additionalScriptParameters['tableSortDir'] = $this->sortDir;

        $this->needsAjax = true;

        //Contains checkAjax()!
        parent::__construct($id, $db, $sqlQuery, $sqlArray, $nameArray, $emptyMsg, $rowsPerPage, $type);
    }

    protected function buildSqlQuery() {
        parent::buildSqlQuery();
        $this->addOrderClause();
    }

    protected function getOrderClause() {
        $this->sqlQuery .= " ORDER BY ? ?, ? ?";
        array_push($this->sqlArgs, $this->sortBy, $this->sortDir, $this->sortByDefault, $this->sortDirDefault);
    }

    protected function printScript() {
        parent::printScript();
        ?>
        <script>
            <?php require "resources/SortableTable.js"; ?>
            setupSortable("<?php echo $this->id; ?>", "<?php echo json_encode($this->sortables); ?>");
        </script>
        <?php
    }
}
