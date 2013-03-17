<?php
/**

Validator.inc (class for validation form submission data)

This class provides a standardized library for validating form input data from
a user based on validation rules set up in an application. All applications 
that accept user input should define their validation rules and check all
submitted values against that list by using this library.

An object that wants to use this validator should create an array of validation
rules (gcForm->setValidationRule() handles this). Objects that extend the 
gcForm class will automatically test all user input against their validation
rules when gcForm->importFromPost() is called. Other objects can also use this
library, but they will need to implement their own methods for creating 
validation rules.

The validator object is passed a collection of gcValidationRule objects. Then
the gcValidator->validate() method is called and passed an associative array
of data to test against the rules (usually $_POST or $_GET, but potentially
any associative array). The keys of the associative array should match the
propName values of the validation rules.
**/

class gcValidator
{
   private $validationRules;
   private $validNamePattern;
   private $validPhonePattern;
   private $validEmailPattern;
   public $msgCenter;
   public $failedTestPropertyNames;
   
   function gcValidator($validationRules)
   {
      $this->validEmailPattern = "/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i";
      $this->validNamePattern = "/[^a-zA-Z0-9!'\.\-_]/";
      $this->validPhonePattern = "/^[2-9]\d{2}-\d{3}-\d{4}$/";
      $this->validationRules = $validationRules;
      $this->failedTestPropertyNames = array();
      $this->msgCenter =& gcMessageCenter::Singleton();
   }
   
   
	/**
	:Function: addErrorMsg()
	
	:Description:
   Takes a standard error message as its only argument. If the current rule
   being validated has a customized error message, then this function will use
   that customized error message. If there is no customized error message, then
   this function will use the standard error message that is passed in. Which
   ever message is used will be added to the message center and displayed to 
   the user as an error.
   
	:Parameters:
   string $msg - a standard error message. See the test*() functions in this
      class for examples of use.
   
	:Return Value:
   None.
   
	:Notes:
	None.
	**/
   function addErrorMsg($msg)
   {
      if (is_a($this->currentRule, "gcValidationRule"))
      {
         $customErrorMessage = $this->currentRule->getCustomErrorMessage();
         if ($customErrorMessage)
         {
            $this->msgCenter->addError($customErrorMessage);
         }
         else
         {
            $this->msgCenter->addError($msg);
         }
      }
   }
	
   
	/**
	:Function: validate()
	
	:Description:
	Loops through all of the validation rules and tests each one. If one rule
   fails, this function returns false, indicating that validation has failed.
   For every rule that fails, an error message will be recorded in the message
   center object.
   
	:Parameters:
	hash $data - the data you want to validate. Keys in the hash should 
      correspond to keys of the validationRules hash, which should correspond to
      the value in the formProps array of the object being validated.
   
	:Return Value:
	boolean - true if all rules pass, false otherwise.
   
	:Notes:
	None.
	**/
   function validate($data)
   {
      $allRulesPassed = true;
         
      $allPropNames = array_keys($this->validationRules);
      foreach($allPropNames as $propName)
      {
         $valueToTest = IsSet($data[$propName]) ? $data[$propName] : $this->validationRules[$propName][0]->getValueToTest();
         $passed = $this->validateProperty($propName, $valueToTest);
      
         if (!$passed)
         {
            $allRulesPassed = false;
         }
      }
      
      $this->failedTestPropertyNames = array_unique($this->failedTestPropertyNames);
      return $allRulesPassed;
   }
   
	
	/**
	:Function: validateProperty()
	
	:Description:
   Takes a submitted value for a property and checks all of the validation rules
   set for that property.
   
	:Parameters:
   string $propName - the name of the property to check.
   mixed $value - the submitted/proposed new value to check against the 
      validation rules for this property.
      
	:Return Value:
	boolean - true if value passes all rules, false otherwise.
   
	:Notes:
	None.
	**/
   function validateProperty($propName, $valueToTest)
   {
      if (!IsSet($this->validationRules[$propName]))
      {
         return true;
      }
      
      if (count($this->validationRules[$propName]) == 0)
      {
         return true;
      }
      
      $allRulesPassed = true;
      foreach ($this->validationRules[$propName] as $rule)
      {
         $this->currentRule = $rule;
         $prettyPropName = $rule->getPrettyPropName();
         $ruleType = $rule->getRuleType();
         $criterium = $rule->getCriterium();
         $passed = true;
         
         switch ($ruleType)
         {
            case "minVal":
               $passed = $this->testMinValue($prettyPropName, $valueToTest, $criterium);
            break;
            
            case "maxVal":
               $passed = $this->testMaxValue($prettyPropName, $valueToTest, $criterium);
            break;
            
            case "minLength":
               $passed = $this->testMinSize($prettyPropName, $valueToTest, $criterium);
            break;
            
            case "notEmpty":
               $passed = $this->testMinSize($prettyPropName, $valueToTest, 1);
            break;
            
            case "maxLength":
               $passed = $this->testMaxSize($prettyPropName, $valueToTest, $criterium);
            break;
            
            case "numeric":
               $passed = $this->testNumeric($prettyPropName, $valueToTest);
            break;
            
            case "pattern":
               $passed = $this->testPattern($prettyPropName, $valueToTest, $criterium);
            break;
            
            case "email":
               $passed = $this->testPattern($prettyPropName, $valueToTest, $this->validEmailPattern);
            break;
            
            case "phone":
               $passed = $this->testPattern($prettyPropName, $valueToTest, $this->validPhonePattern);
            break;
            
            case "anti-pattern":
               $passed = $this->testAntiPattern($prettyPropName, $valueToTest, $criterium);
            break;
            
            case "enum":
               $passed = $this->testEnum($prettyPropName, $valueToTest, $criterium);
            break;
            
            default:
               $this->msgCenter->addDebug("Could not test $propName. No '$ruleType' test has been defined!");
            break;
         }
         //print("<ul><li>propName: $propName</li> <li>ruleType: $ruleType</li> <li>criterium: $criterium</li> <li>valueToTest: $valueToTest</li><li>passed: $passed</li></ul>");
         if (!$passed)
         {
            $allRulesPassed = false;
            $this->failedTestPropertyNames[] = $propName;
         }
      }
      
      return $allRulesPassed;
   }
   
	/**
	:Function: testMinValue()
	
	:Description:
	Tests to see if a numeric value is greater or equal to the passed in value.
   NOTE: if the valueToTest isn't numeric, this function will return false.
   
	:Parameters:
   string $prettyPropName - the human-friendly name of the property being tested.
	string $valueToTest - the value to test.
   int $minValue - the minimum value allowed for the valueToTest.
   
	:Return Value:
	boolean - true if value passes criterium, false otherwise.
   
	:Notes:
	None.
	**/
   function testMinValue($prettyPropName, $valueToTest, $minValue)
   {
      if ($this->testNumeric($prettyPropName, $valueToTest))
      {
         if ($valueToTest < $minValue)
         {
            $errMsg = "$prettyPropName is set to $valueToTest, which is less than $minValue";
            $this->addErrorMsg($errMsg);
            return false;
         }
         return true;
      }
      else
      {
         return false;
      }
   }
   
   
	/**
	:Function: testMaxValue()
	
	:Description:
	Tests to see if a numeric value is less than or equal to the passed in value.
   NOTE: if the valueToTest isn't numeric, this function will return false.
   
	:Parameters:
   string $prettyPropName - the human-friendly name of the property being tested.
	string $valueToTest - the value to test.
   int $maxValue - the maximum value allowed for the valueToTest.
   
	:Return Value:
	boolean - true if value passes criterium, false otherwise.
   
	:Notes:
	None.
	**/
   function testMaxValue($prettyPropName, $valueToTest, $maxValue)
   {
      if ($this->testNumeric($prettyPropName, $valueToTest))
      {
         if ($valueToTest > $maxValue)
         {
            $errMsg = "$prettyPropName is set to $valueToTest, which is greater than $maxValue";
            $this->addErrorMsg($errMsg);
            return false;
         }
         return true;
      }
      else
      {
         return false;
      }
   }
	
   
	/**
	:Function: testMinSize()
	
	:Description:
	None.
   
	:Parameters:
   string $prettyPropName - the human-friendly name of the property being tested.
	string $valueToTest - the value to test.
   int $minSize - the minimum size allowed for the valueToTest.
   
	:Return Value:
	boolean - true if value passes criterium, false otherwise.
   
	:Notes:
	None.
	**/
   function testMinSize($prettyPropName, $valueToTest, $minSize)
   {
      $length = strlen($valueToTest);
      if ($length < intval($minSize))
      {
         $errMsg = "$prettyPropName is set to '$valueToTest', which is $length characters long, but must be at least $minSize characters long.";
         $this->addErrorMsg($errMsg);
         return false;
      }
      return true;
   }
   
	
	/**
	:Function: testMaxSize()
	
	:Description:
   Tests the submitted datum to see if its length is less than the max allowed
   size.
   
	:Parameters:
   string $prettyPropName - the human-friendly name of the property being tested.
	string $valueToTest - the value to test.
   int $maxSize - the maximum size allowed for the valueToTest.
   
	:Return Value:
	boolean - true if value passes criterium, false otherwise.
   
	:Notes:
	None.
	**/
   function testMaxSize($prettyPropName, $valueToTest, $minSize)
   {
      $length = strlen($valueToTest);
      if ($length > intval($minSize))
      {
         $errMsg = "$prettyPropName is set to '$valueToTest', which is $length characters long, but must be no more than $minSize characters long.";
         $this->addErrorMsg($errMsg);
         return false;
      }
      return true;
   }
   
	
	/**
	:Function: testNumeric()
	
	:Description:
	Returns true if the valueToTest is numeric, false otehrwise.
   
	:Parameters:
   string $prettyPropName - the human-friendly name of the property being tested.
	string $valueToTest - the value to test.
   
	:Return Value:
	boolean - true if value passes criterium, false otherwise.
   
	:Notes:
	None.
	**/
   function testNumeric($prettyPropName, $valueToTest)
   {
      if (!is_numeric($valueToTest))
      {
         $errMsg = "$prettyPropName is not a number, it's '$valueToTest'. $prettyPropName can only be numerical.";
         $this->addErrorMsg($errMsg);
         return false;
      }
      return true;
   }
   
	
	/**
	:Function: testPattern()
	
	:Description:
	Compares the valueToTest against a regular expression. The value passes 
   validation if it matches the regex.
   
	:Parameters:
   string $prettyPropName - the human-friendly name of the property being tested.
	string $valueToTest - the value to test.
   string $pattern - a regular expression
   
	:Return Value:
	boolean - true if value passes criterium, false otherwise.
   
	:Notes:
	None.
	**/
   function testPattern($prettyPropName, $valueToTest, $pattern)
   {
      if (!preg_match($pattern, $valueToTest))
      {
         $errMsg = "'$valueToTest' is an invalid format for $prettyPropName.";
         $this->addErrorMsg($errMsg);
         return false;
      }
      return true;
   }
   
	
	/**
	:Function: testAntiPattern()
	
	:Description:
	Compares valueToTest against a regular expression. If the value DOES NOT
   match the expression, then it passes the test.
   
	:Parameters:
   string $prettyPropName - the human-friendly name of the property being tested.
	string $valueToTest - the value to test.
   string $pattern - a regular expression
   
	:Return Value:
	boolean - true if value passes criterium, false otherwise.
   
	:Notes:
	None.
	**/
   function testAntiPattern($prettyPropName, $valueToTest, $pattern)
   {
      if (preg_match($pattern, $valueToTest))
      {
         $errMsg = "$prettyPropName contains illegal characters: '$valueToTest'.";
         $this->addErrorMsg($errMsg);
         return false;
      }
      return true;
   }
   
	
	/**
	:Function: testEnum
	
	:Description:
   Checks to see if the valueToTest is included in the passed in array of values.
   If the valueToTest is present in the array, the test is passed.
   
	:Parameters:
   string $prettyPropName - the human-friendly name of the property being tested.
	string $valueToTest - the value to test.
   array $allowedValues - the list of values allowed for this property.
   
	:Return Value:
	boolean - true if value passes criterium, false otherwise.
   
	:Notes:
	None.
	**/
   function testEnum($prettyPropName, $valueToTest, $allowedValues)
   {
      if (!in_array($valueToTest, $allowedValues))
      {
         $errMsg = "$valueToTest is an invalid selection for $prettyPropName.";
         $this->addErrorMsg($errMsg);
         return false;
      }
      return true;
   }
}


/**

**/
class gcValidationRule
{
   var $propName;
   var $ruleType;
   var $criterium;
   var $prettyPropName;
   var $valueToTest;
   var $customErrorMessage;
   
   function gcValidationRule($ruleType)
   {
      $this->setRuleType($ruleType);
      $this->customErrorMessage = false;
   }
   
	
	/**
	:Function: setCustomErrorMessage()
	
	:Description:
   Sets a custom error message for this rule. You can include "tags" in your
   custom error message for the name of the property being tested, the 
   criterium being tested against, and the tested value, and those tags will
   be replaced with their respective values by getCustomErrorMessage(). Here
   are the tags:
   
   [PROPNAME] = getPrettyPropValue()
   [CRITERIUM] = getCriterium()
   [TESTEDVALUE] = getValueToTest()
   
	:Parameters:
	string $msg - The custom error message for this rule. 
   
	:Return Value:
	None.
   
	:Notes:
	None.
	**/
   function setCustomErrorMessage($msg)
   {
      $this->customErrorMessage = $msg;
   }
   
	
	/**
	:Function: getCustomErrorMessage()
	
	:Description:
   Returns a customized error message for this rule. Usually, the validator
   object creates all the error messages in a standard way. If your error
   messages needs something the validator doesn't provide, you can enter it with
   setCustomErrorMessage(). getCustomErrorMessage() will return your customized
   error message and will do some simple substitution to enter the name of the
   property being checked, the value of the criterium that wasn't met, and the
   tested value itself.
      
	:Parameters:
   None.
   
	:Return Value:
	mixed - string if a custom error message has been set, false otherwise.
   
	:Notes:
	None.
	**/
   function getCustomErrorMessage()
   {
      $msg = $this->customErrorMessage;
      
      if (is_string($msg))
      {
         $msg = str_replace("[PROPNAME]", $this->getPrettyPropName(), $msg);
         $msg = str_replace("[CRITERIUM]", $this->getCriterium(), $msg);
         $msg = str_replace("[TESTEDVALUE]", $this->getValueToTest(), $msg);
      }
      else
      {
         $msg = false;
      }
      return $msg;
   }
   
	
	/**
	:Function: setValueToTest()
	
	:Description:
   Sets the value to tested by the validator. This probably a value submitted
      by a user from POST or GET, but could be from any source.
      
	:Parameters:
	string $valueToTest - the value to be tested. 
   
	:Return Value:
	None.
   
	:Notes:
	None.
	**/
   function setValueToTest($valueToTest)
   {
      $this->valueToTest = $valueToTest;
   }
   
	
	/**
	:Function: getValueToTest()
	
	:Description:
   Returns the value to be tested. The valueToBeTested is probably something
   user-submitted from POST or GET, but could be from any source.
   
	:Parameters:
   None.
   
	:Return Value:
	mixed - the value to be tested. 
   
	:Notes:
	None.
	**/
   function getValueToTest()
   {
      return $this->valueToTest;
   }
   
	
	/**
	:Function: setRuleType()
	
	:Description:
	Sets the rule type value. The rule type value determines what kind of test
   will be performed: maxsize, minsize, pattern, etc. Any rule type passed in
   to this method must have a handler defined in the gcValidator class.
   
	:Parameters:
	string $ruleType - the rule type value.
   
	:Return Value:
	None.
   
	:Notes:
	None.
	**/
   function setRuleType($ruleType)
   {
      $this->ruleType = $ruleType;
   }
   
	
	/**
	:Function: getRuleType()
	
	:Description:
	Returns the ruleType value. The ruleType value determines what kind of test
   needs to be performed for this rule (i.e. max size, pattern match, etc).
   
	:Parameters:
	None.
   
	:Return Value:
	string - the rule type value.
   
	:Notes:
	None.
	**/
   function getRuleType()
   {
      return $this->ruleType;
   }
   
	
	/**
	:Function: setPropName()
	
	:Description:
   Sets the name of the property in the objecting being validated that this
   rule applies to.
   
	:Parameters:
	string $propName - The name of the property the rule applies to.
   
	:Return Value:
	None.
   
	:Notes:
	None.
	**/
   function setPropName($propName)
   {
      $this->propName = $propName;
   }
   
	
	/**
	:Function: getPropName()
	
	:Description:
   Gets the name of the property being tested by this rule. This is a property
   name of the object being validated.
   
	:Parameters:
	None.
   
	:Return Value:
	string - The name of the property the rule applies to.
   
	:Notes:
	None.
	**/
   function getPropName()
   {
      return $this->propName;
   }
   
	
	/**
	:Function: getTestValue()
	
	:Description:
   Returns the value that this rule uses for its test. I.E. for a maxsize
   rule this function would return the max size, say 255. 
   
	:Parameters:
	None.
   
	:Return Value:
	string - the value that the valueToTest property should be 
      compared to/tested against. 
   
	:Notes:
	None.
	**/
   function getCriterium()
   {
      return $this->criterium;
   }
   
	
	/**
	:Function: setCriterium()
	
	:Description:
   Sets the value that the valueToTest property should be compared to and/or
   tested against. This is the criterium that the valueToTest must meet in
   order to pass validation.
   
	:Parameters:
	string $criterium - the value that the valueToTest property should be 
      compared to/tested against. 
   
	:Return Value:
	None.
   
	:Notes:
	None.
	**/
   function setCriterium($criterium)
   {
      $this->criterium = $criterium;
   }
   
	
	/**
	:Function: setPrettyPropName()
	
	:Description:
   Sets the prettyPropName value, which is the human-readable version of the
   property name. If this value isn't defined, getPrettyPropName() will just
   return the raw propName value. This value is used for human-friendly error
   message generation.
   
	:Parameters:
	string $prettyPropName - the prettyPropName value.
   
	:Return Value:
	None.
   
	:Notes:
	None.
	**/
   function setPrettyPropName($prettyPropName)
   {
      $this->prettyPropName = $prettyPropName;
   }
   
	
	/**
	:Function: getPrettyPropName()
	
	:Description:
   Returns the human-friendly version of the property name for display in 
   error messages to the user.
   
	:Parameters:
   None.
   
	:Return Value:
	string - the human-friendly version of the property name being tested.
   
	:Notes:
	None.
	**/
   function getPrettyPropName()
   {
      if (!empty($this->prettyPropName))
      {
         return $this->prettyPropName;
      }
      else
      {
         return $this->propName;
      }
   }
   
}
	
	/**
	:Function: 
	
	:Description:
	None.
   
	:Parameters:
	None.
   
	:Return Value:
	None.
   
	:Notes:
	None.
	**/
?>
