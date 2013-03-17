<?php
require_once("lib/classes/Page.inc");

$page = gcPage::Singleton();
$page->setTitle("Click on a quote to edit");
$page->addStyleSheet("mqt.css");
$page->loadApplication("gcTop");
$page->loadApplication("gcMovieQuoteTrivia");
$page->loadApplication("gcBottom");
$page->applications["gcMovieQuoteTrivia"]->setMode("list");
$page->runApplications();
$page->renderApplications();
?>
