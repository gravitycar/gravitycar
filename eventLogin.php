<?php
require_once("lib/classes/Page.inc");
$page = gcPage::Singleton();
$page->setTitle("Login For Your Event");
$page->loadApplication("gcTop");
$page->loadApplication("gcCOGLogin");
$page->loadApplication("gcBottom");
$page->runApplication("gcCOGLogin");
$page->renderApplications();
?>
