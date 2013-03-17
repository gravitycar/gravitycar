<?php

require_once("lib/classes/Form.inc");
class gcGiftList extends gcForm implements Application
{
   public $allUsers = array();
   public $gifts = array();
   public $table = false;
   
   function gcGiftList()
   {
      $this->init();
      $this->page->addJavascriptFile("js/giftLists.js");
      $this->page->addStylesheet("giftList.css");
   }
   
   
   function run()
   {
      $this->getAllGifts();
      $this->getAllUsers();
   }
   
   
   function getAllUsers()
   {
      $query = $this->db->getSelectQuery("gcUsers", "id as userID");
      $query->addColumn("concat(gcUsers.firstName, ' ', gcUsers.lastName) as recipient", " ");
      $query->addWhereClause("id", "1");
      $query->setOrderByClauses("lastName, firstName");
      $this->db->runQuery($query);
      $data = $this->db->getHash();
      foreach ($data as $user)
      {
         $this->allUsers[$user['userID']] = $user['recipient'];
      }
   }
   
   
   function getAllGifts()
   {
      $query = $this->db->getSelectQuery(array("gcUsers", "gcGifts"), array());
      $query->addColumn("gcUsers.id as userID", " ");
      $query->addColumn("concat(gcUsers.firstName, ' ', gcUsers.lastName) as recipient", " ");
      $query->setColumns("*", "gcGifts");
      $joinClause = $query->addWhereClause("id", "gcGifts.userID");
      $joinClause->noQuoteValues();
      $query->setOrderByClauses("gcUsers.lastName", "gcUsers.firstName", "gcGifts.name");
      $result = $this->db->runQuery($query);
      $data = $this->db->getHash($result);
      
      foreach ($data as $gift)
      {
         $this->gifts[$gift['userID']][] = $gift;
      }
   }
   
   
   function createUsersList()
   {
      $this->usersDiv = $this->renderer->createDivTag();
      $this->usersDiv->setAttribute("id", "usersListContainer");
      
      $this->usersList = $this->renderer->createULTag();
      $this->usersList->setAttribute("id", "usersList");
      
      foreach ($this->allUsers as $userID => $userName)
      {
         $linkAttrs['href'] = "javascript:void(0);";
         $linkAttrs['onClick'] = "showUserGifts($userID);";
         $link = $this->renderer->createATag($userName, $linkAttrs);
         $listItem = $this->renderer->createLITag($link, array("id"=>"userListItem_$userID"));
         $this->usersList->addContents($listItem);
      }
      $this->usersDiv->addContents($this->usersList);
      return $this->usersDiv;
   }
   
   
   function createGiftList()
   {
      $containerForAllLists = $this->renderer->createDivTag();
      $containerForAllLists->setAttribute("id", "allGiftListsContainer");
      reset($this->gifts);
      
      foreach ($this->allUsers as $userID => $userName)
      {
         $giftList = $this->gifts[$userID];
         if (count($giftList) == 0)
         {
            $containerForAllLists->addContents($this->userHasNoGifts($userID));
            continue;
         }
         
         $listContainer = $this->renderer->createDivTag();
         $listContainer->setAttribute("id", "giftList_$userID");
         $listContainer->setAttribute("userID", "$userID");
         $listContainer->setAttribute("class", "giftListContainer");
         $table = $this->renderer->createTableTag();
         $table->setTableAttributes("100%");
         $table->setAttribute("class", "tabularData");
         $header = $table->addRow();
         $header->isHeader = true;
         $header->addCell("Gifts for " . $this->allUsers[$userID]);
         $header->addCell("Purchased");
         $header->cells[1]->setAttribute("width", "1");
         
         $rowClass = "light";
         foreach ($giftList as $gift)
         {
            $row = $table->addRow();
            $row->setAttribute("class", $rowClass);
            $name = stripslashes($gift['name']);
            $url = $gift['url'];
            $desc = "<br>" . stripslashes($gift['description']);
            $purch = $gift['purchased'];
            
            if (!empty($url))
            {
               $link = $this->renderer->createATag($name, array('href'=>$url));
               $link->setAttribute("target", "_store");
            }
            else
            {
               $link = $this->renderer->createSpanTag($name);
            }
            
            $linkCell = $row->addCell($link);
            
            if ($this->auth->checkAuth() && ($this->auth->userOwnsRecord($userID) || $this->auth->isAdmin()))
            {
               $editLink = $this->renderer->createATag("edit");
               $editLink->setAttribute("class", "editLink");
               $editLink->setAttribute("href", "editGifts.php?id=" . $gift['id']); 
               $linkCell->addContents($editLink);
            }
            
            $linkCell->addContents($desc);
            
            $checkbox = $this->renderer->createCheckboxTag(array('value'=>'1'));
            $checkbox->setAttribute("id", $gift['id']);
            $checkbox->setAttribute("name", $gift['id']);
            $checkbox->setAttribute("align", "center");
            $checkbox->setAttribute("onClick", "updatePurchasedState(this);");
            $checkbox->setAttribute("checked", $gift['purchased'] == 1);
            $checkbox->setAttribute("giftName", $gift['name']);
            $checkboxCell = $row->addCell($checkbox);
            $checkboxCell->setAttribute('align', 'center');
            $checkboxCell->setAttribute('class', 'checkboxCell');
            $rowClass = $rowClass == "light" ? "dark" : "light";
         }
                  
         $listContainer->addContents($table);
         $containerForAllLists->addContents($listContainer);
      }
      return $containerForAllLists;
   }
   
   
   function &userHasNoGifts($userID)
   {
      $userName = $this->allUsers[$userID];
      $listContainer = $this->renderer->createDivTag("There are no gifts registered for $userName.");
      $listContainer->setAttribute("id", "giftList_$userID");
      $listContainer->setAttribute("align", "center");
      return $listContainer;
   }
   
   
   function render()
   {
      $this->htmlObjects[] = $this->createUsersList();
      $this->htmlObjects[] = $this->createGiftList();
      return $this->htmlObjects;
   }
}

?>
