<?php

namespace Drupal\commerce_alexanders\Form;

use Drupal\commerce_order\Entity\OrderItemType;
use Drupal\commerce_product\Entity\ProductType;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

/**
 * Basic settings form for Commerce Alexanders submodule.
 *
 * Provides configuration form to toggle fields on product types to send.
 *
 * Class CommerceAlexandersManagementForm
 *
 * @package Drupal\commerce_alexanders\Form
 */
class CommerceAlexandersManagementForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_alexanders_management_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['commerce_alexanders.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('commerce_alexanders.settings');
    $product_types = ProductType::loadMultiple();
    $product_types_formatted = [];

    $form['order_state'] = [
      '#type' => 'select',
      '#title' => $this->t('Order state'),
      '#description' => $this->t('Only send orders to Alexanders when they hit this state.'),
      '#options' => [
        'fulfillment' => $this->t('Fulfillment'),
        'completed' => $this->t('Completed'),
      ],
    ];

    foreach ($product_types as $product_type) {
      $product_types_formatted[$product_type->id()] = $product_type->label();
    }
    $selected = $config->get('product_types');
    $form['product_types'] = [
      '#title' => $this->t('Product Types'),
      '#description' => $this->t('Send Alexanders orders that contain these types of products.'),
      '#type' => 'checkboxes',
      '#options' => $product_types_formatted,
      '#default_value' => $selected,
    ];


    $order_item_types = OrderItemType::loadMultiple();
    $order_item_types_formatted = [];
    foreach ($order_item_types as $order_item_type) {
      $order_item_types_formatted[$order_item_type->id()] = $order_item_type->label();
    }
    $selected = $config->get('order_item_types');
    $form['order_item_types'] = [
      '#title' => $this->t('Order Item Types'),
      '#description' => $this->t('Add necessary fields for URLs to the following order item types.'),
      '#type' => 'checkboxes',
      '#options' => $order_item_types_formatted,
      '#default_value' => $selected,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Define the fields we need to add here, so we can loop instead of
    // repeating code.
    $product_fields = [
      'alxdr_send' => $this->t('Send to Alexanders API'),
    ];
    $order_item_fields = [
      'alxdr_file_url' => $this->t('Alexanders File URL'),
      'alxdr_secondary_url' => $this->t('Alexanders Secondary URL'),
    ];

    foreach ($values['product_types'] as $key => $value) {
      foreach ($product_fields as $field_name => $label) {
        $field_storage = FieldStorageConfig::loadByName('commerce_product', $field_name);
        if (!$field_storage) {
          FieldStorageConfig::create([
            'entity_type' => 'commerce_product',
            'field_name' => $field_name,
            'type' => 'boolean',
            'cardinality' => 1,
          ])->save();
        }
        $field = FieldConfig::loadByName('commerce_product', $key, $field_name);

        if (!$value) {
          if ($field) {
            $field->delete();
          }
          continue;
        }

        if (!$field) {
          $field = FieldConfig::create([
            'entity_type' => 'commerce_product',
            'field_name' => $field_name,
            'bundle' => $key,
            'label' => $label,
          ])->setDefaultValue(TRUE);
          $field->save();
        }
      }
    }

    foreach ($values['order_item_types'] as $key => $value) {
      foreach ($order_item_fields as $field_name => $label) {
        $field_storage = FieldStorageConfig::loadByName('commerce_order_item', $field_name);
        if (!$field_storage) {
          FieldStorageConfig::create([
            'entity_type' => 'commerce_order_item',
            'field_name' => $field_name,
            'type' => 'string',
            'cardinality' => 1,
          ])->save();
        }
        $field = FieldConfig::loadByName('commerce_order_item', $key, $field_name);

        if (!$value) {
          if ($field) {
            $field->delete();
          }
          continue;
        }

        if (!$field) {
          $field = FieldConfig::create([
            'entity_type' => 'commerce_order_item',
            'field_name' => $field_name,
            'bundle' => $key,
            'label' => $label,
          ]);
          $field->save();
        }
      }
    }

    // Save config for future reference.
    $config = $this->configFactory()->getEditable('commerce_alexanders.settings');
    $config->set('product_types', $values['product_types'])->save();
    $config->set('order_state', $values['order_state'])->save();
    $config->set('order_item_types', $values['order_item_types'])->save();
  }

}
