<?php
namespace Stanford\CIBSRSearch;
/** @var \Stanford\CIBSRSearch\CIBSRSearch $module */

use Plugin;
use REDCap;
//
//$fname = "foo";
//$lname = "foo";
//$dob = '2001-06-06';
//$gender = 1;



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
    "sex" => $gender
);

Plugin::log($_POST, "DEBUG", "POST");

// IF POST, PROCESS "SUBMISSION"
if (!empty($_POST['search_participant'])) {
    Plugin::log($_POST, "DEBUG", "GET STARTED");

    //check if this user exists already
    $search_results = $module->searchPerson($data);


    if (count($search_results) > 0) {
        //display results so they can select one
        Plugin::log($search_results, "DEBUG", "SEARCH RESULTS FOUNDD");
        $return = $module->renderFoundTable($search_results);

        $result = array(
            'result' => 'success',
            'html' => $search_results);


    } else {
        // no results so show button to add a new record
        Plugin::log($search_results, "DEBUG", "NO SEARCH RESULTS FOUNDD");
        $return = $module->renderNoneFoundMessage();

        $result = array(
            'result' => 'fail',
            'html' => $search_results);
    }

//    header('Content-Type: application/json');
//    print json_encode($result);
//    exit();


}

if (!empty($_POST['create_new_user'])) {
    Plugin::log($_POST, "DEBUG", "CREATE NEW USER");

    /**
     * $fname        = (!empty($_POST["firstname"])      ? $_POST["firstname"] : null ) ;
     * $lname            = (!empty($_POST["lastname"])        ? $_POST["lastname"] : null) ;
     * $dob            = (!empty($_POST["dob"])            ? $_POST["dob"] : null) ;
     * $gender        = (!empty($_POST["sex"])            ? $_POST["sex"] : null) ;
     *
     * $foo = ($gender === 0) ? 'checked="checked"' : '';
     * Plugin::log($gender, "DEBUG", "FOO IS ".$foo);
     *
     * $data = array(
     * "first_name"     =>$fname,
     * "last_name"      =>$lname,
     * "dob"            =>$dob,
     * "sex"            =>$gender,
     * );
     **/

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
        //redirect to filtering survey
        redirect($survey_link);
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
        redirect($survey_link);
        exit;
    }
}

if (!empty($_POST['search_family'])) {
    //check if this user exists already
    Plugin::log($data, "DEBUG", "SEARCHING FOR FAMILY");

    $h_search_results = $module->searchPerson($data);


    if (count($h_search_results) > 0) {
        //display results so they can select one
        Plugin::log($h_search_results, "DEBUG", "SEARCH RESULTS FOUNDD");

        $result = array('result' => 'success',
                        'data' => $h_search_results);

        header('Content-Type: application/json');
        print json_encode($result);
    } else {
        // no results so show button to add a new record
        Plugin::log($h_search_results, "DEBUG", "NO SEARCH RESULTS FOUNDD");
    }

    //Plugin::log($search_results, "DEBUG", "SEARCH RESULTS");

    //saveData into new record
    //$next_id = $module->setNewUser($data);
    $next_id = false;

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

    <link rel="stylesheet"
          href="<?php echo APP_PATH_WEBROOT . DS . "Resources/css/fontawesome/css/fontawesome-all.min.css" ?>"
          type="text/css"/>
    <link rel="stylesheet" href="<?php echo $module->getUrl('css/bootstrap.min.css', true, true) ?>" type="text/css"/>

    <script src="<?php echo $module->getUrl('/js/jquery.min.js', true, true) ?>"></script>
    <script src="<?php echo $module->getUrl('/js/bootstrap.min.js', true, true) ?>"></script>

    <!-- Bootstrap Date-Picker  -->
    <script type="text/javascript"
            src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/js/bootstrap-datepicker.min.js"></script>
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/css/bootstrap-datepicker3.css"/>
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
                               value=<?php echo $fname ?>>
                    </div>
                    <div class="col-sm-2">
                        <input type="text" class="form-control" name="lastname" id="lastname" placeholder="Last Name"
                               value=<?php echo $lname ?>>
                    </div>
                    <label for="gender" class="control-label col-sm-1">Gender:</label>
                    <div class="col-sm-2">
                        <label><input name="sex" type="radio"
                                      value="0" <?php echo ($gender == 0) ? 'checked="checked"' : ''; ?>>
                            Male</label><br>
                        <label><input name="sex" type="radio"
                                      value="1" <?php echo ($gender == 1) ? 'checked="checked"' : ''; ?>> Female</label><br>
                        <label><input name="sex" type="radio"
                                      value="2" <?php echo ($gender == 2) ? 'checked="checked"' : ''; ?>>
                            Phantom</label>
                    </div>

                    <label for="dob" class="control-label col-sm-1">Date of Birth:</label>
                    <div class='input-group date col-sm-2' id='datetimepicker'>
                        <input name="dob" type='text' class="form-control" placeholder="Date of Birth"
                               value=<?php echo $dob ?>>
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
                        <button class="btn btn-primary" name="create_new_user" value="1">Create a new CIBSR ID</button>
                    </div>
                </div>

                <div class="form-group">
                    <span class="control-label col-sm-12"></span>
                    <div class="col-sm-12">

                    </div>
                </div>
            </div>
        </form>
    </div>
    <div id=created></div>



    <?php if (isset($search_results)) { ?>
        <?php Plugin::log(count($search_results), "DEBUG", "COUNT OF SEARCH RESULTS?"); ?>
        <?php if (count($search_results) > 0) { ?>
            <div class="col-md-12">

                <hr>
                <hr>
                <h4>Search Result</h4>
                <p>

                    We have found these potential matches for your search.<br>
                    If none are correct, please click the 'Create a new CIBSR ID button' to create a new ID.

                    <?php echo "Count found: " ?>
                    <?php echo(count($search_results)) ?>

                </p>
                <p>

                </p>
                <?php print $module->renderSearchTable($search_results) ?>

            </div>
        <?php } else { ?>
            <div class="col-md-12">
                <p>
                    There were no persons found with the specified search criteria.

                </p>

            </div>
        <?php } ?>
        <!-- <form method="POST" id="form0" action="">  -->

        </form>

    <?php } ?>

    <!-- Modal -->
    <div class="modal modal-xl fade" id="reportModal" role="dialog" aria-labelledby="reportModalLabel"
         aria-hidden="true">

        <div class="modal-dialog">
            <form method="POST" id="familysearch" action="">
                <!-- Modal content-->
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title" id="reportModalLabel">Search for Household</h4>

                    </div>
                    <div class="modal-body">
                        <div class="panel-body" id="set_household_modal">

                            <p class="control-label col-sm-12">Household ID has not yet been set for this
                                participant.</p>
                            <div id="family">
                                <label class=" control-label col-sm-12">Search for a family member who might already be
                                    a participant. </label>
                                <div class="clearfix row">
                                    <label for="name" class="control-label col-sm-6">Name:</label>
                                    <label for="gender" class="control-label lb-sm col-sm-3">Gender:</label>
                                    <label for="dob" class="control-label col-sm-3">Date of Birth:</label>


                                </div>
                                <div class="clearfix row">
                                    <div class="col-sm-3">
                                        <input type="text" class="form-control" name="firstname" id="firstname"
                                               placeholder="First Name">
                                    </div>
                                    <div class="col-sm-3">
                                        <input type="text" class="form-control" name="lastname" id="lastname"
                                               placeholder="Last Name">

                                    </div>

                                    <div class="col-sm-3">
                                        <label><input name="sex" type="radio">

                                            Male</label><br>
                                        <label><input name="sex" type="radio">

                                            Female</label><br>
                                        <label><input name="sex" type="radio">

                                            Phantom</label>
                                    </div>

                                    <div class='input-group date col-sm-3' id='datetimepicker2'>
                                        <input name="h_dob" type='text' class="form-control"
                                               placeholder="Date of Birth">
                                        <span class="input-group-addon">
                            <span class="glyphicon glyphicon-calendar"></span>
                        </span>
                                    </div>


                                </div>
                                <div id=created_family></div>


                                <br>
                            </div>


                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button id="new_houseid" type="submit" class="btn btn-primary" name="create_houseid"
                                value="true">
                            Create New HouseId
                        </button>
                        <button id="search_family" type="submit" class="btn btn-primary" name="search_family"
                                value="true">
                            Search for Family Members
                        </button>
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
        $('#datetimepicker').datepicker({
            format: 'yyyy-mm-dd'
        });


        $('#datetimepicker2').datepicker({
            format: 'yyyy-mm-dd'
        });

        $('#getstarted').submit(function () { // catch the form's submit event


            var formValues = {};

            $.each($(this).serializeArray(), function (i, field) {
                formValues[field.name] = field.value;
            });

            console.log("getstarted: in submit" + formValues);


            $.ajax({ // create an AJAX call...
                //data: $(this).serialize(), // get the form data
                data: formValues, // get the form data
                method: $(this).attr('method'), // GET or POST
                url: $(this).attr('action') // the file to call
            })
                .done(function (data) {
                    console.log("DATA RESULT: " + data.result);
                    console.log("DATA HTML: " + data.html);
                    //$('#created').html('<h3>foobar</h3>');

                    if (data.result === 'success') {
                        // all is good
                        console.log("DATA HTML" + data.html);
                        //$('#created').html('<h3>foobar</h3>');
                        $('#created').html(data.html);

                    } else {
                        $('#created').html(data.html);
                        // an error occurred
                        //alert("Unable to Save<br><br>" + data.message, "ERROR - SAVE FAILURE" );
                    }

                })
                .fail(function (data) {
                    console.log("DATA: " + data.result);
                    alert("error:" + data.result);
                })
                .always(function () {
                    //saveBtn.html(saveBtnHtml);
                    //saveBtn.prop('disabled',false);
                });

        });


        $('#familysearch').submit(function () { // catch the form's submit event

            var formValues = {};

            $.each($(this).serializeArray(), function (i, field) {
                formValues[field.name] = field.value;
            });

            formValues['search_family'] = true;

            console.log("familysearch: in submit" + formValues);
            $.ajax({ // create an AJAX call...

                data: formValues, // get the form data
                method: "POST" // GET or POST
            })
                .done(function (data) {
                    $('#created_family').html('<h3>foobar </h3>');

                    if (data.result === 'success') {
                        // all is good
                        $('#created_family').html('<h3>foobar good</h3>');

                    } else {
                        $('#created_family').html('<h3>foobar bad</h3>');
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

        });

        $('#search_table').on('click', 'button.select_id', function () {
            buttonpressed = $(this).attr('name');
            console.log("search_table: in submit" + buttonpressed);

            var id = $(this).data('id');
            var houseid = $(this).data('houseid');
            console.log("id is " + id);
            console.log("houseid is " + houseid);
            var modal = $(this);

            modal.find('.modal-title').text('New message to ' + id);

            if (!houseid) {
                console.log("opening modal");
                //comment out to show lara
                $("#reportModal").find('.modal-title').text('Select household id for ' + id);
                $("#reportModal").modal('show');

                console.log("why does modal keep shutting down.");
            } else {
                console.log("houseid already exists");
                //redirect to existing record to edit.
                var formValues = {};
                formValues['resume_existing'] = true;
                formValues['id'] = id;

                $.ajax({ // create an AJAX call...
                    data: formValues,
                    method: "POST"
                })
            }
        });

    });


</script>