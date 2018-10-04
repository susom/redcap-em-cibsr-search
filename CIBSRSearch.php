<?php
namespace Stanford\CIBSRSearch;

use REDCap;
use Project;
use Plugin;


class CIBSRSearch extends \ExternalModules\AbstractExternalModule {

    function hook_save_record($project_id, $record = NULL, $instrument, $event_id, $group_id = NULL, $survey_hash = NULL, $response_id = NULL, $repeat_instance = 1)
    {
        $triggering_form = 'demographics';  //todo: hardcoded, change to config
        $from_field = 'family_sql_search';
        $to_field = 'house_id';

        $changed = array();

        if ($instrument = $triggering_form) {
            $q = \REDCap::getData('json',$record,array(\REDCap::getRecordIdField(),$from_field));
            $records = json_decode($q,true);
            //$this->emDebug($records);

            //switch out the from_field to the to_field
            foreach ($records as $record) {
                $record[$to_field] = $record[$from_field];
                unset($record[$from_field]);
                //$this->emDebug($record);
                $changed[] = $record;
            }
            //$this->emDebug($changed);

            //save back to the record
            \REDCap::saveData('json',json_encode($changed));
        }

    }

    function hook_survey_complete() {
        //Not necessary.  HOok save record also works for survey completion.
        //$this->mapURL($record,$instrument, $event_id);
    }

    public function redcap_module_link_check_display($project_id, $link)
    {
        if (SUPER_USER) {
            return $link;
        }

        if (!empty($project_id) && \REDCap::getUserRights(USERID)[USERID]['design']) {
            return $link;
        }

        return $link;
    }

    public function getSearchArray() {
        $fields = array(REDCap::getRecordIdField(),'first_name', 'last_name', 'sex', 'dob', 'house_id');
        $q = REDCap::getData('json', NULL,$fields);
        $records = json_decode($q, true);
        return $records;
    }

    public function searchPerson($search_fields, $mandatory_field = '') {

        //todo: use filter to get the list or use SQL??
        $filter = "";
        if (empty($search_fields)) {
            $filter = null;
        } else {
            // Build array of fields and search filter
            $filters = array();
            foreach ($search_fields as $key => $v) {
                //Plugin::log($key, "DEBUG", "KEY FOR ".$v. ' empty: ' . isset($v));
                if (isset($v)) {
                    if (($key == 'first_name') || ($key == 'last_name')) {
                        $filters[] = "contains ([" . $key . "],  '" . $v . "')";
                    } else {
                        $filters[] = "([" . $key . "] = '" . $v . "')";
                    }
                }
            }
            $filter = implode(" AND ", $filters);

            //if doing a fmaily search, only return values which have houseid
            if ($mandatory_field <> '') {
                $filter .= " AND ([" . $mandatory_field . "] <> '')";
            }

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

        return $records;
    }


    public static function getNextHouseId($pid, $id_field, $event_id, $prefix = '', $padding = false) {
        Plugin::log($id_field, "DEBUG", "looking for event: ".$event_id . " in pid: " .$pid);

        $q = REDCap::getData($pid,'array',NULL,array($id_field), $event_id);
        //Plugin::log($q, "DEBUG", "Found records in project $pid using $id_field");

        $house_ids = array();
        foreach ($q as $event_ids)
        {
            foreach ($event_ids as $candidate)
            {
                //Plugin::log($candidate, "DEBUG", "candidate is ". current($candidate));
                $house_ids[] = current($candidate);
            }
        }

        //Plugin::log($house_ids, "DEBUG", "MAX IS ". max($house_ids));
        return max($house_ids) + 1;
        
    }

    public static function getNextId($pid, $id_field, $event_id, $prefix = '', $padding = false) {
        $thisProj = new Project($pid);
        //$recordIdField = $thisProj->table_pk;
        Plugin::log($id_field, "DEBUG", "looking for event: ".$event_id . " in pid: " .$pid);
        
        $q = REDCap::getData($pid,'array',NULL,array($id_field), $event_id);
        //Plugin::log($q, "DEBUG", "Found records in project $pid using $id_field");

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
            //Plugin::log("Prefixed id for $i is $id");

            $i++;
        } while (!empty($q[$id][$event_id][$id_field]));

        Plugin::log("Next ID in project $pid for field $id_field is $id");
        return $id;
    }

    public function renderFoundTable($data) {

        $html = '<div class="col-md-12">';
        $html .= '<hr><hr><h4>Search Result</h4>';
        $html .= '<p>We have found these potential matches for your search.<br>
                     If none are correct, please click the "Create a new CIBSR ID button" to create a new ID.';
        $html .= 'Count found: '.count($data);
        $html .= '</p>';
//                   $module->renderSearchTable($search_results);

        $html .= '</div>';
        return $html;
    }

    public function renderNoneFoundMessage() {
        $html = '<div class="col-md-12">
                    <p>
                        There were no persons found with the specified search criteria.

                    </p>

                </div>';

        return $html;
    }


    function renderSearchTable($data) {
        $header = array("CIBSR ID", "First Name", "Last Name", "Gender", "Date of Birth", "Household ID");
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
        $grid = '<table id="search_table" name="search_table" class="table table-striped table-bordered table-condensed" cellspacing="0" width="95%">';
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
                    case "cibsr_id":
                        $this_id = $this_row[REDCap::getRecordIdField()];
                        $house_id = $this_row['house_id'];
                        $rows .= "<td><button type='button' class='btn btn-info select_id' data-id='$this_id' data-houseid='$house_id'>$this_id</button></td>";
                        //$rows .= "<td><button type='button' class='btn btn-info select_id' data-toggle='modal' data-target='#reportModal' data-id='$this_id' data-houseid='$house_id'>$this_id</button></td>";
                        break;
                    case "sex":
                        switch ($this_col) {
                            case "0":
                                //Plugin::log($this_col, "DEBUG", "PICKING MALE for ".$this_id);
                                $rows .= '<td>Male</td>';
                                break;
                            case "1" :
                                //Plugin::log($this_col, "DEBUG", "PICKING FEMALE for " .$this_id);
                                $rows .= '<td>Female</td>';
                                break;
                            case "2" :
                                //Plugin::log($this_col, "DEBUG", "PICKING PHANTOM for ".$this_id);
                                $rows .= '<td>Phantom</td>';
                                break;
                            default:
                                $rows .= '<td>'.$this_col.'</td>';

                        }
                        break;

                    default:
                        $rows .= '<td>' . $this_col . '</td>';

                }
            }

            $rows .= '</tr>';
        }
        return $rows;
    }


    public function setNewUser($data) {
        global $Proj;
        //Plugin::log($data, "DEBUG", "DATA: : : Saving New User". $this->config );
        //save data from the new user login page
        //create new record so get a new id
        $next_id = self::getNextId($Proj->project_id, REDCap::getRecordIdField(),$Proj->firstEventId);

        $data[REDCap::getRecordIdField()] = $next_id;

        $q = REDCap::saveData('json', json_encode(array($data)));

        //if save was a success, return the new id
        if (!empty($q['errors'])) {
            Plugin::log("Errors in " . __FUNCTION__ . ": data=" . json_encode($data) . " / Response=" . json_encode($q), "ERROR");
            return false;
        } else {
            return $next_id;
        }
    }


    /**
     *
     * emLogging integration
     *
     */
    function emLog() {
        $emLogger = \ExternalModules\ExternalModules::getModuleInstance('em_logger');
        $emLogger->emLog($this->PREFIX, func_get_args(), "INFO");
    }

    function emDebug() {
        // Check if debug enabled
        if ($this->getSystemSetting('enable-system-debug-logging') || $this->getProjectSetting('enable-project-debug-logging')) {
            $emLogger = \ExternalModules\ExternalModules::getModuleInstance('em_logger');
            $emLogger->emLog($this->PREFIX, func_get_args(), "DEBUG");
        }
    }

    function emError() {
        $emLogger = \ExternalModules\ExternalModules::getModuleInstance('em_logger');
        $emLogger->emLog($this->PREFIX, func_get_args(), "ERROR");
    }

}