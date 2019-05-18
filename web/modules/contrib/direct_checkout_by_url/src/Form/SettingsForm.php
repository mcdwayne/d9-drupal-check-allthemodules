<?php

namespace Drupal\direct_checkout_by_url\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Direct checkout by URL settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'direct_checkout_by_url_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['direct_checkout_by_url.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['allow_unknown_skus'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow unknown SKUs in URL'),
      '#description' => $this->t('Make it possible to access URLs that have unknown SKUs in them. This could be useful if you risk ending up with users trying to access old links with products that does not exist anymore. In which case they would end up with a cart missing that product.'),
      '#default_value' => $this->config('direct_checkout_by_url.settings')->get('allow_unknown_skus'),
    ];
    $form['reset_cart'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Reset cart'),
      '#description' => $this->t('Reset the cart if the user accesses a URL when they already have something in their cart. This way you are sure they go directly to checkout with the products specified, but at the same time, you risk that they lose items they want from their cart.'),
      '#default_value' => $this->config('direct_checkout_by_url.settings')->get('reset_cart'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('direct_checkout_by_url.settings')
      ->set('allow_unknown_skus', $form_state->getValue('allow_unknown_skus'))
      ->set('reset_cart', $form_state->getValue('reset_cart'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
