<?php
/**
* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
*      HtmlScraper Class By Kevin Roth on 3/2/2015
*       --Saving the world, one website at a time--
*
* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
*
* @version 0.0.2
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
		} else if ($html = $this->getContents($this->url)) {
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

	// get remote file contents
	private function getContents($uri, $charLimit = 0) {
		$uri = urldecode($uri);
		$uri = str_replace(' ', '%20', $uri);
		$theContent = '';

		$writefn = function($ch, $chunk) {
			global $theContent, $charLimit;

			$len = strlen($theContent) + strlen($chunk);
			if ($len >= $charLimit ) {
				$theContent .= substr($chunk, 0, $charLimit-strlen($theContent));
				//echo strlen($theContent) , ' ', $theContent;
				$theContent = $theContent;
				return -1;
			}

			$theContent .= $chunk;
			return strlen($chunk);
		};

		$userAgent = 'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; AS; rv:11.0) like Gecko';
		$ch = curl_init( $uri );
		curl_setopt($ch, CURLOPT_HEADER, false); // return headers
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // follow redirects
		curl_setopt($ch, CURLOPT_ENCODING, ''); // empty string means handle all encodings
		curl_setopt($ch, CURLOPT_USERAGENT, $userAgent); // who am i
		curl_setopt($ch, CURLOPT_AUTOREFERER, true); // set referer on redirect
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // timeout on connect
		curl_setopt($ch, CURLOPT_TIMEOUT, 10); // timeout on response
		curl_setopt($ch, CURLOPT_MAXREDIRS, 10); // stop after 10 redirects
		curl_setopt($ch, CURLOPT_NOBODY, false); // only get the headers
		curl_setopt($ch, CURLOPT_FILETIME, true); //retrieve the file modification date
		curl_setopt($ch, CURLOPT_WRITEFUNCTION, $writefn);
		if ($charLimit > 0) {
			curl_setopt($ch, CURLOPT_RANGE, '0-'.$charLimit);
			curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
		} else {
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // return string instead of outputting directly to browser
		}

		if ($result = curl_exec( $ch )) {
			$headers = curl_getinfo( $ch );
			if ($charLimit == 0) {
				$theContent = $result;
			}
		} else {
			$err = curl_errno( $ch );
			$errmsg = curl_error( $ch );
		}
		curl_close( $ch );

		return $theContent;
	}
}
