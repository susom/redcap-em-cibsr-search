<?php
namespace Stanford\CIBSRSearch;
/** @var \Stanford\CIBSRSearch\CIBSRSearch $module */

use Plugin;
use REDCap;

$search_results = null;
$h_search_results = null;
$household_id = null;
$cibsr_id = null;
$gender = null;

$fname = (!empty($_POST["firstname"]) ? $_POST["firstname"] : null);
$lname = (!empty($_POST["lastname"]) ? $_POST["lastname"] : null);
$dob = (!empty($_POST["dob"]) ? $_POST["dob"] : null);
$gender = (!empty($_POST["sex"]) ? $_POST["sex"] : null);

$data = array(
    "first_name" => $fname,
    "last_name" => $lname,
    "dob" => $dob,
    "sex" => $_POST["sex"] //$gender,  //loses values of 0
);

Plugin::log($_POST, "DEBUG", "POST");

// IF POST, PROCESS "SUBMISSION"
if (!empty($_POST['search_participant'])) {

    //for security reasons, if either name field is empty, return a fail with an alert
    if (empty($fname) || empty($lname)) {
        $result = array(
            'result' => 'warn',
            'status' => 'incomplete',
            'msg' => 'Please enter names to complete the search.'
        );
    } else {

        //check if this user exists already
        $search_results = $module->searchPerson($data);
        //Plugin::log($search_results, "DEBUG", "SEARCH RESULTS FOUNDD");

        $result = array(
            'result' => 'success',
            'data' => $search_results);

    }

    header('Content-Type: application/json');
    print json_encode($result);
    exit();


}

if (!empty($_POST['create_new_user'])) {
    Plugin::log($_POST, "DEBUG", "CREATE NEW USER");

    //saveData into new record
    $next_id = $module->setNewUser($data);
    Plugin::log($next_id, "DEBUG", "NEXT_ID ");

    //check if household id is populated
    //if not populated then present screen to

    if ($next_id) {
        //get survey link for the next instrument
        $instrument = 'demographics';  //todo: hardcoded?
        $survey_link = REDCap::getSurveyLink($next_id, $instrument);

        Plugin::log($survey_link, "DEBUG", "SURVEY_LINK: " . $next_id . "in instrument" . $instrument);

        $result = array('result' => 'success',
                        'link' => $survey_link);
        header('Content-Type: application/json');
        print json_encode($result);
        exit();

    } else {
        //the record was not saved, report error to REDCap logging?

    }
}

if (!empty($_POST['resume_existing'])) {
    $id = (!empty($_POST["id"]) ? $_POST["id"] : null);

    if ($id) {
        $instrument = 'demographics';  //todo: hardcoded?
        $survey_link = REDCap::getSurveyLink($id, $instrument);

        Plugin::log($survey_link, "DEBUG", "Redirecting to SURVEY_LINK: " . $id . "in instrument " . $instrument);
        //redirect to filtering survey

        $result = array(
            'result' => 'success',
            'link' => $survey_link);

        header('Content-Type: application/json');
        print json_encode($result);
        exit();
    }
}

if (!empty($_POST['save'])) {
    $cibsr_id = (!empty($_POST["cibsr_id"]) ? $_POST["cibsr_id"] : null);
    $houseid = (!empty($_POST["houseid"]) ? $_POST["houseid"] : null);
    $dob_mdy = (!empty($_POST["modal_dob"]) ? $_POST["modal_dob"] : null);

    //save post data to data save array
    $data = array(
        'first_name' => (!empty($_POST["modal_firstname"]) ? $_POST["modal_firstname"] : null),
        'last_name' => (!empty($_POST["modal_lastname"]) ? $_POST["modal_lastname"] : null),
        'sex' => (!empty($_POST["modal_sex"]) ? $_POST["modal_sex"] : null)
    );

    if ($dob_mdy) {
        $dob = date("Y-m-d", strtotime($dob_mdy));
        $data['dob'] = $dob;
    }

    //if $cibsr_id is null, then it's a new record. create a new id
    //probably coming in from the new CIBSRID
    if (!$cibsr_id) {
        //probably coming in from Create New, so create new ID
        $cibsr_id = $module->setNewUser($data);
        Plugin::log($cibsr_id, "DEBUG", "Created new cibsr");
    }

    //create houseid if no houseid
    if (!$houseid) {
        $houseid = CIBSRSearch::getNextHouseId($project_id, 'house_id', $Proj->firstEventId);
        Plugin::log($houseid, "DEBUG", "CREATED NEW HOUSE ID");
    }
    $data['house_id'] = $houseid;

    //only fail if cibsr is missing
    //if ($cibsr_id && $houseid) {
    if ($cibsr_id ) {
        $data['cibsr_id'] = $cibsr_id;

        Plugin::log($data, "DEBUG", "Saving this data in REDCap ");
        $houseid_status = REDCap::saveData('json', json_encode(array($data)));

        //setup the survey
        $instrument = 'demographics';  //todo: hardcoded?
        $survey_link = REDCap::getSurveyLink($cibsr_id, $instrument);
        Plugin::log($survey_link, "DEBUG", "SURVEY_LINK: ID: " . $cibsr_id . " in instrument: " . $instrument);

        $result = array('result' => 'success',
            'status' => $houseid_status,
            'link' => $survey_link);
        header('Content-Type: application/json');
        print json_encode($result);
        exit();

    } else {
        Plugin::log($cibsr_id, "ERROR", "Something bad happend with  cibsr id");
    }
}

if (!empty($_POST['search_family'])) {
    //check if this user exists already
    Plugin::log($data, "DEBUG", "SEARCHING FOR FAMILY");

//for security reasons, if either name field is empty, return a fail with an alert
    if (empty($fname) || empty($lname)) {

        $result = array(
            'result' => 'warn',
            'status' => 'incomplete',
            'msg' => 'Please enter names to complete the search.'
        );
    } else {

        //for this search, only return values which have houseIds
        $h_search_results = $module->searchPerson($data, "house_id");
        //Plugin::log($h_search_results, "DEBUG", "SEARCH RESULTS FOUNDD");

        $result = array(
            'result' => 'success',
            'data' => $h_search_results);

    }

    header('Content-Type: application/json');
    print json_encode($result);
    exit();

}

//TODO: delete if 'save' takes care of both cases.
if (!empty($_POST['create_houseid'])) {
    //reuse the passed in modal_cibsr_id to add a new houseid and redirect to survey link
    $cibsr_id = (!empty($_POST["modal_cibsr_id"]) ? $_POST["modal_cibsr_id"] : null);

    //get new house_id
    //$next_id = self::getNextId($Proj->project_id, REDCap::getRecordIdField(),$Proj->firstEventId);
    $houseid = CIBSRSearch::getNextHouseId($project_id, 'house_id', $Proj->firstEventId);
    Plugin::log($houseid, "DEBUG", "NEW HOUSE ID");


    if (!$cibsr_id) {

        //probably coming in from the new CIBSRID
        $data = array(
            'house_id' => $houseid,
            'first_name' =>(!empty($_POST["modal_firstname"]) ? $_POST["modal_firstname"] : null),
            'last_name' =>(!empty($_POST["modal_lastname"]) ? $_POST["modal_lastname"] : null),
            'dob' =>(!empty($_POST["modal_dob"]) ? $_POST["modal_dob"] : null),
            'sex' =>(!empty($_POST["modal_sex"]) ? $_POST["modal_sex"] : null)
        );

        //probably coming in from Create New, so create new ID
        $cibsr_id = $module->setNewUser($data);

    } else {
        $data = array(
            'cibsr_id' => $cibsr_id,
            'house_id' => $houseid);
    }

    Plugin::log($data, "DEBUG", "ABOUT TO SAVE THIS DATA FOR HOUSE ID");

    $houseid_status = REDCap::saveData('json', json_encode(array($data)));

    //setup the survey
    $instrument = 'demographics';  //todo: hardcoded?
    $survey_link = REDCap::getSurveyLink($cibsr_id, $instrument);

    //Plugin::log($survey_link, "DEBUG", "SURVEY_LINK: " . $cibsr_id . "in instrument" . $instrument);

    $result = array('result' => 'success',
            'status' => $houseid_status,
            'link' => $survey_link);

    header('Content-Type: application/json');
    print json_encode($result);
    exit();

}

?><!DOCTYPE html>
<html lang="en">

<head>
    <title><?php echo $module->config['title']; ?></title>
    <!-- Meta -->
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="description" content="Site / page description"/>
    <meta name="author" content="Stanford | Medicine"/>


    <!--link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <!--script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
    -->
    <script type="text/javascript" src="https://code.jquery.com/jquery-3.3.1.js"></script>

    <link rel="stylesheet" href="<?php echo $module->getUrl('css/bootstrap.min.css', true, true) ?>" type="text/css"/>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css"  type="text/css"/>
    <link rel="stylesheet" href="<?php echo APP_PATH_WEBROOT . DS . "Resources/css/fontawesome/css/fontawesome-all.min.css" ?>" type="text/css" />

    <script src="<?php echo $module->getUrl('/js/jquery.min.js', true, true) ?>"></script>
    <script src="<?php echo $module->getUrl('/js/bootstrap.min.js', true, true) ?>"></script>


    <!-- Bootstrap Date-Picker  -->
    <script type="text/javascript"
            src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/js/bootstrap-datepicker.min.js"></script>
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/css/bootstrap-datepicker3.css"/>

    <script type="text/javascript"
            src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>


</head>
<body>
<div>
    <div class="panel panel-primary">
        <div class="panel-heading">
            <h4><?php echo $module->getModuleName() ?></h4>
        </div>
        <form method="POST" id="getstarted" action="">
            <div class="panel-body">
                <div class="form-group row">

                    <label for="name" class="control-label col-sm-1">Name:</label>
                    <div class="col-sm-2">
                        <input type="text" class="form-control" name="firstname" id="firstname" placeholder="First Name"
                               value="<?php echo $fname ?>" autocomplete="off">
                    </div>
                    <div class="col-sm-2">
                        <input type="text" class="form-control" name="lastname" id="lastname" placeholder="Last Name"
                               value="<?php echo $lname ?>" autocomplete="off">
                    </div>
                    <label for="gender" class="control-label col-sm-1">Sex:</label>
                    <div class="col-sm-2">
                        <label><input name="sex" type="radio"
                                      value="0" <?php echo ($gender == "0") ? 'checked="checked"' : ''; ?>>
                            Male</label><br>
                        <label><input name="sex" type="radio"
                                      value="1" <?php echo ($gender == "1") ? 'checked="checked"' : ''; ?>> Female</label><br>
                        <label><input name="sex" type="radio"
                                      value="2" <?php echo ($gender == "2") ? 'checked="checked"' : ''; ?>>
                            Phantom</label>
                    </div>

                    <label for="dob" class="control-label col-sm-1">Date of Birth:</label>
                    <div class='input-group date col-sm-2' id='datetimepicker'>
                        <input name="dob" type='text' class="form-control" placeholder="mm/dd/yyyy"
                               value="<?php echo $dob ?>" autocomplete="off">
                        <span class="input-group-addon">
                            <span class="glyphicon glyphicon-calendar"></span>
                        </span>
                    </div>

                </div>
                <div class="form-group">
                    <span class="control-label col-sm-12"></span>
                    <div class="col-sm-12">
                        <button id="search_participant" type="submit" class="btn btn-primary search"
                                name="search_participant" value="true">
                            Search
                        </button>
                        <button class="btn btn-primary" type="submit" name="create_new_user" id="create_new_user"value="true">Create a new CIBSR ID</button>
                    </div>
                </div>
            </div>
        </form>
    </div>


    <div id=created>
        <div style="display:none;" id="some_found">
            <h4>Search Result</h4>
            <p>
                We have found these potential matches for your search.<br>
                If none are correct, please click the 'Create a new CIBSR ID button' to create a new ID.
            </p>

        </div>
        <div style="display:none;" id="none_found">
            <p>
            There were no persons found with the specified search criteria.
            </p>
        </div>

        <table id="search_table" class="display" width="100%">
            <thead>
            </thead>
        </table>
    </div>
    </form>


    <!-- Modal -->
    <div class="modal modal-xl fade" id="reportModal" role="dialog" aria-labelledby="reportModalLabel"
         aria-hidden="true">

        <div class="modal-dialog">
            <form method="POST" id="familysearch" action="" autocomplete='off'>
                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title" id="reportModalLabel">Search for Household</h4>

                    </div>
                    <div class="modal-body">
                        <div class="panel-body" id="set_household_modal">
                            <input type="hidden" name="modal_cibsr_id" id="modal_cibsr_id" autocomplete="off"/>
                            <input type="hidden" name="modal_firstname" id="modal_firstname" autocomplete="off"/>
                            <input type="hidden" name="modal_lastname" id="modal_lastname" autocomplete="off" />
                            <input type="hidden" name="modal_sex" id="modal_sex" autocomplete="off"/>
                            <input type="hidden" name="modal_dob" id="modal_dob" autocomplete="off"/>

                            <p class="control-label col-sm-12">Household ID has not yet been set for this
                                participant.</p>
                            <div id="family">
                                <label class=" control-label col-sm-12">Search for a family member who might already be
                                    a participant. </label>
                                <div class="clearfix row">
                                    <label for="name" class="control-label col-sm-6">Name:</label>
                                    <label for="gender" class="control-label lb-sm col-sm-3">Sex:</label>
                                    <label for="dob" class="control-label col-sm-3">Date of Birth:</label>
                                </div>
                                <div class="clearfix row">
                                    <div class="col-sm-3">
                                        <input type="text" class="form-control" name="firstname" id="firstname"
                                               placeholder="First Name" autocomplete="off">
                                    </div>
                                    <div class="col-sm-3">
                                        <input type="text" class="form-control" name="lastname" id="lastname"
                                               placeholder="Last Name" autocomplete="off">
                                    </div>
                                    <div class="col-sm-3">
                                        <label><input name="sex" type="radio" value="0" autocomplete="off">Male</label><br>
                                        <label><input name="sex" type="radio" value="1" autocomplete="off">Female</label><br>
                                        <label><input name="sex" type="radio" value="2" autocomplete="off">Phantom</label>
                                    </div>
                                    <div class='input-group date col-sm-3' id='datetimepicker2'>
                                        <input name="dob" type='text' class="form-control" placeholder="mm/dd/yyyy" autocomplete="off">
                                        <span class="input-group-addon">
                                            <span class="glyphicon glyphicon-calendar"></span>
                                        </span>
                                    </div>
                                </div>
                                <br>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button id="save" type="submit" class="btn btn-primary" name="save" value="true">
                            Create New HouseId
                        </button>
                        <button id="search_family" type="submit" class="btn btn-primary" name="search_family"
                                value="true">
                            Search for Family Members
                        </button>
                    </div>
                    <div id=created>
                        <table id="family_table" class="display" width="100%"></table>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

</div>

</body>
</html>

<script type="text/javascript">

    $(document).ready(function () {

        // turn off cached entries
        $("form :input").attr("autocomplete", "off");

        $('#datetimepicker').datepicker({
            format: 'mm/dd/yyyy'
        });


        $('#datetimepicker2').datepicker({
            format: 'mm/dd/yyyy'
        });

        $('#getstarted').submit(function () { // catch the form's submit event
            $('#some_found').hide();
            $('#none_found').hide();

            let btn = $(document.activeElement).attr('name');

            let formValues = {};

            $.each($(this).serializeArray(), function (i, field) {
                //console.log("adding to formValue: ",field.name, " with value ", field.value);
                formValues[field.name] = field.value;

                //save to modal form to use for new id case
                $("#modal_"+field.name).val(field.value);
            });
            formValues[btn] = true;


            if (btn === 'create_new_user') {
                $("#reportModal").find('.modal-title').text('Select the House ID for this person:'+ formValues.firstname+ " "+ formValues.lastname);
                $("#reportModal").modal('show');
                //update hidden field with id
                $("#modal_cibsr_id").val(null);
                return false;
            }


            if (btn === 'search_participant') {
                $.ajax({ // create an AJAX call...
                    //data: $(this).serialize(), // get the form data
                    data: formValues, // get the form data
                    method: "POST"
                })
                    .done(function (data) {

                        if (data.result === 'success') {
                            if (btn === 'create_new_user') {
                                console.log("redirecting to: ", data.link);
                                $(location).attr('href', data.link);
                            }

                            if (btn === 'search_participant') {
                                if ($.fn.DataTable.isDataTable('#search_table')) {
                                    $('#search_table').dataTable().fnClearTable();
                                    $('#search_table').dataTable().fnDestroy();
                                }

                                if (data.data.length > 0) {
                                    $('#some_found').show();
                                    renderDataTable(data.data, 'search_table');
                                } else {
                                    $('#none_found').show();
                                }

                            }

                        } else if (data.result === 'warn') {
                            alert(data.msg);
                        }

                    })
                    .fail(function (data) {
                        console.log("DATA: ", data);
                        alert("error:", data);
                    })
                    .always(function () {
                    });


                return false;
            }

        });

        $('#familysearch').submit(function () { // catch the form's submit event
            let btn = $(document.activeElement).attr('name');

            var formValues = {};

            $.each($(this).serializeArray(), function (i, field) {
                formValues[field.name] = field.value;
            });

            formValues[btn] = true;

            $.ajax({ // create an AJAX call...
                data: formValues, // get the form data
                method: "POST" // GET or POST
            })
                .done(function (data) {

                    if (data.result === 'success') {
                        //if (btn === 'create_houseid') {
                        if (btn === 'save') {
                            console.log("redirecting to: ", data.link);
                            $(location).attr('href', data.link);
                        }

                        if (btn === 'search_family') {

                            if ($.fn.DataTable.isDataTable( '#family_table' ) ) {
                                $('#family_table').dataTable().fnClearTable();
                                $('#family_table').dataTable().fnDestroy();
                            }


                            // all is good
                            renderFamilyDataTable(data.data, 'family_table', formValues);

                        }

                    } else if (data.result === 'warn') {
                        alert(data.msg);

                    } else {
                        // an error occurred
                        //alert("Unable to Save<br><br>" + data.message, "ERROR - SAVE FAILURE" );
                    }

                })
                .fail(function (data) {
                    alert("error;  failed on family search.");
                })
                .always(function () {
                    //saveBtn.html(saveBtnHtml);
                    //saveBtn.prop('disabled',false);
                });

            return false;

        });

        $('#search_table').on('click', 'button.select_id', function () {
            buttonpressed = $(this).attr('name');

            var id = $(this).data('id');
            var houseid = $(this).data('houseid');

            if (!houseid) {

                //comment out to show lara
                $("#reportModal").find('.modal-title').text('Select house ID for ' + id);
                $("#reportModal").modal('show');
                //update hidden field with id
                $("#modal_cibsr_id").val(id);


            } else {
                //redirect to existing record to edit.
                var formValues = {};
                formValues['resume_existing'] = true;
                formValues['id'] = id;

                $.ajax({ // create an AJAX call...
                    data: formValues,
                    method: "POST"
                })
                    .done(function (data) {
                        console.log("RESUMING====: ", data);
                        if (data.result === 'success') {
                            console.log("redirecting to: ", data.link);
                            $(location).attr('href', data.link);
                        }
                    })
                    .fail(function (data) {
                        console.log("DATA: ", data);
                        alert("error:", data);
                    })
                    .always(function () {
                    });

                return false;
            }
        });

        $('#family_table').on('click', 'button.select_id', function () {
            buttonpressed = $(this).attr('name');

            var id = $(this).data('id');
            var houseid = $(this).data('houseid');
            var first_name = $(this).data('firstname');
            var last_name = $(this).data('lastname');
            var dob = $(this).data('dob');
            var sex = $(this).data('sex');



            var modal_cibsr_id =  $("#modal_cibsr_id").val();

            if (houseid) {
                console.log("houseid exists, Save to current record: ", id);
                //redirect to existing record to edit.
                var formValues = {};

                $.each($(this).serializeArray(), function (i, field) {
                    console.log("family_table: adding to formValue: ",field.name, " with value ", field.value);
                    formValues[field.name] = field.value;
                });

                formValues['save'] = true;
                formValues['cibsr_id'] = modal_cibsr_id;
                //formValues['cibsr_id'] = id;
                formValues['houseid'] = houseid;
                //modal_firstname: "test", modal_lastname: "two"
                formValues['modal_firstname'] = first_name;
                formValues['modal_lastname'] = last_name;
                formValues['modal_dob'] = dob;
                formValues['modal_sex'] = sex;

                console.log("family_table formValues:" , formValues);


                $.ajax({ // create an AJAX call...
                    data: formValues,
                    method: "POST"
                })
                    .done(function (data) {

                        if (data.result === 'success') {
                            $(location).attr('href', data.link);
                        } else {

                        }

                    })
                    .fail(function (data) {
                        console.log("DATA: " , data);
                        alert("error:" , data);
                    })
                    .always(function () {
                    });

                return false;
            }
        });

    });

    function renderDataTable(tbl, id) {
        result = tbl.map(Object.values);


        $('#'+id).DataTable( {
            data: result,
            searching: false,
            paging: false,
            info: false,
            columns: [
                { title: "Select CIBSR ID"},
                { title: "First Name" },
                { title: "Last Name" },
                { title: "Gender" },
                { title: "Birth date" },
                { title: "House ID" }
            ],
            "columnDefs": [
                {
                    "render": function ( data, type, row ) {
                        let foo =  "<td><button type='button' class='btn btn-info select_id' data-id='"+data+"' data-houseid='"+row[5]+"'>"+data+"</button></td>";
                        return foo;
                    },
                    "targets": 0
                },
                {
                    "render": function ( data, type, row ) {
                        switch(data) {
                            case "0":
                                gender = 'Male';
                                break;
                            case "1":
                                gender = 'Female';
                                break;
                            case "2":
                                gender = 'Phantom';
                                break;
                            default:
                                gender = data;
                        }
                        return gender;
                    },
                    "targets": 3
                },
                { "visible": true,  "targets": [ 2 ] }
            ]
        } );
    }

    function renderFamilyDataTable(tbl, id, formVal) {
        result = tbl.map(Object.values);

        $('#'+id).DataTable( {
            data: result,
            searching: false,
            paging: false,
            info: false,
            columns: [
                { title: "CIBSR ID"},
                { title: "First Name" },
                { title: "Last Name" },
                { title: "Gender" },
                { title: "Birth date" },
                { title: "Select House ID" }
            ],
            "columnDefs": [
                {
                    "render": function ( data, type, row ) {
                        let foo =  "<td><button type='button' class='btn btn-info select_id' data-id='' data-firstname='"+formVal.modal_firstname+"' data-lastname='"+formVal.modal_lastname+"' data-dob='"+formVal.modal_dob+"' data-sex='"+formVal.modal_sex+"' data-houseid='"+row[5]+"'>"+data+"</button></td>";
                        return foo;
                    },
                    "targets": 5
                },
                {
                    "render": function ( data, type, row ) {
                        switch(data) {
                            case "0":
                                gender = 'Male';
                                break;
                            case "1":
                                gender = 'Female';
                                break;
                            case "2":
                                gender = 'Phantom';
                                break;
                            default:
                                gender = data;
                        }
                        return gender;
                    },
                    "targets": 3
                },
                { "visible": true,  "targets": [ 2 ] }
            ]
        } );
    }


</script>