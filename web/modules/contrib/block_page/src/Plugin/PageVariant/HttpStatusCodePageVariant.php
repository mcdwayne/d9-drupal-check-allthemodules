<?php

/**
 * @file
 * Contains \Drupal\block_page\Plugin\PageVariant\HttpStatusCodePageVariant.
 */

namespace Drupal\block_page\Plugin\PageVariant;

use Drupal\block_page\Plugin\PageVariantBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Provides a page variant that returns a response with an HTTP status code.
 *
 * @PageVariant(
 *   id = "http_status_code",
 *   admin_label = @Translation("HTTP status code")
 * )
 */
class HttpStatusCodePageVariant extends PageVariantBase {

  /**
   * {@inheritdoc}
   */
  public function getRegionNames() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, array &$form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Get all possible status codes defined by Symfony.
    $options = Response::$statusTexts;
    // Move 403/404/500 to the top.
    $options = array(
      '404' => $options['404'],
      '403' => $options['403'],
      '500' => $options['500'],
    ) + $options;

    // Add the HTTP status code, so it's easier for people to find it.
    array_walk($options, function($title, $code) use(&$options) {
      $options[$code] = t('@code (!title)', array('@code' => $code, '!title' => $title));
    });

    $form['status_code'] = array(
      '#title' => t('HTTP status code'),
      '#type' => 'select',
      '#default_value' => $this->configuration['status_code'],
      '#options' => $options,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    $configuration['status_code'] = '404';
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, array &$form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['status_code'] = $form_state['values']['status_code'];
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $status_code = $this->configuration['status_code'];
    if ($status_code == 200) {
      return array();
    }
    else {
      throw new HttpException($status_code);
    }
  }

}
