<?php

namespace Drupal\test_automatic_updates\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class JsonTestController.
 */
class JsonTestController extends ControllerBase {

  /**
   * Test JSON controller.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Return JSON feed response.
   */
  public function json() {
    $feed = [];
    $feed[] = [
      'title' => 'Critical Release - PSA-2019-02-19',
      'link' => 'https://www.drupal.org/psa-2019-02-19',
      'project' => 'core',
      'extensions' => [],
      'type' => 'module',
      'secure_versions' => [
        '7.99',
        '8.10.99',
        '8.9.99',
        '8.8.99',
        '8.7.99',
        '8.6.99',
        '8.5.99',
      ],
      'pubDate' => 'Tue, 19 Feb 2019 14:11:01 +0000',
    ];
    $feed[] = [
      'title' => 'Critical Release - PSA-Really Old',
      'link' => 'https://www.drupal.org/psa',
      'project' => 'core',
      'extensions' => [],
      'type' => 'module',
      'secure_versions' => [
        '7.0',
        '8.4.0',
      ],
      'pubDate' => 'Tue, 19 Feb 2019 14:11:01 +0000',
    ];
    $feed[] = [
      'title' => 'Node - Moderately critical - Access bypass - SA-CONTRIB-2019',
      'link' => 'https://www.drupal.org/sa-contrib-2019',
      'project' => 'node',
      'extensions' => ['node'],
      'type' => 'module',
      'secure_versions' => ['7.x-7.22', '8.x-8.2.0'],
      'pubDate' => 'Tue, 19 Mar 2019 12:50:00 +0000',
    ];
    $feed[] = [
      'title' => 'Standard - Moderately critical - Access bypass - SA-CONTRIB-2019',
      'link' => 'https://www.drupal.org/sa-contrib-2019',
      'project' => 'Standard Install Profile',
      'extensions' => ['standard'],
      'type' => 'profile',
      'secure_versions' => ['8.x-8.10.99'],
      'pubDate' => 'Tue, 19 Mar 2019 12:50:00 +0000',
    ];
    $feed[] = [
      'title' => 'Seven - Moderately critical - Access bypass - SA-CONTRIB-2019',
      'link' => 'https://www.drupal.org/sa-contrib-2019',
      'project' => 'seven',
      'extensions' => ['seven'],
      'type' => 'theme',
      'secure_versions' => ['8.x-8.10.99'],
      'pubDate' => 'Tue, 19 Mar 2019 12:50:00 +0000',
    ];
    $feed[] = [
      'title' => 'Foobar - Moderately critical - Access bypass - SA-CONTRIB-2019',
      'link' => 'https://www.drupal.org/sa-contrib-2019',
      'project' => 'foobar',
      'extensions' => ['foobar'],
      'type' => 'foobar',
      'secure_versions' => ['8.x-1.2'],
      'pubDate' => 'Tue, 19 Mar 2019 12:50:00 +0000',
    ];
    $feed[] = [
      'title' => 'Token - Moderately critical - Access bypass - SA-CONTRIB-2019',
      'link' => 'https://www.drupal.org/sa-contrib-2019',
      'project' => 'token',
      'extensions' => ['token'],
      'type' => 'module',
      'secure_versions' => ['7.x-1.7', '8.x-1.5'],
      'pubDate' => 'Tue, 19 Mar 2019 12:50:00 +0000',
    ];
    return new JsonResponse($feed);
  }

}
