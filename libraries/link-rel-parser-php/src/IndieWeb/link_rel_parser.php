<?php
namespace IndieWeb;

/* 
http_rels, head_http_rels by Tantek Çelik http://tantek.com/
license: http://creativecommons.org/publicdomain/zero/1.0/
*/

// in $h: HTTP headers as a string
// returns: array of rel values as indices to arrays of URLs
function http_rels($h) {
  $h = preg_replace("/(\r\n|\r)/", "\n", $h);
  $h = explode("\n", preg_replace("/(\n)[ \t]+/", " ", $h));
  $rels = array();
  foreach ($h as $f) {
    if (!strncmp($f, 'X-Pingback: ', 12)) {
      // convert to a link header and have common code handle it
      $f = 'Link: <' . trim(substr($f, 12)) . '>; rel="pingback"';
    }
    if (!strncmp($f, 'Link: ', 6)) {
      $links = explode(', ', trim(substr($f, 6)));
      foreach ($links as $link) {
        $hrefandrel = explode('; ', $link);
        $href = trim($hrefandrel[0], '<>');
        $relarray = '';
        foreach ($hrefandrel as $p) {
          if (!strncmp($p, 'rel=', 4)) {
            $relarray = explode(' ', trim(substr($p, 4), '"\''));
            break;
          }
        }
        if ($relarray !== '') { // ignore Link: headers without rel
          foreach ($relarray as $rel) {
            $rel = strtolower(trim($rel));
            if ($rel != '') {
              if (!array_key_exists($rel, $rels)) { 
                $rels[$rel] = array(); 
              }
              if (!in_array($href, $rels[$rel])) {
                $rels[$rel][] = $href;
              }
            }
          }
        }
      }
    }
  }
  return $rels;
}

// in $url: URL to get HTTP HEAD Link (and effective/x-extended) rels 
// returns: array of 
//        "status"=> HTTP status code, 
//        "rels" array with 
function head_http_rels($url) {
	$c = curl_init();
	curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($c, CURLOPT_URL, $url);
	curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 2);
	curl_setopt($c, CURLOPT_TIMEOUT, 4);
	curl_setopt($c, CURLOPT_USERAGENT, 'head_http_rels function');
	curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($c, CURLOPT_SSL_VERIFYPEER , false );
  curl_setopt($c, CURLOPT_SSL_VERIFYHOST , false );
  curl_setopt($c, CURLOPT_HEADER, true);
  curl_setopt($c, CURLOPT_NOBODY, true);
  $h = curl_exec($c);
	$i = curl_getinfo($c);
  curl_close($c);
  unset($c);

  $r = array();
  $r['status'] = $i['http_code'];
  $r['rels'] = http_rels($h);
  return $r;
}
?>
