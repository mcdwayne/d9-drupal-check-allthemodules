<?php
/**
 * @file
 * Contains Drupal\taxonomy_place\Form\SettingsForm.
 */
namespace Drupal\taxonomy_place\Form;

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
 * @package Drupal\taxonomy_place\Form
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
  protected function getEditableConfigNames() {
    return [
      'taxonomy_place.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */

  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('taxonomy_place.settings');

    $form['container'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Taxonomy Place Settings'),
      '#description' => $this->t('<p>Configure the Place vocabulary and fields.</p>'),
    ];

    $options = taxonomy_vocabulary_get_names();
    $form['container']['vid'] = [
      '#type' => 'select',
      '#title' => $this->t('Place vocabulary'),
      '#options' => $options,
      '#default_value' => $config->get('vid'),
      '#required' => TRUE,
    ];

    $options = self::getTaxonomyFieldOptions(['address']);
    $form['container']['address_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Place address field'),
      '#description' => $this->t('The address field on the Place taxonomy.'),
      '#options' => $options,
      '#default_value' => $config->get('address_field'),
      '#required' => TRUE,
    ];

    $options = self::getTaxonomyFieldOptions(['string','text','text_long','text_with_summary']);
    $form['container']['description_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Place description field'),
      '#description' => $this->t('The description field on the Place taxonomy.'),
      '#options' => $options,
      '#default_value' => $config->get('description_field'),
      '#empty_value' => '',
    ];

    $options = self::getTaxonomyFieldOptions(['string','text','text_long','text_with_summary']);
    $form['container']['short_name_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Place short name field'),
      '#description' => $this->t('The short name field on the Place taxonomy - a text field to contain a short name for this place.'),
      '#options' => $options,
      '#default_value' => $config->get('short_name_field'),
      '#empty_value' => '',
    ];

    $options = self::getTaxonomyFieldOptions(['string','text','text_long','text_with_summary']);
    $form['container']['sortable_name_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Place sortable name field'),
      '#description' => $this->t('The sortable name field on the Place taxonomy - a text field that will contain a value that can be used in views to sort a nested taxonomy correctly.'),
      '#options' => $options,
      '#default_value' => $config->get('sortable_name_field'),
      '#empty_value' => '',
    ];

    $form['container']['labels'] = array(
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => t('Custom labels'),
      '#description' => t('Optional. Change labels of address fields to terminology more appropriate for the address of a place. For instance, change the label "Organization" to say "Place name".'),
      '#tree' => TRUE,
    );
    $labels = $config->get('labels');
    $form['container']['labels']['organization'] = array(
      '#type' => 'textfield',
      '#title' => t('Organization'),
      '#default_value' => $labels['organization'],
    );
    $form['container']['labels']['first_name'] = array(
      '#type' => 'textfield',
      '#title' => t('First name'),
      '#default_value' => $labels['first_name'],
    );
    $form['container']['labels']['last_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Last name'),
      '#default_value' => $labels['last_name'],
    );

    $form['container2'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Referencing Entities Settings'),
      '#description' => $this->t('<p>Each entity type that references the Place taxonomy needs two special fields: <ul><li>The usual entity_reference field to link the node to existing places in the Place taxonomy.</li><li>An address field to allow the user to input a place even if it does not already exist in the Place taxonomy.</li></ul></p><p>Either hide the taxonomy term field on the form so the user only sees the address field, or arrange the node form so that the address field is below the entity reference field to act as an "Other" option when the desired place does not already exist in the taxonomy. When the node is saved, any value in the address field will be used to create a link to that term, adding a new term to the Place taxonomy if it does not already exist.</p>'),
    ];

    $field_map = $this->field_manager->getFieldMapByFieldType('address');
    $options = [];
    $address_bundles = [];
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

    $field_map = $this->field_manager->getFieldMapByFieldType('entity_reference');
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

    $form['container2']['fields'] = array(
      '#type' => 'select',
      '#title' => $this->t('Field(s)'),
      '#description' => $this->t('Select the field combination(s) that should be updated when the entity is saved. The value in the address field on the left will be used to create a link to the taxonomy term on the right, including creating a term if it does not already exist. Note that any previous value in the taxonomy term will be wiped out by this update!'),
      '#default_value' => $config->get('fields'),
      '#options' => $combined,
      '#multiple' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('taxonomy_place.settings')
      ->set('vid', $form_state->getValue('vid'))
      ->set('description_field', $form_state->getValue('description_field'))
      ->set('address_field', $form_state->getValue('address_field'))
      ->set('short_name_field', $form_state->getValue('short_name_field'))
      ->set('sortable_name_field', $form_state->getValue('sortable_name_field'))
      ->set('fields', $form_state->getValue('fields'))
      ->set('labels', $form_state->getValue('labels'))
      ->save();
  }

  /**
   * Helper to get a simple array of field name options.
   *
   * @param $field_types
   *   An array of the types of field we are searching for.
   * @return $options
   *   An array of fields of the requested type that exist on taxonomy terms.
   */
  public function getTaxonomyFieldOptions(array $field_types) {
    $options = [];
    foreach ($field_types as $field_type) {
      $field_map = $this->field_manager->getFieldMapByFieldType($field_type);
      foreach ($field_map as $entity_type => $entity_map) {
        if ($entity_type == 'taxonomy_term') {
          foreach ($entity_map as $field_name => $data) {
            $options[$field_name] = $field_name;
          }
        }
      }
    }
    ksort($options);
    return $options;
  }
}
