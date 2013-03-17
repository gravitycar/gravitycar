<?php
require_once("lib/classes/Page.inc");
$page = gcPage::Singleton();
$page->setTitle("Select a Social Event!");
$page->loadApplication("gcTop");
$page->loadApplication("gcCOGSocialEvent");
$page->loadApplication("gcBottom");
$page->applications["gcCOGSocialEvent"]->setMode("list");
$page->runApplication("gcCOGSocialEvent");
$page->renderApplications();
?>
