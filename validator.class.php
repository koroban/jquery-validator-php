<?php
/**
 * A PHP class which implements the same ruleset as jquery.validator
 * Allows us to specify validation rules in a .json file on the server
 * and have those rules implemented in javascript by jquery.validator
 * and in PHP by this class.
 *
 * @author Howard Yeend <puremango.co.uk@gmail.com>
 */
class Validator
{
    /**
     * list of error messages
     *
     * @var array
     */
    protected static $messages = array();

    /**
     * flag to check if last validation run is valid
     *
     * @var bool
     */
    private $valid = true;

    /**
     * list of errors
     *
     * @var array
     */
    protected $errors = array();

    /**
     * list of rules to validate
     *
     * @var array
     */
    private $rules;

    /**
     * class Constructor
     * set rules and messages
     *
     * @return Validator
     */
    public function __construct($rules = null)
    {
        $this->setRules($rules);
        self::_setMessages();
    }

    /**
     * static function to fill messages array
     * use i18n/gettext
     *
     * @return void
     */
    private static function _setMessages()
    {
        self::$messages = array(
            'REQUIRED' => _('This field is required.') ,
            'EMAIL' => _('Please enter a valid email address.') ,
            'URL' => _('Please enter a valid URL.') ,
            'DATE' => _('Please enter a valid date.') ,
            'DATEISO' => _('Please enter a valid date (YYYY-MM-DD).') ,
            'NUMBER' => _('Please enter a valid number.') ,
            'DIGITS' => _('Please enter only digits.') ,
            'CREDITCARD' => _('Please enter a valid credit card number.') ,
            'EQUALTO' => _('Please enter the same value again.') ,
            'ACCEPT' => _('Please enter a value with a valid extension.') ,
            'MINLENGTH' => _('Please enter at least {0} characters.') ,
            'MAXLENGTH' => _('Please enter no more than {0} characters.') ,
            'RANGE' => _('Please enter a value between {0} and {1}.') ,
            'MIN' => _('Please enter a value greater than or equal to {0}.') ,
            'MAX' => _('Please enter a value less than or equal to {0}.') ,
            'RANGELENGTH' => _('Please enter a value between {0} and {1} characters long.') ,
            'MAXWORDS' => _('Please enter {0} words or fewer.') ,
            'MINWORDS' => _('Please enter at least {0} words.') ,
            'RANGEWORDS' => _('Please enter between {0} and {1} words.') ,
            'LETTERSWITHBASICPUNC' => _('Letters or punctuation only please.') ,
            'ALPHANUMERIC' => _('Letters, numbers, and underscores only please.') ,
            'LETTERSONLY' => _('Letters only please.') ,
            'NOWHITESPACE' => _('No white space please.') ,
            'INTEGER' => _('A positive or negative non-decimal number please.') ,
            'TIME24H' => _('Please enter a valid time, between 00:00 and 23:59') ,
            'TIME12H' => _('Please enter a valid time, between 00:00 am and 12:00 pm') ,
            'PHONEUS' => _('Please specify a valid phone number') ,
            'PHONEUK' => _('Please specify a valid phone number') ,
            'MOBILEUK' => _('Please specify a valid mobile number') ,
            'POSTCODE' => _('Please specify a valid postcode') ,
            'STRIPPEDMINLENGTH' => _('Please enter at least {0} characters')
        );
    }

    /**
     * set rules by given parameter
     * @param $rules
     */
    public function setRules($rules)
    {
        if (is_object($rules)) {
            $this->rules = $rules;
        }
    }

    /**
     * return if last validation run was valid
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * Validates form data according to rules
     * on fail populates errors, publicly accessible via ->getErrors();
     *
     * @param data key/value array of field names to field values, eg $_POST.
     *
     * @return bool, true if all rules are satisfied
     */
    public function validate($data)
    {
        if (is_null($this->rules)) {
            throw new UnexpectedValueException('No rule defined.');
        }

        $this->errors = array();

        // assume valid until we fail
        $this->valid = true;

        // loop through each rule:
        foreach ($this->rules as $fieldName => $fieldRules) {
            // set missing fields as empty
            if (!isset($data[$fieldName])) {
                $data[$fieldName] = "";
            }

            // don't further validate empty fields - they're either required (Fail) or optional (OK)
            // don't use `empty` here; a value of "0" should not be considered empty.
            if ($data[$fieldName] == "") {
                if (!$this->isOptional($fieldName)) {
                    $this->addError('required', '', $fieldName, '');
                    continue;
                }

                // empty but optional; allow
                continue;
            }

            // validate each rule for this field
            // there's a bit of redundancy here in that we'll check required again,
            // but it's such a slim function that testing for $ruleName=="required"
            // and continue-ing if so is going to be about as slow as just calling
            // the damn function.
            // In fact probably slower as we'd be doing that check on every loop!
            foreach ($fieldRules as $ruleName => $ruleParams) {
                // normalise case, in case eg {"dateiso": true} is specified in
                // rules instead of dateISO.
                // (this is a bug in jquery.validator - if the case is wrong or a
                // rule is specified for which a method doesn't exist, it just
                // submits the form! See http://plugins.jquery.com/node/16143 for
                //  a related issue)
                if (!ValidatorMethods::call($ruleName, $data[$fieldName], $ruleParams, $data)) {
                    $this->addError($ruleName, $ruleParams, $fieldName, $data[$fieldName]);
                }
            }
        }

        return $this->valid;
    }

    /**
     * return list of errors
     *
     * @return array list of errors
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * get error for specified field
     *
     * @param string $fieldName name of field to get error of
     *
     * @return string error html formated label field
     */
    public function getErrorForField($fieldName)
    {
        if (array_key_exists($fieldName, $this->errors)) {
            return $this->getLabelForError($fieldName, $this->errors[$fieldName][0]);
        }
        return '';
    }

    /**
     * return html label for given error
     *
     * @param string $fieldName    name of field
     * @param string $errorMessage error string
     *
     * @return string html label
     */
    private function getLabelForError($fieldName, $errorMessage)
    {
        return '<label generated="true" for="' . $fieldName . '" class="error">' . $errorMessage . '</label>';
    }

    /**
     * check if given field name is optional
     *
     * @param string $fieldName field to validate
     *
     * @return bool true if this field not required
     */
    private function isOptional($fieldName)
    {
        return (!(isset($this->rules->$fieldName->required) &&
                 $this->rules->$fieldName->required == true));
    }

    /**
     * Adds an error message to the errors array
     * Sets validity to false
     *
     * @param ruleName string, name of rule eg "rangelength"
     * @param ruleParams mixed, parameters for rule, eg array(6,20)
     * @param fieldName string, name of field, eg "Password"
     * @param fieldValue mixed, value of field, eg "hunter2"
     *
     * @return void
     */
    private function addError($ruleName, $ruleParams, $fieldName, $fieldVal)
    {
        if (!isset($this->errors[$fieldName])) {
            $this->errors[$fieldName] = array();
        }

        $errorMessage = self::$messages[strtoupper($ruleName) ];
        // replace parameter placeholders for this message:
        // i.e. replace "Please enter a value between {0} and {1}." with
        // "Please enter a value between 7 and 9."

        if (!is_array($ruleParams)) {
            $ruleParams = array(
                $ruleParams
            );
        }

        foreach ($ruleParams as $k => $value) {
            $errorMessage = str_replace('{' . (int)$k . '}', htmlspecialchars($value) , $errorMessage);
        }
        // assign message
        $this->errors[$fieldName][] = $errorMessage;
        // we're adding errors, so this form is not valid
        $this->valid = false;
    }
}

/**
 * Validator Method Class
 * implements every validation function as static method
 *
 * FULL SUPPORT - identical to jquery-validate
 */
class ValidatorMethods {
    /**
     * static method to call local validation function
     *
     * @param string $ruleName name of rule to validate
     * @param string $value    value to validate
     * @param mixed  $params   parameter (if needed) for validation
     * @param array  $data     full data to validate
     *
     * @throws BadMethodCallException
     *
     * @return bool true if valid
     */
    public static function call($ruleName, $value, $params, $data) {
        $ruleName = strtolower(trim($ruleName));

        if (method_exists(get_class(), $ruleName)) {
            // We have a validation method for this rule, call it:
            return self::$ruleName($value, $params, $data);
        }
        throw new BadMethodCallException('Undefined validation ' . $ruleName . ' given.');
    }

    public static function minlength($value, $params = 0, $data = null)
    {
        return strlen($value) >= $params;
    }

    public static function maxlength($value, $params = 0, $data = null)
    {
        return strlen($value) <= $params;
    }

    public static function rangelength($value, $params, $data = null)
    {
        if (empty($params)) {
            $params = array(
                0,
                0
            );
        }
        $len = strlen($value);
        return $len >= $params[0] && $len <= $params[1];
    }

    public static function min($value, $params, $data = null)
    {
        return self::number($value) && $value >= $params;
    }

    public static function max($value, $params, $data = null)
    {
        return self::number($value) && $value <= $params;
    }

    public static function range($value, $params, $data = null)
    {
        if (empty($params)) {
            $params = array(
                0,
                0
            );
        }
        return self::number($value) &&
               $value >= $params[0] &&
               $value <= $params[1];
    }

    public static function number($value, $params = null, $data = null)
    {
        return preg_match('/^-?(?:\d+|\d{1,3}(?:,\d{3})+)(?:\.\d+)?$/', $value);
    }

    public static function digits($value, $params = null, $data = null)
    {
        return preg_match('/^[0-9]+$/', $value);
    }

    public static function dateiso($value, $params = null, $data = null)
    {
        return preg_match('/^\d{4}[\/-]\d{1,2}[\/-]\d{1,2}$/', $value);
    }

    public static function creditcard($value, $params = null, $data = null)
    {
        // remove dashes
        $value = str_replace('-', '', $value);
        if (!self::digits($value)) {
            return false;
        } else {
            $nCheck = 0;
            $nDigit = 0;
            $bEven = false;
            for ($i = strlen($value) - 1; $i >= 0; $i--) {
                $nDigit = (int)$value{$i};
                if ($bEven) {
                    if (($nDigit*= 2) > 9) {
                        $nDigit-= 9;
                    }
                }
                $nCheck+= $nDigit;
                $bEven = !$bEven;
            }
            return ($nCheck % 10) == 0;
        }
    }

    /**************************
    PARTIAL SUPPORT
    **************************/
    public static function required($value, $params = null, $data = null)
    {
        // Partial in that: required( dependency-expression ) and (callback) are not supported
        // simple "required" is fully supported though
        // Fix status: Can not fix.
        return $value != ""; // don't use empty; "0" is a valid value for a required field.
    }

    public static function url($value, $params = null, $data = null)
    {
        // Partial in that: this uses an older URL regex
        // Fix status: I just need to port the new regex
        // new (1.10.0) regex: ///^(https?|ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i
        $regex = "^(https?|ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(\%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(([a-z]|\d|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])*([a-z]|\d|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])))\.)+(([a-z]|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(([a-z]|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])*([a-z]|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(\%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(\%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(\%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\x{E000}-\x{F8FF}]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\x{00A0}-\x{D7FF}\x{F900}-\x{FDCF}\x{FDF0}-\x{FFEF}])|(\%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$";
        // had a job converting this regex over, but with help from http://www.regular-expressions.info/unicode.html I managed it.
        // notes: \uFFFF is converted to \x{FFFF} with a 'u' modifier at the end.
        return preg_match('/' . $regex . '/iu', $value);
    }

    public static function email($value, $params = null, $data = null)
    {
        return preg_match('/^.+@[a-z0-9\._-]+\.(xn--)?[a-z0-9]{2,}$/i', $value);
    }

    public static function date($value, $params = null, $data = null)
    {
        // Partial in that: not using same date validation as jquery/validator
        // Fix status: fix not planned
        // jquery/validator uses JS's native Date() to validate, and I don't know exactly what that accepts ( https://developer.mozilla.org/en/JavaScript/Reference/global_objects/date )
        // checking strlen to avoid single chars being interpreted as timezone labels (because strtotime('f')!==false)
        return (strlen($value) > 1 && strtotime($value) !== false);
    }

    public static function equalto($value, $params, $data = null)
    {
        // Partial in that: only supports ID, and then only if ID==name.
        // Fix status: cannot be fixed without a whole lot of pain
        // (eg shipping with http://code.google.com/p/phpquery/ or something)
        // strip CSS ID selector to get field name
        $params = str_replace('#', '', $params);
        // return true if that field exists and value is equal to it
        return isset($data[$params]) &&
               $value == $data[$params];
    }

    /**************************
    UNSUPPORTED AS YET
    **************************/
    public static function accept($value, $params, $data = null)
    {
        // JQV accept now works on mime type.
        // We can port that code, but not as a priority.

    }
    /**************************
    ADDITONAL METHODS
    **************************/
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
    */
    public static function extension($value, $params, $data = null)
    {
        if (is_string($params)) {
            $params = str_replace(',', '|', $params);
        } else {
            $params = 'png|jpe?g|gif';
        }
        return preg_match('/.(' . $params . ')$/i', $value);
    }

    public static function maxwords($value, $params = null, $data = null)
    {
        $value = strip_tags($value);
        return preg_match_all("/\b\w+\b/", $value, $matches) <= (int)$params;
    }

    public static function minwords($value, $params = null, $data = null)
    {
        $value = strip_tags($value);
        return preg_match_all("/\b\w+\b/", $value, $matches) >= (int)$params;
    }

    public static function rangewords($value, $params, $data = null)
    {
        $value = strip_tags($value);
        if (preg_match_all("/\b\w+\b/", $value, $matches)) {
            $value = preg_split('/ +/', $value);
            return count($value) >= $params[0] && count($value) <= $params[1];
        }
    }

    public static function lettersonly($value, $params, $data = null)
    {
        return preg_match("/^[a-z]+$/i", $value);
    }

    public static function letterswithbasicpunc($value, $params, $data = null)
    {
        return preg_match("/^[a-z\-.,()'\"\s]+$/i", $value);
    }

    public static function alphanumeric($value, $params, $data = null)
    {
        return preg_match("/^\w+$/i", $value);
    }

    public static function nowhitespace($value, $params, $data = null)
    {
        return preg_match("/^\S+$/i", $value);
    }

    public static function integer($value, $params, $data = null)
    {
        return preg_match("/^-?\d+$/", $value);
    }

    public static function time24h($value, $params, $data = null)
    {
        return preg_match("/^([0-1]\d|2[0-3]):([0-5]\d)$/", $value);
    }

    public static function time12h($value, $params, $data = null)
    {
        return preg_match("/^((0?[1-9]|1[012])(:[0-5]\d){0,2}(\ [AP]M))$/i", $value);
    }

    public static function phoneus($phone_number, $params, $data = null)
    {
        $phone_number = preg_replace("/\\s/", "", $phone_number);
        return strlen($phone_number) > 9 && preg_match("/^(\+?1-?)?(\([2-9]\d{2}\)|[2-9]\d{2})-?[2-9]\d{2}-?\d{4}$/", $phone_number);
    }

    public static function phoneuk($phone_number, $params, $data = null)
    {
        $phone_number = preg_replace("/\(|\\)|\\s+|-/", "", $phone_number);
        return strlen($phone_number) > 9 && preg_match("/^(?:(?:(?:00\s?|\+)44\s?)|(?:\(?0))(?:(?:\d{5}\)?\s?\d{4,5})|(?:\d{4}\)?\s?(?:\d{5}|\d{3}\s?\d{3}))|(?:\d{3}\)?\s?\d{3}\s?\d{3,4})|(?:\d{2}\)?\s?\d{4}\s?\d{4}))$/", $phone_number);
    }

    public static function mobileuk($phone_number, $params = null, $data = null)
    {
        $phone_number = preg_replace("/\\s+|-/", "", $phone_number);
        return strlen($phone_number) > 9 && preg_match("/^(?:(?:(?:00\s?|\+)44\s?|0)7(?:[45789]\d{2}|624)\s?\d{3}\s?\d{3})$/", $phone_number);
    }

    //Matches UK landline + mobile, accepting only 01-3 for landline or 07 for mobile to exclude many premium numbers
    // On the above three UK functions, do the following server side processing:
    //  Compare with ^((?:00\s?|\+)(44)\s?)?\(?0?(?:\)\s?)?([1-9]\d{1,4}\)?[\d\s]+)
    //  Extract $2 and set $prefix to '+44<space>' if $2 is '44' otherwise set $prefix to '0'
    //  Extract $3 and remove spaces and parentheses. Phone number is combined $2 and $3.
    // A number of very detailed GB telephone number RegEx patterns can also be found at:
    // http://www.aa-asterisk.org.uk/index.php/Regular_Expressions_for_Validating_and_Formatting_UK_Telephone_Numbers
    public static function postcode(&$postcode, $params = null, $data = null)
    {
        // Permitted letters depend upon their position in the postcode.
        $alpha1 = "[abcdefghijklmnoprstuwyz]"; // Character 1
        $alpha2 = "[abcdefghklmnopqrstuvwxy]"; // Character 2
        $alpha3 = "[abcdefghjkstuw]"; // Character 3
        $alpha4 = "[abehmnprvwxy]"; // Character 4
        $alpha5 = "[abdefghjlnpqrstuwxyz]"; // Character 5
        // Expression for postcodes: AN NAA, ANN NAA, AAN NAA, and AANN NAA
        $pcexp[0] = '^(' . $alpha1 . '{1}' . $alpha2 . '{0,1}[0-9]{1,2})([0-9]{1}' . $alpha5 . '{2})$';
        // Expression for postcodes: ANA NAA
        $pcexp[1] = '^(' . $alpha1 . '{1}[0-9]{1}' . $alpha3 . '{1})([0-9]{1}' . $alpha5 . '{2})$';
        // Expression for postcodes: AANA NAA
        $pcexp[2] = '^(' . $alpha1 . '{1}' . $alpha2 . '[0-9]{1}' . $alpha4 . ')([0-9]{1}' . $alpha5 . '{2})$';
        // Exception for the special postcode GIR 0AA
        $pcexp[3] = '^(gir)(0aa)$';
        // Standard BFPO numbers
        $pcexp[4] = '^(bfpo)([0-9]{1,4})$';
        // c/o BFPO numbers
        $pcexp[5] = '^(bfpo)(c\/o[0-9]{1,3})$';
        // Load up the string to check, converting into lowercase and removing spaces
        $postcode = strtolower($postcode);
        $postcode = str_replace(' ', '', $postcode);
        // Assume we are not going to find a valid postcode
        $valid = false;
        // Check the string against the six types of postcodes
        foreach ($pcexp as $regexp) {
            if (preg_match('/' . $regexp . '/', $postcode, $matches)) {
                // Load new postcode back into the form element
                $postcode = strtoupper($matches[1] . ' ' . $matches[2]);
                // Take account of the special BFPO c/o format
                $postcode = preg_replace('/C\/O/', 'c/o ', $postcode);
                // Remember that we have found that the code is valid and break from loop
                $valid = true;
                break;
            }
        }
        // Return with the reformatted valid postcode in uppercase if the postcode was
        // valid
        return $valid;
    }

    public static function strippedminlength($value, $params, $data = null)
    {
        return strlen(strip_tags($value)) >= $params;
    }
}
