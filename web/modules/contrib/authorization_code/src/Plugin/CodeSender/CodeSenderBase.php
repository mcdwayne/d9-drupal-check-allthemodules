<?php

namespace Drupal\authorization_code\Plugin\CodeSender;

use Drupal\authorization_code\CodeSenderInterface;
use Drupal\authorization_code\Plugin\AuthorizationCodePluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for code sender plugins.
 */
abstract class CodeSenderBase extends AuthorizationCodePluginBase implements CodeSenderInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return NestedArray::mergeDeep(parent::defaultConfiguration(), [
      'settings' => [
        'message_template' => '',
      ],
    ]);
  }

  /**
   * The message template.
   *
   * @return string|null
   *   The message template, or null if no template was configured.
   */
  protected function messageTemplate() {
    return NestedArray::getValue($this->configuration,
      ['settings', 'message_template']);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['message_template'] = [
      '#type' => 'textarea',
      '#required' => TRUE,
      '#title' => $this->t('Message template'),
      '#default_value' => $this->messageTemplate(),
      '#description' => $this->t('The template for the email body text (use [authorization_code:code] for the authorization code)'),
    ];

    return $form;
  }

}
