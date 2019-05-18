<?php

/**
 * @file
 * Contains \Drupal\field_prototype\Form\FieldPrototypeAdmin.
 */

namespace Drupal\field_prototype\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Render\Element;
use Drupal\Core\Entity\EntityFieldManager;

class FieldPrototypeAdmin extends ConfigFormBase {

  protected $fieldManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'field_prototype_admin';
  }

  public function __construct(ConfigFactoryInterface $config_factory, EntityFieldManager $field_manager)
  {
    parent::__construct($config_factory);
    $this->fieldManager = $field_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('config.factory'),
        $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('field_prototype.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['field_prototype.settings'];
  }

  protected function entity_type_list() {
    $entities = \Drupal::entityTypeManager()->getDefinitions();
    $entity_types = array();
    foreach($entities as $name => $entity) {
      if(in_array('Drupal\Core\Entity\FieldableEntityInterface', class_implements($entity->getOriginalClass()))) {
        //$entity_name = ucwords(str_replace('_', ' ', $entity->id()));
        $entity_types[$name] = $entity->getLabel();
      }
    }
    asort($entity_types);

    return $entity_types;
  }

  protected function bundle_list($entities) {
    $all_bundles = $bundle_list = \Drupal::service('entity_type.bundle.info')->getAllBundleInfo();
    $bundle_list = array();

    //kint($all_bundles);
    foreach($entities as $machine => $entity_name) {//kint($entity_name, $all_bundles[$entity_name]);
      foreach($all_bundles[$machine] as $bundle_name => $bundle) {
        $bundle_list[$machine][$bundle_name] = $bundle['label'];
        //kint($bundle);
        /*foreach($entity as $bundle_name => $bundle) {
          $bundle_list[$entity_name][$bundle_name] = $bundle['label'];
        }*/
      }
      //kint('Entity bundles', $entity_bundles);
    }

    return $bundle_list;
  }

  protected function entity_instance_list() {
    $info = array();

    // Get entity types, field types, and instances
    $entity_types = $this->entity_type_list();
    $field_types = \Drupal::service('plugin.manager.field.field_type')->getDefinitions();
    $instances_array = \Drupal::service('entity_field.manager')->getFieldDefinitions();
    $bundle_list = \Drupal::service('entity_type.bundle.info')->getAllBundleInfo();
    $instance_arr = array();
    $entity_arr = array();

    // Loop through entity type bundles and instances
    foreach ($entity_types as $entity_name => $entity_type) {
      foreach($bundle_list[$entity_name] as $bundle_name => $bundle_info) {
        $entity_arr[$entity_name][$bundle_name] = $bundle_info['label'];
      }
      if (isset($instances_array[$entity_name])) {
        foreach ($instances_array[$entity_name] as $existing_bundle => $instances) {
          foreach ($instances as $instance) {
            // Get field information
            $field = field_info_field($instance['field_name']);

            // Organize field information
            $info[$instance['field_name']] = array(
                'type' => $field['type'],
                'type_label' => $field_types[$field['type']]['label'],
                'field' => $field['field_name'],
                'label' => $instance['label'],
                'widget_type' => $instance['widget']['type'],
            );

            // Put field information into array
            $instance_arr[$entity_name][$existing_bundle][$instance['field_name']] = t('@type: @field', array(
                '@type' => $field_types[$field['type']]['label'],
                '@field' => $field['field_name'],
            ));
          }
        }
      }
    }
    return array('instances' => $instance_arr, 'entities' => $entity_arr);
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $form = [
      '#submit' => ['field_prototype_admin_submit'],
      '#validate' => [
        'field_prototype_admin_validate'
        ],
    ];

    // Attach module javascript file
    $form['#attached']['library'][] = 'field_prototype/config-form';

    // Get config
    $config = $this->config('field_prototype.settings');

    // Get current config values
    // @FIXME
    // Could not extract the default value because it is either indeterminate, or
    // not scalar. You'll need to provide a default value in
    // config/install/field_prototype.settings.yml and config/schema/field_prototype.schema.yml.
    $default = \Drupal::config('field_prototype.settings')->get('field_prototype_default_bundle');
    // @FIXME
    // Could not extract the default value because it is either indeterminate, or
    // not scalar. You'll need to provide a default value in
    // config/install/field_prototype.settings.yml and config/schema/field_prototype.schema.yml.
    $other_entity_type = \Drupal::config('field_prototype.settings')->get('field_prototype_other_entity_type');
    // @FIXME
    // Could not extract the default value because it is either indeterminate, or
    // not scalar. You'll need to provide a default value in
    // config/install/field_prototype.settings.yml and config/schema/field_prototype.schema.yml.
    $other_bundle = \Drupal::config('field_prototype.settings')->get('field_prototype_other_bundle');

    // Get lists of entity types and field instances
    $entity_instance_list = \Drupal::service('entity_type.bundle.info')->getAllBundleInfo();
    // @FIXME
    // The Assets API has totally changed. CSS, JavaScript, and libraries are now
    // attached directly to render arrays using the #attached property.
    // 
    // 
    // @see https://www.drupal.org/node/2169605
    // @see https://www.drupal.org/node/2408597
    // drupal_add_js(array('instance_bundles' => $entity_instance_list['instances']), 'setting');

    // @FIXME
    // The Assets API has totally changed. CSS, JavaScript, and libraries are now
    // attached directly to render arrays using the #attached property.
    // 
    // 
    // @see https://www.drupal.org/node/2169605
    // @see https://www.drupal.org/node/2408597
    // drupal_add_js(array('entity_instances' => $entity_instance_list['entities']), 'setting');


    // Get list of entity types for Other field
    $entity_types = $this->entity_type_list();
    $entity_types['node'] = 'Node';
    // Set js setting
    $form['#attached']['drupalSettings']['field_prototype']['entity_types'] = $entity_types;

    // Get list of bundles for Other field
    $all_bundles = $bundle_list = \Drupal::service('entity_type.bundle.info')->getAllBundleInfo();
    // kint($all_bundles);
    $bundle_list = $this->bundle_list($entity_types);
    // Set js setting
    $form['#attached']['drupalSettings']['field_prototype']['bundles'] = $bundle_list;

    // Field for default bundle in field overview row
    $form['bundle-default'] = [
      '#type' => 'select',
      '#title' => t('Default bundle'),
      '#options' => [
        'None' => t('None'),
        // No bundle selected
            'Prototype' => t('Prototype'),
        // Prototype bundle
            'Current' => 'Current',
        // The bundle whose fields are being viewed
            'Last' => t('Last Selected'),
        'Other' => 'Other',
        // Select a different bundle
      ],
      '#default_value' => $config->get('field_prototype_default_bundle'),
      '#description' => t('Select the default bundle when cloning a field'),
    ];

    // Full list of entity types, displayed if user selects "Other"
    $form['other-entity-type'] = [
      '#type' => 'select',
      '#title' => t('Entity Type'),
      '#options' => $entity_types,
      //'#default_value' => $other_entity_type,
      '#empty_option' => t('- Select a bundle -'),
      '#description' => t('Bundle'),
      '#attributes' => [
        'class' => [
          'field-select'
          ]
        ],
      '#states' => [
        'visible' => [
          ':input[name="bundle-default"]' => [
            'value' => 'Other'
            ]
          ]
        ],
        '#default_value' => $config->get('field_prototype_other_entity_type'),
    ];

    // Full list of bundles, displayed if user selects "Other"
    $form['other-bundle'] = [
      '#type' => 'select',
      '#title' => t('Bundle'),
      '#options' => $bundle_list,
      //'#default_value' => $other_bundle,
      '#empty_option' => t('- Select a bundle -'),
      '#description' => t('Bundle'),
      '#attributes' => [
        'class' => [
          'field-select'
          ]
        ],
      '#states' => [
        'visible' => [
          ':input[name="bundle-default"]' => [
            'value' => 'Other'
            ]
          ]
        ],
        '#default_value' => $config->get('field_prototype_other_bundle'),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // If "Other" selected but no bundle chosen return error
    if ($form_state->getValue([
      'bundle-default'
      ]) == 'Other') {
      if (!$form_state->getValue(['other-entity-type'])) {
        $form_state->setErrorByName('other-entity-type', 'Please select an entity type');
      }
      if (!$form_state->getValue(['other-bundle'])) {
        $form_state->setErrorByName('other-bundle', 'Please select a bundle');
      }
    }
  }

  public function _submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    // Set config variables
    \Drupal::configFactory()->getEditable('field_prototype.settings')->set('field_prototype_default_bundle', $form_state->getValue(['bundle-default']))->save();
    \Drupal::configFactory()->getEditable('field_prototype.settings')->set('field_prototype_other_bundle', $form_state->getValue(['other-bundle']))->save();
    \Drupal::configFactory()->getEditable('field_prototype.settings')->set('field_prototype_other_entity_type', $form_state->getValue(['other-entity-type']))->save();
  }

}
?>
