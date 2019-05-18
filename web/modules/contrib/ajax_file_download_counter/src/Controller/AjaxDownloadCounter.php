<?php

namespace Drupal\ajax_file_download_counter\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Defines controller for ajax_dlcount
 */
class AjaxDownloadCounter extends ControllerBase{

	/**
	 * {@inheritdoc}
	 */
	public function IncreaseCount($fid){
		//error_log("ajax_dlcount_count: $fid");
		$history = db_query('SELECT COUNT(*) FROM file_dlcount_history WHERE ip = :ip AND fid = :fid', array(':ip' => \Drupal::request()->getClientIp(), ':fid' => $fid))->fetchField();

		  $count = db_query('SELECT count FROM file_dlcount WHERE fid = :fid', array(':fid' => $fid))->fetchField();
		  // if(!$history) {
		    if($count != '') {
		      $count++;
		      db_query('UPDATE file_dlcount SET count = :count WHERE fid = :fid', array(':fid' => $fid, ':count' => $count));
		    } else {
		      $count = 1;
		      db_query('INSERT INTO file_dlcount (fid, count) values (:fid, 1)', array(':fid' => $fid));
		    }

		    db_query('INSERT INTO file_dlcount_history (fid, ip, timestamp) values (:fid, :ip, :timestamp)', array(':ip' => \Drupal::request()->getClientIp(), ':fid' => $fid, ':timestamp' => time()));
		  // }
		  $params = array('dlcount' => $count);
		  return new JsonResponse($params);
	}
}