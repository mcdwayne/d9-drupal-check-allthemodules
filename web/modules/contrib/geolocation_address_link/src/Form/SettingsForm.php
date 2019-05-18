<?php

namespace Drupal\geolocation_address_link\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeBundleInfo;

/**
 * Class SettingsForm.
 *
 * @package Drupal\geolocation_address_link\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * @var Drupal\Core\Entity\EntityFieldManager
   */
  protected $field_manager;

  /**
   * @var Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $bundle_info;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityFieldManager $field_manager, EntityTypeBundleInfo $bundle_info) {
    parent::__construct($config_factory);
    $this->field_manager = $field_manager;
    $this->bundle_info = $bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('geolocation_address_link.settings');
    $form['container'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Geolocation Address Link Settings'),
      '#description' => $this->t('<p>Each entity type that uses this functionality needs two special fields: <ol><li>The usual address field to allow the user to input an address.</li><li>A geolocation field that contains the geocoordinates of the address.</li></ol></p><p>When the entity is saved, any value in the address field will be used to automatically update the geo coordinates in the related geolocation field. Hide the geolocation field on the entity edit form since the user will not actually be able to update it manually.</p>'),
    ];

    // Field all address fields in this Drupal installation.
    $field_map = $this->field_manager->getFieldMapByFieldType('address');
    $options = [];
    foreach ($field_map as $entity_type => $entity_map) {
      $bundle_info = $this->bundle_info->getBundleInfo($entity_type);
      foreach ($entity_map as $field_name => $data) {
        foreach ($data['bundles'] as $bundle) {
          if ($info = FieldConfig::loadByName($entity_type, $bundle, $field_name)) {
            $key = $entity_type . ':'. $bundle;
            $bundle_name = $bundle_info[$bundle]['label'] . ' ('. $entity_type . ')';
            $options[$bundle_name][$entity_type][$bundle][$field_name] = $info->getLabel() . ' (' . $info->getName() . ')';
          }
        }
      }
    }
    ksort($options);

    // Find all geolocation fields in this Drupal installation.
    $field_map = $this->field_manager->getFieldMapByFieldType('geolocation');
    $options2 = [];
    foreach ($field_map as $entity_type => $entity_map) {
      $bundle_info = $this->bundle_info->getBundleInfo($entity_type);
      foreach ($entity_map as $field_name => $data) {
        foreach ($data['bundles'] as $bundle) {
          if ($info = FieldConfig::loadByName($entity_type, $bundle, $field_name)) {
            $bundle_name = $bundle_info[$bundle]['label'] . ' ('. $entity_type . ')';
            $options2[$bundle_name][$entity_type][$bundle][$field_name] = $info->getLabel() . ' (' . $info->getName() . ')';
          }
        }
      }
    }
    ksort($options2);

    // Create an array of bundles that have both an address field and a
    // geolocation field as the possible fields that can be mapped to each
    // other.
    $combined = [];
    foreach ($options as $label => $labels) {
      foreach ($labels as $entity_type => $types) {
        foreach ($types as $bundle => $bundles) {
          foreach ($bundles as $field_name => $value) {
            if (array_key_exists($label, $options2)) {
              foreach ($options2[$label][$entity_type][$bundle] as $field_name2 => $value2) {
                if (array_key_exists($label, $options)
                && array_key_exists($entity_type, $options[$label])
                && array_key_exists($bundle, $options[$label][$entity_type])) {
                  $combined[$label][$entity_type .':'. $bundle .':'. $field_name .':'. $field_name2] = $value .' -> '. $value2;
                }
              }
            }
          }
        }
      }
    }

    $form['container']['fields'] = [
      '#type' => 'select',
      '#title' => $this->t('Field(s)'),
      '#description' => $this->t('Select the field combination(s) that should be updated when the entity is saved. The values in the address field on the left will be used to update the geolocation coordinates in the geolocation field on the right. Note that any previous value in the geolocation field will be wiped out by this update!'),
      '#default_value' => $config->get('fields'),
      '#options' => $combined,
      '#multiple' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'geolocation_address_link_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'geolocation_address_link.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('geolocation_address_link.settings')
      ->set('fields', $form_state->getValue('fields'))
      ->save();
  }

}
