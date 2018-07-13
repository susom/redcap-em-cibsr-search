<?php
namespace Stanford\CIBSRSearch;
/** @var \Stanford\CIBSRSearch\CIBSRSearch $module */

use \REDCap as REDCap;
use Plugin;

/**
 * This is the INTAKE survey
 */

$search_results = null;
$household_id = null;
$cibsr_id = null;
$gender = null;

$fname  		= (!empty($_POST["firstname"])      ? $_POST["firstname"] : null ) ;
$lname 	    	= (!empty($_POST["lastname"]) 	    ? $_POST["lastname"] : null) ;
$dob 	    	= (!empty($_POST["dob"]) 	        ? $_POST["dob"] : null) ;
$gender     	= (!empty($_POST["sex"])     	    ? $_POST["sex"] : null) ;

$data = array(
    "first_name"     =>$fname,
    "last_name"      =>$lname,
    "dob"            =>$dob,
    "sex"            =>$gender
);


if (!empty($_POST['action'])) {
    $action = $_POST['action'];

    switch ($action) {
        case "set_id":
            //save the household ID
            $household_id = $_POST['house_id'];
            $cibsr_id = $_POST['id'];
            Plugin::log($household_id, "DEBUG", "Saving household id to ".$cibsr_id);


            //hide the household search table

            $result = array('result' => 'success');
            header('Content-Type: application/json');
            print json_encode($result);
            exit;
            break;
        case "search_participant":
            Plugin::log($_POST, "DEBUG", "SEARCH PARTICIPANT");
            //check if this user exists already
            $search_results = $module->searchPerson($data);

        default:
            Plugin::log($_POST, "Unknown Action");
    }


}

// IF POST, PROCESS "SUBMISSION"
if (!empty($_POST['search_participant'])) {
    //check if this user exists already
    $search_results = $module->searchPerson($data);


    if (count($search_results) > 0) {
        //display results so they can select one
        Plugin::log($search_results, "DEBUG", "SEARCH RESULTS FOUNDD");
    } else {
        // no results so show button to add a new record
        Plugin::log($search_results, "DEBUG", "NO SEARCH RESULTS FOUNDD");
    }

    //Plugin::log($search_results, "DEBUG", "SEARCH RESULTS");

    //saveData into new record
    //$next_id = $module->setNewUser($data);
    $next_id = false;

}

if (!empty($_POST['create_new_user'])) {
    //Plugin::log($_POST, "DEBUG", "CREATE NEW USER");

    /**
    $fname  		= (!empty($_POST["firstname"])      ? $_POST["firstname"] : null ) ;
    $lname 	    	= (!empty($_POST["lastname"]) 	    ? $_POST["lastname"] : null) ;
    $dob 	    	= (!empty($_POST["dob"]) 	        ? $_POST["dob"] : null) ;
    $gender     	= (!empty($_POST["sex"])     	    ? $_POST["sex"] : null) ;

    $foo = ($gender === 0) ? 'checked="checked"' : '';
    Plugin::log($gender, "DEBUG", "FOO IS ".$foo);

    $data = array(
        "first_name"     =>$fname,
        "last_name"      =>$lname,
        "dob"            =>$dob,
        "sex"            =>$gender,
    );
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

        Plugin::log($survey_link, "DEBUG", "SURVEY_LINK: ".$next_id . "in instrument". $instrument);
        //redirect to filtering survey
        redirect($survey_link);
    } else {
        //the record was not saved, report error to REDCap logging?

    }
}

if (!empty($_POST['search_family'])) {
    Plugin::log($_POST, "DEBUG", "SEARCHING FAMILY");

    //search for these participants and
}



?><!DOCTYPE html>
<!--[if IE 7]> <html lang="en" class="ie7"> <![endif]-->
<!--[if IE 8]> <html lang="en" class="ie8"> <![endif]-->
<!--[if IE 9]> <html lang="en" class="ie9"> <![endif]-->
<!--[if !IE]><!-->
<html lang="en">
<!--<![endif]-->
<head>
    <title><?php echo $module->config['title']; ?></title>
    <!-- Meta -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Site / page description" />
    <meta name="author" content="Stanford | Medicine" />


    <!-- Apple Icons - look into http://cubiq.org/add-to-home-screen -->
    <link rel="apple-touch-icon" sizes="57x57" href="<?php echo $module->getUrl('img/apple-icon-57x57.png',true, true) ?>" />
    <link rel="apple-touch-icon" sizes="72x72" href="<?php echo $module->getUrl('img/apple-icon-72x72.png',true, true) ?>" />
    <link rel="apple-touch-icon" sizes="114x114" href="<?php echo $module->getUrl('img/apple-icon-114x114.png',true, true) ?>" />
    <link rel="apple-touch-icon" sizes="144x144" href="<?php echo $module->getUrl('img/apple-icon-144x144.png',true, true) ?>" />
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $module->getUrl('img/favicon-32x32.png',true, true) ?>" />
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo $module->getUrl('img/favicon-16x16.png',true, true) ?>" />
    <link rel="shortcut icon" href="<?php echo $module->getUrl('/img/favicon.ico',true, true) ?>" />

    <!-- CSS -->
    <link rel="stylesheet" href="<?php echo APP_PATH_WEBROOT . DS . "Resources/css/fontawesome/css/fontawesome-all.min.css" ?>" type="text/css" />
    <link rel="stylesheet" href="<?php echo $module->getUrl('css/bootstrap.min.css',true, true) ?>" type="text/css" />
    <link rel="stylesheet" href="<?php echo $module->getUrl('css/bootstrap-formhelpers.min.css',true, true) ?>" type="text/css" />
<!--    <link rel="stylesheet" href="--><?php //echo $module->getUrl('/css/base.min.css',true, true) ?><!--" type="text/css" />-->
    <link rel="stylesheet" href="<?php echo $module->getUrl('css/custom.css',true, true) ?>" type="text/css"/>

    <!--[if lt IE 9]>
    <!--<script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>-->
    <!--[endif]-->
    <!--[if IE 8]>
    <!--<link rel="stylesheet" type="text/css" href="<?php echo $module->getUrl('/css/ie/ie8.css',true, true) ?>" />-->
    <!--[endif]-->
    <!--[if IE 7]>
    <!--<link rel="stylesheet" type="text/css" href="<?php echo $module->getUrl('/css/ie/ie7.css',true, true) ?>" />-->
    <!--[endif]-->
    <!-- JS and jQuery -->
    <!-- <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
     -->
    <!--[if lt IE 9]>
    <script src="<?php echo $module->getUrl('/js/respond.js',true, true) ?>"></script>
    <!--[endif]-->

    <!-- PLACING JSCRIPT IN HEAD OUT OF SIMPLICITY - http://stackoverflow.com/questions/10994335/javascript-head-body-or-jquery -->
    <!-- Latest compiled and minified JavaScript -->
    <!--
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <script src="https://ajax.aspnetcdn.com/ajax/jquery.validate/1.13.1/jquery.validate.min.js"></script>
    -->
    <!-- Local version for development here -->


    <script src="<?php echo $module->getUrl('/js/jquery.min.js',true, true) ?>"></script>
    <script src="<?php echo $module->getUrl('/js/jquery.validate.min.js',true, true) ?>"></script>
    <script src="<?php echo $module->getUrl('/js/bootstrap-formhelpers.min.js',true, true) ?>"></script>


    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <link href='https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700' rel='stylesheet' type='text/css'>
    <link href='https://fonts.googleapis.com/css?family=Crimson+Text:400,600,700' rel='stylesheet' type='text/css'>

    <!-- Bootstrap core CSS -->
    <link rel="stylesheet" type="text/css" media="screen" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">

    <!-- Bootstrap Date-Picker  -->
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/js/bootstrap-datepicker.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.4.1/css/bootstrap-datepicker3.css"/>

    <script src="<?php echo $module->getUrl('/js/bootstrap.min.js',true, true) ?>"></script>



</head>
<body class="login register">
<div id="su-wrap">
    <form method="POST" id="getstarted" action="">
        <div class="panel panel-primary">
            <div class="panel-heading">
                <h4><?php echo $module->getModuleName() ?></h4>
            </div>
            <div class="panel-body">
                <div class="form-group row">

                    <label for="name" class="control-label col-sm-1">Name:</label>
                    <div class="col-sm-2">
                        <input type="text" class="form-control" name="firstname" id="firstname" placeholder="First Name"
                               value=<?php echo $fname?>>
                    </div>
                    <div class="col-sm-2">
                        <input type="text" class="form-control" name="lastname" id="lastname" placeholder="Last Name"
                               value=<?php echo $lname?>>
                    </div>
                    <label for="gender" class="control-label col-sm-1">Gender:</label>
                    <div class="col-sm-2">
                        <label><input name="sex" type="radio" value="0"  <?php echo ($gender == 0) ? 'checked="checked"' : ''; ?>> Male</label><br>
                        <label><input name="sex" type="radio" value="1" <?php echo ($gender  == 1) ? 'checked="checked"' : ''; ?>> Female</label><br>
                        <label><input name="sex" type="radio" value="2" <?php echo ($gender == 2) ? 'checked="checked"' : ''; ?>> Phantom</label>
                    </div>

                    <label for="dob" class="control-label col-sm-1">Date of Birth:</label>
                    <div class='input-group date col-sm-2' id='datetimepicker'>
                        <input name="dob" type='text' class="form-control" placeholder="Date of Birth" value=<?php echo $dob?>>
                        <span class="input-group-addon">
                            <span class="glyphicon glyphicon-calendar"></span>
                        </span>
                    </div>

                </div>
                <div class="form-group">
                    <span class="control-label col-sm-12"></span>
                    <div class="col-sm-12">
                        <button id="search_participant" type="submit" class="btn btn-primary" name="search_participant" value="true">
                            Search
                        </button>
                    </div>
                </div>
                <br>
            </div>

    <!--</form>  -->
            <?php Plugin::log(count($search_results), "DEBUG", "COUNT OF SEARCH RESULTS?"); ?>
            <?php if (isset($search_results)) { ?>
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
                <div class="form-group">
                    <span class="control-label col-sm-12"></span>
                    <div class="col-sm-12">
                        <button class="btn btn-primary" name="create_new_user" value="1">Create a new CIBSR ID</button>
                    </div>
                </div>
    </form>

            <?php } ?>

    <?php // if ( isset($cibsr_id) && (! isset($household_id))) { ?>


    <?php // } ?>
        </div>



    <script>


        $('#getstarted').validate({
            rules: {
                firstname: {
                    required: true
                },
                lastname: {
                    required: true
                }

            },
            highlight: function (element) {
                $(element).closest('.form-group').addClass('has-error');
            },
            unhighlight: function (element) {
                $(element).closest('.form-group').removeClass('has-error');
            },
            errorElement: 'span',
            errorClass: 'help-block',
            errorPlacement: function (error, element) {
                if (element.parent('.input-group').length) {
                    error.insertAfter(element.parent());
                } else {
                    error.insertAfter(element);
                }
            }
        });

        $("#getstarted").submit(function () {
            var formValues = {};

            console.log("in submit");
            $.each($(this).serializeArray(), function (i, field) {
                formValues[field.name] = field.value;
            });

            if (formValues.firstname == "" || formValues.lastname == "" || formValues.username == "" || $(this).find(".help-block").length) {
                return;
            }

            //ADD LOADING DOTS
            $("button[name='search_participant']").append("<img width=50 height=14 src='<?php echo $module->getUrl('img/loading_dots.gif', true, false) ?>'/>")
        });

        $("#familysearch").validate({
            rules: {
                firstname: {
                    required: true
                },
                lastname: {
                    required: true
                }

            },
            highlight: function (element) {
                $(element).closest('.form-group').addClass('has-error');
            },
            unhighlight: function (element) {
                $(element).closest('.form-group').removeClass('has-error');
            },
            errorElement: 'span',
            errorClass: 'help-block',
            errorPlacement: function (error, element) {
                if (element.parent('.input-group').length) {
                    error.insertAfter(element.parent());
                } else {
                    error.insertAfter(element);
                }
            }
        });


        $("#familysearch").submit(function () {
            var formValues = {};

            $.each($(this).serializeArray(), function (i, field) {
                formValues[field.name] = field.value;
            });

            if (formValues.firstname == "" || formValues.lastname == "" || formValues.username == "" || $(this).find(".help-block").length) {
                return;
            }

            //ADD LOADING DOTS
            $("button[name='search_participant']").append("<img width=50 height=14 src='<?php echo $module->getUrl('img/loading_dots.gif', true, false) ?>'/>")
        });

    </script>
</div>
<div class="modal fade" id="reportModal" tabindex="-1" role="dialog" aria-labelledby="reportModalLabel"
     aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
                <h4 class="modal-title" id="reportModalLabel">Search for Household</h4>
            </div>
            <div class="modal-body">

                <div class="panel-body" id="set_household_modal">
                    <hr>
                    <div class="col-sm-12">
                        <p class="control-label col-sm-12">Household ID is not yet set for this participant.</p>

                        <label for="family_member" class="control-label col-sm-6"> Has a family member of this
                            participant been seen here before?:</label>
                        <div class="col-sm-6">
                            <label><input name="family_member" type="radio" value="0"> No</label><br>
                            <label><input id="watch-me" name="family_member" type="radio" value="1"> Yes</label><br>
                        </div>
                    </div>
                    <div id="family" style="display: none;">
                        <label class=" control-label col-sm-12">Search for a family member who might already be
                            a participant. </label>
                        <label for="name" class="control-label col-sm-1">Name:</label>
                        <div class="clearfix row">
                            <div class="col-sm-2">
                                <input type="text" class="form-control" name="h_firstname" id="h_firstname"
                                       placeholder="First Name"
                                       value=<?php echo $fname ?>>
                            </div>
                            <div class="col-sm-2">
                                <input type="text" class="form-control" name="h_lastname" id="h_lastname"
                                       placeholder="Last Name"
                                       value=<?php echo $lname ?>>
                            </div>
                            <label for="gender" class="control-label col-sm-1">Gender:</label>
                            <div class="col-sm-2">
                                <label><input name="h_sex" type="radio"
                                              value="0" <?php echo ($gender == 0) ? 'checked="checked"' : ''; ?>>
                                    Male</label><br>
                                <label><input name="h_sex" type="radio"
                                              value="1" <?php echo ($gender == 1) ? 'checked="checked"' : ''; ?>>
                                    Female</label><br>
                                <label><input name="h_sex" type="radio"
                                              value="2" <?php echo ($gender == 2) ? 'checked="checked"' : ''; ?>>
                                    Phantom</label>
                            </div>
                            <label for="dob" class="control-label col-sm-1">Date of Birth:</label>
                            <div class='input-group date col-sm-2' id='datetimepicker2'>
                                <input name="h_dob" type='text' class="form-control" placeholder="Date of Birth"
                                       value=<?php echo $dob ?>>
                                <span class="input-group-addon">
                            <span class="glyphicon glyphicon-calendar"></span>
                        </span>
                            </div>


                        </div>


                        <br>
                    </div>

                </div>
                <div class="modal-footer">
                    <button id="search_family" type="submit" class="btn btn-primary" name="search_family" value="true">
                        Search for Family Members
                    </button>

                </div>
            </div>
        </div>
    </div>
</body>
</html>
<script type = "text/javascript">

    $(document).ready(function(){
        $('#datetimepicker').datepicker({
            format: 'yyyy-mm-dd'

        });
        $('#datetimepicker2').datepicker({
            format: 'yyyy-mm-dd'
        });

        $('#search_table').on('click', 'button.select_id',function () {
            var id = $(this).data('id');
            var houseid = $(this).data('houseid');
            console.log("id is "+id );
            console.log("id is "+houseid );

           if (! houseid) {
               console.log("opening modal");
               //comment out to show lara
               //$("#reportModal").modal('show');
               console.log("why does modal keep shutting down.");
           } else {
               console.log("houseid already exists");
           }
        });


        $('input[name=family_member]').click(function () {
            if (this.id == "watch-me") {
                $("#family_search2").show('slow');
            } else {
                $("#family_search2").hide('slow');
            }
        });


    });

    function useID(id, house_id) {
        console.log("this is the id: "+id + ' and house id: '+house_id);
        var data = {
            "action"  : "set_id",
            "id"      : id,
            "house_id": house_id
        };
        $.ajax({
            method: 'POST',
            data: data,
            dataType: "json"
        })
            .done(function (data) {
                console.log("THIS IS THE HOUSE: "+house_id);
                $("#set_household").show();
                if (data.result === 'success') {

                    if (house_id) {
                        alert("This CIBSR ID has been selected: "+id + ' and this record already has a household_id: ' +house_id);

                    } else {
                        $("#set_household").show();
                        alert("This CIBSR ID has been selected: "+id + ' but there is no household_id: ' +house_id);

                    }
                    $("input[name='h_cibsr_id']");
                    $('input[name="h_cibsr_id"]').val(id);
                    $('input[name="h_house_id"]').val(house_id);

                } else {
                    alert("Unable to run<br><br>" + data.message, "ERROR - SAVE FAILURE" );
                }
            })
            .fail(function (data) {
                console.log(data.responseText);
                alert(data.responseText);
            })
            .always(function() {


            });
    }

</script>