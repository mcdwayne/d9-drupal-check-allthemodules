<?php

namespace Drupal\customers_canvas\Form;

use Drupal\Component\Serialization\SerializationInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Customers canvas settings for this site.
 */
class SettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * The serializer which helps us validate JSON.
   *
   * @var \Drupal\Component\Serialization\SerializationInterface
   */
  protected $serializer;

  /**
   * The manager that helps us pull a list of content types.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   For passing on to the parent constructor method.
   * @param \Drupal\Component\Serialization\SerializationInterface $serializer
   *   Using the serializer to check for valid JSON.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Using the entity type manager to load content types.
   */
  public function __construct(ConfigFactoryInterface $config_factory, SerializationInterface $serializer, EntityTypeManagerInterface $entityTypeManager) {
    ConfigFormBase::__construct($config_factory);
    $this->serializer = $serializer;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @var \Drupal\Component\Serialization\SerializationInterface $serializer */
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $serializer = $container->get('serialization.json');
    $entity_type_manager = $container->get('entity_type.manager');
    $config_factory = $container->get('config.factory');
    return new static($config_factory, $serializer, $entity_type_manager);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'customers_canvas_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['customers_canvas.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attached']['library'][] = 'customers_canvas/admin';

    // The builder url.
    $form['builder_url'] = [
      '#title' => 'Builder URL',
      '#type' => 'textfield',
      '#field_prefix' => '[https://yourwebsite.com]/',
      '#default_value' => $this->config('customers_canvas.settings')->get('builder_url'),
      '#description' => $this->t("Does not require first slash. This is the path used to load the iframe for the Customer's Canvas builder tool."),
    ];
    $form['builder_title'] = [
      '#title' => 'Builder Title',
      '#type' => 'textfield',
      '#default_value' => $this->config('customers_canvas.settings')->get('builder_title'),
    ];

    $form['iframe_config'] = [
      '#type' => 'details',
      '#title' => t("Connect to the Customer's Canvas Instance URL"),
      '#open' => TRUE,
      '#description' => $this->t("The instance URL appears as follows at the top of the Customer's Canvas administrative web interface:<br><br>https://h.customerscanvas.com/Users/xxxxxxxx-yyyy-xxxx-yyyy-xxxxxxxxxxxx/SimplePolygraphy/Resources/SPEditor/Scripts/IFrame/IframeApi.js<br><br>xxxxxxxx-yyyy-xxxx-yyyy-xxxxxxxxxxxx is the API connection string that will be stored and used to load the builder."),
    ];
    $form['iframe_config']['iframe_script'] = [
      '#title' => 'API Connection String',
      '#type' => 'textfield',
      '#default_value' => $this->config('customers_canvas.settings')->get('iframe_script'),
      '#size' => 45,
      '#description' => 'Connection string should follow the format: xxxxxxxx-yyyy-xxxx-yyyy-xxxxxxxxxxxx',
      '#maxlength' => 255,
    ];

    $form['customers_canvas_url'] = [
      '#title' => 'Customer\'s Canvas URL',
      '#type' => 'textfield',
      '#size' => 100,
      '#default_value' => $this->config('customers_canvas.settings')->get('customers_canvas_url'),
      '#placeholder' => 'https://h.customerscanvas.com/Users/' . $this->config('customers_canvas.settings')->get('iframe_script') . '/SimplePolygraphy/',
    ];

    // Builder JSON for basic editor.
    $form['builder_json'] = [
      '#title' => 'The JSON that controls the builder.',
      '#type' => 'textarea',
      '#default_value' => $this->config('customers_canvas.settings')->get('builder_json'),
      '#rows' => 5,
    ];

    // Builder JSON for multi-editor.
    $form['multi_editor_builder_json'] = [
      '#title' => 'The JSON that controls the multi-editor builder.',
      '#type' => 'textarea',
      '#default_value' => $this->config('customers_canvas.settings')->get('multi_editor_builder_json'),
      '#rows' => 15,
    ];

    $form['access_denied'] = [
      '#title' => 'Access denied message.',
      '#type' => 'textarea',
      '#default_value' => $this->config('customers_canvas.settings')->get('access_denied'),
    ];

    $form['types_config'] = [
      '#type' => 'details',
      '#title' => t('Configure where you will store Customers Canvas information.'),
      '#open' => TRUE,
      '#description' => $this->t('Selecting a checkbox here will add fields used for customers canvas.'),
    ];

    $form['types_config']['node_types'] = [
      '#title' => $this->t('Content Types'),
      '#description' => $this->t("Enable Customer's Canvas for these types of content."),
      '#type' => 'checkboxes',
      '#options' => $this->nodeTypeOptions(),
      '#default_value' => $this->config('customers_canvas.settings')->get('node_types'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * The validation is based on the same validation that json_field module uses
   * to determine if the JSON is formatted correctly. By trying to decode the
   * given JSON, we can determine if the format is valid.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Determine if JSON is accurate.
    try {
      $this->serializer->decode($form_state->getValue('builder_json'));
    }
    catch (\Exception $e) {
      $form_state->setErrorByName('builder_json', $this->t('The Builder JSON you entered is not valid JSON.'));
    }

    // Determine if JSON is accurate for multi-editor.
    try {
      $this->serializer->decode($form_state->getValue('multi_editor_builder_json'));
    }
    catch (\Exception $e) {
      $form_state->setErrorByName('multi_editor_builder_json', $this->t('The Multi-editor Builder JSON you entered is not valid JSON.'));
    }
  }

  /**
   * Get the list of content types.
   *
   * @return array
   *   The list of node types.
   */
  public function nodeTypeOptions() {
    $types = NodeType::loadMultiple();
    $types_formatted = [];
    foreach ($types as $type) {
      $types_formatted[$type->id()] = $type->label();
    }
    return $types_formatted;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('customers_canvas.settings')
      ->set('builder_url', $form_state->getValue('builder_url'))
      ->set('builder_title', $form_state->getValue('builder_title'))
      ->set('iframe_script', $form_state->getValue('iframe_script'))
      ->set('customers_canvas_url', $form_state->getValue('customers_canvas_url'))
      ->set('builder_json', $form_state->getValue('builder_json'))
      ->set('multi_editor_builder_json', $form_state->getValue('multi_editor_builder_json'))
      ->set('node_types', $form_state->getValue('node_types'))
      ->set('access_denied', $form_state->getValue('access_denied'))
      ->save();

    // Add fields. Shamelessly pulled from commerce_vado.
    $neededFields = [
      'cc_product_json' => $this->t('Customers Canvas Product JSON'),
    ];
    $node_types = $form_state->getValue('node_types');
    foreach ($node_types as $key => $value) {
      foreach ($neededFields as $neededField => $label) {
        $field_storage = FieldStorageConfig::loadByName('node', $neededField);
        if (!$field_storage) {
          FieldStorageConfig::create([
            'entity_type' => 'node',
            'field_name' => $neededField,
            'type' => 'string_long',
            'cardinality' => 1,
          ])->save();
        }

        $field = FieldConfig::loadByName('node', $key, $neededField);
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
            'entity_type' => 'node',
            'field_name' => $neededField,
            'bundle' => $key,
            'label' => $label,
          ]);
          $field->save();
        }
      }
    }

    parent::submitForm($form, $form_state);
  }

}
