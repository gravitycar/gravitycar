<?php
require_once("lib/classes/Page.inc");

$page = gcPage::Singleton();
$page->setTitle("Mike Andersen's home page");
$page->loadApplication("gcTop");
$page->loadApplication("gcHome");
$page->loadApplication("gcSkinner");
$page->loadApplication("gcBottom");
$page->runApplications();
$page->renderApplications();
?>
