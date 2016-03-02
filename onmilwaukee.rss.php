<?php
include('class.htmlscraper.php');

date_default_timezone_set('America/Chicago');

$scrapeUrl = 'http://onmilwaukee.com/myOMC/tags/articles?id=Packers';
$pattern = '/<article.*>\s*.*?<img.*src=[\'|"](.*)[\'|"].*\s*.*\s*.*<h1><a href=[\'|"](.*)[\'|"]>(.*)<\/a><\/h1>\s*.*<p><b>Published (.*)<\/b><\/p>\s*.*<p>(.*)<\/p>/';
/*
keys for rss output (all optional): title, link, description, author, category, comments, enclosure, guid, pubDate, source
cite: http://cyber.law.harvard.edu/rss/rss.html
*/
$keys = array('imgUrl', 'link', 'title', 'pubDate', 'description');
$rssUrl = $_SERVER['REQUEST_URI']; //this page url
$rssTitle = 'OnMilwaukee.com Packers News Feed via Packernet.com';
$rssDescription = 'Packers news article links.';

//now do the work
$scraper = new HtmlScraper();
$scraper->setUrl($scrapeUrl);
if ($htmlRaw = $scraper->fetchContent()) {
	//we got the content
	$scraper->setHtml($htmlRaw);
	$scraper->setPattern($pattern, $keys);
	if ($data = $scraper->parseHtml()) {
		//parsing was successful

		//fix a little bit of the data...
		for ($i = 0; $i < sizeof($data); $i++) {
			//pubDate does not contain a timestamp. Let's set it to noon.
			$tmpDate = new DateTime($data[$i]['pubDate']);
			$tmpDate->modify("+12 hours");
			$data[$i]['pubDate'] = $tmpDate->format("r");

			//prepend the domain name to the image urls and links
			$data[$i]['imgUrl'] = 'http://onmilwaukee.com'.$data[$i]['imgUrl'];
			$data[$i]['link'] = 'http://onmilwaukee.com'.$data[$i]['link'];
		}

		//save the updated data back to the object
		$scraper->setData($data);

		//output the rss
		if ($rssXml = $scraper->getRss($rssUrl, $rssTitle, $rssDescription, $scrapeUrl)) {
			header ("Content-Type: text/xml; charset=UTF-8");
			echo $rssXml;
		}
	}
}
