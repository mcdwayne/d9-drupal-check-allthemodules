<?php

namespace Drupal\consent_iframe\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class OilConsentIframeController.
 */
class OilConsentIframeController extends ConsentIframeControllerBase {

  /**
   * {@inheritdoc}
   */
  public function pageContent(Request $request) {
    $oil_config = $this->getOilConfig();
    if (empty($oil_config)) {
      static::logConfigLoadError();
      throw new NotFoundHttpException();
    }
    return [
      '#theme' => 'oil_iframe',
      '#config' => $oil_config,
    ];
  }

  /**
   * Get the OIL.js configuration parameters.
   *
   * @return array
   *   The OIL.js configuration parameters.
   */
  protected function getOilConfig() {
    $oil_config = [];
    if ($block_id = $this->iframeSettings->get('block')) {
      /** @var \Drupal\block\BlockInterface $block */
      if ($block = $this->blockStorage->load($block_id)) {
        $plugin_settings = $block->getPlugin()->getConfiguration();
        $oil_config = isset($plugin_settings['oil']) ? $plugin_settings['oil'] : [];
      }
    }
    else {
      $oil_config = $this->iframeSettings->get('oil');
    }
    return $oil_config ?: [];
  }

  /**
   * Logs an error regards configuration load.
   */
  protected static function logConfigLoadError() {
    \Drupal::logger('consent_iframe')->error(t('Failed to load configuration.'));
  }

}
