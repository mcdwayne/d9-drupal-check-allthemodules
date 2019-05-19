<?php

/**
 * @file
 * Contains \Drupal\surveygizmodrupal\Controller\ServeygizmoController
 */

namespace Drupal\surveygizmodrupal\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

class ServeygizmoController extends ControllerBase {

  public function listing() {

    if (empty($_REQUEST['page'])) {
      $page = 1;
    } 
    else {
      $page = $_REQUEST['page'] + 1;
    }

    $config = \Drupal::config('surveygizmodrupal.adminsettings');
    $SG_API_KEY = $config->get('SG_API_KEY');
    $SG_API_SECRET = $config->get('SG_API_SECRET');
    $SG_DATA_LIMIT = $config->get('SG_DATA_LIMIT');
    
    if ($SG_DATA_LIMIT) {
      $limit = $SG_DATA_LIMIT;
    } 
    else {
      $limit = 10;
    }

    try {
      \SurveyGizmo\SurveyGizmoAPI::auth($SG_API_KEY, $SG_API_SECRET);
    } 
    catch (\SurveyGizmo\Helpers\SurveyGizmoException $e) {
      die("Error Authenticating");
    }

    \SurveyGizmo\ApiRequest::setRepeatRateLimitedRequest($limit);

    $filter = new \SurveyGizmo\Helpers\Filter();

    $options = array('page' => $page, 'limit' => $limit);
    $surveys = \SurveyGizmo\Resources\Survey::fetch($filter, $options);
    $total_count = $surveys->total_count;
    $total_pages = $surveys->total_pages;

    pager_default_initialize($total_count, $limit);
    $list = [];
    if ($page == $total_pages) {
      $limit = $total_count % $limit;
    }

    for ($j = 0; $j < $limit; $j++) {
      $url = Url::fromRoute('surveygizmo.detial', array('id' => $surveys->data[$j]->id));
      $detial_path = \Drupal::l(t('Play Now'), $url);
      $list['question_listing'][$surveys->data[$j]->id] = ['title'=>$surveys->data[$j]->title, 'url'=>$detial_path];
    }

    $render = [];
    $render[] = [
      '#theme' => 'surveygizmo_listing_page',
      '#data' => $list,
      '#attached' => [
        'library' => [
          'surveygizmo/surveygizmo-js',
          'surveygizmo/surveygizmo-css'
        ]
      ],
      '#cache' => [
        'contexts' => ['url.path'],
        'max-age' => 0,
      ],
    ];

    $render[] = ['#type' => 'pager'];
    return $render;
  }
}
