<?php
require_once("lib/classes/Page.inc");

$page = gcPage::Singleton();
$page->setTitle("Movie Quote Trivia!");
$page->addStyleSheet("mqt.css");
$page->loadApplication("gcTop");
$page->loadApplication("gcMovieQuoteTrivia");
$page->loadApplication("gcBottom");
$page->runApplications();
$page->renderApplications();
?>
