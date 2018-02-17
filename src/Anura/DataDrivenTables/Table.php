<?php

namespace Anura\DataDrivenTables;

abstract class Table {

    static $jsprinted = false;
    protected $sqlArray;
    protected $nameArray;
    protected $sqlQuery;
    protected $action;
    protected $actionKey = "id";
    protected $sqlKey;
    protected $rowsPerPage = -1;
    public $id;
    protected $emptyMsg;
    protected $type = "";
    public $additionalScriptParameters = array();

    public function __construct($id, $sqlQuery, $sqlArray, $nameArray, $emptyMsg) {
        $this->sqlQuery = $sqlQuery;
        $this->sqlArray = $sqlArray;
        $this->nameArray = $nameArray;
        $this->action = method_exists($this, "printAction");
        $this->emptyMsg = $emptyMsg;
        $this->id = $id;
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
                        echo "<th data-id='{$this->sqlArray[$key]}'><div>{$column}</div></th>";
                    }
                    if ($this->action === true) {
                        echo "<th></th>";
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
        <br/>
        <div class='tableSwitcher' id='tableSwitcher<?php echo $this->id; ?>' data-id='<?php echo $this->id; ?>'></div>
        <?php
        if ($this->rowsPerPage !== -1) {
            $this->printScript();
        }
    }

    protected function printTableData() {
        return " data-page='1' data-additional-parameters='" . json_encode($this->additionalScriptParameters) . "' data-content-page='//{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}' ";
    }

    protected function printScript() {
        global $DB;
        $pages = 0;
        $rows = count($DB->query($this->sqlQuery()));
        if ($rows != 0) {
            $pages = ceil($rows / $this->rowsPerPage);
        }
        if (!Table::$jsprinted) {
            ?>
            <script>
                var timestamps = Array;
                function updateTable(id, pagenr) {
                var timestamp = Date.now();
                timestamps[id] = timestamp;
                var e = document.getElementById(id);
                if (typeof pagenr === "undefined") {
                pagenr = e.dataset.page;
                }
                var additionalParameters = "";
                var obj = JSON.parse(e.dataset.additionalParameters);
                for (var key in obj) {
                additionalParameters += "&"+key+"="+obj[key];
                }
                ajax(e.dataset.contentPage+"?"+id+"&tablePage="+pagenr+additionalParameters, function(data) {
                if (timestamps[id] === timestamp) {
                e.getElementsByTagName('tbody')[0].innerHTML = data.html;
                updateSwitcher(id,parseInt(pagenr),parseInt(data.pages));
                e.dataset.id = pagenr;
                }
                }, "GET", "", true, function() {});
                }
                function updateSwitcher(id, page, pages) {
                var html = "";
                if (pages > 1) {
                if (page - 1 > 0) {
                html += "<a data-page='1'>&lt;&lt;</a>&nbsp;<a data-page='"+(page - 1)+"'>&lt; Vorherige Seite</a>&nbsp;|";
                }
                for (var i = page - 5;i <= page + 5;i++) {
                if (i > 0 && i <= pages) {
                if (i === page) {
                html += "&nbsp;"+i+"&nbsp;";
                } else {
                html += "&nbsp;<a data-page='"+i+"'>"+i+"</a>&nbsp;";
                }
                }
                }
                if (page + 1 <= pages) {
                html += "|&nbsp;<a data-page='"+(page + 1)+"'>Nächste Seite &gt;</a>&nbsp;<a data-page='"+pages+"'>&gt;&gt;</a>";
                }
                html += "<br/>"+pages+" Seiten";
                }
                document.getElementById("tableSwitcher"+id).innerHTML = html;
                }
                document.addEventListener("click", function(ev) {
                var thiz = ev.target;
                if (thiz.tagName.toLowerCase() === "a" && thiz.parentElement.classList.contains("tableSwitcher")) {
                updateTable(thiz.parentElement.dataset.id, thiz.dataset.page);
                }
                });
            </script>
        <?php } ?>
        <script>
            updateSwitcher("<?php echo $this->id; ?>",1,<?php echo $pages; ?>);
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
        if ($this->rowsPerPage !== -1) {
            $sql .= " LIMIT " . ($this->rowsPerPage * ($page - 1)) . ", " . $this->rowsPerPage;
        }
        $answer = $DB->query($sql);
        $pages = 0;
        $query = substr($this->sqlQuery(), strpos($this->sqlQuery(), "FROM"));
        $query = "SELECT COUNT(*) as count " . substr($query, 0, strpos($query, "ORDER BY"));
        $rows = $DB->query($query)[0]['count'];
        if ($rows != 0) {
            $pages = ceil($rows / $this->rowsPerPage);
        }
        $html = "";
        if (!empty($answer)) {
            foreach ($answer as $key => $row) {
                $html .= "<tr>";
                foreach ($this->sqlArray as $column) {
                    if (method_exists($this, $column)) {
                        $html .= "<td>" . call_user_func(array($this, $column), $row[$column], $row, $key, $page, $rows) . "</td>";
                    } else if (strpos($column, "timestamp") !== false || strpos($column, "Timestamp") !== false) {
                        $html .= "<td>" . date("d.m.Y H:i", $row[$column]) . "</td>";
                    } else {
                        $html .= "<td>{$row[$column]}</td>";
                    }
                }
                if ($this->action === true) {
                    $html .= "<td>{$this->printAction($row[$this->actionKey], $row, $rows)}</td>";
                }
                $html .= "</tr>";
            }
        } else {
            $html .= "<tr>";
            $length = $this->action === true ? count($this->sqlArray) + 1 : count($this->sqlArray);
            $html .= "<td colspan='$length'><center>{$this->emptyMsg}</center></td>";
            $html .= "</tr>";
        }
        if ($ajax) {
            header('Content-Type: application/json');
            echo json_encode(array("html" => $html, "pages" => $pages));
        } else {
            echo $html;
        }
    }

    protected function checkAjax() {
        if (filter_has_var(INPUT_GET, $this->id)) {
            $this->printContent(filter_input(INPUT_GET, 'tablePage'), true);
            exit();
        }
    }

}
