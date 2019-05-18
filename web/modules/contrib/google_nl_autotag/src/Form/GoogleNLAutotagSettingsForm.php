<?php

namespace Drupal\google_nl_autotag\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\encryption\EncryptionService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Configure Google NL Autotag settings for this site.
 */
class GoogleNLAutotagSettingsForm extends ConfigFormBase {

  /**
   * The encryption service.
   *
   * @var \Drupal\encryption\EncryptionService
   */
  protected $encryption;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a GoogleNLAutotagSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\encryption\EncryptionService $encryption
   *   The encryption service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EncryptionService $encryption, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->encryption = $encryption;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('encryption'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_nl_autotag_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['google_nl_autotag.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('google_nl_autotag.settings');

    $form['classification_threshold'] = [
      '#type' => 'number',
      '#step' => '.01',
      '#min' => 0,
      '#max' => 1,
      '#required' => TRUE,
      '#title' => $this->t('Classification threshold'),
      '#description' => $this->t('The threshold determines at what confidence level we consider a Google classification as valid. It should be a number between 0 and 1.'),
      '#default_value' => $config->get('classification_threshold') ?? '.5',
    ];

    foreach ($this->getContentTypeOptions() as $content_type_id => $content_type) {
      $form[$content_type_id] = [
        '#type' => 'fieldset',
        '#title' => $this->t('@type settings', [
          '@type' => $content_type,
        ]),
      ];
      $form[$content_type_id][$content_type_id . '_enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Auto-classify'),
        '#default_value' => isset($config->get('content_types')[$content_type_id]),
        '#description' => $this->t('This is the list of content types that will receive the Google Autotag field. Note that unselecting one previously selected will delete the field and all field data.'),
      ];

      $text_fields = [
        'list_string',
        'text',
        'text_long',
        'text_with_summary',
        'string',
        'string_long',
      ];
      $fields = $this->entityTypeManager->getStorage('field_config')->loadByProperties(['entity_type' => 'node', 'bundle' => $content_type_id]);
      $field_options = [];
      foreach ($fields as $field_id => $field) {
        if (array_search($field->getType(), $text_fields)) {
          $field_options[$field_id] = $field->getLabel();
        }
      }
      $selected_fields = [];
      if ($configured_fields = $config->get('content_types')[$content_type_id] ?? NULL) {
        foreach ($configured_fields as $field) {
          $selected_fields[$field] = $field;
        }
      }
      $form[$content_type_id][$content_type_id . '_fields'] = [
        '#type' => 'checkboxes',
        '#options' => $field_options,
        '#title' => $this->t('Analysis fields'),
        '#description' => $this->t('All fields checked will be included in the Google classification analysis.'),
        '#default_value' => $selected_fields,
        '#states' => [
          'visible' => [
            ':input[name="' . $content_type_id . '_enabled' . '"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Reusable function to fetch available content types.
   *
   * @return array
   *   Array of content types, keyed by ID with labels as the values.
   */
  private function getContentTypeOptions() {
    /** @var \Drupal\node\Entity\NodeType[] $content_types */
    $content_types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
    $content_type_options = [];
    foreach ($content_types as $content_type) {
      $content_type_options[$content_type->id()] = $content_type->label();
    }
    return $content_type_options;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Check for existence of fields.
    $content_types = [];
    foreach ($this->getContentTypeOptions() as $content_type_id => $content_type) {
      // If the field exists on the content type, but shouldn't, remove it.
      $cat_field = $this->entityTypeManager->getStorage('field_config')->loadByProperties([
        'entity_type' => 'node',
        'bundle' => $content_type_id,
        'field_name' => 'field_google_nl_autotag_cats',
      ]);
      if (count($cat_field) == 1 && !$form_state->getValue($content_type_id . '_enabled')) {
        reset($cat_field)->delete();
      }

      // Add to config if enabled.
      if ($form_state->getValue($content_type_id . '_enabled')) {
        $fields = [];
        foreach ($form_state->getValue($content_type_id . '_fields') as $field) {
          if ($field) {
            $fields[] = $field;
          }
        }
        $content_types[$content_type_id] = $fields;
      }

      // If the field doesn't exist on the content type and it should, add it.
      if (count($cat_field) == 0 && $form_state->getValue($content_type_id . '_enabled')) {

        // If field storage config is missing, create it.
        $field_storage = $this->entityTypeManager->getStorage('field_storage_config')->loadByProperties(['id' => 'node.field_google_nl_autotag_cats']);

        if (!$field_storage) {
          FieldStorageConfig::create([
            'field_name' => 'field_google_nl_autotag_cats',
            'type' => 'entity_reference',
            'entity_type' => 'node',
            'cardinality' => -1,
            'settings' => [
              'target_type' => 'taxonomy_term',
            ],
          ])->save();
        }

        // Add the field.
        $instance = [
          'field_name' => 'field_google_nl_autotag_cats',
          'entity_type' => 'node',
          'bundle' => $content_type_id,
          'label' => 'Google NL Autotag Categories',
          'description' => 'Google NL Autotag content classification categories.',
          'settings' => [
            'handler' => 'straw',
            'handler_settings' => [
              'target_bundles' => [
                'google_nl_autotag_categories' => 'google_nl_autotag_categories',
              ],
              'auto_create' => TRUE,
            ],
          ],
        ];

        $this->entityTypeManager->getStorage('field_config')->create($instance)->save();

        // Set the form display options.
        $form_settings = $this->entityTypeManager
          ->getStorage('entity_form_display')
          ->load('node.' . $content_type_id . '.default');
        $content = $form_settings->get('content');
        $content['field_google_nl_autotag_cats'] = [
          'type' => 'super_term_reference_autocomplete_widget',
          'region' => 'content',
          'weight' => 10,
          'settings' => [
            'match_operator' => 'CONTAINS',
            'size' => 60,
          ],
          'third_party_settings' => [],
        ];
        $form_settings->set('content', $content);
        $form_settings->save();
      };

    }

    $this->config('google_nl_autotag.settings')
      ->set('content_types', $content_types)
      ->set('classification_threshold', $form_state->getValue('classification_threshold'))
      ->save();

    parent::submitForm($form, $form_state);

  }

}
