<?php
require_once("lib/classes/Page.inc");
$page = gcPage::Singleton();
$page->setTitle("Event Organizer - ");
$page->loadApplication("gcTop");
$page->loadApplication("gcCOGChart");
$page->loadApplication("gcBottom");
$page->runApplication("gcCOGChart");
$page->renderApplications();
?>
