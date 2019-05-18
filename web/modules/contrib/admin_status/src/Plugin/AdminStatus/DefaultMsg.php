<?php

namespace Drupal\admin_status\Plugin\AdminStatus;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a default message.
 *
 * Because the plugin manager class for our plugins uses annotated class
 * discovery, our default message only needs to exist within the
 * Plugin\AdminStatus namespace to be declared as a plugin. This is defined in
 * \Drupal\admin_status\AdminStatusPluginManager::__construct().
 *
 * The following is the plugin annotation. This is parsed by Doctrine to make
 * the plugin definition. Any values defined here will be available in the
 * plugin definition.
 *
 * This should be used for metadata that is specifically required to instantiate
 * the plugin, or for example data that might be needed to display a list of all
 * available plugins where the user selects one. This means many plugin
 * annotations can be reduced to a plugin ID, a label and perhaps a description.
 *
 * @Plugin(
 *   id = "default_message",
 *   name = "Default Message",
 *   admin_permission = "administer admin status",
 * )
 */
class DefaultMsg extends AdminStatusPluginBase {

  /**
   * {@inheritdoc}
   */
  public function description() {
    return $this->t('Display a generic message.');
  }

  /**
   * {@inheritdoc}
   */
  public function configForm(array $form,
                             FormStateInterface $form_state,
                             array $configValues) {
    $form = [
      'type' => [
        '#type' => 'select',
        '#title' => $this->t('Message type'),
        '#options' => [
          'status' => $this->t('status'),
          'warning' => $this->t('warning'),
          'error' => $this->t('error'),
        ],
        '#default_value' => empty($configValues['type']) ? '' : $configValues['type'],
      ],
      'message' => [
        '#type' => 'textfield',
        '#title' => $this->t('Message text'),
        '#default_value' => empty($configValues['message']) ? '' : $configValues['message'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configValidateForm(array $form,
                                     FormStateInterface $form_state,
                                     array $configValues) {
    // Nothing to validate.
  }

  /**
   * {@inheritdoc}
   */
  public function configSubmitForm(array $form,
                                   FormStateInterface $form_state,
                                   array $configValues) {
    $config = $form_state->getValue(
      ['plugins', 'default_message', 'config']);
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function message(array $configValues) {
    return [
      [
        'status' => $configValues['type'],
        'message' => $configValues['message'],
      ],
    ];
  }

}
