<?php
require_once("lib/classes/Page.inc");

$page = gcPage::Singleton();
$page->setTitle("List Users");
$page->loadApplication("gcTop");
$page->loadApplication("gcListUsers");
$page->loadApplication("gcBottom");
$page->runApplications();
$page->renderApplications();
?>
