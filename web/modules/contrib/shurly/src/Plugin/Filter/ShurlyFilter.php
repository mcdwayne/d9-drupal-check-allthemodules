<?php

namespace Drupal\shurly\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * @Filter(
 *   id = "shurly_filter",
 *   title = @Translation("Shorten all outgoing URL's"),
 *   description = @Translation("All links starting with http or https will be replaced."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE
 * )
 */
class ShurlyFilter extends FilterBase {
  public function process($text, $langcode) {
	  preg_match_all('/<a[^>]*href="(http[^"]*)"[^>]*>/i', $text, $links);
	  if (!empty($links)) {
	    $links = $links[1];
	    foreach ($links as $key => $link) {
	      $short_url = shurly_shorten($link);
	      if ($short_url['success'] === TRUE) {
	        $text = str_replace('"' . $link . '"', '"' . $short_url['shortUrl'] . '"', $text);
	      }
	    }
	  }
	  return new FilterProcessResult($text);
	}
}
