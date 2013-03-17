<?php
require_once("lib/classes/Page.inc");

$page = gcPage::Singleton();
$page->setTitle("Select an Article to Edit");
$page->loadApplication("gcTop");
$page->loadApplication("gcArticle");
$page->loadApplication("gcBottom");
$page->applications["gcArticle"]->setMode("list");
$page->runApplications();
$page->renderApplications();
?>
