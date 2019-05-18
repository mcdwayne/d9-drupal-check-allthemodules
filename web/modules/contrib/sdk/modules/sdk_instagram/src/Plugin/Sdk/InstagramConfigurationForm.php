<?php

namespace Drupal\sdk_instagram\Plugin\Sdk;

use Drupal\Core\Form\FormStateInterface;
use Drupal\sdk\SdkPluginConfigurationFormBase;

/**
 * Form for SDK configuration.
 */
class InstagramConfigurationForm extends SdkPluginConfigurationFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array &$form, FormStateInterface $form_state) {
    $form['information'] = [
      '#markup' => $this->t('You can manage API clients here: @link', [
        '@link' => static::externalLink('https://www.instagram.com/developer/clients/manage'),
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
      '#default_value' => ['basic', 'public_content'],
      '#description' => $this->t('Additional information about permissions level can be found here: @link', [
        '@link' => static::externalLink('https://www.instagram.com/developer/authorization'),
      ]),
      '#options' => [
        'basic' => $this->t('Read a user profile info and media'),
        'likes' => $this->t('Like and unlike media on a user behalf'),
        'comments' => $this->t('Post and delete comments on a user behalf'),
        'follower_list' => $this->t('Read the list of followers and followed-by users'),
        'relationships' => $this->t('Follow and unfollow accounts on a user behalf'),
        'public_content' => $this->t('Read any public profile info and media on a user behalf'),
      ],
    ];
  }

}
