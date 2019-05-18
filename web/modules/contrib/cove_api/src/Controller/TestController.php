<?php
/**
* @file
* Contains \Drupal\cove_api\Controller\TestController.
*/
namespace Drupal\cove_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\cove_api\CoveRequest;

class TestController extends ControllerBase
{
  public function testPage()
  {
    $args = [
      'filter_tp_media_object_id' => '2365749120',
      'w' => 640,
      'h' => 480,
      'chapterbar' => FALSE,
      'autoplay' => FALSE,
    ];

    $request = new CoveRequest();
    $response = $request->request('videos', $args);
    //$response = $request->request('programs', array('filter_producer__name' => 'WETA'));
    //$response = $request->request('programs');

    $player = $response->results[0]->partner_player;


    //$output .= 'Response...';

    return [
      '#markup' =>  'Player: ' . $player . '<br/><br/><br/><br/><br/><br/><br/><br/><br/><br/>',
      '#allowed_tags' => ['iframe'],
      '#cache' => array('max-age' => 0),
    ];
  }

}