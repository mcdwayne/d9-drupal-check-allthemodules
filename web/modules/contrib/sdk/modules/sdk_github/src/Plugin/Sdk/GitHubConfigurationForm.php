<?php

namespace Drupal\sdk_github\Plugin\Sdk;

use Drupal\Core\Form\FormStateInterface;
use Drupal\sdk\SdkPluginConfigurationFormBase;

/**
 * Form for SDK configuration.
 */
class GitHubConfigurationForm extends SdkPluginConfigurationFormBase {

  /**
   * Access scopes.
   */
  const SCOPES = [
    'user',
    'user:email',
    'user:follow',
    'public_repo',
    'repo',
    'repo_deployment',
    'repo:status',
    'delete_repo',
    'notifications',
    'gist',
    'read:repo_hook',
    'write:repo_hook',
    'admin:repo_hook',
    'admin:org_hook',
    'read:org',
    'write:org',
    'admin:org',
    'read:public_key',
    'write:public_key',
    'admin:public_key',
    'read:gpg_key',
    'write:gpg_key',
    'admin:gpg_key',
  ];

  /**
   * {@inheritdoc}
   */
  public function form(array &$form, FormStateInterface $form_state) {
    $form['information'] = [
      '#markup' => $this->t('You can manage applications here: @link', [
        '@link' => static::externalLink('https://github.com/settings/developers'),
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
      '#title' => $this->t('Scopes'),
      '#options' => array_combine(static::SCOPES, static::SCOPES),
      '#required' => TRUE,
      '#multiple' => TRUE,
      '#description' => $this->t('All listed scopes available here: @link', [
        '@link' => static::externalLink('https://developer.github.com/v3/oauth/#scopes'),
      ]),
    ];
  }

}
