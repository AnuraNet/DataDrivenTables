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
        ?>
        <table class="table <?php echo $this->type; ?>" id="<?php echo $this->id; ?>" <?php echo $this->printTableData(); ?>>
            <thead>
                <tr>
                    <?php
                    foreach ($this->nameArray as $key => $column) {
                        echo "<th data-id='{$this->sqlArray[$key]}'><div>{$column}</div></th>\n";
                    }
                    if ($this->action === true) {
                        echo "<th></th>\n";
                    }
                    ?>
                </tr>
            </thead>
            <tbody>
                <?php
                $this->printContent();
                ?>
            </tbody>
        </table>
        <br />
        <div class="tableSwitcher" data-id="<?php echo $this->id; ?>"></div>
        <?php

        if ($this->needsAjax) {
            $this->printScript();
        }
    }

    protected function printTableData() {
        return " data-page='1' data-additional-parameters='" . json_encode($this->additionalScriptParameters) . "' data-content-page='//" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "' ";
    }

    protected function printScript() {
        $pages = 0;
        if ($this->rowCount != 0) {
            $pages = ceil($this->rowCount / $this->rowsPerPage);
        }
        if (!Table::$jsprinted) {
        ?>
            <script type="text/javascript">
            <?php require "resources/Table.js"; ?>
            </script>
        <?php } ?>
        <script type="text/javascript">
            updateSwitcher("<?php echo $this->id; ?>", 1, <?php echo $pages; ?>, <?php echo $this->rowCount; ?>);
        </script>
        <?php
        Table::$jsprinted = true;
    }

    protected function buildSqlQuery() {
        $this->sqlQuery = $this->rawSqlQuery;
        $this->sqlArgs = $this->rawSqlArgs;
    }

    protected function printContent($page = NULL, $ajax = false) {
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
                $html .= "<tr>\n";
                foreach ($this->sqlArray as $column) {
                    $html .= "<td>";
                    if (method_exists($this, $column)) {
                        $html .= call_user_func(array($this, $column), $row[$column], $row, $key, $page, $this->rowCount);
                    } else if ($this->timestampFormat !== NULL && strpos(strtolower($column), "timestamp") !== false) {
                        $html .= date($this->timestampFormat, $row[$column]);
                    } else {
                        $html .= $row[$column];
                    }
                    $html .= "</td>\n";
                }
                if ($this->action === true) {
                    $html .= "<td>{$this->printAction($row[$this->actionKey], $row, $this->rowCount)}</td>\n";
                }
                $html .= "</tr>\n";
            }
        } else {
            $html .= "<tr>";
            $length = count($this->sqlArray) + ($this->action ? 1 : 0);
            $html .= "<td colspan='{$length}'><center>{$this->emptyMsg}</center></td>";
            $html .= "</tr>";
        }
        if ($ajax) {
            header("Content-Type: application/json");
            echo json_encode(array("html" => $html, "pages" => $pages, "records" => $this->rowCount));
        } else {
            echo $html;
        }
    }

    protected function checkAjax() {
        if (filter_has_var(INPUT_GET, $this->id)) {
            $this->printContent(filter_input(INPUT_GET, "tablePage"), true);
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
