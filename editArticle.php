<?php
require_once("lib/classes/Page.inc");

$page = gcPage::Singleton();
$page->setTitle("Manage Articles");
$page->loadApplication("gcTop");
$page->loadApplication("gcArticle");
$page->loadApplication("gcBottom");
$page->applications["gcArticle"]->setMode("form");
$page->runApplications();
$page->renderApplications();
?>
