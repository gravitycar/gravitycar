<?php
require_once("lib/classes/Page.inc");

$page = gcPage::Singleton();
$page->setTitle("Log in");
$page->loadApplication("gcTop");
$page->loadApplication("gcLogin");
$page->loadApplication("gcBottom");
$page->runApplications();
$page->renderApplications();

?>
