<?php

namespace Anura\DataDrivenTables;

abstract class Table {

    private static $jsprinted = false;

    protected $id;
    protected $sqlQuery;
    protected $sqlArray;
    protected $nameArray;
    protected $emptyMsg;
    protected $rowsPerPage;
    protected $type;

    protected $action;
    protected $actionKey = "id";
    protected $additionalScriptParameters = array();

    private $rowCount;

    public function __construct($id, $sqlQuery, $sqlArray, $nameArray, $emptyMsg, $rowsPerPage = -1, $type = "") {
        $this->id = $id;
        $this->sqlQuery = $sqlQuery;
        $this->sqlArray = $sqlArray;
        $this->nameArray = $nameArray;
        $this->emptyMsg = $emptyMsg;
        $this->rowsPerPage = $rowsPerPage;
        $this->type = $type;

        $this->action = method_exists($this, "printAction");

        if ($this->rowsPerPage !== -1) {
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
        if ($this->rowsPerPage !== -1) {
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

    protected function sqlQuery() {
        return $this->sqlQuery;
    }

    protected function printContent($page = 1, $ajax = false) {
        global $DB;
        $sql = $this->sqlQuery();

        $this->rowCount = count($DB->query($sql));

        if ($this->rowsPerPage !== -1) {
            $sql .= " LIMIT " . ($this->rowsPerPage * ($page - 1)) . ", " . $this->rowsPerPage;
        }
        $answer = $DB->query($sql);
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
            $length = $this->action === true ? count($this->sqlArray) + 1 : count($this->sqlArray);
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
