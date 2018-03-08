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
    protected $additionalScriptParameters = array();
    protected $needsAjax = false;

    private $rawSql;
    private $rawSqlArgs;

    private $rowCount;
    private $action;

    public function __construct($id, $db, $sqlQuery, $sqlArray, $nameArray, $emptyMsg, $rowsPerPage = -1, $type = "") {
        $this->id = $id;
        $this->db = $db;

        if (is_array($sqlQuery)) {
            $this->rawSql = $sqlQuery["query"];
            $this->rawSqlArgs = $sqlQuery["args"];
        } else {
            $this->rawSql = $sqlQuery;
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
            <script>
                <?php require "ressources/Table.js"; ?>
            </script>
        <?php } ?>
        <script>
            updateSwitcher("<?php echo $this->id; ?>", 1, <?php echo $pages; ?>);
        </script>
        <?php
        Table::$jsprinted = true;
    }

    protected function buildSqlQuery() {
        $this->sqlQuery = $this->rawSql;
        $this->sqlArgs = $this->rawSqlArgs;
    }

    protected function printContent($page = NULL, $ajax = false) {
        if ($page == NULL) {
            $page = 1;
        }

        $this->buildSqlQuery();

        $this->rowCount = count($this->db->query($this->sqlQuery, $this->sqlArgs));

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
                    if (method_exists($this, $column)) {
                        $html .= "<td>" . call_user_func(array($this, $column), $row[$column], $row, $key, $page, $this->rowCount) . "</td>";
                    } else if (strpos($column, "timestamp") !== false || strpos($column, "Timestamp") !== false) {
                        $html .= "<td>" . date("d.m.Y H:i", $row[$column]) . "</td>";
                    } else {
                        $html .= "<td>{$row[$column]}</td>";
                    }
                    $html .= "\n";
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
            echo json_encode(array("html" => $html, "pages" => $pages));
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
}
