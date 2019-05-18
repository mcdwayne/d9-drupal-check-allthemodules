<?php

namespace Drupal\shopify\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Class ShopifySettingsAdminForm.
 *
 * @package Drupal\shopify\Form
 */
class ShopifySettingsAdminForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shopify_settings_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    try {
      $info = shopify_shop_info('', $refresh = TRUE);
    }
    catch (\Exception $e) {
      // Error connecting to the store.
      drupal_set_message(t('Could not connect to the Shopify store.'), 'error');
      return [];
    }
    $store_info = [
      'My Store Admin' => \Drupal::l($info->domain, Url::fromUri('https://' . $info->domain . '/admin', ['attributes' => ['target' => '_blank']])),
      'Owned By' => $info->shop_owner . ' &lt;<a href="mailto:' . $info->email . '">' . $info->email . '</a>&gt;',
      'Address' => $info->address1,
      'City' => $info->city,
      'State/Province' => $info->province,
      'Zip' => $info->zip,
    ];
    foreach ($store_info as $label => $data) {
      $form[$label] = [
        '#type' => 'item',
        '#title' => $label,
        '#markup' => $data,
      ];
    }
    $form['all_info'] = [
      '#type' => 'details',
      '#title' => t('More info'),
    ];
    foreach ($info as $label => $data) {
      $form['all_info'][$label] = [
        '#type' => 'item',
        '#title' => $label,
        '#markup' => $data ?: '[EMPTY]',
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }

}
