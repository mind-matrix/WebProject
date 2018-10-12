<?php
error_reporting(E_ALL);
ini_set("display_errors", 1);
include 'site-wrappers/wiki.php';

if(isset($_GET['q']) && isset($_GET['res']))
{
	$res = $_GET['res'];
	if($res == "wp")
	{
		$wiki = new WikiFilter(WikiSites::WIKIPEDIA);
	}
	else
	{
		$wiki = new WikiFilter(WikiSites::RATIONAL_WIKI);
	}
	echo $wiki->DeepGet($_GET['q']);
}