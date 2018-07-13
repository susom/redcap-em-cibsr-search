<?php
namespace Stanford\CIBSRSearch;


include_once "Util.php";
include_once "classes/DataMirror.php";

use Psr\Log\NullLogger;
use REDCap;
use Project;
use Plugin;


/**
 * Class
 *
 * https://uit.stanford.edu/developers/apis/person
 *
 * @package Stanford\SPL
 */
class CIBSRSearch extends \ExternalModules\AbstractExternalModule
{

    public function redcap_survey_page_top() {
        Plugin::log(__METHOD__);
    }



    public function redcap_survey_complete ($project_id, $record = NULL, $instrument, $event_id, $group_id = NULL, $survey_hash, $response_id = NULL, $repeat_instance = 1 ) {
        Plugin::log(__METHOD__);

        if ($instrument == 'demographics') {
            Plugin::log("Demographics completed. Redirecting to household screen.");

        }

    }

    public function redcap_save_record($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance) {
        Plugin::log(__METHOD__);

    }




    private static function getNextId($pid, $id_field, $event_id, $prefix = '', $padding = false) {
        $thisProj = new Project($pid);
        $recordIdField = $thisProj->table_pk;
        $q = REDCap::getData($pid,'array',NULL,array($id_field), $event_id);

        Plugin::log("Found records in project $pid using $recordIdField", $q);

        $i = 1;
        do {
            // Make a padded number
            if ($padding) {
                // make sure we haven't exceeded padding, pad of 2 means
                $max = 10^$padding;
                if ($i >= $max) {
                    Plugin::log("Error - $i exceeds max of $max permitted by padding of $padding characters");
                    return false;
                }
                $id = str_pad($i, $padding, "0", STR_PAD_LEFT);
                Plugin::log("Padded to $padding for $i is $id");
            } else {
                $id = $i;
            }

            // Add the prefix
            $id = $prefix . $id;
            Plugin::log("Prefixed id for $i is $id");

            $i++;
        } while (!empty($q[$id][$event_id][$id_field]));

        Plugin::log("New ID in project $pid is $id");
        return $id;
    }



    public function searchPerson($search_fields) {

        //todo: use filter to get the list or use SQL??
        $filter = "";
        Plugin::log($search_fields, "DEBUG", "serach fields");
        if (empty($search_fields)) {
            $filter = null;
        } else {
            // Build array of fields and search filter
            $filters = array();
            foreach ($search_fields as $key => $v) {
                Plugin::log($key, "DEBUG", "KEY FOR ".$v. ' empty: ' . empty($v));
                if (!empty($v)) {
                    $filters[] = "([" . $key . "] = '" . $v . "')";
                }
            }
            $filter = implode(" AND ", $filters);
        }

        Plugin::log($filter, "Filter");

        //which fields do we want returned
        $get_data = array_keys($search_fields);
        $get_data[] = REDcap::getRecordIdField();

        //also add the Household ID so they can see its status
        $get_data[] = 'house_id';

        //Plugin::log($get_data, "DEBUG", "GET DATA");
        // Load the clinicians
        $q = REDCap::getData('json', NULL,$get_data, NULL, NULL, FALSE, FALSE, FALSE, $filter);
        $records = json_decode($q, true);

        //Plugin::log($records, "Records");
        return $records;
    }


    function renderSearchTable($data) {
        $header = array("CIBSR ID", "First Name", "Last Name", "Gender", "Date of Birth", "Household ID",  "Select This ID");
        $data2 = array(
            array(
            "participant_id" =>1,
            "test"      => "foo"
            ),
            array(
                "participant_id" =>2,
                "test"      => "foo2"
            )
        );

        // Render table
        $grid = '<table id="search_table" class="table table-striped table-bordered table-condensed" cellspacing="0" width="95%">';
        $grid .= $this->renderHeaderRow($header, 'thead');
        $grid .= $this->renderSummaryTableRows($data);
        $grid .= '</table>';

        return $grid;
    }

    function renderHeaderRow($header = array(), $tag) {
        $row = '<' . $tag . '><tr>';
        foreach ($header as $col_key => $this_col) {
            $row .= '<th>' . $this_col . '</th>';
        }
        $row .= '</tr></' . $tag . '>';
        return $row;
    }


    function renderSummaryTableRows($row_data = array()) {
        global $module;

        $this_id = null;

        $rows = '';
        foreach ($row_data as $row_key => $this_row) {
            $rows .= '<tr>';
            foreach ($this_row as $col_key => $this_col) {


                switch ($col_key) {
                    case "sex":
                        switch ($this_col) {
                            case "0":
                                Plugin::log($this_col, "DEBUG", "PICKING MALE for ".$this_id);
                                $rows .= '<td>Male</td>';
                                break;
                            case "1" :
                                Plugin::log($this_col, "DEBUG", "PICKING FEMALE for " .$this_id);
                                $rows .= '<td>Female</td>';
                                break;
                            case "2" :
                                Plugin::log($this_col, "DEBUG", "PICKING PHANTOM for ".$this_id);
                                $rows .= '<td>Phantom</td>';
                                break;
                            default:
                                $rows .= '<td>'.$this_col.'</td>';

                        }
                        break;
                    case "participant_id":
                        $items = "";
                        $url = $module->getUrl("ClinicianDashboard.php", true);
                        $url .= '&record=' . $this_col;
                        $this_id = $this_col;
                        $link = "<a  href='$url'>" . $this_col . "</a>";
                        $rows .= '<td>' . $link . '</td>';
                        break;
                    case "primary_clinicians":
                        $items = "";

                        //foreach($this_col as $item) $items[] = "<span class='label label-default'>" . $item . "</span> ";
                        foreach ($this_col as $item) {
                            $items[] = "
                        <div class='btn-group'>
                            <button type='button' data-toggle='dropdown' class='btn btn-xs btn-info dropdown-toggle'>" . $item . "
                                <span class=\"caret\"></span>
                            </button>
                            <ul class='dropdown-menu'>
                                <li><a href='#' class='removeClinician' value='$item' name='$this_id'>Remove</a></li>
                                <li><a href='#' class='editClinicianLink' value='$item' name='$this_id'>Edit Clinician</a></li>
                            </ul>
                        </div> ";
                        }
                        // SurveyDashboard::log($this_id, "THIS ID");
                        $rows .= '<td>' . implode("",$items) . '<i id="editClinicianListButton_'.$this_id.'" class=\' editClinicianListButton glyphicon glyphicon-plus\' value="'.$this_id.'"  name="'.$this_id.'" style="float: right; width: 32px;"></i></td>';

                        break;

                       default:
                        $rows .= '<td>' . $this_col . '</td>';

                }
            }
            $this_id = $this_row[REDCap::getRecordIdField()];
            $house_id = $this_row['house_id'];

            $rows .= "<td><button class='select_id' data-id='$this_id' data-houseid='$house_id' >$this_id</button></td>";

            $rows .= '</tr>';
        }
        return $rows;
    }



    public function setNewUser($data) {
        global $Proj;
        Plugin::log($data, "DEBUG", "DATA: : : Saving New User". $this->config );
        //save data from the new user login page
        //create new record so get a new id
        $next_id = self::getNextId($Proj->project_id, REDCap::getRecordIdField(),$Proj->firstEventId);

        $data[REDCap::getRecordIdField()] = $next_id;

        $q = REDCap::saveData('json', json_encode(array($data)));
        Plugin::log($Proj->project_id, "DEBUG", "PROJECT ID:  ". $next_id );


        Plugin::log("Saved New User", $this->config, $data, $q);

        //if save was a success, return the new id
        if (!empty($q['errors'])) {
            Plugin::log("Errors in " . __FUNCTION__ . ": data=" . json_encode($data) . " / Response=" . json_encode($q), "ERROR");
            return false;
        } else {
            return $next_id;
        }
    }

}