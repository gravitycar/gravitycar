<?php
require_once("lib/classes/Page.inc");

$page = gcPage::Singleton();
$page->setTitle("Article Reader");
$page->loadApplication("gcTop");
$page->loadApplication("gcArticle");
$page->loadApplication("gcBottom");
$page->applications["gcArticle"]->setMode("text");
$page->applications["gcArticle"]->allowComments(false);
$page->runApplications();
$page->renderApplications();
?>
