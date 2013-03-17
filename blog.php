<?php
require_once("lib/classes/Page.inc");

$page = gcPage::Singleton();
$page->loadApplication("gcTop");
$page->loadApplication("gcBlog");
$page->loadApplication("gcBottom");
$page->runApplications();
$page->renderApplications();
?>
