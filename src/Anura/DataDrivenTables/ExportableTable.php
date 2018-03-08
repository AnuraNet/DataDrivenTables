<?php

namespace Anura\DataDrivenTables;

define('FPDF_FONTPATH', 'fpdf/font/');
define('FPDF_INSTALLDIR', 'fpdf/');
include(FPDF_INSTALLDIR . 'fpdf.php');

class ExportableTable extends FilterableTable {

    public static $headerSize = 6;
    public static $rowSize = 7;
    protected $exportFileName = "export";
    protected $exportEncoding = "UTF-8";
    protected $csvData = array();
    protected $pdfData = array();
    protected $pdfTitle;
    protected $pdfHeader;
    protected $pdfFooter;
    protected $pdfAuthor = 'IT-Service Merkelt Tabellensystem';

    public function __construct($id, $sqlQuery, $sqlArray, $nameArray, $emptyMsg) {
        //Contains checkAjax()!
        parent::__construct($id, $sqlQuery, $sqlArray, $nameArray, $emptyMsg);
    }

    public function printTable() {
        echo "<div class='tableExport' id='tableExport{$this->id}' data-id='{$this->id}'>"
        . "<a data-export='CSV'>Export als CSV</a>&nbsp;<a data-export='PDF'>Export als PDF</a>"
        . "<br/><br/></div>";
        parent::printTable();
    }

    protected function printScript() {
        ?>
        <script>
            <?php require "resources/ExportableTable.js"; ?>
        </script>
        <?php
        parent::printScript();
    }

    protected function checkAjax() {
        if (filter_has_var(INPUT_GET, $this->id) && filter_has_var(INPUT_GET, 'export')) {
            if (filter_input(INPUT_GET, 'export') === "CSV") {
                $this->exportCSV();
            } else if (filter_input(INPUT_GET, 'export') === "PDF") {
                $this->exportPDF();
            }
            exit();
        } else {
            parent::checkAjax();
        }
    }

    protected function setupExport($filename, $encoding) {
        $this->exportFileName = $filename;
        $this->exportEncoding = $encoding;
    }

    protected function setupPDF($title, $header, $footer, $author) {
        $this->pdfTitle = $title;
        $this->pdfHeader = $header;
        $this->pdfFooter = $footer;
        $this->pdfAuthor = $author;
    }

    protected function addCSVData($csvData) {
        array_push($this->csvData, $csvData);
    }

    protected function addPDFData($pdfData) {
        array_push($this->pdfData, $pdfData);
    }

    protected function exportCSV() {
        global $DB;
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename={$this->exportFileName}.csv");
        $rows = $DB->query($this->sqlQuery());
        $header = "";
        foreach ($this->csvData as $csvData) {
            $header .= '"' . $csvData->headerName . '";';
        }
        echo iconv("UTF-8", $this->exportEncoding, $header) . PHP_EOL;
        foreach ($rows as $row) {
            $line = "";
            foreach ($this->csvData as $csvData) {
                $line .= '"' . $csvData->getData($row, $rows) . '";';
            }
            echo iconv("UTF-8", $this->exportEncoding, $line) . PHP_EOL;
        }
    }

    protected function exportPDF() {
        global $DB;
        $rows = $DB->query($this->sqlQuery());
        $pdf = new ExportPDF($this->pdfTitle, $this->pdfHeader, $this->pdfFooter, $this->pdfAuthor);
        $pdf->setHead(function($pdf) {
            $pdf->SetFont('Arial', 'B', 8);
            foreach ($this->pdfData as $key => $pdfData) {
                $pdf->Cell($pdfData->width, $this::$headerSize, iconv("UTF-8", $this->exportEncoding, $pdfData->headerName), 1, (count($this->pdfData) - 1 == $key) ? 1 : 0, $pdfData->align);
            }
            $pdf->SetFont('Arial', '', 8);
        });
        $pdf->AddPage();
        foreach ($rows as $row) {
            foreach ($this->pdfData as $key => $pdfData) {
                $pdf->Cell($pdfData->width, $this::$rowSize, iconv("UTF-8", $this->exportEncoding, $pdfData->getData($row, $rows)), 1, (count($this->pdfData) - 1 == $key) ? 1 : 0, $pdfData->align);
            }
        }
        $pdf->Output("export_{$this->exportFileName}.pdf", 'I');
    }

}

class CSVData {

    public $headerName;
    protected $sqlColumn;
    protected $func;

    public function __construct($headerName, $sqlColumn, $func = null) {
        $this->headerName = $headerName;
        $this->sqlColumn = $sqlColumn;
        if ($func == null) {
            $this->func = function($value) {
                return $value;
            };
        } else {
            $this->func = $func;
        }
    }

    public function getData($row, $rows) {
        $func = $this->func;
        return $func($row[$this->sqlColumn], $row, $rows);
    }

}

class PDFData {

    public $headerName;
    protected $sqlColumn;
    protected $func;
    public $align;
    public $width;

    public function __construct($headerName, $sqlColumn, $width, $func = null, $align = 'C') {
        $this->headerName = $headerName;
        $this->sqlColumn = $sqlColumn;
        $this->width = $width;
        if ($func == null) {
            $this->func = function($value) {
                return $value;
            };
        } else {
            $this->func = $func;
        }
        $this->align = $align;
    }

    public function getData($row, $rows) {
        $func = $this->func;
        return $func($row[$this->sqlColumn], $row, $rows);
    }

}

class ExportPDF extends FPDF {

    private $headerFunc;
    private $footerFunc;
    private $headFunc;

    function __construct($title, $header, $footer, $author) {
        $this->headerFunc = $header;
        $this->footerFunc = $footer;
        parent::__construct('L', 'mm', 'A4');
        $this->SetAutoPageBreak(true, 15);
        $this->SetCreator('IT-Service Merkelt Tabellensystem');
        $this->SetAuthor($author);
        $this->SetTitle($title());
    }

    function AddPage($orientation = '', $size = '', $rotation = 0) {
        parent::AddPage($orientation, $size, $rotation);
        $func = $this->headFunc;
        $func($this);
    }

    function setHead($head) {
        $this->headFunc = $head;
    }

    function Header() {
        $func = $this->headerFunc;
        $func($this);
    }

    function Footer() {
        $func = $this->footerFunc;
        $func($this);
    }

}
