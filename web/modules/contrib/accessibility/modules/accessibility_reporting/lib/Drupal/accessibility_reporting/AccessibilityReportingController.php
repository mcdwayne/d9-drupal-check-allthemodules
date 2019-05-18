<?php

namespace Drupal\accessibility_reporting;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Config;
use Symfony\Component\HttpFoundation\JsonResponse;
use \Symfony\Component\HttpFoundation\Request;

class AccessibilityReportingController extends ControllerBase {

  public function __construct() {
  }

  /**
   * Returns new instance
   */
  public static function create(ContainerInterface $container) {
    return new AccessibilityReportingController();
  }
  /**
   * Returns a list of QUAIL tests to run
   *
   * @return
   *   JSON array of tests to call
   */
  public function report(Request $request) {
  	if(!$request->get('results', FALSE)) {
			return;
		}
		$result = TRUE;
		foreach($request->get('results', array()) as $result) {
			array_walk($result, 'check_plain');
	    db_delete('accessibility_reporting')
				->condition('entity_type', $result['entity_type'])
				->condition('entity_id', $result['entity_id'])
				->execute();
			if(isset($result['total']) && count($result['total'])) {
				foreach($result['total'] as $test_id => $total) {
					db_insert('accessibility_reporting')
						->fields(array('test_id' => $test_id,
													 'entity_type' => $result['entity_type'],
	                         'bundle'      => $result['bundle'],
													 'entity_id' => $result['entity_id'],
													 'field' => $result['field'],
													 'total' => $total,
													 ))
						->execute();
				}
			}
		}

	  return new JsonResponse(array('result' => $result));
  }

}