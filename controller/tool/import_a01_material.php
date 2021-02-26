<?php

class ControllerToolImportA01Material extends Controller {

    public function index() {
        $this->load->model('doctype/doctype');
        $this->load->model('document/document');
        $field_name1 = "";
        $field_name2 = "";
        $field_uid1 = "827e20fa-618e-11e8-82a0-52540028bc1e";
        $field_uid2 = "9a58c3a9-618e-11e8-82a0-52540028bc1e";
        if ($this->request->server['REQUEST_METHOD'] == 'POST') {

            $field_uid1 = $this->request->post['field_uid1'];
            $field_uid2 = $this->request->post['field_uid2'];


            if (isset($this->request->get['update'])) {
                $field_uid1 = $this->request->post['field_uid1'];
                $field_uid2 = $this->request->post['field_uid2'];
            }
        } else {

            if (isset($this->request->get['field1'])) {
                $field_uid1 = $this->request->get['field1'];
            }
            if (isset($this->request->get['field2']) && $this->request->get['field2']) {
                $field_uid2 = $this->request->get['field2'];
                $field_info2 = $this->model_doctype_doctype->getField($field_uid2);
                $field_name2 = $field_info2['name'];
            }
        }
        $doctype_name1 = "";
        if ($field_uid1) {
            $field_info1 = $this->model_doctype_doctype->getField($field_uid1);
            if ($field_info1) {
                $doctype_name1 = $this->model_doctype_doctype->getDoctypeDescriptions($field_info1['doctype_uid'])[2]['name'];
            }

            $field_name1 = $field_info1['name'];
        }
        $doctype_name2 = "";
        if ($field_uid2) {
            $field_info2 = $this->model_doctype_doctype->getField($field_uid2);

            if ($field_info2) {
                $doctype_name2 = $this->model_doctype_doctype->getDoctypeDescriptions($field_info2['doctype_uid'])[2]['name'];
            }
            $field_name2 = $field_info2['name'];
        }

        $html = <<<_EOF_
                    <h3> Import form .xlsx. Materials. </h3>
                <form formaction='index.php?route=tool/import_a01_material' enctype="multipart/form-data">
                <label>колонка 1: </label>
                <input type='text' name='field_uid1' value="$field_uid1" style='width: 320px'></input>
                <label> $doctype_name1 : $field_name1 </label></br></br>
                <label>колонка 2: </label>
                <input type='text' name='field_uid2' value="$field_uid2" style='width: 320px'></input>
                 <label> $doctype_name2 : $field_name2 </label></br></br>
                    
                <button type='ok' formaction='index.php?route=tool/import_a01_material&update' formmethod="post">Update</button>
                <button type='ok' formaction='index.php?route=tool/import_a01_material&import' formmethod="post">Import</button>
                </form>
_EOF_;
        $err = false;
        $xslx_file = DIR_STORAGE . "import.xlsx";

        if (!is_file($xslx_file)) {
            echo " <h3> $xslx_file doesn't exists </h3>";
            $err = true;
        }

        if ($this->request->server['REQUEST_METHOD'] == 'POST' && isset($this->request->get['import'])) {
            $err = false;
            if (!$doctype_name1) {
                $err = true;
                echo " <h3> Field 1 is not set or invalid uid </h3>";
            }
            if (!$doctype_name2) {
                $err = true;
                echo " <h3> Field 2 is not set or invalid uid </h3>";
            }
            if (!$err && $doctype_name2 !== $doctype_name1) {
                $err = true;
                echo " <h3> field 1 and 2 have different doctypes </h3>";
            }
            if ($err) {
                $this->response->setOutput($html);
                return;
            } else {
                require_once DIR_SYSTEM . 'library/composer/vendor/autoload.php';
                //загрузка xlsx-файла
                $units = array();
                foreach ($field_info2['params']['values'] as $unit) {
                    $units[$unit['title']] = $unit['value'];
                }
                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
                $spreadsheet = $reader->load($xslx_file);
                $worksheet = $spreadsheet->getActiveSheet();
                //$this->import($worksheet, $field_info1, $field_info2, $units);

                if ($this->check_units($worksheet, $field_info2, $units)) {
                    $cnt = 0;
                    echo " <h3> Import from .xlsx. Materials. </h3>";
                    $cnt = $this->import($worksheet, $field_info1, $field_info2, $units);
                    echo "</br> $cnt records were imported.";
                } 
            }
        } else {
            $this->response->setOutput($html);
        }
    }

    private function import($worksheet, $field_info1, $field_info2, $units) {
        $row = 2;
        $c1 = $worksheet->getCellByColumnAndRow(2, $row)->getValue();
        $c2_cell_value = $worksheet->getCellByColumnAndRow(3, $row)->getValue();
        $c2 = "$c2_cell_value";
        $c2 = trim($c2);
        $c2 = str_replace(urldecode("%C2%A0"), '', $c2);
        do {
            if ($c2 && isset($units[$c2])) {
                $document_uid = $this->model_document_document->addDocument($field_info1['doctype_uid'], 0, 0);
                $this->model_document_document->editFieldValue($field_info1['field_uid'], $document_uid, "$c1");
                $this->model_document_document->editFieldValue($field_info2['field_uid'], $document_uid, $units[$c2]);
            }
            $row++;
            $c1 = $worksheet->getCellByColumnAndRow(2, $row)->getValue();
            $c2_cell_value = $worksheet->getCellByColumnAndRow(3, $row)->getValue();
            $c2 = "$c2_cell_value";
            $c2 = trim($c2);
            $c2 = str_replace(urldecode("%C2%A0"), '', $c2);
        } while ($c1);
        return ($row - 2);
    }

    private function check_units($worksheet, $field_info2, $units) {
        $row = 2;
        $c1 = $worksheet->getCellByColumnAndRow(2, $row)->getValue();
        $c2_cell_value = $worksheet->getCellByColumnAndRow(3, $row)->getValue();
        $c2 = "$c2_cell_value";
        $c2 = trim($c2);
        $c2 = str_replace(urldecode("%C2%A0"), '', $c2);
        $err_record = 0;
        $error_units = array();
        do {
            if ($c2) {
                if (!isset($units[$c2])) {
                    if (isset($error_units[$c2])) {
                        $error_units[$c2] = ++$error_units[$c2];
                    } else {
                        $error_units[$c2] = 1;
                    }

                    $err_record++;
                }
            }
            $row++;
            $c1 = $worksheet->getCellByColumnAndRow(2, $row)->getValue();
            $c2_cell_value = $worksheet->getCellByColumnAndRow(3, $row)->getValue();
            $c2 = "$c2_cell_value";
            $c2 = trim($c2);
            $c2 = str_replace(urldecode("%C2%A0"), '', $c2);
        } while ($c1);
        if (!empty($error_units)) {
            echo " <h3> Units at third row in xlsx file are not match value list of field '" . $field_info2['name'] . "'</h3>";
            echo "<table width='400px'><tr><th style='border-bottom: 1px solid black'> unit </th><th style='border-bottom: 1px solid black'> count </th></tr>";
            foreach ($error_units as $error_unit => $count) {
                echo "<tr><td> " . $error_unit . " </td><td align='center'> " . $count . " </td></tr>";
            }
            echo "</table>";
            return false;
        } else {
            return true;
        }
    }

}
