<?php

/**
 * @file
 * Contains \Drupal\ert\Form\ReadTimeForm.
 */

namespace Drupal\ert\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Config\Entity\ConfigEntityType;

/**
 * Configure read time settings for an entity.
 */
class ReadTimeForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;
  
  /**
   * The entity type.
   * 
   * @var string
   */
  protected $entityTypeId;
  
  /**
   * The entity bundle.
   * 
   * @var string
   */
  protected $entityBundleId;
  
  /**
   * Constructs a read time config form.
   * 
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, RouteMatchInterface $route_match) {
    parent::__construct($config_factory);
    $module_handler = \Drupal::moduleHandler();
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;

    // Extracts Entity Type from route
    $route_options = $route_match->getRouteObject()->getOptions();
    $array_keys = array_keys($route_options['parameters']);
    $entity_type_parameter = array_shift($array_keys);
    /* @var $config_entity_bundle \Drupal\Core\Config\Entity\ConfigEntityBundleBase */
    $config_entity_bundle = $route_match->getParameter($entity_type_parameter);
    
    // Set properties entity type & entity bundle
    $this->entityTypeId = $config_entity_bundle->getEntityType()->getBundleOf();
    $this->entityBundleId = $config_entity_bundle->id();
  }


  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {
    return ['ert.settings'];
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'ert_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $key = $this->entityTypeId. '__' . $this->entityBundleId;
    $config = $this->config('ert.settings');

    $form[$key . '_status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable read time.'),
      '#description' => $this->t('Enables read time for this entity.'),
      '#default_value' => $config->get($key . '_status'),
    ];
    
    $form[$key .'_enabled_entity_fields'] = [
        '#type' => 'details',
        '#open' => TRUE,
        '#title' => $this->t('Enabled entity fields'),
        '#description' => $this->t('Select fields used for counting words.'),
        '#tree' => TRUE,
    ];
    
    /* @var $entity_fields \Drupal\Core\Field\FieldDefinitionInterface[] */
    $entity_fields  = $this->entityFieldManager->getFieldDefinitions($this->entityTypeId, $this->entityBundleId);
    foreach ($entity_fields as $field_name => $field){
      if($field->getTargetBundle() == $this->entityBundleId && in_array($field->getType(), ['string', 'text', 'text_long', 'entity_reference_revisions'])) {
        $form[$key . '_enabled_entity_fields'][$field_name] = [
            '#type' => 'checkbox',
            '#title' => $field_name,
            '#default_value' => (is_array($config->get($key . '_enabled_entity_fields'))) ? in_array($field_name, $config->get($key . '_enabled_entity_fields')) : 0,
            '#states' => [
                'invisible' => [
                    ':input[name="'.$key . '_status"]' => ['checked' => FALSE],
                ]
            ],
        ];
      }
    }
    
    $form[$key . '_wpm'] = array(
        '#type' => 'number',
        '#title' => t('Words per minute'),
        '#description' => t('Average reading speed used for the calculation.'),
        '#min' => 0,
        '#max' => 900,
        '#default_value' => $config->get($key . '_wpm'),
    );

    $form[$key . '_format'] = array(
        '#type' => 'select',
        '#title' => t('Format'),
        '#description' => t('How the calculation will be formatted.'),
        '#options' => array(
            'hour_short' => t('Hours & minutes, short (1 hr, 5 min)'),
            'hour_long' => t('Hours & minutes, long (1 hour, 5 minute)'),
            'min_short' => t('Minutes, short (65 min)'),
            'min_long' => t('Minutes, long (65 minute)'),
        ),
        '#default_value' => $config->get($key . '_format'),
    );

    $form[$key . '_display'] = array(
        '#type' => 'textfield',
        '#title' => t('Read time display'),
        '#description' => t("How the read time will be displayed. Use <em>%read_time</em> to output the read time formatted as above."),
        '#default_value' =>  $config->get($key . '_display'),
    );
    
    return parent::buildForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    $config = $this->config('ert.settings');
    
    $form_state->cleanValues();
    
    foreach ($form_state->getValues() as $key => $value) {
      if (strpos($key, '_enabled_entity_fields')) {
        $enabled_entity_fields= [];
        foreach ($value as $field_name => $enabled) {
          if($enabled){
            $enabled_entity_fields[] = $field_name;
          }
        }
        $value = $enabled_entity_fields;
      }
      $config->set($key, $value);
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }
}
