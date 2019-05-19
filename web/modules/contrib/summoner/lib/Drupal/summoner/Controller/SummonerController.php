<?php
/**
 * @file
 * Contains \Drupal\summoner\Controller\SummonerController.
 */

namespace Drupal\summoner\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\summoner\LibraryList;

/**
 * Page callback controller for fetching asset uri's.
 */
class SummonerController {
  /**
   * @param LibraryList $libraries
   * @return AjaxResponse
   */
  public function load(LibraryList $libraries) {
    $attached['#attached'] = array(
      'library' => $libraries,
      'js' => array(
        'state' => array(
          'data' => array('summonerState' => $libraries->toState()),
          'type' => 'setting',
        ),
        'inline' => array(
          'type' => 'inline',
          'group' => 'summon',
          'data' => 'Drupal.summonerAttachBehavior("' . $libraries . '");',
        ),
      ),
    );
    drupal_render($attached);
    $response = new AjaxResponse();
    return $response;
  }
}
