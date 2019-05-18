<?php

namespace Drupal\uc_cart\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configure general shopping cart settings for this site.
 */
class CartSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_cart_cart_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'uc_cart.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cart_config = $this->config('uc_cart.settings');

    $form['cart-settings'] = [
      '#type' => 'vertical_tabs',
      '#attached' => [
        'library' => [
          'uc_cart/uc_cart.admin.scripts',
        ],
      ],
    ];

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('Basic settings'),
      '#group' => 'cart-settings',
    ];

    $form['general']['uc_cart_add_item_msg'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display a message when a customer adds an item to their cart.'),
      '#default_value' => $cart_config->get('add_item_msg'),
    ];
    $form['general']['uc_add_item_redirect'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Add to cart redirect'),
      '#description' => $this->t('Enter the page to redirect to when a customer adds an item to their cart, or &lt;none&gt; for no redirect.'),
      '#default_value' => $cart_config->get('add_item_redirect'),
      '#size' => 32,
      '#field_prefix' => Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(),
    ];

    $form['general']['uc_cart_empty_button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show an "Empty cart" button on the cart page.'),
      '#default_value' => $cart_config->get('empty_button'),
    ];

    $form['general']['uc_minimum_subtotal'] = [
      '#type' => 'uc_price',
      '#title' => $this->t('Minimum order subtotal'),
      '#description' => $this->t('Customers will not be allowed to check out if the subtotal of items in their cart is less than this amount.'),
      '#default_value' => $cart_config->get('minimum_subtotal'),
    ];

    $form['lifetime'] = [
      '#type' => 'details',
      '#title' => $this->t('Cart lifetime'),
      '#description' => $this->t('Set the length of time that products remain in the cart. Cron must be running for this feature to work.'),
      '#group' => 'cart-settings',
    ];

    $range = range(1, 60);
    $form['lifetime']['anonymous'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Anonymous users'),
      '#attributes' => ['class' => ['uc-inline-form', 'clearfix']],
    ];
    $form['lifetime']['anonymous']['uc_cart_anon_duration'] = [
      '#type' => 'select',
      '#title' => $this->t('Duration'),
      '#options' => array_combine($range, $range),
      '#default_value' => $cart_config->get('anon_duration'),
    ];
    $form['lifetime']['anonymous']['uc_cart_anon_unit'] = [
      '#type' => 'select',
      '#title' => $this->t('Units'),
      '#options' => [
        'minutes' => $this->t('Minute(s)'),
        'hours' => $this->t('Hour(s)'),
        'days' => $this->t('Day(s)'),
        'weeks' => $this->t('Week(s)'),
        'years' => $this->t('Year(s)'),
      ],
      '#default_value' => $cart_config->get('anon_unit'),
    ];

    $form['lifetime']['authenticated'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Authenticated users'),
      '#attributes' => ['class' => ['uc-inline-form', 'clearfix']],
    ];
    $form['lifetime']['authenticated']['uc_cart_auth_duration'] = [
      '#type' => 'select',
      '#title' => $this->t('Duration'),
      '#options' => array_combine($range, $range),
      '#default_value' => $cart_config->get('auth_duration'),
    ];
    $form['lifetime']['authenticated']['uc_cart_auth_unit'] = [
      '#type' => 'select',
      '#title' => $this->t('Units'),
      '#options' => [
        'hours' => $this->t('Hour(s)'),
        'days' => $this->t('Day(s)'),
        'weeks' => $this->t('Week(s)'),
        'years' => $this->t('Year(s)'),
      ],
      '#default_value' => $cart_config->get('auth_unit'),
    ];

    $form['continue_shopping'] = [
      '#type' => 'details',
      '#title' => $this->t('Continue shopping element'),
      '#description' => $this->t('These settings control the <em>continue shopping</em> option on the cart page.'),
      '#group' => 'cart-settings',
    ];
    $form['continue_shopping']['uc_continue_shopping_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('<em>Continue shopping</em> element'),
      '#options' => [
        'link' => $this->t('Text link'),
        'button' => $this->t('Button'),
        'none' => $this->t('Do not display'),
      ],
      '#default_value' => $cart_config->get('continue_shopping_type'),
    ];
    $form['continue_shopping']['uc_continue_shopping_use_last_url'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make <em>continue shopping</em> go back to the last item that was added to the cart.'),
      '#description' => $this->t('If this is disabled or the item is unavailable, the URL specified below will be used.'),
      '#default_value' => $cart_config->get('continue_shopping_use_last_url'),
    ];
    $form['continue_shopping']['uc_continue_shopping_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default <em>continue shopping</em> destination'),
      '#default_value' => $cart_config->get('continue_shopping_url'),
      '#size' => 32,
      '#field_prefix' => Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(),
    ];

    $form['breadcrumb'] = [
      '#type' => 'details',
      '#title' => $this->t('Cart breadcrumb'),
      '#description' => $this->t('Drupal automatically adds a <em>Home</em> breadcrumb to the cart page, or you can use these settings to specify a custom breadcrumb.'),
      '#group' => 'cart-settings',
    ];
    $form['breadcrumb']['uc_cart_breadcrumb_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cart page breadcrumb text'),
      '#description' => $this->t('Leave blank to use the default <em>Home</em> breadcrumb.'),
      '#default_value' => $cart_config->get('breadcrumb_text'),
    ];
    $form['breadcrumb']['uc_cart_breadcrumb_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cart page breadcrumb destination'),
      '#default_value' => $cart_config->get('breadcrumb_url'),
      '#size' => 32,
      '#field_prefix' => Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $cart_config = $this->config('uc_cart.settings');
    $cart_config
      ->set('add_item_msg', $form_state->getValue('uc_cart_add_item_msg'))
      ->set('add_item_redirect', $form_state->getValue('uc_add_item_redirect'))
      ->set('empty_button', $form_state->getValue('uc_cart_empty_button'))
      ->set('minimum_subtotal', $form_state->getValue('uc_minimum_subtotal'))
      ->set('anon_duration', $form_state->getValue('uc_cart_anon_duration'))
      ->set('anon_unit', $form_state->getValue('uc_cart_anon_unit'))
      ->set('auth_duration', $form_state->getValue('uc_cart_auth_duration'))
      ->set('auth_unit', $form_state->getValue('uc_cart_auth_unit'))
      ->set('continue_shopping_type', $form_state->getValue('uc_continue_shopping_type'))
      ->set('continue_shopping_use_last_url', $form_state->getValue('uc_continue_shopping_use_last_url'))
      ->set('continue_shopping_url', $form_state->getValue('uc_continue_shopping_url'))
      ->set('breadcrumb_text', $form_state->getValue('uc_cart_breadcrumb_text'))
      ->set('breadcrumb_url', $form_state->getValue('uc_cart_breadcrumb_url'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
