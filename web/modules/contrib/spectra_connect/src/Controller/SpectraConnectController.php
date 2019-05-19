<?php

namespace Drupal\spectra_connect\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\spectra_connect\SpectraConnectUtilities;

/**
 * Class SpectraConnectController.
 *
 * @package Drupal\spectra_connect\Controller
 *
 * Provides the route and API controller for spectra_connect.
 */
class SpectraConnectController extends ControllerBase {

  /**
   * Connection Test.
   *
   * @param string $spectra_connect
   *   Machine name of the Spectra Connect Entity.
   *
   * @return array
   *   Renderable array of test results.
   */
  public static function connectTest($spectra_connect) {
    $test_data = [
      'plugin' => 'connect_test',
    ];
    $test['DELETE'] = SpectraConnectUtilities::spectraDelete($spectra_connect, $test_data);
    $test['GET'] = SpectraConnectUtilities::spectraGet($spectra_connect, $test_data);
    $test['POST'] = SpectraConnectUtilities::spectraPost($spectra_connect, $test_data);

    $ret = [];

    foreach ($test as $type => $response) {
      if ($response) {
        $ret[] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $type . ': ' . $response->getStatusCode() . ' - ' . json_decode($response->getBody()->getContents()),
        ];
      }
      else {
        $ret[] = [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $type . ': Connect Test Failed.',
        ];
      }
    }

    $url = Url::fromRoute('entity.spectra_connect.collection');
    $link = Link::fromTextAndUrl(t('Return to Spectra Connect Entity Listing'), $url)->toRenderable();

    $ret[] = $link;

    return $ret;
  }

}
