<?php

include "helper/simple_html_dom.php";

define("NOT_FOUND_MSG","Could not find a matching article. Please try again with a different search term.");
define("TOO_LARGE_MSG", "The contrived file was too large to be parseable!");

abstract class WikiSites
{
	const WIKIPEDIA = 1;
	const RATIONAL_WIKI = 2;
}

class WikiFilter
{
	var $url;
	function __construct($site)
	{
		global $url;
		switch ($site) {
			case WikiSites::WIKIPEDIA :
				$url = "https://en.wikipedia.org/wiki/";
				break;
			case WikiSites::RATIONAL_WIKI :
				$url = "https://rationalwiki.org/wiki/";
				break;
			default :
				$url = "https://en.wikipedia.org/wiki/";
		}
	}

	function startsWith($haystack, $needle)
	{
	     $length = strlen($needle);
	     return (substr($haystack, 0, $length) === $needle);
	}

	function getRaw($term)
	{
		global $url;
		$term = str_replace(" ", "_", $term);
		return file_get_contents($url.$term);
	}

	function deepGetRaw($term)
	{
		global $url;
		
		$term = str_replace(" ", "_", $term);
		$html_str = @file_get_contents($url.$term);

		if($html_str === false)
		{
			return 0;
		}
		
		$html = str_get_html($html_str);

		if($html === false)
		{
			return -1;
		}

		$is_search_res = false;
		foreach($html->find(".shortdescription") as $verf)
		{
			if(strpos($verf->plaintext, "Disambiguation page providing links to articles with similar titles") !== false)
			{
				$is_search_res = true;
				break;
			}
		}

		if($is_search_res)
		{
			foreach($html->find("#mw-content-text .mw-parser-output > ul > li > a") as $link)
			{
				if($this->startsWith($link->href, "/wiki/"))
				{
					return $this->getRaw(str_replace("/wiki/", "", $link->href));
				}
			}
		}
		else
		{
			return $html_str;
		}
	}

	function filterContent($pageContent)
	{
		$html = str_get_html($pageContent);
		$content = $html->find("#mw-content-text")[0];

		//remove all references and citations
		$paras = $content->find("p");
		foreach($paras as $para)
		{
			foreach($para->find(".reference, .references, .noprint") as &$reference)
			{
				$reference->outertext = '';
			}
		}

		return join('',$paras);
	}

	function getPlaintext($content)
	{
		$html = str_get_html($content);
		return $html->plaintext;
	}

	function Get($term)
	{
		$content = $this->filterContent($this->getRaw($term));
		return $this->getPlaintext($content);
	}

	function DeepGet($term)
	{
		$raw = $this->deepGetRaw($term);
		if($raw === 0)
		{
			return NOT_FOUND_MSG;
		}
		else if($raw === -1)
		{
			return TOO_LARGE_MSG;
		}
		else
		{
			$content = $this->filterContent($raw);
			return $this->getPlaintext($content);
		}
	}
}