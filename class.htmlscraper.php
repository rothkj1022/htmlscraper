<?php
/**
* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
*      HtmlScraper Class By Kevin Roth on 3/2/2015
*       --Saving the world, one website at a time--
*
* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
*
* @version 0.0.1
* @package HtmlScraper
* @author Kevin Roth
*
*/

class HtmlScraper {
	private $url;
	private $html;
	private $pattern;
	private $keys;
	private $data;
	//public $matches;

	public function __construct() {
		//don't do anything yet
	}

	public function setUrl($url) {
		$this->url = $url;
	}

	public function fetchContent() {
		if ($html = @file_get_contents($this->url)) {
			return $html;
		}
		return false;
	}

	public function getHtml() {
		return $this->html;
	}

	public function setHtml($html) {
		$this->html = $html;
	}

	public function setPattern($pattern, $keys = array()) {
		$this->pattern = $pattern;
		$this->keys = $keys;
	}

	public function parseHtml() {
		preg_match_all($this->pattern, $this->html, $matches);
		if (sizeof($matches) > 0) {
			for ($i = 1; $i < sizeof($matches); $i++) {
				for($j = 0; $j < sizeof($matches[$i]); $j++) {
					//$i are attributes, $j are values
					$this->data[$j][$this->keys[($i-1)]] = $matches[$i][$j];
				}
			}
			return $this->data;
		}
		return false;
	}

	public function getData() {
		return $this->data;
	}

	//allow for setting the data in case we need to format dates, etc before parsing
	public function setData($data) {
		$this->data = $data;
	}

	public function getJson() {
		return json_encode($this->data);
	}

	public function getRss($rssUrl, $rssTitle, $rssDescription, $scrapeUrl, $encoding = 'UTF-8', $language = 'en-us') {
		//header ("Content-Type: text/xml; charset=UTF-8");
		$rss = chr(60).'?xml version="1.0" encoding="'.$encoding.'" ?'.chr(62)."\n";
		$rss .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">'."\n".
			'	<channel>'."\n".
			'		<atom:link href="'.$rssUrl.'" rel="self" type="application/rss+xml" />'."\n".
			'		<title>'.$rssTitle.'</title>'."\n".
			'		<description>'.$rssDescription.'</description>'."\n".
			'		<link>'.$scrapeUrl.'</link>'."\n".
			'		<language>'.$language.'</language>'."\n";
		//loop through data
		foreach ($this->data as $row) {
			/*
			keys for rss output (all optional): title, link, description, author, category, comments, enclosure, guid, pubDate, source
			cite: http://cyber.law.harvard.edu/rss/rss.html
			*/
			$guid = ((!empty($row['guid'])) ? $row['guid'] : $row['link']);

			$rss .= '		<item>'."\n";
			if (!empty($row['title'])) {
				$rss .= '			<title><![CDATA['.$row['title'].']]></title>'."\n";
			}
			if (!empty($row['description'])) {
				$rss .= '			<description><![CDATA['.$row['description'].']]></description>'."\n";
			}
			if (!empty($row['pubDate'])) {
				$rss .= '			<pubDate>'.$row['pubDate'].'</pubDate>'."\n";
			}
			if (!empty($guid)) {
				$rss .= '			<guid isPermaLink="true">'.$guid.'</guid>'."\n";
			}
			if (!empty($row['link'])) {
				$rss .= '			<link>'.$row['link'].'</link>'."\n";
			}
			if (is_array($row['source']) && (sizeof($row['source']) > 0)) {
				$rss .= '			<source url="'.$row['source']['url'].'">'.$row['source']['title'].'</source>'."\n";
			}
			if (is_array($row['enclosure']) && (sizeof($row['enclosure']) > 0)) {
				$enclosureType = $row['enclosure']['type'];
				if ($enclosureType == 'audio/mp3') {
					$enclosureType = 'audio/mpeg'; //correct bad (yet common) enclosure type
				}
				$rss .= '			<enclosure url="'.$row['enclosure']['url'].'" length="'.$row['enclosure']['length'].'" type="'.$enclosureType.'" />'."\n";
			}
			$rss .= '		</item>'."\n";
		}
		//append rss footer
		$rss .= '	</channel>'."\n".
			'</rss>'."\n";
		return $rss;
	}
}
