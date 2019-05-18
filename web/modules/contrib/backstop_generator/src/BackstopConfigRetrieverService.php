<?php

namespace Drupal\backstop_generator;

/**
 * A service definiton for retreiving backstop config.
 */
class BackstopConfigRetrieverService {
  protected $configurationObject;

  /**
   * Create config.
   */
  public function __construct() {
    $config = \Drupal::config('backstop_generator.backstopconfiguration');

    $this->configurationObject = [];
    $this->configurationObject['id'] = strtolower(str_replace(' ', '_', \Drupal::config('system.site')->get('name')));
    $this->configurationObject['viewports'] = [];

    if ($config->get('viewports.Mobile')) {
      $viewport = [
        'label' => 'Mobile',
        'width' => 320,
        'height' => 480,
      ];
      array_push($this->configurationObject['viewports'], $viewport);
    }
    if ($config->get('viewports.Tablet')) {
      $viewport = [
        'label' => 'Tablet',
        'width' => 1024,
        'height' => 768,
      ];
      array_push($this->configurationObject['viewports'], $viewport);
    }
    if ($config->get('viewports.HD')) {
      $viewport = [
        'label' => 'HD',
        'width' => 1920,
        'height' => 1080,
      ];
      array_push($this->configurationObject['viewports'], $viewport);
    }
    if ($config->get('viewports.HD+')) {
      $viewport = [
        'label' => 'HD+',
        'width' => 2560,
        'height' => 1440,
      ];
      array_push($this->configurationObject['viewports'], $viewport);
    }
    if ($config->get('viewports.UHD')) {
      $viewport = [
        'label' => 'UHD',
        'width' => 3840,
        'height' => 2160,
      ];
      array_push($this->configurationObject['viewports'], $viewport);
    }
    $this->configurationObject['onBeforeScript'] = "chromy/onBefore.js";
    $this->configurationObject['onReadyScript'] = "chromy/onReady.js";
    $this->configurationObject['scenarios'] = [];
    $this->configurationObject['paths'] = [
      'bitmaps_reference' => 'backstop_data/bitmaps_reference',
      "bitmaps_test" => "backstop_data/bitmaps_test",
      "engine_scripts" => "backstop_data/engine_scripts",
      "html_report" => "backstop_data/html_report",
      "ci_report" => "backstop_data/ci_report",
    ];
    $this->configurationObject['report'] = ['browser'];
    $this->configurationObject['engine'] = "chrome";
    $this->configurationObject['engineFlags'] = [];
    $this->configurationObject['asyncCaptureLimit'] = 5;
    $this->configurationObject['asyncCompareLimit'] = 50;
    $this->configurationObject['debug'] = FALSE;
    $this->configurationObject['debugWindow'] = FALSE;

    $pages_config_array = $config->get('pre_defined_pages_table');
    $randomExclude = [];
    foreach ($pages_config_array as $key => $value) {
      if ($value['page'] != NULL) {
        $node = \Drupal::entityTypeManager()->getStorage('node')->load($value['page']);
        $url = $node->toUrl('canonical', ['absolute' => TRUE])->toString();
        $scenario = [
          'label' => $node->getTitle(),
          'cookiePath' => "backstop_data/engine_scripts/cookies.json",
          'url' => $url,
          "referenceUrl" => "",
          "readyEvent" => "",
          "readySelector" => "",
          "delay" => 0,
          "hideSelectors" => [],
          "removeSelectors" => [],
          "hoverSelector" => "",
          "clickSelector" => "",
          "postInteractionWait" => "",
          "selectors" => [],
          "selectorExpansion" => TRUE,
          "misMatchThreshold" => 0.1,
          "requireSameDimensions" => TRUE,
        ];
        array_push($this->configurationObject['scenarios'], $scenario);
        array_push($randomExclude, $value['page']);
      }
    }
    $randomCount = $config->get('additional_random_pages');
    if (!empty($randomExclude)) {
      $nids = \Drupal::entityQuery('node')
        ->condition('nid', $randomExclude, 'NOT IN')
        ->addTag('random')
        ->range(0, $randomCount)
        ->execute();
    }
    if ($nids) {
      foreach ($nids as $nid) {
        $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
        $url = $node->toUrl('canonical', ['absolute' => TRUE])->toString();
        $scenario = [
          'label' => $node->getTitle(),
          'cookiePath' => "backstop_data/engine_scripts/cookies.json",
          'url' => $url,
          "referenceUrl" => "",
          "readyEvent" => "",
          "readySelector" => "",
          "delay" => 0,
          "hideSelectors" => [],
          "removeSelectors" => [],
          "hoverSelector" => "",
          "clickSelector" => "",
          "postInteractionWait" => "",
          "selectors" => [],
          "selectorExpansion" => TRUE,
          "misMatchThreshold" => 0.1,
          "requireSameDimensions" => TRUE,
        ];
        array_push($this->configurationObject['scenarios'], $scenario);
      }
    }
  }

  /**
   * Expose method for calling constructed config.
   */
  public function getConfigurationObject() {
    return $this->configurationObject;
  }

}
