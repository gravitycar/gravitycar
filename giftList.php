<?php
require_once("lib/classes/Page.inc");

$page = gcPage::Singleton();
$page->setTitle("Gift Lists - buy stuff!");
$page->loadApplication("gcTop");
$page->loadApplication("gcGiftList");
$page->loadApplication("gcBottom");
$page->runApplications();
$page->renderApplications();
?>
