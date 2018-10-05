<?php
namespace Stanford\CIBSRSearch;
/** @var \Stanford\CIBSRSearch\CIBSRSearch $module */

use REDCap;

require_once $module->getModulePath().'/vendor/autoload.php';

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
        //$module->emLog($search_results, "DEBUG", "SEARCH RESULTS FOUNDD");
        //$module->emDebug("SEARCH RECS: ", $search_results);

        $result = array(
            'result' => 'success',
            'data' => $search_results);

    }

    header('Content-Type: application/json');
    print json_encode($result);
    exit();


}

if (!empty($_POST['create_new_user'])) {
    $module->emLog($_POST, "DEBUG", "CREATE NEW USER");

    //saveData into new record
    $next_id = $module->setNewUser($data);
    $module->emLog($next_id, "DEBUG", "NEXT_ID ");

    //check if household id is populated
    //if not populated then present screen to

    if ($next_id) {
        //get survey link for the next instrument
        $instrument = 'demographics';  //todo: hardcoded?
        $survey_link = REDCap::getSurveyLink($next_id, $instrument);

        $module->emLog($survey_link, "DEBUG", "SURVEY_LINK: " . $next_id . "in instrument" . $instrument);

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

        $module->emLog($survey_link, "DEBUG", "Redirecting to SURVEY_LINK: " . $id . "in instrument " . $instrument);
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
    $module->emLog("SAVING with POST: ", $_POST);
    $cibsr_id = (!empty($_POST["cibsr_id"]) ? $_POST["cibsr_id"] : null);
    $houseid = (!empty($_POST["houseid"]) ? $_POST["houseid"] : null);
    $dob_mdy = (!empty($_POST["modal_dob"]) ? $_POST["modal_dob"] : null);

    //save post data to data save array
    $data = array(
        'first_name' => (!empty($_POST["modal_firstname"]) ? $_POST["modal_firstname"] : null),
        'last_name' => (!empty($_POST["modal_lastname"]) ? $_POST["modal_lastname"] : null),
    );

    //if $cibsr_id is null, then it's a new record. create a new id
    //probably coming in from the new CIBSRID
    if (!$cibsr_id) {
        //probably coming in from Create New, so create new ID
        $cibsr_id = $module->setNewUser($data);
        $module->emLog($cibsr_id, "DEBUG", "Created new cibsr");
    }

    //create houseid if no houseid
    if (!$houseid) {
        $houseid = $module->getNextHouseId($project_id, 'house_id', $Proj->firstEventId);
        $module->emLog($houseid, "DEBUG", "CREATED NEW HOUSE ID");
    }
    $data['house_id'] = $houseid;

    //only fail if cibsr is missing
    //if ($cibsr_id && $houseid) {
    if ($cibsr_id ) {
        $data['cibsr_id'] = $cibsr_id;

        $module->emLog($data, "DEBUG", "Saving this data in REDCap ");
        $houseid_status = REDCap::saveData('json', json_encode(array($data)));

        //setup the survey
        $instrument = 'demographics';  //todo: hardcoded?
        $survey_link = REDCap::getSurveyLink($cibsr_id, $instrument);
        $module->emLog($survey_link, "DEBUG", "SURVEY_LINK: ID: " . $cibsr_id . " in instrument: " . $instrument);

        $result = array('result' => 'success',
            'status' => $houseid_status,
            'link' => $survey_link);
        header('Content-Type: application/json');
        print json_encode($result);
        exit();

    } else {
        $module->emLog($cibsr_id, "ERROR", "Something bad happend with  cibsr id");
    }
}

//todo: delete this
if (!empty($_POST['search_family'])) {
    //check if this user exists already
    $module->emLog($data, "DEBUG", "SEARCHING FOR FAMILY");

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
        //$module->emLog($h_search_results, "DEBUG", "SEARCH RESULTS FOUNDD");

        $result = array(
            'result' => 'success',
            'data' => $h_search_results);

    }

    header('Content-Type: application/json');
    print json_encode($result);
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title><?php echo $module->config['title']; ?></title>
    <!-- Meta -->
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="description" content="Site / page description"/>
    <meta name="author" content="Stanford | Medicine"/>

    <link rel="stylesheet" href="<?php echo $module->getUrl('css/bootstrap.min.css', true, true) ?>" type="text/css"/>
    <!--link rel="stylesheet" href="<?php echo $module->getUrl('css/datatables.min.css', true, true) ?>" type="text/css"/-->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css"  type="text/css"/>

    <script src="<?php echo $module->getUrl('js/jquery.min.js', true, true) ?>"></script>
    <script src="<?php echo $module->getUrl('js/bootstrap.min.js', true, true) ?>"></script>
    <script src="<?php print $module->getUrl('js/datatables.min.js',true,true) ?>"></script>


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

        <div class="panel panel-primary">
        <table id="search_table" class="display" width="100%">
            <thead>
            </thead>
        </table>
        </div>
    </div>

<!-- -->
</div>

</body>
</html>

<script type="text/javascript">

    $(document).ready(function () {

        // turn off cached entries
        $("form :input").attr("autocomplete", "off");

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
                $.ajax({ // create an AJAX call...
                    //data: $(this).serialize(), // get the form data
                    data: formValues, // get the form data
                    method: "POST"
                })
                    .done(function (data) {
                        console.log("DONE CREATE_NEW_USER", data);

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


            if (btn === 'search_participant') {
                $.ajax({ // create an AJAX call...
                    //data: $(this).serialize(), // get the form data
                    data: formValues, // get the form data
                    method: "POST"
                })
                    .done(function (data) {
                        if (data.result === 'success') {
                            console.log("returning from search");
                            console.log("data length: ", data);
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

        $('#search_table').on('click', 'button.select_id', function () {
            buttonpressed = $(this).attr('name');

            var id = $(this).data('id');
            var houseid = $(this).data('houseid');

            console.log("HOUSEID is ", houseid);

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
                { title: "CIBSR ID"},
                { title: "First Name" },
                { title: "Last Name" },
                { title: "Gender" },
                { title: "Birth date" },
                { title: "House ID" },
                { title: "Modify Existing Entry" }
            ],
            "columnDefs": [
                {
                    "render": function ( data, type, row ) {
                        let foo =  "<td><button type='button' class='btn btn-info select_id' data-id='"+row[0]+"'>"+row[0]+"</button></td>";
                        return foo;
                    },
                    "targets": 6
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