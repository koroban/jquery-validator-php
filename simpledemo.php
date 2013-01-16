<?php
// This demo still performs validation on both the client and server side
// But it requires JS to display the server-generated errors

include("jqueryvalidator.class.php");

// Start validator:
$V = new Validator();

// Fetch and set the rules
$V->setRuleFile("./demo.validate.json");

// Add remote methods
$V->addMethod('checkEmail', 'Please check your email address.', function($value){
	return ($value=='howard.yeend@gg.com');
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
		// You can use $V->getErrors() and $V->getErrorForField($SomeFieldName) to get more detailed error data
	}
}

?>
<html>
<head>
	<title></title>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js" type="text/javascript"></script>
	<script src="jquery-validate/jquery.validate.js" type="text/javascript"></script><!-- tested with 1.1.0 -->
	<script type="text/javascript">
	
	$(document).ready(function() {

	  // fetch rules and set up validation using jquery validator.
	  $.getJSON("./demo.validate.json", function(rulesObject) {
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
<form id="demoForm" method="post" action="simpledemo.php" enctype="multipart/form-data">
	
	<h1>Example Form:</h1>
	<p>
		This form shows all the types of validation that are possible by default. jquery-validator's <a href="additional.php">additional validation methods</a> are also supported.
	</p>
	<p>
		All fields are required. Additional rules are shown under the field label.
	</p>
	
	<div class="first formContainer">
		<div class="formElement">
			<label for="RequiredField">Required Field:<br /><small>"required": true</small></label>
			<input type="text" name="RequiredField" id="RequiredField" value="" />
		</div>

		<div class="formElement">
			<label for="EmailField">Email Address:<br /><small>"email": true</small></label>
			<input type="text" name="EmailField" id="EmailField" value="" />
		</div>

		<div class="formElement">
			<label for="ConfirmEmailField">Confirm Email:<br /><small>"equalTo":"#EmailField"</small></label>
			<input type="text" name="ConfirmEmailField" id="ConfirmEmailField" value="" />
		</div>

		<div class="formElement">
			<label for="UrlField">URL:<br /><small>"url": true</small></label>
			<input type="text" name="UrlField" id="UrlField" value="" />
		</div>

		<div class="formElement">
			<label for="DateField">Date:<br /><small>"date": true</small></label>
			<input type="text" name="DateField" id="DateField" value="" />
		</div>

		<div class="formElement">
			<label for="DateISOField">DateISO:<br /><small>"dateiso": true</small></label>
			<input type="text" name="DateISOField" id="DateISOField" value="" />
		</div>

		<div class="formElement">
			<label for="NumberField">Number:<br /><small>"number": true</small></label>
			<input type="text" name="NumberField" id="NumberField" value="" />
		</div>

		<div class="formElement">
			<label for="DigitsField">Digits:<br /><small>"digits": true</small></label>
			<input type="text" name="DigitsField" id="DigitsField" value="" />
		</div>

		<div class="formElement">
			<label for="CreditCardField">Credit Card:<br /><small>"creditcard":true</small></label>
			<input type="text" name="CreditCardField" id="CreditCardField" value="" />
		</div>

	</div>
	<div class="formContainer">

		<div class="formElement">
			<label for="FileField">File:<br /><small>"accept":"txt|pdf|odf|doc"</small></label>
			<input type="file" name="FileField" id="FileField" value="" />
		</div>

		<div class="formElement">
			<label for="MinlengthField">Minlength:<br /><small>"minlength":6</small></label>
			<input type="text" name="MinlengthField" id="MinlengthField" value="" />
		</div>

		<div class="formElement">
			<label for="MaxlengthField">Maxlength:<br /><small>"maxlength":10</small></label>
			<input type="text" name="MaxlengthField" id="MaxlengthField" value="" />
		</div>

		<div class="formElement">
			<label for="RangeField">Range:<br /><small>"range":[0,100]</small></label>
			<input type="text" name="RangeField" id="RangeField" value="" />
		</div>

		<div class="formElement">
			<label for="MinField">Min:<br /><small>"min":20</small></label>
			<input type="text" name="MinField" id="MinField" value="" />
		</div>

		<div class="formElement">
			<label for="MaxField">Max:<br /><small>"max":50</small></label>
			<input type="text" name="MaxField" id="MaxField" value="" />
		</div>

		<div class="formElement">
			<label for="RangelengthField">Rangelength:<br /><small>"rangelength":[5,10]</small></label>
			<input type="text" name="RangelengthField" id="RangelengthField" value="" />
		</div>

		<div class="formElement">
			<label for="RemoteField">RemoteField:</label>
			<input type="text" name="RemoteField" id="RemoteField" value="" />
		</div>

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
