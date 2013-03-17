<?php
require_once("lib/classes/Page.inc");

$page = gcPage::Singleton();
$page->setTitle("Comment Writer");
$page->loadApplication("gcComment");
$page->applications["gcComment"]->setMode("raw");
$page->runApplications();
$page->applications["gcComment"]->set("userName", $page->auth->getUserName());
$page->renderApplication("gcComment");
print($page->renderer->body->contents[0]->renderTag());
?>
