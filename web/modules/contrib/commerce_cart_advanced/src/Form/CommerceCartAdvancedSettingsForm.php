<?php

namespace Drupal\commerce_cart_advanced\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class CommerceCartAdvancedSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_cart_advanced_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_cart_advanced.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_cart_advanced.settings');

    $form['display_non_current_carts'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show non-current carts'),
      '#description' => $this->t(
        'When checked the non-current carts will be displayed as a separate list
         on the cart page, below the current carts. When unchecked the
         non-current carts will not be displayed at all.'
      ),
      '#default_value' => $config->get('display_non_current_carts'),
    ];

    $help_label = $this->t('What are current/non-current carts?');
    $help_text = $this->t(
      'By default, one cart per store (the most recent) is considered as current
       while the rest are considered as non-current. Users have the ability to
       explicitly flag carts as non-current (save for later), while other
       installed modules are given the opportunity to change which carts are
       considered currents and which not.'
    );
    $form['help'] = [
      '#markup' => '&nbsp;<h5 style="margin-top:20px"><strong>' . $help_label . '</strong></h5><p>' . $help_text . '</p>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $this->configFactory->getEditable('commerce_cart_advanced.settings')
      // Set the submitted configuration setting.
      ->set('display_non_current_carts', $form_state->getValue('display_non_current_carts'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
