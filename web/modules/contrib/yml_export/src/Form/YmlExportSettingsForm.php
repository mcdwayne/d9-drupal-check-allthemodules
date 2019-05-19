<?php

/**
 * @file
 * Contains \Drupal\yml_export\Form\YmlExportSettingsForm.
 */

namespace Drupal\yml_export\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Url;

class YmlExportSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'yml_export_settings_form';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['yml_export.settings'];
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('yml_export.settings');

    $ctypes = uc_product_types();
    $form['types'] = [
      '#type' => 'checkboxes',
      '#title' => t('Select node types for export'),
      '#options' => $ctypes,
      '#default_value' => $config->get('types'),
      '#required' => TRUE,
    ];

    $term_fields = [];
    $field = FieldConfig::loadByName('node', 'product', 'taxonomy_catalog');
    if ($field) $term_fields['taxonomy_catalog'] = $field->getLabel();

    $descr_fields = [];
    $field = FieldConfig::loadByName('node', 'product', 'body');
    if ($field) $descr_fields['body'] = $field->getLabel();

    if (count($term_fields) == 0) {
      drupal_set_message(t("No term fields attached to product node! Export can't work properly. Please create at least one taxonomy vocabulary and use it for your products."), 'warning');
    }

    if (count($descr_fields) == 0) {
      drupal_set_message(t("No text fields attached to product node! Export can't work properly. Please create at least one text field and use it for your products."), 'warning');
    }

    $form['term_field'] = [
      '#type' => 'select',
      '#title' => t('Category field'),
      '#description' => t('Select product term field where primary product categories are stored'),
      '#empty_value' => '',
      '#options' => $term_fields,
      '#default_value' => $config->get('term_field'),
      '#required' => TRUE,
    ];

    $form['descr_field'] = [
      '#type' => 'select',
      '#title' => t('Description field'),
      '#description' => t('Select text field which will be used as product description'),
      '#empty_value' => '',
      '#options' => $descr_fields,
      '#default_value' => $config->get('descr_field'),
      '#required' => TRUE,
    ];

    $form['delivery'] = [
      '#type' => 'select',
      '#title' => t('Select if delivery is enabled'),
      '#description' => t('Yandex.Market has "delivery" field. Select if it is enabled'),
      '#options' => ['true' => t("true"), 'false' => t("false")],
      '#default_value' => $config->get('delivery'),
    ];

    $url = Url::fromRoute('uc_store.config_form');
    $form['currency'] = [
      '#type' => 'item',
      '#title' => t('Ubercart currency'),
      '#description' => t("Should be 'RUR' for Russia"),
      '#markup' => \Drupal::config('uc_store.settings')->get('currency.code') . ' (' . \Drupal::l(t('Change'), $url) . ')',
    ];

    $url = Url::fromRoute('yml_export.yml_products')->setAbsolute($absolute = TRUE);
    $form['generated_url'] = [
      '#type' => 'item',
      '#title' => t('Generated file'),
      '#description' => t('Use this url in Yandex.Market'),
      '#markup' => $url->toString() . ' (' . \Drupal::l(t('View'), $url) . ')',
    ];

    return parent::buildForm($form, $form_state);
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
    $this->config('yml_export.settings')
      ->set('types', $form_state->getValue('types'))
      ->set('term_field', $form_state->getValue('term_field'))
      ->set('descr_field', $form_state->getValue('descr_field'))
      ->set('delivery', $form_state->getValue('delivery'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}