<?php
namespace Stanford\CIBSRSearch;

use REDCap;
use Project;

class CIBSRSearch extends \ExternalModules\AbstractExternalModule {


    /** TURN OFF THIS FEATURE FOR NOW
    function hook_save_record($project_id, $record = NULL, $instrument, $event_id, $group_id = NULL, $survey_hash = NULL, $response_id = NULL, $repeat_instance = 1)
    {
    //$this->emLog("Record is $record and instrument is $instrument");

        $triggering_form = $this->getProjectSetting('survey');  //'demographics';
        $from_field = $this->getProjectSetting('family-sql-search');
        $to_field = $this->getProjectSetting('house-id');

        $changed = array();

        if ($instrument = $triggering_form) {
            $q = \REDCap::getData('json',$record,array(\REDCap::getRecordIdField(),$from_field, $to_field));
            $records = json_decode($q,true);
            //$this->emDebug($records);

            $to_entry = $records[0][$to_field];
            //if $to_Field has values, then don't overwrite
            if (strlen($to_entry) > 0) {
                //$this->emDebug($to_entry. " TO FIELD is OCCUPIED " . strlen($to_entry));
                return;
            }

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
    */

    public function redcap_survey_page($project_id, $record, $instrument, $event_id, $group_id, $survey_hash, $response_id, $repeat_instance)
    {
        $triggering_form = $this->getProjectSetting('survey');  //'demographics';
        //$this->emDebug($instrument . " VS " .$triggering_form );
        if ($instrument = $triggering_form) {
            $this->displayNextHouseID($project_id);
        }

    }

    public function redcap_data_entry_form($project_id, $record, $instrument, $event_id)
    {
        $triggering_form = $this->getProjectSetting('survey');  //'demographics';
        //$this->emDebug($instrument . " VS " .$triggering_form);
        if ($instrument = $triggering_form) {
            $this->displayNextHouseID($project_id);
        }

    }


    public function displayNextHouseID($project_id) {
        $next_house_id = $this->getNextHouseId($project_id, $this->getProjectSetting('house-id'), $this->getFirstEventId() );
        //$this->emDebug("NEXT ID IS " .$next_house_id);
        ?>
        <script>
            $(document).ready(function () {
                $(".hijack_house_id").text(function (index, text) {
                    return text.replace("next id", "<?php echo $next_house_id;?>");
                });
            });
        </script>
        <?php
    }

    /**
     *
     * Need to override the EM method to allow display of EM link
     *
     * @param $project_id
     * @param $link
     * @return mixed
     */
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
                //$this->emLog($key, "DEBUG", "KEY FOR ".$v. ' empty: ' . isset($v));
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

        $this->emLog(USERID . " searched for ".$filter);

        //which fields do we want returned
        $get_data = array_keys($search_fields);
        $get_data[] = REDcap::getRecordIdField();

        //also add the Household ID so they can see its status
        $get_data[] = 'house_id';

        //$this->emLog($get_data, "DEBUG", "GET DATA");
        // Load the clinicians
        $q = REDCap::getData('json', NULL,$get_data, NULL, NULL, FALSE, FALSE, FALSE, $filter);
        $records = json_decode($q, true);

        return $records;
    }


    /**
     * Using sql as the getData was very slow
     *
     * @param $pid
     * @param $id_field
     * @param $event_id
     * @return bool|null
     */
    public function getNextHouseId($pid, $id_field, $event_id) {
        $data_table = method_exists('\REDCap', 'getDataTable') ? \REDCap::getDataTable($pid) : "redcap_data";

         $sql = sprintf(
             "select (max(cast(value as unsigned)) +1) from $data_table where project_id = '%s'  and event_id ='%s' and field_name='%s'",
            db_real_escape_string($pid),
            db_real_escape_string($event_id),
            db_real_escape_string($id_field)
        );

        $q = db_query($sql);
        //$this->emDebug($sql, $q);
        if (db_num_rows($q) < 1) {

            $this->emError('Unable to find a valid house_id for $instrument.');
            return null;
        }

        //$survey_id = db_result($q, 0, $id_field);
        $survey_id = db_result($q, 0);
        return $survey_id;
    }


    public function getNextHouseIdSlow($pid, $id_field, $event_id) {
        $this->emDebug($id_field. " looking for event: ". $event_id . " in pid: " .$pid);

        $params = array(
            'return_format'=>'array',
            'fields'=>array($id_field),
            'filterLogic'=>'[$id_field] <> ""');
//        $q = REDCap::getData($params);

        $q = REDCap::getData($pid,'array',NULL,array($id_field), $event_id);
        $this->emDebug($params, "Found records in project $pid using $id_field");

        $house_ids = array();
        foreach ($q as $event_ids)
        {
            foreach ($event_ids as $candidate)
            {
                //$this->emLog($candidate, "DEBUG", "candidate is ". current($candidate));
                $house_ids[] = $candidate[$this->getProjectSetting('house-id')];
            }
        }

        //$this->emLog($house_ids, "DEBUG", "MAX IS ". max($house_ids));
        return max($house_ids) + 1;
        
    }

    /**
     * New version of getNextId to get one up from the highest existing value.
     *
     * @param $pid
     * @param $id_field
     * @param $event_id
     * @return int|mixed
     * @throws \Exception
     */
    public function getNextHighestId($pid, $id_field, $event_id) {

        $this->Proj = new Project($pid);
        //$recordIdField = $thisProj->table_pk;

        $q = REDCap::getData($pid,'array',NULL,array($id_field), $event_id);
        //$this->emDebug($q, "DEBUG", "Found records in project $pid using $id_field");
        $maxid = max(array_keys($q));
        //$this->emDebug("MAX ID : ".$maxid);
        return $maxid + 1;
    }

    public function getNextId($pid, $id_field, $event_id, $prefix = '', $padding = false) {

        $this->Proj = new Project($pid);
        //$recordIdField = $thisProj->table_pk;

        $q = REDCap::getData($pid,'array',NULL,array($id_field), $event_id);
        //$this->emLog($q, "DEBUG", "Found records in project $pid using $id_field");

        $i = 1;
        do {
            // Make a padded number
            if ($padding) {
                // make sure we haven't exceeded padding, pad of 2 means
                $max = 10**$padding;
                if ($i >= $max) {
                    $this->emLog("Error - $i exceeds max of $max permitted by padding of $padding characters");
                    return false;
                }
                $id = str_pad($i, $padding, "0", STR_PAD_LEFT);
                //$this->emLog("Padded to $padding for $i is $id");
            } else {
                $id = $i;
            }

            // Add the prefix
            $id = $prefix . $id;
            //$this->emLog("Prefixed id for $i is $id");

            $i++;
        } while (!empty($q[$id][$event_id][$id_field]));

        //$this->emLog(USERID . ": Next ID in project $pid for field $id_field is $id");
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
                                //$this->emLog($this_col, "DEBUG", "PICKING MALE for ".$this_id);
                                $rows .= '<td>Male</td>';
                                break;
                            case "1" :
                                //$this->emLog($this_col, "DEBUG", "PICKING FEMALE for " .$this_id);
                                $rows .= '<td>Female</td>';
                                break;
                            case "2" :
                                //$this->emLog($this_col, "DEBUG", "PICKING PHANTOM for ".$this_id);
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
        //$this->emLog($data, "DEBUG", "DATA: : : Saving New User". $this->config );
        //save data from the new user login page
        //create new record so get a new id
        //$next_id = $this->getNextId($Proj->project_id, REDCap::getRecordIdField(),$Proj->firstEventId);

        //get Next ID incrementing from the highest ID rather than the next available
        $next_id = $this->getNextHighestId($Proj->project_id, REDCap::getRecordIdField(),$Proj->firstEventId);

        $data[REDCap::getRecordIdField()] = $next_id;

        $q = REDCap::saveData('json', json_encode(array($data)));

        //if save was a success, return the new id
        if (!empty($q['errors'])) {
            $this->emLog("Errors in " . __FUNCTION__ . ": data=" . json_encode($data) . " / Response=" . json_encode($q), "ERROR");
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
        if ($this->getSystemSetting('enable-system-debug-logging') || ( !empty($_GET['pid']) && $this->getProjectSetting('enable-project-debug-logging'))) {
            $emLogger = \ExternalModules\ExternalModules::getModuleInstance('em_logger');
            $emLogger->emLog($this->PREFIX, func_get_args(), "DEBUG");
        }
    }

    function emError() {
        $emLogger = \ExternalModules\ExternalModules::getModuleInstance('em_logger');
        $emLogger->emLog($this->PREFIX, func_get_args(), "ERROR");
    }

}