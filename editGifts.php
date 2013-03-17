<?php
require_once("lib/classes/Page.inc");

$page = gcPage::Singleton();
$page->setTitle("Manage Gifts");
$page->loadApplication("gcTop");
$page->loadApplication("gcGift");
$page->loadApplication("gcBottom");
$page->runApplications();
$page->renderApplications();
?>
