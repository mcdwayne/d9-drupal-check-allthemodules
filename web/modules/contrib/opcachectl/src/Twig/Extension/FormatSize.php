<?php

namespace Drupal\opcachectl\Twig\Extension;

class FormatSize extends \Twig_Extension {

	public function getFilters() {
		return array(
			new \Twig_SimpleFilter('format_size', [$this, 'formatSize']),
		);
	}

	public function getName() {
		return 'format_size';
	}

	function formatSize($size) {
		return format_size($size);
	}

}
