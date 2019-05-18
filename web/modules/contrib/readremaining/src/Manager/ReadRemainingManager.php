<?php

namespace Drupal\readremaining\Manager;

use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ReadRemainingManager
 * @package Drupal\readremaining\Manager
 */
class ReadRemainingManager {

  protected $request;
  protected $config;

  public function __construct(RequestStack $request_stack, ConfigFactoryInterface $config) {
    $this->request = $request_stack->getCurrentRequest();
    $this->config = $config->get('read_remaining_configuration.settings');
  }

  /**
   * Returns look and feel setting.
   * @return string
   */
  public function getLookAndFeel() {
    return $this->config->get('look_feel');
  }

  /**
   * Returns JS settings.
   * @return array
   */
  public function getJsSettings() {
    return [
      'selector' => $this->config->get('selector'),
      'show_gauge_delay' => $this->config->get('show_gauge_delay'),
      'show_gauge_on_start' => $this->config->get('show_gauge_on_start'),
      'time_format' => $this->config->get('time_format'),
      'max_time_to_show' => $this->config->get('max_time_to_show'),
      'min_time_to_show' => $this->config->get('min_time_to_show'),
      'gauge_container' => $this->config->get('gauge_container'),
      'insert_position' => $this->config->get('insert_position'),
      'verbose_mode' => $this->config->get('verbose_mode'),
      'gauge_wrapper' => $this->config->get('gauge_wrapper'),
      'top_offset' => $this->config->get('top_offset'),
      'bottom_offset' => $this->config->get('bottom_offset'),
    ];
  }


  /**
   * Checks if the current page needs readremaining lib.
   * @return bool
   */
  public function pageApplies() {
    $node = $this->request->attributes->get('node');
    // We only provide behavior for nodes, so when visiting another page don't
    // bother to even load the config.
    if (!$node) {
      return FALSE;
    }

    $content_types = $this->config->get('contenttypes');

    // If no content types are configured, don't bother to do anything else.
    if (empty($content_types)) {
      return FALSE;
    }

    // The current node's type is not in the configured types.
    if (array_search($node->getType(), $content_types, TRUE) === FALSE) {
      return FALSE;
    }

    return TRUE;
  }

}