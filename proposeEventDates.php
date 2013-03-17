<?php
require_once("lib/classes/Page.inc");
$page = gcPage::Singleton();
$page->setTitle("Propose Dates for a Social Event");
$page->loadApplication("gcTop");
$page->loadApplication("gcCOGProposeDate");
$page->loadApplication("gcBottom");
$page->runApplication("gcCOGProposeDate");
$page->renderApplications();
?>
