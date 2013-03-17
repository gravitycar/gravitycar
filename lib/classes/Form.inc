<?php
/**

Form.inc (class for applications that build forms)

Applications that gather information from the user via an HTML form are Form
Applications, and should extend the Form class. This class contains methods
and properites designed to make rendering a form as painless as possible and
as consistent across various applications as possible. This class will define
methods for displaying particular properties of the class extending this class,
as well as for importing data to populate the classes properties from POST, GET
or other data sources like text files or mysql queries. It will also define
methods for validating submitted data. Specific applications are expected to
define methods for saving changes or deleting records.

**/
require_once("lib/classes/Application.inc");
require_once("lib/classes/Validator.inc");

abstract class gcForm extends gcApplication
{
   protected $formProps = array();
   protected $skipProps = array();
   protected $controlButtons = array();
   protected $validationPassed = true;
	protected $inputParams = array();
   protected $inputAttrs = array();
   protected $failedProps = array();
	protected $validate = true;
	protected $validationRules = array();
	protected $inputSuffix = "";
	protected $freezeForm = false;
	protected $inputsOnly = false;
   protected $action = "";
   protected $allowedTags = "";
   public $errorProps = array();
   public $acceptUploaded = false;
   
   
	/**
	:Function: setFormProps()
	
	:Description:
	Sets a list of properties to be displayed as a form. Most applications will
   interact with the user via a form (NOTE: Some applications will NOT, they
   will just make output and be done). The formProps property is the list of
   properties that will be rendered on the page for the user to see.
   
	:Parameters:
	mixed - This function takes one or more arguements. Each arg must either be
      a string or an array of strings, and each string must match up to a 
      property of the object being rendered.
	
	:Return Value:
	None.
   
	:Notes:
	None. 
	**/
   function setFormProps($propertyNames)
   {
      $args = func_get_args();
      
      foreach ($args as $arg)
      {
         if (is_string($arg))
         {
            $this->formProps[] = $arg;
         }
         else if (is_array($arg))
         {
            $this->formProps = array_merge($this->formProps, $arg);
         }
      }
   }
   
   
   /**
	:Function: resetFormProps()
	
	:Description:
   Clears any previous form property names and changes the list of property
   names to the passed in list.
   
	:Parameters:
	mixed - This function takes one or more arguements. Each arg must either be
      a string or an array of strings, and each string must match up to a 
      property of the object being rendered.
	
	:Return Value:
	None.
   
	:Notes:
	None. 
	**/
   function resetFormProps($propertyNames)
   {
      $this->formProps = array();
      $args = func_get_args();
      $this->setFormProps($args);
   }
   

	/**
	:Function: setSkipProps
	
	:Description:
	If there is a property you want to skip in the rendering process, you can 
   add that property name to "skipProps" and it will be skipped during the
   rendering process. It will also be skipped during the validation process.
   
	:Parameters:
	mixed - This function takes one or more arguements. Each arg must either be
      a string or an array of strings, and each string must match up to a 
      property of the object being rendered.
	
	:Return Value:
	None.
   
	:Notes:
	None. 
	**/
   function setSkipProps($propertyNames)
   {
      $args = func_get_args();
      
      foreach ($args as $arg)
      {
         if (is_string($arg))
         {
            $this->skipProps[] = $arg;
         }
         else if (is_array($arg))
         {
            $this->skipProps = array_merge($this->skipProps, $arg);
         }
      }
   }
   

	/**
	:Function: addFormProp()
	
	:Description:
   Adds a property name to the formProps array.
	
	:Parameters:
	string $propertyName - the name of the property to add to formProps.
	
	:Return Value:
	None.
   
	:Notes:
	None. 
	**/
   function addFormProp($propertyName)
   {
      $this->formProps[] = $propertyName;
   }

	/**
	:Function: addSkipProp()
	
	:Description:
   Adds a property name to the skipProps array.
	
	:Parameters:
	string $propertyName - the name of the property to add to skipProps.
	
	:Return Value:
	None.
   
	:Notes:
	None. 
	**/
   function addSkipProp($propertyName)
   {
      $this->skipProps[] = $propertyName;
   }

	/**
	:Function: removeSkipProp()
	
	:Description:
   Removes a property name from the list of properties to skip while building
   the form.
	
	:Parameters:
	string $propertyName - the name of the property to remove from skipProps.
	
	:Return Value:
	None.
   
	:Notes:
	None. 
	**/
   function removeSkipProp($propertyToRemove)
   {
      $newSkipProps = array();
      foreach ($this->skipProps as $propName)
      {
         if ($propName != $propertyToRemove)
         { 
            $newSkipProps[] = $propName;
         }
      }
      $this->skipProps = $newSkipProps;
   }
   

	/**
	:Function: getFormProps()
	
	:Description:
	Gets the list of property name to be displayed to the user.
   
	:Parameters:
	None.
	
	:Return Value:
	array - the list of form props.
   
	:Notes:
	None. 
	**/
   function getFormProps()
   {
      return $this->formProps;
   }
   

	/**
	:Function: getSkipProps()
	
	:Description:
	Returns the list of form properties to skip.
   
	:Parameters:
	None.
	
	:Return Value:
	array - the list of form props to skip.
   
	:Notes:
	None. 
	**/
   function getSkipProps()
   {
      return $this->skipProps;
   }
   

	/**
	:Function: setControlButtons()
	
	:Description:
	Sets a list of input IDs as "control" buttons, i.e. buttons like the submit
   button, the cancel button, the "back" button, etc. Buttons that cause the
   form to take an action rather than inputing new information. These inputs
   are typically of type "button" or type "submit" and they have special 
   rendering. Control buttons are always rendered at the end of the form, and
   are all on one line together.
   
	:Parameters:
	mixed - this function takes one or more arguments. Each arg must either be
      a string or an array of strings. The strings/arrays will all be 
      amalgamated into one array. These strings must all have attributes set
      in the instance of this object via setInputAttr().
	
	:Return Value:
	None.
   
	:Notes:
	None. 
	**/
   function setControlButtons($propertyNames)
   {
      $args = func_get_args();
      foreach ($args as $arg)
      {
         if (is_string($arg))
         {
            $this->controlButtons[] = $arg;
         }
         else if (is_array($arg))
         {
            $this->controlButtons[] = array_merge($this->controlButtons, $arg);
         }
      }
   }
   

	/**
	:Function: addControlButton()
	
	:Description:
	Adds a new control button to the array of control buttons (see setControlButtons
   for more info).
   
	:Parameters:
	string - the name of a control button. This string must have its attributes
      set in the instance of this object via setInputAttr().
	
	:Return Value:
	None.
   
	:Notes:
	None. 
	**/
   function addControlButton($inputID)
   {
      $this->controlButtons[] = $inputID;
   }
   

	/**
	:Function: getControlButtons()
	
	:Description:
	Returns the list of control buttons for this object.
   
	:Parameters:
	None.
	
	:Return Value:
	array - an array of input ids.
   
	:Notes:
	None. 
	**/
   function getControlButtons()
   {
      return $this->controlButtons;
   }
   

	/**
	:Function: isControlButton()
	
	:Description:
	Returns true if the passed in input ID is in the list of control buttons,
   false otherwise.
   
	:Parameters:
	String $id - an input id.
	
	:Return Value:
	boolean - true if the passed in ID is in the list of control buttons, false
      otherwise.
   
	:Notes:
	None. 
	**/
   function isControlButton($id)
   {
      return in_array($id, $this->controlButtons);
   }
   
   
	/**
	:Function: getFormPropValue()
	
	:Description:
   Returns the value of the passed in form property. This is typically a 
   property of this object. However, if setInputAttr() is called and passed
   a property name that is NOT a property of this object, and an attribute
   name of "value" and then some arbitrary value, that property will be
   displayed with the passed in value.
   
	:Parameters:
	string $propName - the name of the property you want the value for.
	
	:Return Value:
	mixed - the value of the passed in property.
   
	:Notes:
   Use this function to display values that are not properties of this object
   OR are properties of objected nested in this object.
	**/
   function getFormPropValue($propName)
   {
      $getMethodName = "get" . ucFirst($propName);
      if (IsSet($this->inputAttrs[$propName]['value']))
      {
         return $this->inputAttrs[$propName]['value'];
      }
      else if (property_exists($this, $propName))
      {
         return $this->$propName;
      }
      else if (method_exists($this, $getMethodName))
      {
         return call_user_func(array(&$this, $getMethodName));
      }
      else
      {
         return "";
      }
   }
   
	
	/**
	:Function: setInputAttr()
	
	:Description:
	Sets an attribute value for an input to be displayed on the page. The
   Attribute name may be any HTML legal attribute for a given tag.
   
	:Parameters:
	string $propName - the name of the property you're setting an attribute for.
   string $attName - the name of the attribute you're setting.
   string $attValue - the value you're setting the attribute to.
	
	:Return Value:
	None.
	
	:Notes:
	None.
	**/
	function setInputAttr($propName, $attName, $attVal)
	{
		$this->inputAttrs[$propName][$attName] = $attVal;
	}
   
	
	/**
	:Function: getInputAttr()
	
	:Description:
	Returns the attribute value for the named input field and attribute.
   
	:Parameters:
	string $propName - the name of the property you want an attribute value for.
   string $attrName - the name of the attribute you want.
	mixed $default - If the attribute value isn't set and you need a generic
      default value, you can pass that in here and get it back as the prop
      attribute value.
      
	:Return Value:
	Mixed - whatever the attribute value was set to (or the default).
	
	:Notes:
	None.
	**/
	function getInputAttr($propName, $attrName, $default="")
	{
      if (IsSet($this->inputAttrs[$propName][$attrName]))
      {
         return $this->inputAttrs[$propName][$attrName];
      }
      else
      {
         return $default;
      }
	}
   
	
	/**
	:Function: setInputParams()
	
	:Description:
	Sets parameter values for a given property, which will affect how that
   property is displayed in a form or other output.
   
   "Parameters" are a little different from "Attributes" for various object
   properties. An "Attribute" will be rendered as an attribute in the input
   tag. Attributes are always things that should be represented as tag
   attributes for an input tag. 
   
   A "Parameter" is something that cannot legally be represented by attributes
   of an input tag. For example, the options collection for a select tag is
   NOT an attribute, but instead nested tags. So to set a select tag's options
   to be an array of option tags returned by some function, you would set the
   'options' parameter of select tag to that array. Parameters are anything
   you can't represent as an attribute of an HTML tag.
   
   Any value for a parameter name is legal, but in order for that param to
   have an effect it will need to be handled in either buildForm() or in
   buildInput().
   
	:Parameters:
	string $propName - the name of the property you're setting an attribute for.
   string $paramName - the name of the parameter you're setting.
   mixed $paramValue - the value you're setting the parameter to. This can be
      a function name, which will be executed when the property is rendered.
	
	:Return Value:
	None.
	
	:Notes:
	None.
	**/
	function setInputParams($propName, $paramName, $paramValue)
	{
		$this->inputParams[$propName][$paramName] = $paramValue;
	}
   
	
	/**
	:Function: getInputParams()
	
	:Description:
	Returns the parameter value for the named input field and parameter.
   
	:Parameters:
	string $propName - the name of the property you want an attribute value for.
   string $paramName - the name of the param you want.
	mixed $default - If the param value isn't set and you need a generic
      default value, you can pass that in here and get it back as the prop
      param value.
      
	:Return Value:
	Mixed - whatever the param value was set to (or the default).
	
	:Notes:
	None.
	**/
	function getInputParams($propName, $paramName, $default="")
	{
      if (IsSet($this->inputParams[$propName][$paramName]))
      {
         return $this->inputParams[$propName][$paramName];
      }
      else
      {
         return $default;
      }
	}
   
		
	/**
	:Function: inputsOnly()
	
	:Description:
	If passed true, any calls to buildForm() will produce only the form input
	fields with no table markup. This is useful for situations where you need
	to output only one input field and you already have a table layout you
	want to drop it into. NOTE: you also don't get the pretty-property version
	of the form field labels, so if you want those you'll have to call 
	prettyProp() and it the name of the field you're displaying.
	
	:Parameters:
	boolean $state - true if you want the form in regular two-column layout,
		false if you just want the input fields.
	
	:Return Value:
	None.
	
	:Notes:
	None.
	**/
	function inputsOnly($state=true)
	{
		$this->inputsOnly = $state;
	}
   
	
	/**
	:Function: freezeForm()
	
	:Synopsis:
	If passed true, any calls to buildForm() will produce a form with no inputs,
	and everything will be displayed as plain text.
	
	:Description:
	
	:Parameters:
	boolean $state - true if you want the form in plain text, false if you want
		a form with inputs. Defaults to true.
	
	:Return Value:
	None.
	
	:Notes:
	None.
	**/
	function freezeForm($state=true)
	{
		$this->_freezeForm = $state;
	}// freezeForm()
	
   
   /**
   :Function: getFormTitle()
   
   :Description:
   Returns the value of the formTitle property.
   
   :Parameters:
   None
   
   :Return Value:
   string - the value of the formTitle property.
   
   :Notes:
   **/
   function getFormTitle()
   {
      return $this->formTitle;
   }
   
   
   /**
   :Function: setFormTitle()
   
   :Description:
   Sets the formTitle property and sets a corresponding input attribute to
   the value of the form title so it is displayed correctly when the form is
   rendered.
   
   :Parameters:
   string - the title of the form you want displayed, i.e. "Update User".
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function setFormTitle($title)
   {
      $this->formTitle = $title;
      $this->setInputAttr("title", "value", $this->getFormTitle());
   }
   
	
	/**
	:Function: buildForm()
	
	:Description:
	Loops through all the properties listed in the formProps array (and skips
   any props in the skipProps array) and produces an HTML table with the 
   various input fields and labels specified in the object.
   
	:Parameters:
	None.
	
	:Return Value:
	gcFormTag - a reference to the form object.
	
	:Notes:
	None.
	**/
	function &buildForm()
   {
      $this->table = $this->renderer->createTableTag();
      $this->table->setAttribute("class", "formTable");
      $this->form = $this->renderer->createFormTag();
      if ($this->acceptUploads)
      {
         $this->form->setAttribute("enctype", "multipart/form-data");
      }
      $props = $this->getFormProps();
      $skip = $this->getSkipProps();
      foreach ($props as $propName)
      {
         if (in_array($propName, $this->skipProps))
         {
             continue;
         }
         
         $propValue = $this->getFormPropValue($propName); 
         
         // special handling for the table heading/title
         if ($this->getInputAttr($propName, "type") == "heading")
         {
            $headingRow = $this->table->addRow();
            $headingRow->isHeader = true;
            $heading = $this->getFormTitle() ? $this->getFormTitle() : $this->page->getPageTitle();
            $headingCell = $headingRow->addCell($heading);
            $headingCell->setAttributes(array('align'=>'center', 'colSpan'=>'2'));
            continue;
         }
         
         // special handling for submit buttons is in by buildControlButtons()
         if ($this->isControlButton($propName))
         {
            continue;
         }
         
         // special handling for hidden inputs.
         if ($this->getInputAttr($propName, "type", "") == "hidden")
         {
            $inputField = $this->buildInput($propName, $propValue);
            $this->form->addContents($inputField);
            continue;
         }
         
         $row = $this->table->addRow(array('valign'=>'top'));
         $labelCell = $this->buildInputLabelCell($propName);
         $inputField = $this->buildInput($propName, $propValue);
         $valueCell = $this->renderer->createTDTag($inputField);
         $row->addCell($labelCell);
         $row->addCell($valueCell);
      }
      
      $this->buildControlButtons();
      $this->form->addContents($this->table);
      return $this->form;
   }
   
   
   /**
   :Function: buildInputLabelCell()
   
   :Description:
   Builds the table cell with a label for a form input field. Marks the field
   as "required" if there is a "not empty" validation rule for that field.
   
   :Parameters:
   None
   
   :Return Value:
   gcTDTag - a table-row tag, which contains the nested table with the buttons.
   
   :Notes:
   **/
   function &buildInputLabelCell($propName)
   {
      if (in_array($propName, $this->failedProps))
      {
         $labelCellAttributes = array('class'=>'formErrorLabel');
      }
      else
      {
         $labelCellAttributes = array('class'=>'formLabel');
      }
      
      $propLabel = $this->getPrettyPropName($propName) . ":&nbsp;";
      
      if (
            $this->hasValidationRule($propName, "notEmpty") ||
            $this->hasValidationRule($propName, "pattern") ||
            $this->hasValidationRule($propName, "email") ||
            $this->hasValidationRule($propName, "enum") ||
            $this->hasValidationRule($propName, "minLength")
         )
      {
         $requiredSpan = $this->renderer->createSpanTag("*", array("class"=>"requiredField", "title"=>"Required Field"));
      }
      else
      {
         $requiredSpan = "";
      }
      
      $labelCell = $this->renderer->createTDTag($requiredSpan, $labelCellAttributes);
      $labelCell->addContents($propLabel);
      return $labelCell;
   }
   
   
   /**
   :Function: buildControlButtons()
   
   :Description:
   Builds a nested table with all of the control buttons for this form, in the
   order they were added.
   
   :Parameters:
   None
   
   :Return Value:
   gcTRTag - a table-row tag, which contains the nested table with the buttons.
   
   :Notes:
   **/
   function buildControlButtons()
   {
      $row = $this->table->addRow(array('valign'=>'top', 'id'=>'controlButtons'));
      $cellAttrs = array('align'=>'center', 'colSpan'=>'2');
      
      $controlTable = $this->renderer->createTableTag();
      $controlTable->setTableAttributes();
      $cell = $this->renderer->createTDTag($controlTable, $cellAttrs);
      $controlRow = $controlTable->addRow(array('valign'=>'top'));
      
      $numControlButtons = count($this->controlButtons);
      
      if ($numControlButtons > 0)
      {
         $width = intval(100 / $numControlButtons);
      }
      else
      {
         $width = "100%";
      }
      
      foreach ($this->controlButtons as $buttonID)
      {
         $propValue = $this->getFormPropValue($buttonID);
         $button = $this->buildInput($buttonID, $propValue);
         $buttonCellAttrs = array('width'=>"$width%", 'align'=>"center");
         $buttonCell = $this->renderer->createTDTag($button, $buttonCellAttrs);
         $controlRow->addCell($buttonCell);
      }
      $row->addContents($cell);
   }
   
	
	/**
	:Function: buildInput()
   
	:Description:
	Takes a property name and value, and creates an input field tag object for
   that property based on the parameters set in inputParams and populates that
   field with values based on the property values of this object.
   
	:Parameters:
	string $propName - the name of the property being output as an input field.
   mixed $propValue - the value of the property
	
	:Return Value:
	gcTag - a GC tag object appropriate to the "type" attribute associated with
      the property name by setInputAttr($propName, 'type', $inputType);
	
	:Notes:
	None.
	**/
	function &buildInput($propName, $propValue)
   {
      $propValue = stripslashes($propValue);
      $inputType = $this->inputAttrs[$propName]['type'];
      if (empty($inputType))
      {
         $inputType = "plainText";
      }
      
      $attr['id'] = $propName . $this->getInputSuffix();
      $attr['name'] = $this->getInputAttr($propName, "name", $attr['id']);
      $attr['class'] = $this->getInputAttr($propName, "class", "");
      
      if (is_array($this->inputAttrs[$propName]))
      {
         $setAttributes = array_keys($this->inputAttrs[$propName]);
         if ($setAttributes)
         {
            foreach ($setAttributes as $attrName)
            {
               $attr[$attrName] = $this->inputAttrs[$propName][$attrName];
            }
         }
      }
      
      switch ($inputType)
      {
         case "text":
            $attr['value'] = $propValue;
            $inputTag = $this->renderer->createTextInputTag($attr);
         break;
         
         case "plainText":
            $attr['type'] = "hidden";
            
            $displayValue = $propValue;
            if ($propValue == "")
            {
               $displayValue = "&nbsp;";
            }
            else if (is_bool($propValue))
            {
               $displayValue = $propValue ? "Yes" : "No";
            }
            else if (is_array($this->inputParams[$propName]['options']))
            {
               $displayValue = $this->inputParams[$propName]['options'][$propValue];
            }
            
            $hiddenInput = $this->renderer->createHiddenInputTag($propValue, $attr);
            $inputTag = $this->renderer->createSpanTag($displayValue, array('id'=> $id . "_text"));
            $inputTag->addContents($hiddenInput);
         break;
         
         case "emptyContainer":
            $inputTag = $this->renderer->createDivTag(false, $attr);
         break;
         
         case "hidden":
            $inputTag = $this->renderer->createHiddenInputTag($propValue, $attr);
         break;
            
         case "select":
            $options = $this->getInputParams($propName, "options");
            $attr['type'] = "";
            $inputTag = $this->renderer->createSelectTag($options, $attr);
            $inputTag->setPreselectValue($propValue);
         break;
            
         case "textarea":
            $attr['type'] = "";
            $inputTag = $this->renderer->createTextAreaTag($propValue, $attr);
         break;
         
         case "radio":
            $options = $this->getInputParams($propName, "options");
            $inputTag = $this->renderer->createRadioButtonCollection($options, $propValue, $attr);
         break;
            
         case "checkbox":
            $span = $this->renderer->createSpanTag();
            $attr['checked'] = $this->$propName == $attr['value'];
            $checkbox = $this->renderer->createCheckboxTag($attr, $span);
            $span->addContents($attr['desc']);
            $inputTag = $span;
         break;
            
         case "submit":
            $inputTag = $this->renderer->createSubmitButtonTag($propValue, $attr);
         break;
         
         case "delete":
            $attr['type'] = "submit";
            if (!IsSet($attr['onClick']))
            {
               $genericMsg = "Are you sure you want to delete?";
               $attr['onClick'] = "this.form.onsubmit = function(){return window.confirm('$genericMsg');}";
            }
            $inputTag = $this->renderer->createSubmitButtonTag($propValue, $attr);
         break;
         
         case "new":
            $attr['type'] = "button";
            if (!IsSet($attr['onClick']))
            {
               $attr['onClick'] = "document.location = document.location;";
            }
            $inputTag = $this->renderer->createButtonTag($propValue, $attr);
         break;
         
         case "back":
            $attr['type'] = "button";
            if (!IsSet($attr['onClick']))
            {
               $attr['onClick'] = "window.history.back();";
            }
            $inputTag = $this->renderer->createButtonTag($propValue, $attr);
         break;
         
         case "button":
            $inputTag = $this->renderer->createButtonTag($propValue, $attr);
         break;
         
         case "password":
            $inputTag = $this->renderer->createPasswordInputTag($attr);
         break;
         
         case "file":
            $inputTag = $this->renderer->createFileInputTag($attr);   
         break;
      }
      
      return $inputTag;
   }
   
	
	/**
	:Function: setInputSuffix()
	
	:Synopsis:
	Sets a suffix to be appended to every form input element this object
	creates. This lets this library support generating forms that change more
	than one object at a time. 
	
	:Description:
	
	:Parameters:
	string $suffix - the suffix you want to use.
	
	:Return Value:
	None.
	
	:Notes:
	None.
	**/
	function setInputSuffix($suffix)
	{
		if (strpos($suffix, "_") !== 0)
		{
			$suffix = "_" . $suffix;
		}
		$this->inputSuffix = $suffix;
	}// setInputSuffix()
	
	
	/**
	:Function: getInputSuffix()
	
	:Synopsis:
	Returns the suffix to be appended to every input form field name generated
	by this class's buildForm() method.
	
	:Description:
	
	:Parameters:
	None.
	
	:Return Value:
	string - the suffix.
	
	:Notes:
	None.
	**/
	function getInputSuffix()
	{
		return $this->inputSuffix;
	}// getInputSuffix()
			
	
	/**
	:Function: enableValidation()
	
	:Synopsis:
	Means that all calls to set() or save() will be validated, and error 
	messages will be produced, and save() will fail if validation fails.
	
	:Description:
	
	:Parameters:
	None.
	
	:Return Value:
	None.
	
	:Notes:
	None.
	**/
	function enableValidation()
	{
		$this->validate = true;
	}// enableValidation()
	
	
	/**
	:Function: disableValidation()
	
	:Synopsis:
	Means that all calls to set() or save() will NOT be validated. No error
	messages will be produced if any property has been set to an invalid 
	value.
	
	:Description:
	
	:Parameters:
	None.
	
	:Return Value:
	None.
	
	:Notes:
	None.
	**/
	function disableValidation()
	{
		$this->validate = false;
	}// disableValidation
	
	
	/**
	:Function: setValidationRule()
	
	:Description:
	Creates a new gcValiationRule object and asigns it the passed in property
   name, rule type and test value, and the calculated prettyProp name for
   error messages if validation fails. All the validation rules are stored in
   the validationRules array, which is numerically indexed.
   
	:Parameters:
	string $propName - the property to set.
	string $ruleType - the rule being set.
	mixed $testValue - criterium to pass the rule. 
	
	:Return Value:
	gcValidationRule - a validation rule object, which you can further modify.
	
	:Notes:
	None.
	**/
	function &setValidationRule($propName, $ruleType, $testValue="")
	{
      $newRule = new gcValidationRule($ruleType);
      $newRule->setPropName($propName);
      $newRule->setCriterium($testValue);
      $newRule->setPrettyPropName($this->getPrettyPropName($propName));
      $this->validationRules[$propName][] =& $newRule;
      return $newRule;
	}
	
	
	/**
	:Function: hasValidationRule()
	
	:Synopsis:
   Checks to see if a given property has a particular type of validation rule.
   Returns true if a rule of the passed in type is set for the passed in 
   property name, false if not.
	
	:Description:
	
	:Parameters:
   string $propName - the name of the property you want to check for a rule.
   string $ruleType - the type of rule you want to check for.
	
	:Return Value:
   boolean - true if the rule is set, false otherwise.
	
	:Notes:
	None.
	**/
   function hasValidationRule($propName, $ruleType)
   {
      if ($this->validationRules[$propName])
      {
         foreach ($this->validationRules[$propName] as $ruleObj)
         {
            if ($ruleObj->ruleType == $ruleType)
            {
               return true;
            }
         }
      }
      return false;
   }
	
	/**
	:Function: getPrettyPropName()
	
	:Synopsis:
	Given a property name, it returns that name broken up by capital letters.
	For example, getPrettyPropName("firstName") returns the string "First
	Name". This makes it more human readable (pretty) for error messages, etc.
	
	:Description:
	
	:Parameters:
	string $propName - the property name to beautify.
	
	:Return Value:
	string - pretty property name.
	
	:Notes:
	None.
	**/
	function getPrettyPropName($propName)
	{
		if (IsSet($this->inputParams[$propName]['label']))
		{
			return $this->inputParams[$propName]['label'];
		}
		$prettyProp = ucfirst(preg_replace("/([A-Z])/", " \\1", $propName));
		return trim($prettyProp);
	}
   
   
   /**
   :Function: importFromPost()
   
   :Description:
   Imports any data submitted via POST that matches the property value names for
   this object. Data from POST must be validated before it can be assigned to
   a property name, unless the $skipValidation arg is set to true. Submitted 
   data that fails validation will be skipped and NO value will be set in this
   object.
   
   If the property being set has a "set" method (i.e. a method named 
   "setUserName" for the property "userName") then that method is called and
   passed the value from post. Otherwise, the property is set explicitly.
   
   :Parameters:
   boolean $skipValidation - true if you want to skip value validation.
   
   :Return Value:
   None.
   
   :Notes:
   No return value, but properties of this object will be updated if POST data
   is available.
   **/
   function importFromPost($skipValidation=false)
   {
      $objectVars = $this->getFormProps();
      
      if ($this->validate && $skipValidation === false)
      {
         $this->validator = new gcValidator($this->validationRules);
      }
      else
      {
         $this->validator = false;
      }
      
      foreach ($objectVars as $propName)
      {  
         if (in_array($propName, $this->skipProps))
         {
            continue;
         }
         
         if (!IsSet($_POST[$propName]))
         {
            $propValue = "";
         }
         else
         {
            $propValue = stripslashes(trim($_POST[$propName]));
         }
         
         // scrub any html tags from the submitted value.
         $propValue = strip_tags($propValue, $this->allowedTags);
         $propValue = htmlentities($propValue, ENT_QUOTES, "UTF-8");
         
         if ($this->validator)
         {
            if (!$this->validator->validateProperty($propName, $propValue))
            {
               // $validator will enter error messages for any props that fail.
               $this->validationPassed = false;
            }
         }
         
         $setMethodName = "set" . ucFirst($propName);
         if (method_exists($this, $setMethodName))
         {
            call_user_func(array(&$this, $setMethodName), $propValue);
         }
         else if (property_exists(get_class($this), $propName))
         {
            $this->$propName = $propValue;
         }
         else
         {
            continue;
         }
      }
      
      $this->failedProps = array_unique($this->validator->failedTestPropertyNames);
   }
	
   
   /**
   :Function: exportToHash()
   
   :Description:
   Takes the properties in formProps and loops through them, looking for 
   properties of this object and their values. For each property that exists
   in the instantiated object (which extends this class) a key will be created
   in the hash and its value will be set to the property value. After looping
   through all the form props the method returns the hash.
   
   :Parameters:
   None.
   
   :Return Value:
   hash - with formProps as keys and instantiated object values as values.
   
   :Notes:
   **/
   function exportToHash()
   {
      $data = array();
      $propsToSave = $this->getFormProps();
      foreach ($propsToSave as $propName)
      {
         if (in_array($propName, $this->skipProps))
         {
            continue;
         }
         
         $getMethodName = "get" . ucfirst($propName);
         
         if (method_exists($this, $getMethodName))
         {
            $data[$propName] = call_user_func(array($this, $getMethodName));
         }
         else if (property_exists(get_class($this), $propName))
         {
            $data[$propName] = $this->$propName;
         }
         else
         {
            continue;
         }
      }
      return $data;
   }
   
   
   /**
   :Function: importFromHash()
   
   :Description:
   Takes a hash of data with keys that match the values in the formProps array
   and assigns those values to this object's properties, either via set methods
   or by direct assignment.
   
   :Parameters:
   hash $data - a hash of data keyed to match the values in the formProps array.
   
   :Return Value:
   None.
   
   :Notes:
   If there is a formProp entry that is not represented in the $data hash, that
   property will be skipped. It will not be set to "" or NULL or anything else.
   **/
   function importFromHash($data)
   {
      $formProps = $this->getFormProps();
      foreach ($formProps as $propName)
      {
         if (!IsSet($data[$propName]))
         {
            continue;
         }
         
         $setMethodName = "set" . ucfirst($propName);
         if (method_exists($this, $setMethodName))
         {
            call_user_func(array(&$this, $setMethodName), $data[$propName]);
         }
         else if (property_exists(get_class($this), $propName))
         {
            $this->$propName = $data[$propName];
         }
         else
         {
            continue;
         }
      }
   }
   
   
   /**
   :Function: importFromMySQL()
   
   :Description:
   Reads this user's data from MySQL.
   
   :Parameters:
   string $id - the name of the ID row of the user to import data from mysql.
   
   :Return Value:
   boolean - true if query succeeds, false if it fails.
   
   :Notes:
   **/
   function importFromMySQL($id)
   {
      $this->setIDValidation("Sorry, we could not locate the requested record.", $id);
      
      $validator = new gcValidator($this->validationRules);
      $validID = $validator->validateProperty('id', $id);
      
      if (!$validID)
      {
         // fail silently - error messages already registered by validator.
         return false;
      }
      
      $this->importFromHash($this->getUserData($id));
   }
   
   
   function isUpdate()
   {
      $action = $this->determineAction();
      if ($action == "viewing" || $action == "updating" || $action == "deleting")
      {
         return true;
      }
      else
      {
         return false;
      }
   }
   
   
   /**
   :Function: determineAction()
   
   :Description:
   Determines whether a form should be displaying an empty form to create a
   new entry, or a filled in form to edit an entry, or if you're deleting an
   entry, or just displaying data.
   
   :Parameters:
   string $idColumnName - the name of the ID column to look for in GET and
      POST, which determines whether the page is creating, updating or deleting.
      Default is "id".
      
   (but examines post and get)
   
   :Return Value:
   string - one of these values:
      entering - creating a new object.
      updating - an existing object is being updated.
      creating - a new object is being created.
      deleting - deleting an object.
      viewing - examining an object.
   
   :Notes:
   **/
   function determineAction($idColumnName="id")
   {
      if ($this->action !== "")
      {
         //print("<br>action already set to $this->action");
         return $this->action;
      }
      
      // if the user had to log in before using a page, the authenticator will
      // know what their original action was supposed to be. Use that because
      // POST will be contain login data.
      if (IsSet($_SESSION['original action']))
      {
         //print("<br>getting session: " . $_SESSION['original action'] . "<br>");
         $this->action = $_SESSION['original action'];
         unset($_SESSION['original action']);
         return $this->action;
      }
      
      if (count($_POST) > 0)
      {
         // data was submitted via POST.
         if ($_POST[$idColumnName] != "")
         {
            // existing object was updated or deleted.
            if (IsSet($_POST['deleteButton']))
            {
               $this->action = "deleting";
            }
            else
            {
               $this->action = "updating";
            }
         }
         else
         {
            // new object was created.
            $this->action = "creating";
         }
      }
      else if (IsSet($_GET[$idColumnName]))
      {
         // displaying an object's data so it can be edited.
         $this->action = "viewing";
      }
      else
      {
         // displaying an empty form for creation.
         $this->action = "entering";
      }
      
      //print("<br>$this->action");
      return $this->action;
   }
   
   
   /**
   :Function: allowTag()
   
   :Description:
   Adds a tag or list of tags to a list of allowed tags for strip_tags().
   
   :Parameters:
   string $tagsAllowed - a string of the allowed tags.
   
   :Return Value:
   None
   
   :Notes:
   **/
   function allowTags($tag)
   {
      if (strpos($tag, "<") !== 0)
      {
         $tag = "<" . $tag;
      }
      
      if (strpos($tag, ">") !== strlen($tag) - 1)
      {
         $tag = $tag . ">";
      }
      
      $this->allowedTags .= $tag;
   }
   
   
   /**
   :Function: buildEditList()
   
   :Description:
   Builds a list of database records that can be edited by clicking on a link
   in the list. Takes a db query result and a url to point to for editing the
   file and returns a div container which contains the list.
   
   The query needs to contain at least two fields: an id field and a title field
   which will be the text displayed to the item in the list.
   
   :Parameters:
   resource mysqli result $dbResult - a query result.
   string $url - the file name you want the links to point to.
   string $idFieldName - the name of the field in the query used for row id.
   string $titleFieldName - the name of the field for the text to display in
      the list.
   
   :Return Value:
   gcDivTag - a div tag container for the list.
   
   :Notes:
   **/
   function &buildEditList(&$dbResult, $url, $idFieldName, $titleFieldName)
   {
      $class = "listItemLinkLight";
      $listContainer = $this->renderer->createDivTag(false, array("class"=>"listContainer"));
      
      $fields = array();
      while($item = $this->db->getRow(&$dbResult))
      {
         $itemID = $item[$idFieldName];
         $itemTitle = $item[$titleFieldName];
         $editURL = $url . "?" . $idFieldName . "=" . $itemID;
         
         if (!$fields)
         {
            $fields = array_keys($item);
         }
         
         $link = $this->renderer->createATag(false, array("class"=>$class, "href"=>$editURL), $listContainer);
         
         $titleDiv = $this->renderer->createDivTag($itemTitle, array("class"=>"listTitleField"), $link);
         foreach ($fields as $fieldName)
         {
            if ($fieldName == $idFieldName || $fieldName == $titleFieldName)
            {
               continue;
            }
            //$fieldValue = $item[$fieldName];
            //$fieldDiv = $this->renderer->createDivTag($fieldValue, array("class"=>"listField"), $titleDiv);
         }
         
         $class = $class == "listItemLinkLight" ? "listItemLinkDark" : "listItemLinkLight";
      }
      return $listContainer;
   }
   
   
   /**
   :Function: setMode()
   
   :Description:
   Sets the mode for this object. "Modes" are modes of operation and rendering.
   Specific classes will allow specific modes and modify their logic and
   rendering behavior according to the class's requirements. Some classes may
   not support modes at all. Consult the specific class code for calls to
   setMode() and getMode() to determine what purpose these methods serve for a
   given application.
   
   :Parameters:
   string $mode - the render mode..
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function setMode($mode)
   {
      $this->mode = $mode;
      $this->determineRequiredPermission();
   }
   
   
   /**
   :Function: getMode()
   
   :Description:
   Returns the mode of this object. "Modes" are modes of operation and rendering.
   Specific classes will allow specific modes and modify their logic and
   rendering behavior according to the class's requirements. Some classes may
   not support modes at all. Consult the specific class code for calls to
   setMode() and getMode() to determine what purpose these methods serve for a
   given application.
   
   :Parameters:
   None.
   
   :Return Value:
   string - the render mode. 
   
   :Notes:
   **/
   function getMode()
   {
      return $this->mode;
   }
   
   
   /**
   :Function: setIDValidation()
   
   :Description:
   Sets the validation rule for the ID property (which is not always present 
   and so not always validated).
   
   :Parameters:
   string $msg - a custom error message for the id validation.
   string $idPropName - the name of the property containing the ID value.
   
   :Return Value:
   None
   
   :Notes:
   **/
   function setIDValidation($msg, $idPropName="id")
   {
      $this->setValidationRule($idPropName, "notEmpty");
      $this->validationRules[$idPropName][count($this->validationRules[$idPropName]) - 1]->setCustomErrorMessage($msg . " The record ID cannot be empty.");
      $this->setValidationRule($idPropName, "numeric");
      $this->validationRules[$idPropName][count($this->validationRules[$idPropName]) - 1]->setCustomErrorMessage($msg . " Records must be represented by a number.");
      $this->setValidationRule($idPropName, "minVal", 1);
      $this->validationRules[$idPropName][count($this->validationRules[$idPropName]) - 1]->setCustomErrorMessage($msg . " The record ID must be greater than zero.");
   }

   
   /**
   :Function: validateID()
   
   :Description:
   Validates the row id field, if one has been set to be validated. Returns
   true if the row id is a valid id (is a number greater than 0) and false if
   it fails.
   
   :Parameters:
   string $propName - the name of the property for the row id. Defaults to "id".
   
   :Return Value:
   bool - true if validation passes for id field, false otherwise.
   
   :Notes:
   **/
   function validateID($rowIDPropName="id")
   {
      static $previousResult;
      if (IsSet($previousResult[$rowIDPropName]))
      {
         return $previousResult[$rowIDPropName];
      }
      
      $this->validator = new gcValidator($this->validationRules);
      
      if ($this->validator->validateProperty($rowIDPropName, $this->$rowIDPropName))
      {
         $previousResult[$rowIDPropName] = true;
         return true;
      }
      else
      {
         $previousResult[$rowIDPropName] = false;
         return false;
      }
   }
   
}
?>
