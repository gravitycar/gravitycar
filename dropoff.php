<?php
require_once("lib/classes/Page.inc");
$page = gcPage::Singleton();
$page->setTitle("Drop off files for Mike Andersen");
$page->loadApplication("gcTop");
$page->loadApplication("gcDropoff");
$page->loadApplication("gcBottom");
$page->runApplication("gcDropoff");
$page->renderApplications();
?>
