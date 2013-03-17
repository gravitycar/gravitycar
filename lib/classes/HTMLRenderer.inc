<?php
/**

HTMLRenderer.inc (class for html rendering engine)

The renderer object is the source of all HTML displayed on the page. Other 
applications will call the renderer's singleton method and from there, can
register all their dcreateSelectTagisplayable elements by creating new HTML objects and giving
those objects attributes and contents. Contents can be scalars or nested
objects. The renderer keeps track of all objects just like the HTML DOM does:
Everything belongs to the HTML tag, which has two main children, HEAD and BODY,
which have their own children, and so on. Individual applications will assemble
the HTML object heirarchy necessary to display their contents and the renderer
is ultimately responsible for outputting that content.

**/

require_once("lib/classes/MessageCenter.inc");


class gcHTMLRenderer
{
   private static $instance;
   
   private function gcHTMLRenderer()
   {
      $this->msgCenter = gcMessageCenter::Singleton();
      $this->allTags = array();
      $this->html = new gcHTMLTag();
      $this->html->setAttribute('xmlns', 'http://www.w3.org/1999/xhtml');
      $this->head = new gcHeadTag();
      $this->title = new gcTitleTag();
      $this->body = new gcBodyTag();
      
      $this->head->addContents($this->title);
      $this->html->addContents($this->head);
      $this->html->addContents($this->body);
   }
   
   
   public static function Singleton()
   {
      if (!IsSet(self::$instance))
      {
         $c = __CLASS__;
         self::$instance = new $c;
      }
      return self::$instance;
   }
   
   
   /**
   :Function: registerID()
   
   :Description:
   If a tag has an ID attribute (or a name attribute) that attribute can be
   used to "register" a reference to the tag allowing for easy access by the
   render for future use.
   
   :Parameters:
   mixed $tag - a reference to an object which extends the gcTag class.
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function registerTagByID(&$tag)
   {
      if (IsSet($this->allTags[$tag->getAttribute("id")]))
      {
         return;
      }
      
      $id = $tag->getAttribute('id');
      $name = $tag->getAttribute('name');
      $noID = empty($id) ? true : false;
      $noName = empty($name) ? true : false;
      
      if ($noID && !$noName)
      {
         $tag->setAttribute('id', $name);
      }
      
      $this->allTags[$tag->getAttribute('id')] =& $tag;
   }
   
   
   /**
   :Function: setNameToID()
   
   :Description:
   Sets the name attribute of this tag to the value of the id attribute, if
   present. Otherwise does nothing.
   
   :Parameters: 
   hash $attributes - a hash of name/value pairs for tag attributes.
   
   :Return Value:
   None.
   
   :Notes:
   None.
   **/
   function setNameToID(&$attributes)
   {
      if ($attributes)
      {
         if (empty($attributes['name']) && !empty($attributes['id']))
         {
            $attributes['name'] = $attributes['id'];
         }
      }
   }
   
   
   /**
   :Function: getTagById()
   
   :Description:
   Returns a reference to a tag that has been registered with the renderer by
   its ID attribute value. For example, if you create a DIV tag with
   renderer->createDivTag() and give that tag an attribute id value of "myDiv",
   you can use this function to get a reference to that tag by passing this
   function the ID of the tag.
   
   :Parameters:
   string $id - the ID of the tag you want to retrieve. 
   
   :Return Value:
   mixed - a gcTag object if their is an objecte registered by the passed in ID,
      boolean false if there is not.
   
   :Notes:
   **/
   function &getTagById($id)
   {
      if (IsSet($this->allTags[$id]))
      {
         return $this->allTags[$id];
      }
      else
      {
         return false;
      }
   }
   
   
   /**
   :Function: createTag()
   
   :Description:
   Creates the named kind of tag. This method is meant to be "wrapped" by more
   specific methods for each kind of HTML tag to be created.
   
   :Parameters:
   string $tagNmae - the name of the tag you want to create.
   contents $value - either a string value or another tag object.
   hash $attributes - a hash of attribute name/value pairs.
   mixed $parent - a tag object to append this tag to.
   
   :Return Value:
   mixed - a tag object; an object that extends the gcTag class
   
   :Notes:
   **/
   function &createTag($tagName, $contents=false, $attributes=false, $parent=false)
   {
      $tagClassName = "gc" . ucfirst($tagName) . "Tag";
      
      if (!class_exists($tagClassName) || $tagName == "")
      {
         $this->msgCenter->addDebug("$tagClassName is not a known HTML tag class");
         return false;
      }
      
      $tag = new $tagClassName;
      
      if ($contents)
      {
         $tag->addContents($contents);
      }
      
      if ($attributes)
      {
         if (IsSet($attributes['id']))
         {
            $this->registerTagByID(&$tag);
            
            if (!IsSet($attributes['name']))
            {
               //$attributes['name'] = $attributes['id'];
            }
         }
         
         foreach ($attributes as $name => $value)
         {
            $tag->setAttribute($name, $value);
         }
      }
      
      if ($parent)
      {
         if (method_exists($parent, "addContents"))
         {
            $parent->addContents($tag);
         }
      }
      return $tag;
   }
   
   
   /**
   :Function: create*Tag() (for open tags).
   
   :Description:
   All of these methods return an object that extends the gcTag class. These
   functions are primarily wrappers and convenience functions for those various
   types of tags. 
   
   :Parameters: (except where noted)
   mixed $contents - either a string another gcTag descendent class instance.
   hash $attributes - the name/value pairs for HTML attributes for the rendered
      tag.
   gc*Tag $parent - a tag object to attach the created object to.
   
   :Return Value:
   gc*Tag object instance.
   
   :Notes:
   These functions typically return an "open" tag, like this in HTML:
   <tag></tag>. The first argument to these functions is typically the children
   for that tag object. These children can be either more tags or text. 
   
   HOWEVER: some of these methods do return closed tags, like this:
   <tag/>
   Example methods are createButtonTag(), which returns an input tag with a type
   attribute set to "button". These methods are passed a $contents argument,
   which should be a string, which will be used as the value attribute for the
   tag.
   **/
   function &createDivTag($contents=false, $attributes=false, &$parent=false)
   {
      return $this->createTag("div", $contents, $attributes, &$parent);
   }
   
   
   function &createULTag($contents=false, $attributes=false, $parent=false)
   {
      return $this->createListTag("ul", $contents, $attributes, $parent);
   }
   
   
   function &createOLTag($contents=false, $attributes=false, $parent=false)
   {
      return $this->createListTag("ol", $contents, $attributes, $parent);
   }
   
   
   function &createH1Tag($contents=false, $attributes=false, $parent=false)
   {
      return $this->createTag("h1", $contents, $attributes, $parent);
   }
   
   
   function &createH2Tag($contents=false, $attributes=false, $parent=false)
   {
      return $this->createTag("h2", $contents, $attributes, $parent);
   }
   
   
   function &createH3Tag($contents=false, $attributes=false, $parent=false)
   {
      return $this->createTag("h3", $contents, $attributes, $parent);
   }
   
   
   function &createJavascriptTag($contents=false, $attributes=false, $parent=false)
   {
      return $this->createTag("Javascript", $contents, $attributes, $parent);
   }
   
   /**
   :Function: createListTag
   
   :Description:
   This is a convenience wrapper for createULTag and createOLTag. It takes a 
   tag type as its first arg and then the other, standard args afterwards.
   Typically, the contents of a UL or OL tag are LI tags. These tags can be
   passed in as an array of tags to this method as its second argument. An
   array of string could also be passed in here and they'll be turned into
   LI tags.
   
   :Parameters:
   string $listType - valid values are "UL" and "OL".
   mixed $contents - could be another tag object, an array of gcLITag objects
      or an array of strings.
   hash $attributes - a hash of html tag attribute names and values for the 
      ul or ol tag.
   gcTag $parent - a tag object to attach the list object to after creation.
      
   :Return Value:
   mixed - either a gcULTag or a gcOLTag object.
   
   :Notes:
   **/
   function &createListTag($listType="ul", $contents=false, $attributes=false, $parent=false)
   {
      if (!in_array(strtolower($listType), array("ul", "ol")))
      {
         $this->msgCenter->addError("There is no $listType list tag. Please use 'ul' or 'ol'.");
      }
      
      // don't pass in contens to createTag here. Handle them separately.
      $listTag = $this->createTag($listType, false, $attributes, $parent);
      
      // handle contents separately here. You could be passed an array of
      // strings or an array of LI tags.
      if ($contents)
      {
         if (is_array($contents))
         {
            foreach ($contents as $content)
            {
               if (is_a($content, "gcLITag"))
               {
                  $listTag->addContents($content);
               }
               else
               {
                  $listTag->addContents($this->createLITag($content));
               }
            }
         }
         else
         {
            $listTag->addContents($content);
         }
      }
      return $listTag;
   }
   
   
   
   function &createLITag($contents=false, $attributes=false, $parent=false)
   {
      return $this->createTag("li", $contents, $attributes, $parent);
   }
   
   
   function &createATag($contents=false, $attributes=false, $parent=false)
   {
      return $this->createTag("a", $contents, $attributes, $parent);
   }
   
   
   function &createPTag($contents=false, $attributes=false, $parent=false)
   {
      return $this->createTag("p", $contents, $attributes, $parent);
   }
   
   
   function &createTableTag($contents=false, $attributes=false, $parent=false)
   {
      return $this->createTag("table", $contents, $attributes, $parent);
   }
   
   
   function &createTHeadTag($contents=false, $attributes=false, $parent=false)
   {
      return $this->createTag("thead", $contents, $attributes, $parent);
   }
   
   
   function &createTRTag($contents=false, $attributes=false, $parent=false)
   {
      return $this->createTag("tr", $contents, $attributes, $parent);
   }
   
   
   function &createTDTag($contents=false, $attributes=false, $parent=false)
   {
      return $this->createTag("td", $contents, $attributes, $parent);
   }
   
   
   function &createTHTag($contents=false, $attributes=false, $parent=false)
   {
      return $this->createTag("th", $contents, $attributes, $parent);
   }
   
   
   function &createFormTag($contents=false, $attributes=false, $parent=false)
   {
      return $this->createTag("form", $contents, $attributes, $parent);
   }
   
   
   function &createHiddenInputTag($value="", $attributes=false, $parent=false)
   {
      $attributes['value'] = $value;
      $attributes['type'] = "hidden";
      $this->setNameToID(&$attributes);
      return $this->createTag("hiddenInput", false, $attributes, $parent);
   }
   
   
   function &createTextAreaTag($contents=false, $attributes=false, $parent=false)
   {
      $this->setNameToID(&$attributes);
      return $this->createTag("textarea", $contents, $attributes, $parent);
   }
   
   
   /**
   :Function: createSelectTag()
   
   :Description:
   Creates a select tag with options. If you don't pass in any options, you
   don't get a select tag, the function returns false.
   
   :Parameters:
   mixed $options - either a hash of values for each option as the keys and text
      to be displayed in the select menu as values OR an array of gcOptionTag
      objects.
   hash $attributes - a hash of attribute name/value pairs for the select menu.
   mixed $parent - a tag object to append this tag to.
   
   :Return Value:
   A gcSelectTag object.
   
   :Notes:
   If you want other attributes for your option tags, you have to assign them
   separately after creating the select tag.
   **/
   function &createSelectTag($options, $attributes=false, $parent=false)
   {
      $this->setNameToID(&$attributes);
      if (!is_array($options))
      {
         $id = $attributes['id'];
         $name = $attributes;
         $this->msgCenter->addDebug("Tried to add select ($name/$id) with no options.");
         $options = array();
      }
      
      $selectTag = $this->createTag("select", false, $attributes, $parent);
      foreach ($options as $index => $value)
      {
         if (is_a($value, "gcOptionTag"))
         {
            $selectTag->addContents($value);
         }
         else
         {
            $optionAttributes['value'] = $index;
            $this->createOptionTag($value, $optionAttributes, $selectTag);
         }
      }
      return $selectTag;
   }
   
   
   function &createOptionTag($contents=false, $attributes=false, $parent=false)
   {
      if (!IsSet($attributes['value']) || !$contents)
      {
         return false;
      }
      return $this->createTag("option", $contents, $attributes, $parent);
   }
   
   function &createFileInputTag($attributes=false, $parent=false)
   {
      return $this->createTag("file", false, $attributes, $parent);
   }
   
   
   /**
   :Function: createRadioButtonCollection()
   
   :Description:
   Returns an array of radio button objects based on a passed in array of values
   for each radio button. This is a convenience wrapper function to make a 
   formatted group of radio buttons.
   
   :Parameters:
   mixed $values - an array strings which represent a value for each button.
   string $defaultValue - the value of the radio button that should be pre-
      selected (ie already marked as checked).
   hash $attributes - a hash of html tag attributes which will be applied to
      each radio button.
   
   :Return Value:
   array - an array of gcSpanTag objects, where each span contains both a radio
      button tag object and a text label for the button.
   
   :Notes:
   The ID of each radio button will be unique, but the name attributes will all
   be the same. Each radio button's ID will be formatted like this:
   id="name_N", where "N" is the number representing the buttons position in the
   array of buttons.
   **/
   function &createRadioButtonCollection($values, $defaultValue="", $attributes=false)
   {
      $buttons = array();
      $count = 0;
      $this->setNameToID(&$attributes);
      foreach ($values as $value => $label)
      {
         if ($value == $defaultValue)
         {
            $attributes['checked'] = true;
         }
         else
         {
            $attributes['checked'] = false;
         }
         
         $attributes['value'] = $value;
         $attributes['id'] = $attributes['name'] . "_" . $count;
         $parent = false;
         $button = $this->createRadioButtonTag($attributes, $parent);
         $buttons[] = $this->createSpanTag(array($button, $label . "&nbsp;&nbsp;"));
         $count++;
      }
      return $buttons;
   }
   
   
   function &createSubmitButtonTag($value, $attributes=false, $parent=false)
   {
      $this->setNameToID(&$attributes);
      if (is_object($value))
      {
         $this->msgCenter->addDebug("createSubmitButtonTag() passed an object for arg 1, needs a string.");
         $value = "";
      }
      
      $attributes['value'] = $value;
      return $this->createTag("submitButton", "", $attributes, $parent);
   }
   
   
   function &createButtonTag($value=false, $attributes=false, $parent=false)
   {
      $this->setNameToID(&$attributes);
      if (is_object($value))
      {
         $this->msgCenter->addDebug("createButtonTag() passed an object for arg 1, needs a string.");
         $value = "";
      }
      
      $attributes['value'] = $value;
      return $this->createTag("inputButton", "", $attributes, $parent);
   }
   
   
   function &createSpanTag($contents=false, $attributes=false, $parent=false)
   {
      return $this->createTag("span", $contents, $attributes, $parent);
   }
   
   
   function &createNOBRTag($contents=false, $attributes=false, $parent=false)
   {
      return $this->createTag("nobr", $contents, $attributes, $parent);
   }
   
   
   /**
   :Function: create*Tag() (for closed tags).
   
   :Description:
   All of these methods return an object that extends the gcTag class. These
   functions are primarily wrappers and convenience functions for those various
   types of tags. Because these tags don't have children, they don't take a
   $contents argument. They only tag $attributes and $parent, which are both
   optional.
   
   :Parameters: (except where noted)
   hash $attributes - the name/value pairs for HTML attributes for the rendered
      tag.
   gc*Tag $parent - a tag object to attach the created object to.
   
   :Return Value:
   gc*Tag object instance.
   
   :Notes:
   Closed tags, like image tags or input tags, don't have any children and 
   therefore do not take "contents" as an argument.
   **/
   
   
   /**
   :Function: createImgTag()
   
   :Description:
   Creates an img tag object. Because image tags don't have children, this
   function only takes two args: attributes and parent. Note that you need to
   at least assign a src attribute with a valid path to the url of an image in
   the $attributes hash.
   
   :Parameters:
   hash $attributes - a hash of attribute name/value pairs.
   gc*Tag $parent - a tag object to append this tag to.
   
   :Return Value:
   gcImageTag - an image tag object.
   
   :Notes:
   **/
   function &createImgTag($attributes=false, $parent=false)
   {
      //$defaultAttributes['border'] = "0";
      $attributes = $this->setDefaultAttributes($defaultAttributes, $attributes);
      return $this->createTag("Image", false, $attributes, $parent);
   }
   
   
   function &createTextInputTag($attributes=false, $parent=false)
   {
      $this->setNameToID(&$attributes);
      return $this->createTag("textInput", false, $attributes, $parent);
   }
   
   
   function &createPasswordInputTag($attributes=false, $parent=false)
   {
      $this->setNameToID(&$attributes);
      $tag = $this->createTextInputTag($attributes, $parent);
      $tag->setAttribute("type", "password");
      return $tag;
   }
   
   
   function &createCheckboxTag($attributes=false, $parent=false)
   {
      $defaultAttributes['type'] = "checkbox";
      $defaultAttributes['checked'] = false;
      $attributes = $this->setDefaultAttributes($defaultAttributes, $attributes);
      $this->setNameToID(&$attributes);
      $cb = $this->createTag("checkbox", false, $attributes, $parent);
      return $cb; 
   }
   
   
   function &createRadioButtonTag($attributes=false, $parent=false)
   {
      $this->setNameToID(&$attributes);
      $defAttr['type'] = "radio";
      $defAttr['checked'] = false;
      $attributes = $this->setDefaultAttributes($defAttr, $attributes);
      $tag = $this->createTag("radio", false, $attributes, $parent);
      return $tag;
   }
   
   
   function &createBRTag($attributes=false, $parent=false)
   {
      return $this->createTag("br", false, false, $parent);
   }
   
   
   function &createImageTag($attributes=false, $parent=false)
   {
      return $this->createTag("image", false, $attributes, $parent);
   }
   
   
   /**
   :Function: setDefaultAttributes()
   
   :Description:
   Takes a hash of attribute names and values that are the default values, and
   the passed in hash of attributes, and adds the defaults to the passed in
   hash UNLESS the default attribute is already defined in the passed in 
   attributes hash.
   
   :Parameters:
   hash $defaultAttributes - the attributes this tag should always have.
   hash $passedInAttributes - the attributes passed to handler function for this
      tag.
   
   :Return Value:
   hash - the passed in attributes with the defaults filled in where missing.
   
   :Notes:
   **/
   function &setDefaultAttributes($defaultAttributes, $passedInAttributes)
   {
      if (!is_array($passedInAttributes))
      {
         $passedInAttributes = array();
      }
      
      foreach ($defaultAttributes as $name => $value)
      {
         if (!IsSet($passedInAttributes[$name]))
         {
            $passedInAttributes[$name] = $value;
         }
      }
      
      return $passedInAttributes;
   }
   
   
   /**
   :Function: render()
   
   :Description:
   This function renders the HTML tag element of the renderer, which should
   in turn recursively call the render() method for every child tag contained
   in the HTML tag. So this function effectively renders the entire web page.
   
   :Parameters:
   None.
   
   :Return Value:
   None. But prints to stdout all the HTML for all the tags within the HTML
   tag.
   
   :Notes:
   **/
   function render()
   {
      print($this->html->renderTag());
   }
}


class gcTag
{
   var $attributes = array();
   var $tagName = "";
   var $contents = array();
   var $attributeString = "";
   var $closedTag = false;
   var $renderedHTML = "";
   var $prefixWhiteSpace = "";
   var $suffixWhiteSpace = "";
   
   
   /**
   :Function: setAttribute()
   
   :Description:
   Sets a name/value pair to the hash of attributes for this tag.
   
   :Parameters:
   string $name - the name of the attribute being set.
   mixed $value - the value of the attribute being set.
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function setAttribute($name, $value)
   {
      $this->attributes[$name] = $value;
      
      if ($name == "id")
      {
         $renderer = gcHTMLRenderer::Singleton();
         $renderer->registerTagById($this);
      }
   }
   
   
   /**
   :Function: setAttributes()
   
   :Description:
   Takes a hash of name/value pairs and sets this object's attributes value to
   that hash.
   
   :Parameters:
   string $nameValuePairs - A hash of name/value pairs, like this:
   $hash['type'] = "hidden";
   $hash['value'] = "secret";
   
   :Return Value:
   None.
   
   :Notes:
   This function overwrites any previously existing attributes.
   **/
   function setAttributes($hash=false)
   {
      foreach ($hash as $name => $value)
      {
         $this->setAttribute($name, $value);
      }
   }
   
   
   /**
   :Function: getAttributes()
   
   :Description:
   Returns the hash of attributes for this tag.
   
   :Parameters:
   None.
   
   :Return Value:
   hash - a hash with the attribute names as keys and the attribute values as
      values.
   
   :Notes:
   **/
   function getAttributes()
   {
      return $this->attributes;
   }
   
   
   /**
   :Function: getAttribute()
   
   :Description:
   Returns the named attribute's value.
   
   :Parameters:
   string $name - the name of the attribute you want.
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function getAttribute($name)
   {
      if (IsSet($this->attributes[$name]))
      {
         return $this->attributes[$name];
      }
      else
      {
         return "";
      }
   }
   
   
   /**
   :Function: buildAttributeString()
   
   :Description:
   Concatenates every attribute together in a string to use them as the HTML
   attributes for the rendered tag. The string's pattern is:
   name="value"[ name="value"]. . . 
   
   :Parameters:
   None
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function buildAttributeString()
   {
      $pairs = array();
      foreach ($this->attributes as $name => $value)
      {
         if ($value === "")
         {
            continue;
         }
         
         if (is_bool($value))
         {
            if ($value === true)
            {
               $pairs[] = $name;
            }
         }
         else
         {
            $value = str_replace('"', '\"', $value);
            $pairs[] = "$name=\"$value\"";
         }
      }
      
      if ($pairs)
      {
         $this->attributesString = " " . join(" ", $pairs);
      }
      else
      {
         $this->attributesString = "";
      }
   }
   
   
   /**
   :Function: addContents()
   
   :Description:
   Adds either a scalar value or another tag object to the list of contents for
   this tag. Contents are rendered in the order they are added.
   
   :Parameters:
   mixed - either a scalar or a tag object (an object that extends this class).
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function addContents(&$contents)
   {
      if (is_array($contents))
      {
         foreach ($contents as $content)
         {
            $this->addContents($content);
         }
      }
      else
      {
         $this->contents[] = $contents;
      }
   }
   
   /**
   :Function: setContents()
   
   :Description:
   Sets the contents of this tag. Contents can either be scalar (strings or 
   numerical value which will be displayed as strings) or they can be other
   tag objects (any object that extends this class.
      
   This method will over-write any previously existing contents for this tag.
   
   :Parameters:
   array $contents - an array of either scalar values or tag objects.
   
   :Return Value:
   None.
   
   :Notes:
   NOTE: if you pass a single variable to this method, it will be converted to
   an array and replace the previously existing contents.
   **/
   function setContents(&$contents)
   {
      if (!is_array($contents))
      {
         $contents = array($contents);
      }
      $this->contents = $contents;
   }
   
   
   /**
   :Function: removeContents()
   
   :Description:
   Removes the specified contents by type.
   
   :Parameters:
   string $tagClass - the type of tag you want to remove. Set to "string" to
      remove strings of text.
   string $attrName - an attribute name to filter which tags you want to
      remove.
   string $attrValue - the value that an attribute should be set to to pass the
      fileter set by attrName.
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function removeContents($tagClass, $attrName="", $attrValue="")
   {
      $newContents = array();
      
      foreach ($this->contents as $tag)
      {
         $remove = false;
         if (is_a($tag, $tagClass))
         {
            if ($attrName == "")
            {
               $remove = true;
            }
            else
            {
               if ($tag->getAttribute($attrName) == $attrValue)
               {
                  $remove = true;
               }
            }
         }
         
         if (!$remove)
         {
            $newContents[] = $tag;
         }
      }
      
      $this->contents = $newContents;
   }
   
   
   /**
   :Function: getContents()
   
   :Description:
   Returns the contents of this tag.
   
   :Parameters:
   None
   
   :Return Value:
   mixed - the contents of this tag, which could be scalar or another type of
   tag object.
   
   :Notes:
   **/
   function getContents()
   {
      return $this->contents;
   }
   
   
   /**
   :Function: setWhitespace()
   
   :Description:
   Sets the whitespace to be inserted before and after the opening and closing
   tag.
   
   :Parameters:
   string $prefix - the text to place in front of a tag.
   string $suffix - the text to place after the tag.
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function setWhitespace($openPrefix="", $closePrefix="", $openSuffix="", $closeSuffix="")
   {
      $this->openPrefixWhiteSpace = $openPrefix;
      $this->closePrefixWhiteSpace = $closePrefix;
      $this->openSuffixWhiteSpace = $openSuffix;
      $this->closeSuffixWhiteSpace = $closeSuffix;
   }
   
   
   /**
   :Function: renderTag()
   
   :Description:
   Returns the HTML for displaying this tag and its contents.
   
   :Parameters:
   None
   
   :Return Value:
   None.
   
   :Notes:
   **/
   function renderTag()
   {
      $this->renderedHTML = $this->openPrefixWhiteSpace;
      
      if ($this->tagName == "")
      {
         return false;
      }
      
      $this->buildAttributeString();
      
      $this->renderedHTML .= "<" . $this->tagName . $this->attributesString;
      
      if ($this->closedTag == true)
      {
         $this->renderedHTML .= " />" . $this->closeSuffixWhiteSpace;
      }
      else
      {
         $this->renderedHTML .= ">" . $this->closePrefixWhiteSpace;
         if ($this->contents)
         {
            $this->renderedHTML .= $this->getRenderedContents();
         }
         $this->renderedHTML .= $this->openSuffixWhiteSpace . "</" . $this->tagName . ">";
         $this->renderedHTML .= $this->closeSuffixWhiteSpace;
      }
      return $this->renderedHTML;
   }
   
   
   /**
   :Function: getRenderedContents()
   
   :Description:
   Returns HTML for all the contents for this tag.
   
   :Parameters:
   None
   
   :Return Value:
   string - the rendered contents of this tag.
   
   :Notes:
   **/
   function getRenderedContents()
   {
      $renderedHTML = "";
      foreach ($this->contents as $contentItem)
      {
         if (is_object($contentItem))
         {
            if (method_exists($contentItem, "renderTag"))
            {
               $renderedHTML .= $contentItem->renderTag();
            }
         }
         else
         {
            $renderedHTML .= $contentItem;
         }
      }
      return $renderedHTML;
   } 
}


/**
Various tag classes. These tag classes define a separate class for each kind of
html tag we want to render. All of these tag classes define the tagName property
for their class. Some of them define default attributes, which you can 
overwrite in your applications with setAttribute(). Some of these classes also
define methods, like gcTableTag::addRow(), to make your coding life a little
easier.
**/
class gcHTMLTag extends gcTag
{
   function gcHTMLTag()
   {
      $this->tagName = "html";
      
   }
}

class gcHeadTag extends gcTag
{
   function gcHeadTag()
   {
      $this->tagName = "head";
      $this->setWhitespace("\n", "\n", "\n", "\n");
   }
   
   function addStylesheet($filePath)
   {
      $this->addContents(new gcLinkTag($filePath));
   }
   
   function removeStylesheet($filePath)
   {
      $this->removeContents("gcLinkTag", "href", $filePath);
   }
   
   function removeAllStylesheets()
   {
      $this->removeContents("gcLinkTag");
   }
   
   function addJavascriptFile($filePath)
   {
      $this->addContents(new gcJavascriptTag($filePath));
   }
   
   function removeJavascriptFile($filePath)
   {
      $this->removeContents("gcJavascriptTag", "src", $filePath);
   }
   
   function removeAllJavascriptFiles()
   {
      $this->removeContents("gcJavascriptTag");
   }
}


class gcJavascriptTag extends gcTag
{
   function gcJavascriptTag($filePath="")
   {
      $this->tagName = "script";
      
      if ($filePath != "")
      {
         $this->setAttribute('src', $filePath);
         $this->setWhitespace("\n\t", "", "", "\n");
      }
      else
      {
         $this->setWhitespace("\n", "\n", "\n", "\n");
      }
      
      $this->setAttribute('type', 'text/javascript');
      //$this->setAttribute('lang', 'JavaScript');
   }
}


class gcLinkTag extends gcTag
{
   function gcLinkTag($filePath)
   {
      $this->tagName = "link";
      $this->setWhitespace("\n\t", "", "", "\n");
      $this->closedTag = true;
      $this->setAttribute('href', $filePath);
      $this->setAttribute('type', 'text/css');
      $this->setAttribute('rel', 'stylesheet');
   }
}


class gcTitleTag extends gcTag
{
   function gcTitleTag($titleText = "")
   {
      $this->tagName = "title";
      $this->setWhitespace("\n\t", "", "", "\n");
      if ($titleText != "")
      {
         $this->addContents($titleText);
      }
   }
}


class gcBodyTag extends gcTag
{
   function gcBodyTag()
   {
      $this->tagName = "body";
      $this->setWhitespace("\n", "\n", "\n", "\n");
      $this->javascriptFiles = array();
   }
   
   function addJavascriptFile($filePath)
   {
   	$this->javascriptFiles[] = $filePath;
   }
   
   /*
   This version of renderTag() overwrites the original function in order to 
   append javascript files to the end of the body element instead of writing 
   them in the head element. WOW!
   
   After adding the javascript tags, it just calls its parent and returns the
   value as it normally would.
   */
   function renderTag()
   {
   	foreach ($this->javascriptFiles as $jsFile)
   	{
			$this->addContents(new gcJavascriptTag($jsFile));
   	}
   	return parent::renderTag();
   }
}

class gcPTag extends gcTag
{
   function gcPTag($contents="")
   {
      $this->tagName = "p";
      $this->setWhitespace("\n", "\n", "\n", "\n");
      
      if ($contents != "")
      {
         $this->addContents($contents);
      }
   }
}

class gcDivTag extends gcTag
{
   function gcDivTag()
   {
      $this->tagName = "div";
      $this->setWhitespace("\n", "", "", "\n");
   }
}

class gcUlTag extends gcTag
{
   function gcULTag()
   {
      $this->tagName = "ul";
      $this->setWhitespace("\n", "\n", "\n", "\n");
   }
}

class gcOlTag extends gcTag
{
   function gcOLTag()
   {
      $this->tagName = "ol";
      $this->setWhitespace("\n", "\n", "\n", "\n");
   }
}

class gcLiTag extends gcTag
{
   function gcLITag()
   {
      $this->tagName = "li";
      $this->setWhitespace("\n\t", "", "", "");
   }
}

class gcTableTag extends gcTag
{
   function gcTableTag()
   {
      $this->rows = array();
      $this->tagName = "table";
      $this->setAttribute("border", "0");
      $this->setAttribute("cellPadding", "0");
      $this->setAttribute("cellSpacing", "0");
   }
   
   function &addRow($attributes=false)
   {
      if (!$attributes)
      {
         $attributes = array();
      }
      
      $newRow = new gcTRTag();
      $newRow->setAttributes($attributes);
      $this->rows[] = $newRow;
      $this->addContents($newRow);
      return $newRow;
   }
   
   function setTableAttributes($width="100%", $padding="0", $spacing="0", $border="0")
   {
      $this->attributes['width'] = $width;
      $this->attributes['cellPadding'] = $padding;
      $this->attributes['cellSpacing'] = $spacing;
      $this->attributes['border'] = $border;
   }
}


class gcTHEADTag extends gcTableTag
{
   function gcTHEADTag()
   {
      $this->rows = array();
      $this->tagName = "thead";
      $this->setWhitespace("\n", "\n", "\n", "\n");
   }
}


class gcTRTag extends gcTag
{
   function gcTRTag()
   {
      $this->tagName = "tr";
      $this->cells = array();
      $this->isHeader = false;
      $this->setAttribute("vAlign", "top");
      $this->setWhitespace("\n", "\n", "\n", "\n");
   }
   
   function &addCells($arrayOfCellContents)
   {
      foreach ($cellContents as $content)
      {
         $cell = new gcTDTag();
         $cell->addContents($content);
         $this->addCell($cell);
      }
   }
   
   function &addCell($tdObject=false)
   {
      if (!is_a($tdObject, "gcTDTag"))
      {
         $contents = $tdObject;
         $tdObject = $this->isHeader ? new gcTHTag() : new gcTDTag();
         if ($contents)
         {
            $tdObject->addContents($contents);
         }
      }
      
      $this->cells[] = $tdObject;
      $this->addContents($tdObject);
      return $tdObject;
   }
}

class gcTHTag extends gcTag
{
   function gcTHTag()
   {
      $this->tagName = "TH";
      $this->setWhitespace("\n\t", "", "", "\n");
   }
}

class gcTDTag extends gcTag
{
   function gcTDTag()
   {
      $this->tagName = "TD";
      $this->setWhitespace("\n\t", "", "", "\n");
   }
}

class gcFormTag extends gcTag
{
   function gcFormTag()
   {
      $this->tagName = "form";
      $this->setWhitespace("\n", "\n", "\n", "\n");
      $this->setAttribute("action", $_SERVER['PHP_SELF']);
      $this->setAttribute("method", "POST");
   }
}

class gcTextInputTag extends gcTag
{
   function gcTextInputTag()
   {
      $this->tagName = "input";
      $this->setWhitespace("");
      $this->type = "text";
      $this->closedTag = true;
      $this->setAttribute("type", "text");
      $this->setAttribute("size", "20");
      $this->setAttribute("maxsize", "255");
   }
}

class gcHiddenInputTag extends gcTag
{
   function gcHiddenInputTag()
   {
      $this->tagName = "input";
      $this->setWhitespace("");
      $this->type = "text";
      $this->closedTag = true;
      $this->setAttribute("type", "hidden");
   }
}

class gcInputButtonTag extends gcTag
{
   function gcInputButtonTag()
   {
      $this->tagName = "input";
      $this->setWhitespace("");
      $this->type = "button";
      $this->closedTag = true;
      $this->setAttribute('type', "button");
   }
}

class gcSubmitButtonTag extends gcTag
{
   function gcSubmitButtonTag()
   {
      $this->tagName = "input";
      $this->setWhitespace("");
      $this->type = "text";
      $this->closedTag = true;
      $this->setAttribute("type", "submit");
   }
}

class gcCheckboxTag extends gcTag
{
   function gcCheckboxTag()
   {
      $this->tagName = "input";
      $this->setWhitespace("");
      $this->type = "text";
      $this->closedTag = true;
      $this->setAttribute("type", "checkbox");
      $this->setAttribute("checked", false);
   }
}

class gcRadioTag extends gcTag
{
   function gcRadioTag()
   {
      $this->tagName = "input";
      $this->setWhitespace("");
      $this->type = "text";
      $this->closedTag = true;
      $this->setAttribute("type", "radio");
      $this->setAttribute("checked", false);
   }
}

/*
class gcInputTag extends gcTag
{
   function gcInputTag()
   {
      $this->tagName = "input";
      $this->setWhitespace("");
      $this->type = "text";
      $this->closedTag = true;
   }
}
*/

class gcTextareaTag extends gcTag
{
   function gcTextareaTag()
   {
      $this->tagName = "textarea";
      $this->setWhitespace("");
      $this->setAttribute("cols", "25");
      $this->setAttribute("rows", "4");
   }
}

class gcSelectTag extends gcTag
{
   function gcSelectTag()
   {
      $this->tagName = "select";
      $this->setWhitespace("\n", "\n", "\n", "\n");
      $this->options = array();
      $this->setAttribute("size", "1");
      $this->setAttribute("multiple", false);
   }
   
   function setPreselectValue($value)
   {
      $options = $this->getOptions();
      {
         foreach($options as $option)
         {
            if (trim($option->getAttribute("value")) == trim($value))
            {
               $option->setAttribute("selected", true);
            }
         }
      }
   }
   
   function getOptions()
   {
      if (!$this->options)
      {
         foreach ($this->contents as $contentItem)
         {
            if (is_a($contentItem, "gcOptionTag"))
            {
               $this->options[] = $contentItem;
            }
         }
      }
      return $this->options;
   }
}

class gcOptionTag extends gcTag
{
   function gcOptionTag()
   {
      $this->tagName = "option";
      $this->setWhitespace("\n\t", "", "", "");
   }
}


class gcFileTag extends gcTag
{
   function gcFileTag()
   {
      $this->tagName = "input";
      $this->setAttribute("type", "file");
   }
}

class gcATag extends gcTag
{
   function gcATag()
   {
      $this->tagName = "a";
      $this->setWhitespace("", "");
   }
}

class gcNOBRTag extends gcTag
{
   function gcNOBRTag()
   {
      $this->tagName = "nobr";
      $this->setWhitespace("", "");
   }
}

class gcBRTag extends gcTag
{
   function gcBRTag()
   {
      $this->tagName = "br";
      $this->setWhitespace("", "");
      $this->closedTag = true;
   }
}

class gcImageTag extends gcTag
{
   function gcImageTag()
   {
      $this->tagName = "img";
      $this->setWhitespace("", "");
      //$this->setAttribute("border", "0");
      $this->closedTag = true;
   }
}

class gcSpanTag extends gcTag
{
   function gcSpanTag()
   {
      $this->tagName = "span";
      $this->setWhitespace("", "");
   }
}

class gcH1Tag extends gcTag
{
   function gcH1Tag()
   {
      $this->tagName = "h1";
      $this->setWhitespace("\n", "", "", "\n");
   }
}

class gcH2Tag extends gcTag
{
   function gcH2Tag()
   {
      $this->tagName = "h2";
      $this->setWhitespace("\n", "", "", "\n");
   }
}

class gcH3Tag extends gcTag
{
   function gcH3Tag()
   {
      $this->tagName = "h3";
      $this->setWhitespace("\n", "", "", "\n");
   }
}
/*
class gcTag extends gcTag
{
   function gcTag()
   {
      $this->tagName = "";
      $this->setWhitespace("", "");
   }
}
*/
?>
