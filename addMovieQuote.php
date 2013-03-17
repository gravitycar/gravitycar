<?php
require_once("lib/classes/Page.inc");

$page = gcPage::Singleton();
$page->setTitle("Add a new movie quote");
$page->loadApplication("gcTop");
$page->loadApplication("gcMQTQuestion");
$page->loadApplication("gcBottom");
$page->runApplications();
$page->renderApplications();
?>
