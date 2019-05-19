<?php

namespace Drupal\socialfeed\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Abstract base class SocialBlockBase.
 *
 * @package Drupal\socialfeed\Plugin\Block
 */
abstract class SocialBlockBase extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $settings = $this->getConfiguration();
    $access = $this->currentUser->hasPermission('administer social feeds');

    $form['override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Customize feed'),
      '#default_value' => isset($settings['override']) ? $settings['override'] : FALSE,
      '#access' => $access,
    ];

    $form['overrides'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Feed configuration'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#access' => $access,
      '#states' => [
        'invisible' => [
          ':input[name="settings[override]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * Apply state based on the override field.
   *
   * @param array $form
   *   blockFormElementStates.
   */
  protected function blockFormElementStates(array &$form) {
    $global_config = $this->config;
    $privileged_user = $this->currentUser->hasPermission('administer social feeds');
    $config_is_incomplete = FALSE;
    foreach ($form['overrides'] as $key => $val) {
      if (strrpos($key, '#') === 0) {
        continue;
      }

      if (isset($form['overrides'][$key]['#states']['required'])) {
        continue;
      }

      $form['overrides'][$key]['#states']['required'] = [
        ':input[name="settings[override]"]' => ['checked' => TRUE],
      ];

      $config_is_incomplete = $config_is_incomplete || empty($global_config->get($key));
    }

    if ($config_is_incomplete) {
      $form['override']['#disabled'] = TRUE;
      $form['override']['#default_value'] = 1;
      $form['override']['#description'] = $this->t('To disable this option, configure default values at @admin', [
        '@admin' => Url::fromRoute('socialfeed.socialfeed')->toString(),
      ]);
    }

    if ($config_is_incomplete && !$privileged_user) {
      // When global config is invalid, and a non-privileged user still has
      // sufficient access to place this block, add a warning message and a
      // validator to ensure the form cannot be submitted in this state.
      $form['configuration_warning'] = [
        '#type' => 'status_messages',
        '#weight' => -1000,
        '#element_validate' => [[$this, 'invalidConfigurationValidator']],
      ];
      \Drupal::messenger()->addWarning($this->getInvalidConfigurationWarning());
    }

  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->configuration['override'] = $values['override'];
    foreach ($values['overrides'] as $key => $value) {
      $this->configuration[$key] = $value;
    }
  }

  /**
   * Validation handler for social blocks with invalid configuration.
   */
  public function invalidConfigurationValidator($element, FormStateInterface $form_state) {
    $form_state->setErrorByName('configuration_warning', $this->t('This block cannot be placed.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function defaultSettingValue($key) {
    $settings = $this->getConfiguration();
    return isset($settings[$key]) ?
      $settings[$key] :
      $this->config->get($key);
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($key) {
    $block_settings = $this->getConfiguration();
    return $block_settings['override'] ?
      $block_settings[$key] :
      $this->config->get($key);
  }

  /**
   * {@inheritdoc}
   */
  public function getInvalidConfigurationWarning() {
    return $this->t('Social media feed configuration is missing or is incomplete. Please contact your site administrator.');
  }

}
