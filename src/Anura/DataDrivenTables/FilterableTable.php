<?php

namespace Anura\DataDrivenTables;

const TEXT_FIELD = 0;
const CHECK_BOX = 1;
const RADIO_BUTTON = 2;
const DROP_DOWN = 3;

class FilterableTable extends SortableTable {

    protected $parameters = array();

    public function __construct($id, $sqlQuery, $sqlArray, $nameArray, $emptyMsg) {
        foreach ($this->parameters as $param) {
            $param->setTable($id);
        }
        //Contains checkAjax()!
        parent::__construct($id, $sqlQuery, $sqlArray, $nameArray, $emptyMsg);
    }

    public function registerParameter($param) {
        if ($param->valid) {
            array_push($this->parameters, $param);
        } else {
            trigger_error("Registered parameter for " . $param->getColumn() . " is invalid!", E_USER_WARNING);
        }
    }

    protected function sqlQuery() {
        $query = $this->sqlQuery;
        if (!empty($this->parameters)) {
            $first = true;
            foreach ($this->parameters as $param) {
                $toAdd = $param->getClause($this);
                if (empty($toAdd)) {
                    continue;
                }
                if ($first) {
                    $first = false;
                    if (strpos($query, "WHERE") !== false) {
                        $query .= " AND (";
                    } else {
                        $query .= " WHERE (";
                    }
                } else {
                    $query .= " AND ";
                }
                $query .= $toAdd;
            }
            if (!$first) {
                $query .= ")";
            }
        }
        $query .= " ";
        $query .= $this->getOrderClause();
        return $query;
    }

    public function getTableHtml() {
        $html = "<div class='tableFilters' data-id='{$this->id}'>";
        foreach ($this->parameters as $param) {
            $html .= $param->getHTML();
        }
        $html .= "</div>";
        $html .= parent::getTableHtml();
        return $html;
    }

    public function getScriptHtml() {
        $html = parent::getScriptHtml();
        $html .= "<script type='text/javascript'>";
        $html .= file_get_contents("resources/FilterableTable.js");
        $html .= "</script>";
        return $html;
    }

    public function getUpdateScriptHtml() {
        $html = parent::getUpdateScriptHtml();
        $html .= "<script type='text/javascript'>";
        $html .= "setupFilterable(\"{$this->id}\");";
        $html .= "</script>";
        return $html;
    }

}

class FilterableParam {

    protected static $ids = array();
    protected $column;
    protected $type;
    protected $name;
    protected $default;
    protected $params;
    protected $value;
    public $valid = true;
    protected $table;
    protected $id;

    public function __construct($column, $type, $name, $default = "", $params = array()) {
        $this->column = $column;
        if ($type > 3 || $type < 0) {
            $this->valid = false;
            return;
        }
        switch ($type) {
            case TEXT_FIELD:
                if (!is_array($this->column))
                    $this->column = array($this->column);
                break;
            case CHECK_BOX:
                if (!is_array($default))
                    $default = array($default);
                if (!is_array($name))
                    $name = array($name);
                if (!is_array($params))
                    $params = array($params);
                break;
            case RADIO_BUTTON:
            case DROP_DOWN:
                if (!array_key_exists($default, $params) && !array_key_exists("_query_", $params)) {
                    $this->valid = false;
                    return;
                }
                break;
        }
        $this->type = $type;
        $this->name = $name;
        $this->default = $default;
        $this->params = $params;
    }

    public function getColumn() {
        return $this->column;
    }

    public function setTable($tableId) {
        $this->table = $tableId;
        $this->id = array_key_exists($tableId, FilterableParam::$ids) ? FilterableParam::$ids[$tableId] : 0;
        FilterableParam::$ids[$tableId] = $this->id + 1;
        if (filter_has_var(INPUT_GET, "tableFilter-" . $tableId . "-" . $this->id)) {
            $this->value = filter_input(INPUT_GET, "tableFilter-" . $tableId . "-" . $this->id);
            if ($this->type === CHECK_BOX) {
                $this->value = json_decode($this->value, true);
            }
        } else {
            $this->value = $this->default;
        }
    }

    public function getValue() {
        return $this->value;
    }

    public function getClause() {
        global $DB;
        $sql = "";
        switch ($this->type) {
            case TEXT_FIELD:
                $values = explode(",", trim($this->value));
                if (count($values) >= 1) {
                    $first = true;
                    foreach ($values as $val) {
                        if (empty($val))
                            continue;
                        if ($first) {
                            $first = false;
                            $sql .= "(";
                        } else {
                            $sql .= " OR ";
                        }
                        $firstTwo = true;
                        foreach ($this->column as $col) {
                            if ($firstTwo) {
                                $firstTwo = false;
                                $sql .= "(";
                            } else {
                                $sql .= " OR ";
                            }
                            $col = $this->escapeColumn($col);
                            $sql .= $col . " LIKE '%" . $DB->escapeString(trim($val)) . "%'";
                        }
                        if (!$firstTwo) {
                            $sql .= ")";
                        }
                    }
                    if (!$first) {
                        $sql .= ")";
                    }
                }
                break;
            case CHECK_BOX:
                $first = true;
                foreach ($this->value as $key => $val) {
                    if (sizeof($this->params) <= $key || $val != "true") {
                        continue;
                    }
                    if ($first) {
                        $first = false;
                        $sql .= "(";
                    } else {
                        $sql .= " OR ";
                    }
                    $sql .= $this->params[$key];
                }
                if (!$first) {
                    $sql .= ")";
                } else if (sizeof($this->params) > sizeof($this->value)) {
                    $sql .= $this->params[sizeof($this->params) - 1];
                }
                break;
            case RADIO_BUTTON:
            case DROP_DOWN:
                $column = $this->escapeColumn($this->column);
                if ($this->value !== "noQueryDefault" && (array_key_exists($this->value, $this->params) || array_key_exists("_query_", $this->params))) {
                    $sql .= $column . " = '" . $DB->escapeString($this->value) . "'";
                }
                break;
            default:
                break;
        }
        return $sql;
    }

    public function getHTML() {
        global $DB;
        $html = "<div>";
        switch ($this->type) {
            case TEXT_FIELD:
                $html .= "<input data-id='{$this->table}-{$this->id}' type='text' placeholder='{$this->name}' value='{$this->default}' />&nbsp;&nbsp;";
                break;
            case CHECK_BOX:
                $html .= "<div data-id='{$this->table}-{$this->id}' class='checkboxes'>";
                $id = 0;
                foreach ($this->name as $box) {
                    $html .= "<input type='checkbox' id='{$this->table}-{$this->id}-{$id}'" . ($this->default[$id] == true ? "checked " : "") . "/>";
                    $html .= "<label for='{$this->table}-{$this->id}-{$id}'>{$box}</label>&nbsp;&nbsp;";
                    $id++;
                }
                $html .= "</div>";
                break;
            case RADIO_BUTTON:
                $html .= $this->name . "&nbsp;";
                foreach ($this->params as $key => $value) {
                    $html .= "<input id='{$this->table}-{$this->id}-{$key}' data-id='{$this->table}-{$this->id}' type='radio' value='{$key}' name='{$this->table}-{$this->id}' " . ($this->default == $key ? "checked" : "") . " />" .
                            "<label for='{$this->table}-{$this->id}-{$key}'>{$value}</label>&nbsp;&nbsp;";
                }
                break;
            case DROP_DOWN:
                if (array_key_exists("_query_", $this->params)) {
                    $res = $DB->query($this->params["_query_"], false, MYSQLI_NUM);
                    if (!empty($res)) {
                        foreach ($res as $name) {
                            $this->params[$name[0]] = $name[1];
                        }
                    }
                    unset($this->params["_query_"]);
                }
                if (!empty($this->name)) {
                    $html .= "{$this->name}:";
                }
                $html .= "&nbsp;<select id='{$this->table}-{$this->id}' data-id='{$this->table}-{$this->id}'>";
                foreach ($this->params as $key => $value) {
                    $html .= "<option value='{$key}' " . ($this->default === $key ? "selected" : "") . ">{$value}</option>";
                }
                $html .= "</select>&nbsp;&nbsp;";
                break;
            default:
                return "";
        }
        return $html . "</div>";
    }

    private function escapeColumn($col) {
        if (strpos($col, ".") !== false) {
            return $col;
        } else {
            return "`" . $col . "`";
        }
    }

}
