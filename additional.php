<?php
include("validator.class.php");
include("additionalvalidator.class.php");

// default form values:
$formDefault = array();
$formDefault["maxWordsField"] = "";
$formDefault["minWordsField"] = "";
$formDefault["rangeWordsField"] = "";
$formDefault["lettersonlyField"] = "";
$formDefault["letterswithbasicpuncField"] = "";
$formDefault["alphanumericField"] = "";
$formDefault["nowhitespaceField"] = "";
$formDefault["integerField"] = "";
$formDefault["time24hField"] = "";
$formDefault["time12hField"] = "";
$formDefault["phoneUSField"] = "";
$formDefault["phoneUKField"] = "";
$formDefault["mobileUKField"] = "";
$formDefault["postcodeField"] = "";
$formDefault["strippedminlengthField"] = "";

// Start validator:
$V = new AdditionalValidator();

// Fetch and set the rules
$V->setRuleFile("./additional.validate.json");

// Add remote methods
$V->addMethod('checkEmail', 'Please check your email address.', function($value){
	return ($value=='howard@gg.com');
});

// Perform remote validation, if appropriate
$V->performRemoteValidation();

if(!empty($_POST)) {

	// Munge the posted data to include file name details
	$postedDetails = $_POST;
	// add posted file names to form details for validation:
	foreach($_FILES as $field=>$details) {
		$postedDetails[$field] = $details['name'];
	}
	
	// Validate the data
	$V->validate($postedDetails);

	// If the form is valid then do something
	if($V->isValid()) {
		echo "VALID";
	} else {
		echo "INVALID";
		//var_dump();
		//exit();
		// the form was invalid; overwrite default form values with posted vals
		// so we can retain the values when we redisplay the form.
		foreach($postedDetails as $fieldName=>$value) {
			$formDefault[$fieldName] = htmlspecialchars($value);
		}
	}
}

?>
<html>
<head>
	<title></title>
	<script src="jquery-1.4.2.min.js" type="text/javascript"></script>
	<script src="jquery-validate/jquery.validate.js" type="text/javascript"></script>
	<script src="jquery-validate/additional-methods.js" type="text/javascript"></script>
	<script type="text/javascript">
	
	$(document).ready(function() {

	  // fetch rules and set up validation using jquery validator.
	  $.getJSON("./additional.validate.json", function(rulesObject) {
		var validator = $("#demoForm").validate({
			// initialise the validator:
			success: function(label) {
			  label.addClass("success");
			},
			rules: rulesObject
		});
		// Display PHP-generated errors if present
		if($('body').attr('data-errors')!='') {
			validator.showErrors(JSON.parse($('body').attr('data-errors')));
		}
	  });
	});
	
	</script>
	<link rel="stylesheet" type="text/css" href="default.css" />
</head>
<body data-errors='<?php echo $V->getJQVCompatibleErrors();?>'>
<form id="demoForm" method="post" action="additional.php" enctype="multipart/form-data">
	
	<h1>Example Form:</h1>
	<p>
		This form shows all the types of validation that are possible by default. jquery-validator's <a href="additional.php">additional validation methods</a> are also supported.
	</p>
	<p>
		All fields are required. Additional rules are shown under the field label.
	</p>

<div class="formContainer">	

		<div class="formElement">
			<label for="RequiredField">maxWords Field:<br /><small>"maxWords": 2</small></label>
			<input type="text" name="maxWordsField" id="maxWordsField" value="<?php echo $formDefault["maxWordsField"] ?>" />
			<?php echo $V->getErrorForField("maxWordsField"); ?>		</div>


		<div class="formElement">
			<label for="RequiredField">minWords Field:<br /><small>"minWords": 2</small></label>
			<input type="text" name="minWordsField" id="minWordsField" value="<?php echo $formDefault["minWordsField"] ?>" />
			<?php echo $V->getErrorForField("minWordsField"); ?>		</div>


		<div class="formElement">
			<label for="RequiredField">rangeWords Field:<br /><small>"rangeWords": [2,4]</small></label>
			<input type="text" name="rangeWordsField" id="rangeWordsField" value="<?php echo $formDefault["rangeWordsField"] ?>" />
			<?php echo $V->getErrorForField("rangeWordsField"); ?>		</div>


		<div class="formElement">
			<label for="RequiredField">lettersonly Field:<br /><small>"lettersonly": true</small></label>
			<input type="text" name="lettersonlyField" id="lettersonlyField" value="<?php echo $formDefault["lettersonlyField"] ?>" />
			<?php echo $V->getErrorForField("lettersonlyField"); ?>		</div>


		<div class="formElement">
			<label for="RequiredField">letterswithbasicpunc Field:<br /><small>"letterswithbasicpunc": true</small></label>
			<input type="text" name="letterswithbasicpuncField" id="letterswithbasicpuncField" value="<?php echo $formDefault["letterswithbasicpuncField"] ?>" />
			<?php echo $V->getErrorForField("letterswithbasicpuncField"); ?>		</div>


		<div class="formElement">
			<label for="RequiredField">alphanumeric Field:<br /><small>"alphanumeric": true</small></label>
			<input type="text" name="alphanumericField" id="alphanumericField" value="<?php echo $formDefault["alphanumericField"] ?>" />
			<?php echo $V->getErrorForField("alphanumericField"); ?>		</div>


		<div class="formElement">
			<label for="RequiredField">nowhitespace Field:<br /><small>"nowhitespace": true</small></label>
			<input type="text" name="nowhitespaceField" id="nowhitespaceField" value="<?php echo $formDefault["nowhitespaceField"] ?>" />
			<?php echo $V->getErrorForField("nowhitespaceField"); ?>		</div>


		<div class="formElement">
			<label for="RequiredField">integer Field:<br /><small>"integer": true</small></label>
			<input type="text" name="integerField" id="integerField" value="<?php echo $formDefault["integerField"] ?>" />
			<?php echo $V->getErrorForField("integerField"); ?>		</div>

	</div>
	<div class="formContainer">

		<div class="formElement">
			<label for="RequiredField">time24h Field:<br /><small>"time24h": true</small></label>
			<input type="text" name="time24hField" id="time24hField" value="<?php echo $formDefault["time24hField"] ?>" />
			<?php echo $V->getErrorForField("time24hField"); ?>		</div>


		<div class="formElement">
			<label for="RequiredField">time12h Field:<br /><small>"time12h": true</small></label>
			<input type="text" name="time12hField" id="time12hField" value="<?php echo $formDefault["time12hField"] ?>" />
			<?php echo $V->getErrorForField("time12hField"); ?>		</div>


		<div class="formElement">
			<label for="RequiredField">phoneUS Field:<br /><small>"phoneUS": true</small></label>
			<input type="text" name="phoneUSField" id="phoneUSField" value="<?php echo $formDefault["phoneUSField"] ?>" />
			<?php echo $V->getErrorForField("phoneUSField"); ?>		</div>


		<div class="formElement">
			<label for="RequiredField">phoneUK Field:<br /><small>"phoneUK": true</small></label>
			<input type="text" name="phoneUKField" id="phoneUKField" value="<?php echo $formDefault["phoneUKField"] ?>" />
			<?php echo $V->getErrorForField("phoneUKField"); ?>		</div>


		<div class="formElement">
			<label for="RequiredField">mobileUK Field:<br /><small>"mobileUK": true</small></label>
			<input type="text" name="mobileUKField" id="mobileUKField" value="<?php echo $formDefault["mobileUKField"] ?>" />
			<?php echo $V->getErrorForField("mobileUKField"); ?>		</div>


		<div class="formElement">
			<label for="RequiredField">postcode Field:<br /><small>"postcode": true</small></label>
			<input type="text" name="postcodeField" id="postcodeField" value="<?php echo $formDefault["postcodeField"] ?>" />
			<?php echo $V->getErrorForField("postcodeField"); ?>		</div>


		<div class="formElement">
			<label for="RequiredField">strippedminlength Field:<br /><small>"strippedminlength": 4</small></label>
			<input type="text" name="strippedminlengthField" id="strippedminlengthField" value="<?php echo $formDefault["strippedminlengthField"] ?>" />
			<?php echo $V->getErrorForField("strippedminlengthField"); ?>		</div>



		<div class="formElement action">
			<label for="Validate">Submit with JS Validation:</label>
			<input type="submit" id="Validate" value="Submit" class="submit button" />
		</div>

		<div class="formElement action">
			<label for="NoValidate">Submit for server validation:</label>
			<input type="submit" id="NoValidate" value="Submit" class="submit button cancel" />
		</div>
	</div>

</form>

</body>
</html>
