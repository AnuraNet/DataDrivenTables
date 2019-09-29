<?php

namespace Anura\DataDrivenTables;

class SortableTable extends Table {

    protected $sortables = NULL;
    protected $sortByDefault = NULL;
    protected $sortDirDefault = "ASC";
    private $sortBy;
    private $sortDir;

    public function __construct($id, $db, $sqlQuery, $sqlArray, $nameArray, $emptyMsg = "", $rowsPerPage = -1, $type = "") {

        if ($this->sortByDefault == NULL) {
            $this->sortByDefault = $sqlArray[0];
        }

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

        $this->addAdditionalScriptParameter('tableSortBy', $this->sortBy);
        $this->addAdditionalScriptParameter('tableSortDir', $this->sortDir);

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

    public function getScriptHtml() {
        $html = parent::getScriptHtml();
        $html .= "<script type='text/javascript'>";
        $html .= file_get_contents("resources/SortableTable.js");
        $html .= "</script>";
        return $html;
    }

    public function getUpdateScriptHtml() {
        $sortablesJson = json_encode($this->sortables);
        $html = parent::getUpdateScriptHtml();
        $html .= "<script type='text/javascript'>";
        $html .= "setupSortable(\"{$this->id}\", \"{$sortablesJson}\");";
        $html .= "</script>";
        return $html;
    }
}
