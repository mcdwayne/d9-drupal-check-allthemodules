<?php

namespace Drupal\config_perms_context\Plugin\ContextReaction;

use Drupal\config_perms\Entity\CustomPermsEntity;
use Drupal\context\ContextReactionPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a content reaction that adds a css 'active' class to menu item.
 *
 * @ContextReaction(
 *   id = "config_perms",
 *   label = @Translation("Custom permissions")
 * )
 */
class ConfigPerms extends ContextReactionPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $permission = [
      'config_perms_forbiden' => [],
      'config_perms_allow' => [],
    ];

    return parent::defaultConfiguration() + $permission;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->getConfiguration()['config_perms_allow'];
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    return $this->getConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [];
    $perms = CustomPermsEntity::loadMultiple();
    foreach ($perms as $perm) {
      /** @var \Drupal\config_perms\Entity\CustomPermsEntity $perm */
      // Convert route . to __ to prevent save config error.
      $route = str_replace('.', '___', $perm->getRoute());
      $options[$route] = '[' . $perm->getRoute() . '] ' . $perm->label();
    }

    $form['config_perms_forbiden'] = [
      '#multiple' => 'true',
      '#type' => 'select',
      '#required' => FALSE,
      '#title' => $this->t('Forbiden routes'),
      '#default_value' => empty($this->getConfiguration()['config_perms_forbiden']) ? [] : $this->getConfiguration()['config_perms_forbiden'],
      '#empty_option' => $this->t('- Select -'),
      '#options' => $options,
      '#description' => $this->t('Select permissions forbiden routes.'),
    ];

    $form['config_perms_allow'] = [
      '#type' => 'select',
      '#multiple' => 'true',
      '#required' => FALSE,
      '#title' => $this->t('Allow routes'),
      '#default_value' => empty($this->getConfiguration()['config_perms_allow']) ? [] : $this->getConfiguration()['config_perms_allow'],
      '#empty_option' => $this->t('- Select -'),
      '#options' => $options,
      '#description' => $this->t('Select permissions allow routes.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration([
      'config_perms_forbiden' => $form_state->getValue('config_perms_forbiden'),
      'config_perms_allow' => $form_state->getValue('config_perms_allow'),
    ]);

  }

}
