<?php
namespace Stanford\CIBSRSearch;
/** @var \Stanford\CIBSRSearch\CIBSRSearch $module */

use REDCap;

//require_once $module->getModulePath().'/vendor/autoload.php';

//get user id
$user_id = strtolower(USERID);
$module->emLog("Logged in as $user_id ");

$search_results = null;
$h_search_results = null;
$household_id = null;
$cibsr_id = null;
$gender = null;

$fname = (!empty($_POST["firstname"]) ? $_POST["firstname"] : null);
$lname = (!empty($_POST["lastname"]) ? $_POST["lastname"] : null);
$dob = (!empty($_POST["dob"]) ? $_POST["dob"] : null);
$gender = (!empty($_POST["sex"]) ? $_POST["sex"] : null);

$min_letter_first_name = $module->getProjectSetting('first_name_minimum_letters');
$min_letter_last_name = $module->getProjectSetting('last_name_minimum_letters');

//if not set, use 0
$min_letter_first_name = $min_letter_first_name == '' ? 1 : $min_letter_first_name;
$min_letter_last_name = $min_letter_last_name == '' ? 1 : $min_letter_last_name;

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
    } elseif (strlen($fname) < $min_letter_first_name) {
        $result = array(
            'result' => 'warn',
            'status' => 'incomplete',
            'msg' => 'Minimum number of '.$min_letter_first_name.' letters required for first name to complete the search.'
        );
    } elseif (strlen($lname) < $min_letter_last_name) {
        $result = array(
            'result' => 'warn',
            'status' => 'incomplete',
            'msg' => 'Minimum number of '.$min_letter_last_name.' letters required for last name to complete the search.'
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

if ((!empty($_POST['create_new_user'])) OR (!empty($_POST['resume_existing'])))  {

    if (!empty($_POST['create_new_user'])) {
        //saveData into new record
        $id = $module->setNewUser($data);
        $msg = " created new record:  ";
    }
    if (!empty ($_POST['resume_existing'])) {
        $id = (!empty($_POST["id"]) ? $_POST["id"] : null);
        $msg = " redirected to existing survey: ";
    }

    if ($id) {
        //get survey link for the next instrument
        $instrument = $module->getProjectSetting('survey');  //'demographics';

        if (empty($instrument)) {
            $result = array(
                'result' => 'warn',
                'status' => 'incomplete',
                'msg' => 'The survey has not been set in the EM configuration');
        } else {
            $survey_link = REDCap::getSurveyLink($id, $instrument);

            $module->emLog(USERID . $msg . $survey_link . " Record ID : " . $id . " in instrument : " . $instrument);

            $result = array(
                'result' => 'success',
                'link' => $survey_link);
        }
        header('Content-Type: application/json');
        print json_encode($result);
        exit();

    } else {
        //the record was not saved, report error to REDCap logging?
        $module->emError("MISSING ID FOR NEXT SURVEY!");

    }
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
                        <button class="btn btn-primary search" id="search_participant" type="submit" name="search_participant" value="true">Search</button>
                        <button class="btn btn-primary" id="create_new_user" type="submit" name="create_new_user" value="true">Create a new CIBSR ID</button>
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
            There was no one found with the specified search criteria.
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

        //bind button for create new user
        $('#create_new_user').on('click', function() {

            console.log("clicked create_new_user");

            $('#some_found').hide();
            $('#none_found').hide();

            let formValues = {
                "firstname" : $("input#firstname.form-control").val(),
                "lastname" : $("input#lastname.form-control").val(),
                "create_new_user" : true
            };

            $.ajax({ // create an AJAX call...
                data: formValues, // get the form data
                method: "POST"
            })
                .done(function (data) {
                    console.log("DONE CREATE_NEW_USER", data);

                    if (data.result === 'success') {
                        console.log("redirecting to: ", data.link);
                        $(location).attr('href', data.link);

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

        });


        //bind button for create new user
        $('#search_participant').on('click', function() {
            console.log("clicked search_participant");
            console.log("this", $(this));

            $('#some_found').hide();
            $('#none_found').hide();

            let formValues = {
                "firstname" : $("input#firstname.form-control").val(),
                "lastname" : $("input#lastname.form-control").val(),
                "search_participant" : true
            };

            $.ajax({ // create an AJAX call...
                //data: $(this).serialize(), // get the form data
                data: formValues, // get the form data
                method: "POST"
            })
                .done(function (data) {
                    if (data.result === 'success') {

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



</script>