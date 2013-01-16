<?php

/*

A PHP class which implements the same ruleset as jquery.validator
Allows us to specify validation rules in a .json file on the server
and have those rules implemented in javascript by jquery.validator
and in PHP by this class.

*/

class Validator {
	protected static $messages = array();
	private $valid = true;
	protected $errors = array();
	private $rules;
	protected $data;
	protected $remote_methods = array();
	protected $remote_method_messages = array();
	
	public function __construct($rules=null) {
		self::$messages = array(
			'REQUIRED'		=> 'This field is required.',
			'EMAIL'			=> 'Please enter a valid email address.',
			'URL'			=> 'Please enter a valid URL.',
			'DATE'			=> 'Please enter a valid date.',
			'DATEISO'		=> 'Please enter a valid date (YYYY-MM-DD).',
			'NUMBER'		=> 'Please enter a valid number.',
			'DIGITS'		=> 'Please enter only digits.',
			'CREDITCARD'	=> 'Please enter a valid credit card number.',
			'EQUALTO'		=> 'Please enter the same value again.',
			'ACCEPT'		=> 'Please enter a value with a valid extension.',
			'MINLENGTH'		=> 'Please enter at least {0} characters.',
			'MAXLENGTH'		=> 'Please enter no more than {0} characters.',
			'RANGE'			=> 'Please enter a value between {0} and {1}.',
			'MIN'			=> 'Please enter a value greater than or equal to {0}.',
			'MAX'			=> 'Please enter a value less than or equal to {0}.',
			'RANGELENGTH'	=> 'Please enter a value between {0} and {1} characters long.'
		);
		
		$this->setRules($rules);
	}

	public function setRuleFile($ruleFile) {
		$this->setRules(json_decode(file_get_contents($ruleFile)));
	}
	
	public function setRules($rules) {
		if(is_object($rules)) {
			$this->rules = $rules;
		}
	}
	
	public function isValid() {
		return $this->valid;
	}

	public function addMethod($name, $message, $method) {
		$this->remote_methods[$name]         = $method;
		$this->remote_method_messages[$name] = $message;
	}
	
	public function performRemoteValidation($data=null) {
		// Default to $_POST, but allow this to be overridden
		if($data == null) {
			$data = $_POST;
		}
		// Check to see if this is a remote request
		if(array_key_exists("remoteMethod", $data)) {
			// We are a remote request, and we'll be returning some JSON
			header('Content-type: application/json');
			// Start by assuming it generically failed validation
			// we'll override on success or if we have a more specific message to give.
			$response = false;					
			// Let's unset remoteMethod and pick that up from our rules instead. Should be identical.
			unset($data['remoteMethod']);
			// You can only send one field to be remotely validated at a time
			// the presence of a "remoteMethod" key indicates that this is indeed a remote request
			// the other key in $data will be the field, but we need to access it from a foreach
			// in order to get the field name(key) and then we can pair it against our rules
			foreach($data as $fieldName=>$value) {
				// Check this field is mentioned in our rules and is a remote field
				if(property_exists($this->rules, $fieldName) && property_exists($this->rules->$fieldName, "remote")) {
					// Find the remote method for this field as defined in our rules
					$remoteMethod = $this->rules->$fieldName->remote->data->remoteMethod;
					// Check that this method exists
					if(array_key_exists($remoteMethod, $this->remote_methods)) {
						// Fire the method and return the result as json
						if($this->remote_methods[$remoteMethod]($data[$fieldName])) {
							// All good.
							$response = true;
						} else {
							// Find the error message for this method and return that
							$response = $this->remote_method_messages[$remoteMethod];
						}
					}
				}
			}
			// End.
			echo json_encode($response);
			exit();
		} // else this is not a remote request, carry on.
	}

	/**
	 * Validates form data according to rules
	 * @param data key/value array of field names to field values, eg $_POST.
	 *
	 * @return bool, true if all rules are satisfied
	 * on fail populates errors, publicly accessible via ->getErrors();
	 */
	public function validate($data) {
	
		if(is_null($this->rules)) {
			// TODO: throw an error
			$this->valid = false;
			return false;
		}
		
		$this->errors = array();
		// assume valid until we fail
		$this->valid = true;
		// save data (required for equalTo)
		$this->data = $data;
		
		// loop through each rule:
		foreach($this->rules as $fieldName=>$fieldRules) {

			// set missing fields as empty
			if(!isset($data[$fieldName])) {
				$data[$fieldName] = "";
			}
		
			// don't further validate empty fields - they're either required (Fail) or optional (OK)
			// don't use `empty` here; a value of "0" should not be considered empty.
			if($data[$fieldName]=="") {
				if(!$this->isOptional($fieldName)) {
					$this->addError('required','',$fieldName, '');
					continue;
				} else {
					// empty but optional; allow
					continue;
				}
			}

			// validate each rule for this field
			// there's a bit of redundancy here in that we'll check required again, but it's such a slim function that testing for $ruleName=="required" and continue-ing if so is going to be about as slow as just calling the damn function. In fact probably slower as we'd be doing that check on every loop!
			foreach($fieldRules as $ruleName=>$ruleParams) {
				// normalise case, in case eg {"dateiso": true} is specified in rules instead of dateISO.
				// (this is a bug in jquery.validator - if the case is wrong or a rule is specified for which a method doesn't exist, it just submits the form! See http://plugins.jquery.com/node/16143 for a related issue)
				$ruleName = strtolower(trim($ruleName));
				if(method_exists($this, $ruleName)) {
					// We have a validation method for this rule, call it:
					if(!$this->$ruleName($data[$fieldName], $ruleParams)) {
						$this->addError($ruleName, $ruleParams, $fieldName, $data[$fieldName]);
					}
				} // else there's something in the rules that we can't deal with - just allow it.
			}
		}
		
		// no need to keep this in memory any more.
		$this->data = false;
		
		return $this->valid;
	}
	
	/**
	 * @return errors
	 */
	public function getErrors() {
		return $this->errors;
	}

	public function getJQVCompatibleErrors() {
		$compatible_errors = array();
		foreach($this->errors as $field=>$errors) {
			$compatible_errors[$field] = $errors[0];
		}
		return json_encode($compatible_errors);
	}
	
	public function getErrorForField($fieldName) {
		if(array_key_exists($fieldName,$this->errors)) {
			return $this->getLabelForError($fieldName, $this->errors[$fieldName][0]);
		} else {
			return '';
		}
	}
	
	private function getLabelForError($fieldName, $errorMessage) {
		return '<label generated="true" for="'.$fieldName.'" class="error">'.$errorMessage.'</label>';
	}
	
	/**
	 * @return true if this field not required
	 */
	private function isOptional($fieldName) {
		return !(isset($this->rules->$fieldName->required) && $this->rules->$fieldName->required==true);
	}
	
	/**
	 * Adds an error message to the errors array
	 * Sets validity to false
	 *
	 * @param ruleName string, name of rule eg "rangelength"
	 * @param ruleParams mixed, parameters for rule, eg array(6,20)
	 * @param fieldName string, name of field, eg "Password"
	 * @param fieldValue mixed, value of field, eg "hunter2"
	 */
	private function addError($ruleName, $ruleParams, $fieldName, $fieldVal) {
		if(!isset($this->errors[$fieldName])) {
			$this->errors[$fieldName] = array();
		}

		if($ruleName == 'remote') {
			$errorMessage = $this->remote_method_messages[$ruleParams->data->remoteMethod];
		} else {
			$errorMessage = self::$messages[strtoupper($ruleName)];
			// replace parameter placeholders for this message:
			// i.e. replace "Please enter a value between {0} and {1}." with "Please enter a value between 7 and 9."
			if(!is_array($ruleParams)) {
				$ruleParams = array($ruleParams);
			}
			foreach($ruleParams as $k=>$value) {
				$errorMessage = str_replace('{'.(int)$k.'}',htmlspecialchars($value),$errorMessage);
			}			
		}
		// assign message
		$this->errors[$fieldName][] = $errorMessage;
		
		// we're adding errors, so this form is not valid
		$this->valid = false;
	}
		
	/************************
	*						*
	*	METHODS				*
	*						*
	*************************/
	// a nested "methods" class would be nice here, but PHP doesn't support it.

	/***********************************************
		FULL SUPPORT - identical to jquery-validate
	/***********************************************/
	protected function minlength($value, $params=0) {
		return strlen($value)>=$params;
	}
	protected function maxlength($value, $params=0) {
		return strlen($value)<=$params;
	}
	protected function rangelength($value, $params) {
		if(empty($params)) {
			$params = array(0,0);
		}
		$len = strlen($value);
		return $len>=$params[0] && $len<=$params[1];
	}
	protected function min($value, $params) {
		return $this->number($value) && $value>=$params;
	}
	protected function max($value, $params) {
		return $this->number($value) && $value<=$params;
	}
	protected function range($value, $params) {
		if(empty($params)) {
			$params = array(0,0);
		}
		return $this->number($value) && $value>=$params[0] && $value<=$params[1];
	}
	protected function number($value, $params=null) {
		return preg_match('/^-?(?:\d+|\d{1,3}(?:,\d{3})+)(?:\.\d+)?$/',$value);
	}
	protected function digits($value, $params=null) {
		return preg_match('/^[0-9]+$/',$value);
	}
	protected function dateISO($value, $params=null) {
		return preg_match('/^\d{4}[\/-]\d{1,2}[\/-]\d{1,2}$/',$value);
	}
	protected function extension($value, $params) {
		if(is_string($params)) {
			$params = str_replace(',','|',$params);
		} else {
			$params = 'png|jpe?g|gif';
		}
		return preg_match('/.('.$params.')$/i',$value);
	}
	protected function creditcard($value, $params=null) {
	
		// remove dashes
		$value = str_replace('-','',$value);
		
		if(!$this->digits($value)) {
			return false;
		} else {
			$nCheck = 0;
			$nDigit = 0;
			$bEven = false;

			for($i=strlen($value)-1 ; $i>=0 ; $i--) {
				$nDigit = (int)$value{$i};
				if($bEven) {
					if (($nDigit *= 2) > 9) {
						$nDigit -= 9;
					}
				}
				$nCheck += $nDigit;
				$bEven = !$bEven;
			}

			return ($nCheck % 10) == 0;
		}
	}
	/**************************
		PARTIAL SUPPORT
	**************************/
	protected function required($value, $params=null) {
		// Partial in that: required( dependency-expression ) and (callback) are not supported
		// simple "required" is fully supported though
		// Fix status: Can not fix.

		return $value!=""; // don't use empty; "0" is a valid value for a required field.
	}
	protected function url($value, $params=null) {
		// Partial in that: this uses an older URL regex
		// Fix status: I just need to port the new regex
		// new (1.10.0) regex: ///^(https?|ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i

		$regex = "^(https?|ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(\%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(([a-z]|\d|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])*([a-z]|\d|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])))\.)+(([a-z]|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(([a-z]|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])*([a-z]|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(\%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(\%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(\%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\x{E000}-\x{F8FF}]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(\%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$";
		// had a job converting this regex over, but with help from http://www.regular-expressions.info/unicode.html I managed it.
		// notes: \uFFFF is converted to \x{FFFF} with a 'u' modifier at the end.
		return preg_match('/'.$regex.'/iu', $value);
	}
	protected function remote($value, $params=null) {
		// Partial in that: only supports a specific remote format
		// Fix status: Will not fix; this is the only way it can work
		if(array_key_exists($params->data->remoteMethod, $this->remote_methods)) {
			// Call the validation method
			return $this->remote_methods[$params->data->remoteMethod]($value);
		} else {
			return false;
		}
	}

	protected function email($value, $params=null) {
	// Partial in that: not using the same email regex as it borks and I'm too lazy to port it.
	// Fix status: Will attempt to port regex in a future version.
/*
email regex in 1.10.0:
/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))$/i
*/
		return preg_match('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i',$value);
	}
	protected function date($value, $params=null) {
	// Partial in that: not using same date validation as jquery/validator
	// Fix status: fix not planned
	// jquery/validator uses JS's native Date() to validate, and I don't know exactly what that accepts ( https://developer.mozilla.org/en/JavaScript/Reference/global_objects/date )
		// checking strlen to avoid single chars being interpreted as timezone labels (because strtotime('f')!==false)
		return (strlen($value)>1 && strtotime($value)!==false);
	}

	protected function equalTo($value, $params) {
	// Partial in that: only supports ID, and then only if ID==name.
	// Fix status: cannot be fixed without a whole lot of pain (eg shipping with http://code.google.com/p/phpquery/ or something)
	
		// strip CSS ID selector to get field name
		$params = str_replace('#','',$params);
		// return true if that field exists and value is equal to it
		return isset($this->data[$params]) && $value==$this->data[$params];
	}
	/**************************
		UNSUPPORTED AS YET
	**************************/
	protected function accept($value, $params) {
		// JQV accept now works on mime type.
		// We can port that code, but not as a priority.
	}
}