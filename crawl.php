<?php
set_time_limit(0);
ob_start();

$urlsToCrawl = [$_GET["root"]];
$urlsCrawled = [];
$count = 0;

while(count($urlsToCrawl) > 0){
	$url = array_pop($urlsToCrawl);
	
	if(isset($urlsCrawled[$url])){
		continue;
	}
	
	echo 'Crawling: ', $url, '<br>';
	flush();
	ob_flush();
	
	$handle = fopen($url, 'r');
	if($handle === FALSE) 
		echo 'Foud broken link ', $url, '<br>';

	$doc = DOMImplementation::createDocument(null, 'html',
				DOMImplementation::createDocumentType('html', 
				'-//W3C//DTD XHTML 1.0 Transitional//EN', 
				'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'));
	@$doc->loadHTML(str_replace('&nbsp;', '', stream_get_contents($handle)));

	$urlsCrawled[$url] = true;
	
	$links = $doc->getElementsByTagName('a');
	ob_flush();
	
	$f = 0;
	$url = parse_url($url);
	
	foreach($links as $link){
		$href = $link->getAttribute('href');
		
		if(isset($urlsCrawled[$href])){
			continue;
		}
		
		if(!empty($href)){
			$href = preg_replace("/^(?:(?:http:|https:)\/\/)?www\./", '', $href);
			$h = parse_url($href);
			
			if(array_search($h['scheme'], ['http', 'https', 'mailto']) === FALSE){
				$h = $url['scheme'].'://'.$url['host'];
				
				if($href[0] == '#')
					continue;
				
				if($href[0] != '/'){
					$sub = explode('/', $url['path']);
					array_pop($sub);
					$sub = join('/', $sub);
					
					if($sub[0] != '/')
						$sub = '/'.$sub;
					
					$h .= $sub;
				}
				
				// echo 'Info ', $href, ' - ', $h, ' - ', $url['path'], '<br>';
				// echo 'Add ', $h.$href, '<br>';
				
				$href = $h.$href;
			}
			else {
				if($h['scheme'] == 'mailto'){
					$urlsCrawled[$href] = false;
					echo 'Skipped mailto link: ', $href, '<br>';
					continue;
				}
				
				if($h['host'] != $url['host']){
					$urlsCrawled[$href] = false;
					
					echo 'Skipped external host: ', $h['host'], '<br>';
					continue;
				}
			}
			
			++$f;
			$href = str_replace(' ', '%20', $href);
			$urlsToCrawl[] = $href;
		}
	}
	
	if($f > 0){
		echo 'Queued ', $f, ' links<br>';
		flush();
	}
	
	// if(++$count == 10)
		// die('Died from too much work!');
}