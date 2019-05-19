<?php

namespace Drupal\split\Controller;

use Drupal\Core\Controller\ControllerBase;

class SplitController extends ControllerBase {
	public function splitStat() {
		$output[] = array(
			'#type' => 'table',
      		'#header' => array('Recieved'),
      		'#rows' => getRecieveStat(),
		);
		$output[] = array(
			'#type' => 'table',
      		'#header' => array('Send'),
      		'#rows' => getSendStat(),
		);
		return $output;
	}
}
