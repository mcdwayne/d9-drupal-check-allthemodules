<?php

namespace Drupal\uvrp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure uvrp settings for this site.
 */
class RVPSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uvrp_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'uvrp.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('uvrp.settings');

    $form['uvrp_expire_interval'] = [
      '#type' => 'textfield',
      '#attributes' => [
        ' type' => 'number',
      ],
      '#title' => $this->t('Products Expire time'),
      '#default_value' => $config->get('uvrp_expire_interval', 86400),
      '#description' => $this->t("Specify the time (in sec) for which viewed products should remain in database."),
    ];

    $form['uvrp_limit'] = [
      '#type' => 'textfield',
      '#attributes' => [
        ' type' => 'number',
      ],
      '#title' => $this->t('Number of products to show'),
      '#default_value' => $config->get('uvrp_limit', 4),
      '#description' => $this->t("Specify the number of products to show."),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Retrieve the configuration.
    $this->configFactory()->getEditable('uvrp.settings')
    // Set the submitted configuration setting.
      ->set('uvrp_expire_interval', $form_state->getValue('uvrp_expire_interval'))
    // You can set multiple configurations at once by making
    // multiple calls to set()
      ->set('uvrp_limit', $form_state->getValue('uvrp_limit'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
