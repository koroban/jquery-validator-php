<?php

/*

A PHP class which implements the same ruleset as jquery.validator
Allows us to specify validation rules in a .json file on the server
and have those rules implemented in javascript by jquery.validator
and in PHP by this class.

*/

class AdditionalValidator extends Validator {
	public function __construct($rules=null) {
		parent::__construct($rules);
	
		self::$messages = array_merge(self::$messages,array(
			'MAXWORDS'             => "Please enter {0} words or less.",
			'MINWORDS'             => "Please enter at least {0} words.",
			'RANGEWORDS'           => "Please enter between {0} and {1} words.",
			'LETTERSWITHBASICPUNC' => "Letters or punctuation only please.",
			'ALPHANUMERIC'         => "Letters, numbers, and underscores only please.",
			'LETTERSONLY'          => "Letters only please.",
			'NOWHITESPACE'         => "No white space please.",
			'INTEGER'              => "A positive or negative non-decimal number please.",
			'TIME24H'              => "Please enter a valid time, between 00:00 and 23:59",
			'TIME12H'              => "Please enter a valid time, between 00:00 am and 12:00 pm",
			'PHONEUS'              => "Please specify a valid phone number",
			'PHONEUK'              => "Please specify a valid phone number",
			'MOBILEUK'             => "Please specify a valid mobile number",
			'POSTCODE'             => "Please specify a valid postcode",
			'STRIPPEDMINLENGTH'    => "Please enter at least {0} characters"
		));
	}

	/*
	Not supported:
	ziprange
	zipcodeUS
	vinUS
	dateITA
	dateNL
	phonesUK
	email2
	url2
	creditcardtypes
	ipv4
	ipv6
	pattern
	require_from_group
	skip_or_fill_minimum
	extension
	*/

	protected function maxWords($value, $params=null) {
		$value = strip_tags($value);
		return preg_match_all("/\b\w+\b/", $value, $matches) <= (int)$params;
	}
	protected function minWords($value, $params=null) {
		$value = strip_tags($value);
		return preg_match_all("/\b\w+\b/", $value, $matches) >= (int)$params;
	}
	protected function rangeWords($value, $params) {
	    $value = strip_tags($value);
	     if (preg_match_all("/\b\w+\b/", $value, $matches)) {
	        $value = preg_split('/ +/', $value);
	        return count($value) >= $params[0] && count($value) <= $params[1];
	    }
	}
	protected function lettersonly($value) {
	    return preg_match_all("/^[a-z]+$/i", $value, $matches);
	}

	protected function letterswithbasicpunc($value) {
	    return preg_match_all("/^[a-z\-.,()'\"\s]+$/i", $value, $matches);
	}

	protected function alphanumeric($value) {
	    return preg_match_all("/^\w+$/i", $value, $matches);
	}

	protected function nowhitespace($value) {
	    return preg_match_all("/^\S+$/i", $value, $matches, $matches);
	}

	protected function integer($value) {
	    return preg_match_all("/^-?\d+$/", $value, $matches);
	}

	protected function time24h($value) {
	    return preg_match_all("/^([0-1]\d|2[0-3]):([0-5]\d)$/", $value, $matches);
	}

	protected function time12h($value) {
	    return preg_match_all("/^((0?[1-9]|1[012])(:[0-5]\d){0,2}(\ [AP]M))$/i", $value, $matches);
	}

	protected function phoneUS($phone_number) {
	    $phone_number = preg_replace("/\\s/", "", $phone_number);
	    return strlen($phone_number) > 9 && preg_match_all("/^(\+?1-?)?(\([2-9]\d{2}\)|[2-9]\d{2})-?[2-9]\d{2}-?\d{4}$/", $phone_number, $matches);
	}

	protected function phoneUK($phone_number) {
	    $phone_number = preg_replace("/\(|\\)|\\s+|-/", "", $phone_number);
	    return strlen($phone_number) > 9 && preg_match_all("/^(?:(?:(?:00\s?|\+)44\s?)|(?:\(?0))(?:(?:\d{5}\)?\s?\d{4,5})|(?:\d{4}\)?\s?(?:\d{5}|\d{3}\s?\d{3}))|(?:\d{3}\)?\s?\d{3}\s?\d{3,4})|(?:\d{2}\)?\s?\d{4}\s?\d{4}))$/", $phone_number, $matches);
	}

	protected function mobileUK($phone_number) {
	    $phone_number = preg_replace("/\\s+|-/", "", $phone_number);
	    return strlen($phone_number) > 9 && preg_match_all("/^(?:(?:(?:00\s?|\+)44\s?|0)7(?:[45789]\d{2}|624)\s?\d{3}\s?\d{3})$/", $phone_number, $matches);
	}

	//Matches UK landline + mobile, accepting only 01-3 for landline or 07 for mobile to exclude many premium numbers

	// On the above three UK functions, do the following server side processing:
	//  Compare with ^((?:00\s?|\+)(44)\s?)?\(?0?(?:\)\s?)?([1-9]\d{1,4}\)?[\d\s]+)
	//  Extract $2 and set $prefix to '+44<space>' if $2 is '44' otherwise set $prefix to '0'
	//  Extract $3 and remove spaces and parentheses. Phone number is combined $2 and $3.
	// A number of very detailed GB telephone number RegEx patterns can also be found at:
	// http://www.aa-asterisk.org.uk/index.php/Regular_Expressions_for_Validating_and_Formatting_UK_Telephone_Numbers

	protected function postcode (&$postcode) {
	 
	  // Permitted letters depend upon their position in the postcode.
	  $alpha1 = "[abcdefghijklmnoprstuwyz]";                          // Character 1
	  $alpha2 = "[abcdefghklmnopqrstuvwxy]";                          // Character 2
	  $alpha3 = "[abcdefghjkstuw]";                                   // Character 3
	  $alpha4 = "[abehmnprvwxy]";                                     // Character 4
	  $alpha5 = "[abdefghjlnpqrstuwxyz]";                             // Character 5
	 
	  // Expression for postcodes: AN NAA, ANN NAA, AAN NAA, and AANN NAA
	  $pcexp[0] = '^('.$alpha1.'{1}'.$alpha2.'{0,1}[0-9]{1,2})([0-9]{1}'.$alpha5.'{2})$';
	 
	  // Expression for postcodes: ANA NAA
	  $pcexp[1] =  '^('.$alpha1.'{1}[0-9]{1}'.$alpha3.'{1})([0-9]{1}'.$alpha5.'{2})$';
	 
	  // Expression for postcodes: AANA NAA
	  $pcexp[2] =  '^('.$alpha1.'{1}'.$alpha2.'[0-9]{1}'.$alpha4.')([0-9]{1}'.$alpha5.'{2})$';
	 
	  // Exception for the special postcode GIR 0AA
	  $pcexp[3] =  '^(gir)(0aa)$';
	 
	  // Standard BFPO numbers
	  $pcexp[4] = '^(bfpo)([0-9]{1,4})$';
	 
	  // c/o BFPO numbers
	  $pcexp[5] = '^(bfpo)(c\/o[0-9]{1,3})$';
	 
	  // Load up the string to check, converting into lowercase and removing spaces
	  $postcode = strtolower($postcode);
	  $postcode = str_replace (' ', '', $postcode);
	 
	  // Assume we are not going to find a valid postcode
	  $valid = false;
	 
	  // Check the string against the six types of postcodes
	  foreach ($pcexp as $regexp) {
	 
	    if (preg_match('/'.$regexp.'/',$postcode, $matches)) {
	 
	      // Load new postcode back into the form element  
	      $postcode = strtoupper ($matches[1] . ' ' . $matches [2]);
	 
	      // Take account of the special BFPO c/o format
	      $postcode = preg_replace ('/C\/O/', 'c/o ', $postcode);
	 
	      // Remember that we have found that the code is valid and break from loop
	      $valid = true;
	      break;
	    }
	  }
	 
	  // Return with the reformatted valid postcode in uppercase if the postcode was 
	  // valid
	  return $valid;
	}

	protected function strippedminlength($value, $params) {
	    return strlen(strip_tags($value)) >= $params;
	}

}