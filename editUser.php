<?php

require_once("lib/classes/Page.inc");


$page = gcPage::Singleton();
$page->setTitle("Gravitycar");
$page->loadApplication("gcTop");
$page->loadApplication("gcUser");
$page->loadApplication("gcBottom");
$page->runApplications();
$page->renderApplications();
//$page->auth->clearAuth();
?>
