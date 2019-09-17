<?php
/**
 * PLUGIN NAME: Raw <=> label conversion
 * DESCRIPTION: Convert report like csv file to raw or label. It also convert custom ontology fields
 * VERSION: 1.0
 * AUTHOR: Yec'han Laizet
 */

// Obtain web service label from cache table of one item
function getWebServiceCacheValues($project_id, $service, $category)
{
// Query table
$sql = "select label, value from `redcap_web_service_cache` where category like '%$category%' ;";

$q = db_query($sql);
return $q;
}

$convertible_field_types = array(
    "dropdown",
    "yesno",
    "truefalse",
    "radio",
    "text" // Check if bioportal inside choices, Calc
);
if (isset($_POST["submit"])) {
    if (isset($_FILES["file"])) {
        if ($_FILES["file"]["error"] > 0) {
            // Errors
            echo "Return Code: " . $_FILES["file"]["error"] . "<br />";
        } else {
            // Parse DataDictionnary
            $fields_values_mapping = array();
            $fields_names_mapping = array();
            $dataDict = REDCap::getDataDictionary(PROJECT_ID, 'array');
			$keys_values=[];
			$dataDict=array_merge($dataDict, $keys_values);
            foreach($dataDict as $key => $field) {
                // Create raw - label mapping
                if (in_array($field["field_type"], $convertible_field_types)) {
                    if ($field["field_type"]=="text"){
                        if ((strpos($field["select_choices_or_calculations"], 'BIOPORTAL') !==false)){
							$category=str_replace("BIOPORTAL:","",$field["select_choices_or_calculations"]);
							$dict = getWebServiceCacheValues(PROJECT_ID, 'test', $category);
							$field["select_choices_or_calculations"]="";
							foreach($dict as $key){
								$field["select_choices_or_calculations"]=$field["select_choices_or_calculations"].$key['value'].", ".$key['label']." | ";
							}
                        }
                    }
                    $mapping = array();
                    $values  = explode(" | ", $field["select_choices_or_calculations"]);
                    foreach($values as $value) {
                        $parts = explode(", ", $value);
                        if ($_POST["submit"] == "to LABEL") {
                            $mapping[$parts[0]] = $parts[1];
                        } else {
                            $mapping[$parts[1]] = $parts[0];
                        }
                    }
                    if ($_POST["submit"] == "to LABEL") {
                        $fields_values_mapping[$field["field_name"]] = $mapping;
                    } else {
                        $fields_values_mapping[$field["field_label"]] = $mapping;
                    }
                }
                // Create field name mapping
                if ($_POST["submit"] == "to LABEL") {
                    $fields_names_mapping[$field["field_name"]] = $field["field_label"];
                } else {
                    $fields_names_mapping[$field["field_label"]] = $field["field_name"];
                }
            }
            // Convert data
            $csv_rows = array_map('str_getcsv', file($_FILES['file']['tmp_name']));
            $column_indexes = array_flip($csv_rows[0]);
            foreach($csv_rows as $i => $csv_row) {
                if ($i > 0) {
                    foreach($csv_rows[0] as $field_index => $field_name) {
                        $src_value = $csv_row[$field_index];
                        $csv_rows[$i][$field_index] = array_key_exists($csv_row[$field_index],
                            $fields_values_mapping[$field_name]) ? $fields_values_mapping[$field_name][$src_value] : $src_value;
                    }
                }
            }
            // Convert column names
            if (isset($_POST["convertHeader"])) {
                foreach($csv_rows[0] as $i => $col) {
                    $csv_rows[0][$i] = array_key_exists($col,
                        $fields_names_mapping) ? $fields_names_mapping[$col] : $col;
                }
            }
        }
    } else {
        // No file
        echo "No file selected <br />";
    }
}
?>
<?php if (!isset($_POST["submit"])) : ?>
<h1>Convert raw <=> label</h1>
<p>Available for:</p>
<ul>
    <li>Multiple Choice - Drop-down List (Single Answer)</li>
    <li>Multiple Choice - Radio Buttons (Single Answer)</li>
    <li>Yes - No</li>
    <li>True - False</li>
</ul>
<br>
<br>

<form action="<?php echo $_SERVER["PHP_SELF"] . "?pid=" . PROJECT_ID; ?>&prefix=convertLabel&page=convert" method="post" enctype="multipart/form-data">

<input type="file" name="file" id="file" />
<br>

<input id="convertHeader" type="checkbox" name="convertHeader" value="1" checked=true>
<label for="convertHeader"> Convert column names</label>
<br>
<br>

<input type="submit" name="submit" value="to RAW"/>
<span style="margin-left:50px;"></span>
<input type="submit" name="submit" value="to LABEL" />

</form>
<?php endif; ?>
<?php
// OPTIONAL: Display the project footer
    if (!isset($_POST["submit"])) {
        require_once APP_PATH_DOCROOT . 'ProjectGeneral/footer.php';
    } else {

    // http://php.net/manual/fr/function.str-getcsv.php
    if(!function_exists('str_putcsv')) {
        function str_putcsv($input, $delimiter = ',', $enclosure = '"') {
            // Open a memory "file" for read/write...
            $fp = fopen('php://temp', 'r+');
            // ... write the $input array to the "file" using fputcsv()...
            fputcsv($fp, $input, $delimiter, $enclosure);
            // ... rewind the "file" so we can read what we just wrote...
            rewind($fp);
            // ... read the entire line into a variable...
            $data = fgets($fp);
            // ... close the "file"...
            fclose($fp);
            // ... and return the $data to the caller, with the trailing newline from fgets() removed.
            return rtrim( $data, "\n" );
        }
    }

    $as_type = "RAW";
    if ($_POST["submit"] == "to LABEL") {
        $as_type = "LABEL";
    }
    $csv_data = implode("\n", array_map("str_putcsv", $csv_rows));
    //Download csv file
    $date = date("Y-m-d_H:i:s");
    $filename = preg_replace('/.csv$/', ".$as_type.csv", $_FILES['file']['name']);
    header("Content-type: text/csv");
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header("Pragma: no-cache");
    header("Expires: 0");
    print($csv_data);
}

