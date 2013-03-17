<?php
require_once("lib/classes/Page.inc");
$page = gcPage::Singleton();
$page->setTitle("Invite People to a Social Event");
$page->loadApplication("gcTop");
$page->loadApplication("gcCOGInvitation");
$page->loadApplication("gcBottom");
$page->runApplication("gcCOGInvitation");
$page->renderApplications();
?>
