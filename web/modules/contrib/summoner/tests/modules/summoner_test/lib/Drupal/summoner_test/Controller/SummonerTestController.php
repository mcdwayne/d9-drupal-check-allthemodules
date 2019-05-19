<?php
/**
 * Created by PhpStorm.
 * User: philipp
 * Date: 31.03.14
 * Time: 17:31
 */

namespace Drupal\summoner_test\Controller;


class SummonerTestController {
  public function testPage() {
    return array(
      '#theme' => 'summoner_test_page',
      '#link' => array(
        '#type' => 'link',
        '#title' => 'Dynamic link',
        '#href' => 'summoner/test',
        '#id' => 'summoner-test-link',
      ),
      '#attached' => array(
        'library' => array(
          'summoner_test/summoner.test',
        ),
      ),
    );
  }
}