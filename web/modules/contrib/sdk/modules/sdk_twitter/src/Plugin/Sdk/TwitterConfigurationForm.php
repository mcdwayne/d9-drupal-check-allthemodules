<?php

namespace Drupal\sdk_twitter\Plugin\Sdk;

use Drupal\Core\Form\FormStateInterface;
use Drupal\sdk\SdkPluginConfigurationFormBase;

/**
 * Form for SDK configuration.
 */
class TwitterConfigurationForm extends SdkPluginConfigurationFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array &$form, FormStateInterface $form_state) {
    $form['information'] = [
      '#markup' => $this->t('You can manage applications: @link', [
        '@link' => static::externalLink('https://apps.twitter.com'),
      ]),
    ];

    $form['consumer_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Consumer key'),
      '#required' => TRUE,
    ];

    $form['consumer_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Consumer secret'),
      '#required' => TRUE,
    ];

    $form['access_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access key'),
      '#required' => TRUE,
    ];

    $form['access_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access secret'),
      '#required' => TRUE,
    ];
  }

}
