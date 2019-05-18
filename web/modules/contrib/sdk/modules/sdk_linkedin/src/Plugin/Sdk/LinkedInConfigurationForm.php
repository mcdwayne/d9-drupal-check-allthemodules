<?php

namespace Drupal\sdk_linkedin\Plugin\Sdk;

use Drupal\Core\Form\FormStateInterface;
use Drupal\sdk\SdkPluginConfigurationFormBase;

/**
 * Form for SDK configuration.
 */
class LinkedInConfigurationForm extends SdkPluginConfigurationFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array &$form, FormStateInterface $form_state) {
    $form['information'] = [
      '#markup' => $this->t('You can manage applications: @link', [
        '@link' => static::externalLink('https://www.linkedin.com/developer/apps'),
      ]),
    ];

    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#required' => TRUE,
    ];

    $form['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client secret'),
      '#required' => TRUE,
    ];

    $form['scope'] = [
      '#type' => 'select',
      '#title' => $this->t('Authorisation permissions'),
      '#required' => TRUE,
      '#multiple' => TRUE,
      '#default_value' => ['r_basicprofile'],
      '#description' => $this->t('Additional information about permissions level can be found on application page.'),
      '#options' => [
        'w_share' => $this->t('Share [W]'),
        'r_basicprofile' => $this->t('Basic profile [R]'),
        'r_emailaddress' => $this->t('Email address [R]'),
        'rw_company_admin' => $this->t('Company admin [RW]'),
      ],
    ];
  }

}
