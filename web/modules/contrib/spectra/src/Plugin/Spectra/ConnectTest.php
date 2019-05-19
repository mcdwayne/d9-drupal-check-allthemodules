<?php

namespace Drupal\spectra\Plugin\Spectra;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Component\Utility\Xss;
use Drupal\spectra\SpectraPluginInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\spectra\Entity\SpectraData;
use Drupal\spectra\Entity\SpectraStatement;

/**
 *
 * @SpectraPlugin(
 *   id = "connect_test",
 *   label = @Translation("ConnectTest"),
 * )
 */
class ConnectTest extends PluginBase implements SpectraPluginInterface {

  /**
   * @return string
   *   A string description of the plugin.
   */
  public function description()
  {
    return $this->t('Spectra Connection Test Plugin');
  }

  /**
   * {@inheritdoc}
   */
  public function handleDeleteRequest(Request $request) {
    $content = json_decode($request->getContent(), TRUE);
    if (isset($content['data_only']) && $content['data_only']) {
      return json_encode($content);
    }
    else {
      return t('Test of DELETE Request successful. Here is the data you sent: ' . json_encode($content));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function handleGetRequest($request) {
    $content = $request->query->all();

    if (isset($content['data_only']) && $content['data_only']) {
      return json_encode($content);
    }
    else {
      return t('Test of GET Request successful. Here is the data you sent: ' . json_encode($content));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function handlePostRequest(Request $request) {
    $content = json_decode($request->getContent(), TRUE);
    if (isset($content['data_only']) && $content['data_only']) {
      return json_encode($content);
    }
    else {
      return t('Test of POST Request successful. Here is the data you sent: ' . json_encode($content));
    }
  }

}