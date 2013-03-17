<?php
require_once("lib/classes/Page.inc");
$page = gcPage::Singleton();
$page->setTitle("Create a Social Event!");
$page->loadApplication("gcTop");
$page->loadApplication("gcCOGSocialEvent");
$page->loadApplication("gcBottom");
$page->runApplication("gcCOGSocialEvent");
$page->renderApplications();
?>
