<?php

namespace Anura\DataDrivenTables;

class Table {

    private static $jsprinted = false;

    protected $id;
    protected $db;
    protected $sqlQuery;
    protected $sqlArgs = array();
    protected $sqlArray;
    protected $nameArray;
    protected $emptyMsg;
    protected $rowsPerPage;
    protected $type;

    protected $actionKey = "id";
    protected $timestampFormat = "d.m.Y H:i";
    protected $countQueryTemplate = "SELECT COUNT(*) as rowCount FROM ($1) count";
    protected $countArgs = NULL;
    protected $additionalScriptParameters = array();
    protected $needsAjax = false;

    private $rawSqlQuery;
    private $rawSqlArgs;

    private $rowCount;
    private $action;

    public function __construct($id, $db, $sqlQuery, $sqlArray, $nameArray, $emptyMsg = "", $rowsPerPage = -1, $type = "") {
        $this->id = $id;
        $this->db = $db;

        if (is_array($sqlQuery)) {
            $this->rawSqlQuery = $sqlQuery["query"];
            $this->rawSqlArgs = $sqlQuery["args"];
        } else {
            $this->rawSqlQuery = $sqlQuery;
        }

        $this->sqlArray = $sqlArray;
        $this->nameArray = $nameArray;
        $this->emptyMsg = $emptyMsg;
        $this->rowsPerPage = $rowsPerPage;
        $this->type = $type;

        $this->action = method_exists($this, "printAction");

        if ($this->rowsPerPage !== -1) {
            $this->needsAjax = true;
        }

        if ($this->needsAjax) {
            $this->checkAjax();
        }
    }

    public function printTable() {
        echo $this->getTableHtml();
    }

    public function getTableHtml() {
        $html = "<table class='table{$this->type}' id='{$this->id}' {$this->getTableData()}>";
        $html .= "<thead>";
        $html .= "<tr>";
        foreach ($this->nameArray as $key => $column) {
            $html .= "<th data-id='{$this->sqlArray[$key]}'><div>{$column}</div></th>";
        }
        if ($this->action === true) {
            $html .= "<th></th>";
        }
        $html .= "</tr>";
        $html .= "</thead>";
        $html .= "<tbody>";
        $html .= $this->getContentHtml();
        $html .= "</tbody>";
        $html .= "</table>";
        $html .= "<div class='tableSwitcher' data-id='{$this->id}'></div>";

        if ($this->needsAjax) {
            if (!Table::$jsprinted) {
                $html .= $this->getScriptHtml();
                Table::$jsprinted = true;
            }
            $html .= $this->getUpdateScriptHtml();
        }
        return $html;
    }

    protected function getTableData() {
        return " data-page='1' data-additional-parameters='" . json_encode($this->additionalScriptParameters) . "' data-content-page='//" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "' ";
    }

    public function getScriptHtml() {
        $html = "<script type='text/javascript'>";
        $html .= file_get_contents("resources/Table.js");
        $html .= "</script>";
        return $html;
    }

    public function getUpdateScriptHtml() {
        $pages = ceil($this->rowCount / $this->rowsPerPage);
        $html = "<script type='text/javascript'>";
        $html .= "updateSwitcher(\"{$this->id}\", 1, {$pages}, {$this->rowCount});";
        $html .= "</script>";
        return $html;
    }

    protected function buildSqlQuery() {
        $this->sqlQuery = $this->rawSqlQuery;
        $this->sqlArgs = $this->rawSqlArgs;
    }

    public function getContentHtml($page = NULL, $ajax = false) {
        if ($page == NULL) {
            $page = 1;
        }

        $this->buildSqlQuery();

        $countQuery = str_replace("$1", $this->sqlQuery, $this->countQueryTemplate);

        $this->rowCount = $this->db->query($countQuery, $this->sqlArgs)[0]["rowCount"];

        if ($this->rowsPerPage !== -1) {
            $this->sqlQuery .= " LIMIT ?, ?";
            $this->sqlArgs[] = $this->rowsPerPage * ($page - 1);
            $this->sqlArgs[] = $this->rowsPerPage;
        }

        $answer = $this->db->query($this->sqlQuery, $this->sqlArgs);
        $pages = 0;

        if ($this->rowCount != 0) {
            $pages = ceil($this->rowCount / $this->rowsPerPage);
        }

        $html = "";
        if (!empty($answer)) {
            foreach ($answer as $key => $row) {
                $html .= "<tr>";
                foreach ($this->sqlArray as $column) {
                    $html .= "<td>";
                    if (method_exists($this, $column)) {
                        $html .= call_user_func(array($this, $column), $row[$column], $row, $key, $page, $this->rowCount);
                    } else if ($this->timestampFormat !== NULL && strpos(strtolower($column), "timestamp") !== false) {
                        $html .= date($this->timestampFormat, $row[$column]);
                    } else {
                        $html .= $row[$column];
                    }
                    $html .= "</td>";
                }
                if ($this->action === true) {
                    $html .= "<td>{$this->printAction($row[$this->actionKey], $row, $this->rowCount)}</td>";
                }
                $html .= "</tr>";
            }
        } else {
            $html .= "<tr>";
            $length = count($this->sqlArray) + ($this->action ? 1 : 0);
            $html .= "<td colspan='{$length}'><center>{$this->emptyMsg}</center></td>";
            $html .= "</tr>";
        }
        if ($ajax) {
            header("Content-Type: application/json");
            return json_encode(array("html" => $html, "pages" => $pages, "records" => $this->rowCount));
        } else {
            return $html;
        }
    }

    protected function checkAjax() {
        if (filter_has_var(INPUT_GET, $this->id)) {
            echo $this->getContentHtml(filter_input(INPUT_GET, "tablePage"), true);
            exit;
        }
    }

    protected function setActionKey($actionKey) {
        $this->actionKey = $actionKey;
    }

    protected function setTimestampFormat($timestamp) {
        $this->timestampFormat = $timestamp;
    }

    protected function setCountQueryTemplate($templateQuery, $templateArgs = NULL) {
        $this->countQueryTemplate = $templateQuery;

        if ($templateArgs === NULL) {
            if (strpos($templateQuery, "$1") !== false) {
                $this->countArgs = $this->rawSqlArgs;
            } else {
                $this->countArgs = array();
            }
        } else {
            $this->countArgs = $templateArgs;
        }
    }

    protected function addAdditionalScriptParameter($key, $value) {
        $this->additionalScriptParameters[$key] = $value;
    }
}
