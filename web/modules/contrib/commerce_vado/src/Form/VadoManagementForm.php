<?php

namespace Drupal\commerce_vado\Form;

use Drupal\Core\Messenger\Messenger;
use Drupal\commerce_product\Entity\ProductVariationType;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Basic settings form for Commerce VADO.
 *
 * Provides configuration form to toggle fields on product variation types.
 *
 * Class VadoManagementForm
 *
 * @package Drupal\commerce_vado\Form
 */
class VadoManagementForm extends ConfigFormBase {

  protected $entityTypeManager;
  protected $messenger;

  /**
   * VadoManagementForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Configuration interface.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity manager.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   Drupal messenger.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entityTypeManager, Messenger $messenger) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entityTypeManager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vado_management_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['commerce_vado.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $wrapper = 'product_variations';
    $form['#prefix'] = '<div id="' . $wrapper . '">';
    $form['#suffix'] = '</div>';
    $config = $this->configFactory()->getEditable('commerce_vado.settings');
    // Load & format product variation types for checkboxes.
    $product_variation_types = ProductVariationType::loadMultiple();
    $product_variation_types_formatted = [];
    foreach ($product_variation_types as $type) {
      $product_variation_types_formatted[$type->id()] = $type->label();
    }
    $selected = $config->get('product_variations');
    $form['product_variations'] = [
      '#title' => $this->t('Product Variations'),
      '#description' => $this->t('Enable variation addon children for these product variations.'),
      '#type' => 'checkboxes',
      '#options' => $product_variation_types_formatted,
      '#default_value' => $selected,
      '#ajax' => [
        'callback' => [$this, 'ajaxWarning'],
        'wrapper' => $wrapper,
      ]
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  public function ajaxWarning(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory()->getEditable('commerce_vado.settings');
    $enabled = $config->get('product_variations');
    $items = [];

    foreach ($form_state->getValues()['product_variations'] as $key => $state) {
      if ($state === 0 && $enabled[$key] === $key) {
        $items[] = $key;
      }
    }
    $items = implode(',', $items);
    if ($items) {
      $this->messenger->addWarning(t('All addon data will be lost for the following: @items', ['@items' => $items]));
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Define the fields we need to add here, so we can loop instead of
    // repeating code.
    $neededFields = [
      'field_variation_addon' => $this->t('Product Variation Add On'),
      'field_variation_addon_sync' => $this->t('Add On Sync Quantity'),
    ];
    foreach ($values['product_variations'] as $key => $value) {
      foreach ($neededFields as $neededField => $label) {
        $field_storage = FieldStorageConfig::loadByName('commerce_product_variation', $neededField);
        if (!$field_storage) {
          FieldStorageConfig::create([
            'entity_type' => 'commerce_product_variation',
            'field_name' => $neededField,
            'type' => $neededField === 'field_variation_addon' ? 'entity_reference' : 'boolean',
            'settings' => $neededField === 'field_variation_addon' ? [
              'target_type' => 'commerce_product_variation',
            ] : [],
            'cardinality' => 1,
          ])->save();
        }
        $field = FieldConfig::loadByName('commerce_product_variation', $key, $neededField);
        // Bit funky but allows us to safely delete the field if it exists,
        // or skip if it doesn't.
        if (!$value) {
          if ($field) {
            $field->delete();
          }
          continue;
        }
        if (!$field) {
          $field = FieldConfig::create([
            'entity_type' => 'commerce_product_variation',
            'field_name' => $neededField,
            'bundle' => $key,
            'label' => $label,
          ])->setDefaultValue(TRUE);
          $field->save();

          // Set visibility and component type so it automatically shows up.
          $display = $this->entityTypeManager->getStorage('entity_form_display')
            ->load('commerce_product_variation.' . $value . '.default');
          $display->setComponent($neededField, [
            'label' => 'hidden',
            'type' => $neededField === 'field_require_approval' ? 'boolean_checkbox' : 'string_textfield',
            'weight' => 90,
          ])->save();
        }
      }
    }
    // Save config for future reference.
    $config = $this->configFactory()->getEditable('commerce_vado.settings');
    $config->set('product_variations', $values['product_variations'])->save();
  }

}
