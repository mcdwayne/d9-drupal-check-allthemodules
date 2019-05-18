<?php

namespace Drupal\pricespider\Controller;


use Drupal\Core\Controller\ControllerBase;

class WhereToBuyController extends ControllerBase  {


  /**
   * {@inheritdoc}
   */
  public function content() {

    $build = [
      '#theme' => 'pricespider_wtb_page',
      '#attached' => [
        'library' => ['pricespider/pricespider.js'],
        // Add metatags
        'html_head' => \Drupal::service('pricespider')->getMetaTags(['ps-account', 'ps-config', 'ps-language', 'ps-country']),
      ]
    ];

    return $build;
  }
}