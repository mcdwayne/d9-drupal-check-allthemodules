<?php

namespace Drupal\flexfield\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\Url;

/**
 * Plugin implementation of the 'flex' field type.
 *
 * @FieldType(
 *   id = "flex",
 *   label = @Translation("Flexfield"),
 *   description = @Translation("This field stores simple mluti-value fields in the database."),
 *   default_widget = "flex_default",
 *   default_formatter = "flex_formatter"
 * )
 */
class FlexItem extends FieldItemBase {

  /**
   * The default max length for each flexfield item
   * @var integer
   */
  protected $max_length_default = 255;

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    // Need to have at least one item by default because the table is created
    // before the user gets a chance to customize and will throw an Exception
    // if there isn't at least one column defined.
    return [
      'columns' => [
        'value' => [
          'name' => 'value',
          'max_length' => 255,
        ],
      ],
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties = [];

    // Prevent early t() calls by using the TranslatableMarkup.
    foreach ($field_definition->getSetting('columns') as $delta => $item) {
      $properties[$item['name']] = DataDefinition::create('string')
        ->setLabel(new TranslatableMarkup($item['name'] . ' value'))
        ->setRequired(FALSE);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {

    $schema = [];

    foreach ($field_definition->getSetting('columns') as $delta => $item) {
      $schema['columns'][$item['name']] = [
        'type' => 'varchar',
        'length' => $item['max_length'],
      ];
    }

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    foreach ($field_definition->getSetting('columns') as $delta => $item) {
      $values[$item['name']] = $random->word(mt_rand(1, $item['max_length']));
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    foreach ($this->getSetting('columns') as $delta => $item) {
      if ($max_length = $item['max_length']) {
        $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
        $constraints[] = $constraint_manager->create('ComplexData', [
          $item['name'] => [
            'Length' => [
              'max' => $max_length,
              'maxMessage' => t('%name: may not be longer than @max characters.', [
                '%name' => $item['name'],
                '@max' => $max_length
              ]),
            ],
          ],
        ]);
      }
    }

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {

    $elements = [];

    if ($form_state->isRebuilding()) {
      $settings = $form_state->getValue('settings');
    }
    else {
      $settings = $this->getSettings();
      $settings['items'] = $settings['columns'];
    }

    // Add a new item if there aren't any or we're rebuilding.
    if ($form_state->get('add') || count($settings['items']) == 0) {
      $settings['items'][] = [];
      $form_state->set('add', NULL);
    }

    $wrapper_id = 'flexfield-items-wrapper';
    $elements['#tree'] = TRUE;

    // Need to pass the columns on so that it persists in the settings between
    // ajax rebuilds
    $elements['columns'] = [
      '#type' => 'value',
      '#value' => $settings['columns'],
    ];

    // Support copying settings from another flexfield
    if (!$has_data) {
      $sources = $this->getExistingFlexFieldStorageOptions($form_state->get('entity_type_id'));
      $elements['clone'] = [
        '#type' => 'select',
        '#title' => t('Clone Settings From:'),
        '#options' => [
          '' => '- Don\'t Clone Settings -',
        ] + $sources,
        '#attributes' => [
          'data-id' => 'flexfield-settings-clone',
        ],
      ];

      $elements['clone_message'] = [
        '#type' => 'container',
        '#states' => [
          'invisible' => [
            'select[data-id="flexfield-settings-clone"]' => ['value' => ''],
          ],
        ],
        // Initialize the display so we don't see it flash on init page load
        '#attributes' => [
          'style' => 'display: none;',
        ],
      ];

      $elements['clone_message']['message'] = [
        '#markup' => 'The selected flexfield field settings will be cloned. Any existing settings for this field will be overwriiten. Field widget and formatter settings will not be cloned.',
        '#prefix' => '<div class="messages messages--warning" role="alert" style="display: block;">',
        '#suffix' => '</div>',
      ];
    }

    // We're using the 'items' container for the form configuration rather than
    // putting it directly in 'columns' because the schema method gets run
    // between ajax form rebuilds and would be given any new 'columns' that
    // were added (but not created yet) which results in a missing column
    // database error.
    $elements['items'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('The flexfield items'),
      '#description' => $this->t('These can be re-ordered on the main field settings form after the field is created'),
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
      '#states' => [
         'visible' => [
           'select[data-id="flexfield-settings-clone"]' => ['value' => ''],
         ],
       ],
    ];

    foreach ($settings['items'] as $i => $item) {
      if ($i === $form_state->get('remove')) {
        $form_state->set('remove', NULL);
        continue;
      }

      $elements['items'][$i]['name'] = [
        '#type' => 'machine_name',
        '#description' => $this->t('A unique machine-readable name containing only letters, numbers, or underscores. This will be used in the column name on the field table in the database.'),
        '#default_value' => !empty($settings['items'][$i]['name']) ? $settings['items'][$i]['name'] : 'value_' . $i,
        '#disabled' => $has_data,
        '#machine_name' => [
          'exists' => [$this, 'machineNameExists'],
          'label' => t('Machine-readable name'),
          'standalone' => TRUE,
        ],
      ];

      $elements['items'][$i]['max_length'] = [
        '#type' => 'number',
        '#title' => $this->t('Maximum length'),
        '#default_value' => !empty($settings['items'][$i]['max_length']) ? $settings['items'][$i]['max_length'] : $this->max_length_default,
        '#required' => TRUE,
        '#description' => t('The maximum length of the field in characters.'),
        '#min' => 1,
        '#disabled' => $has_data,
      ];

      $elements['items'][$i]['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove'),
        '#submit' => [get_class($this) . '::removeSubmit'],
        '#name' => 'remove:' . $i,
        '#delta' => $i,
        '#disabled' => $has_data,
        '#ajax' => [
          'callback' => [$this, 'actionCallback'],
          'wrapper' => $wrapper_id,
        ],
      ];
    }

    if (!$has_data) {
      $elements['actions'] = [
        '#type' => 'actions',
      ];
      $elements['actions']['add'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add another'),
        '#submit' => [get_class($this) . '::addSubmit'],
        '#ajax' => [
          'callback' => [$this, 'actionCallback'],
          'wrapper' => $wrapper_id,
        ],
        '#states' => [
           'visible' => [
             'select[data-id="flexfield-settings-clone"]' => ['value' => ''],
           ],
         ],
      ];
    }

    $form_state->setCached(FALSE);

    return $elements;
  }

  /**
   * Submit handler for the StorageConfigEditForm.
   *
   * This handler is added in flexfield.module since it has to be placed
   * directly on the submit button (which we don't have access to in our
   * ::storageSettingsForm() method above).
   */
  public static function submitStorageConfigEditForm(array &$form, FormStateInterface $form_state) {
    // Rekey our column settings and overwrite the values in form_state so that
    // we have clean settings saved to the db
    $columns = [];

    if ($field_name = $form_state->getValue(['settings', 'clone'])) {
      list($bundle_name, $field_name) = explode('.', $field_name);
      // Grab the columns from the field storage config
      $columns = FieldStorageConfig::loadByName($form_state->get('entity_type_id'), $field_name)->getSetting('columns');
      // Grab the field settings too as a starting point
      $source_field_config = FieldConfig::loadByName($form_state->get('entity_type_id'), $bundle_name, $field_name);
      $form_state->get('field_config')->setSettings($source_field_config->getSettings())->save();
    }
    else {
      foreach ($form_state->getValue(['settings', 'items']) as $item) {
        $columns[$item['name']] = $item;
        unset($columns[$item['name']]['remove']);
      }
    }
    $form_state->setValue(['settings', 'columns'], $columns);
    $form_state->setValue(['settings', 'items'], null);

    // Reset the field storage config property - it will be recalculated when
    // accessed via the property definitions getter
    // @see Drupal\field\Entity\FieldStorageConfig::getPropertyDefinitions()
    // If we don't do this, an exception is thrown during the table update that
    // is very difficult to recover from since the original field tables have
    // already been removed at that point.
    $field_storage_config = $form_state->getBuildInfo()['callback_object']->getEntity();
    $field_storage_config->set('propertyDefinitions', NULL);
  }

  /**
   * Check for duplicate names on our columns settings.
   */
  public function machineNameExists($value, array $form, FormStateInterface $form_state) {
    $count = 0;
    foreach ($form_state->getValue(['settings', 'items']) as $item) {
      if ($item['name'] == $value) {
        $count++;
      }
    }
    return $count > 1;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    foreach ($this->getProperties() as $name => $value) {
      if ($value->getValue()) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function actionCallback(array &$form, FormStateInterface $form_state) {
    return $form['settings']['items'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function addSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->set('add', TRUE);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->set('remove', $form_state->getTriggeringElement()['#delta']);
    $form_state->setRebuild();
  }

  /**
   * Get the existing flexfield storage config options.
   */
  protected function getExistingFlexFieldStorageOptions($entity_type_id) {
    $sources = ['' => []];
    $existing_flexfields = \Drupal::service('entity_field.manager')->getFieldMapByFieldType('flex');
    $bundle_info = \Drupal::entityManager()->getBundleInfo($entity_type_id);
    foreach ($existing_flexfields[$entity_type_id] as $field_name => $info) {
      // Skip ourself
      if ($this->getFieldDefinition()->getName() != $field_name) {
        foreach ($info['bundles'] as $bundle_name) {
          $group = isset($bundle_info[$bundle_name]['label']) ? $bundle_info[$bundle_name]['label'] : '';
          $info = FieldConfig::loadByName($entity_type_id, $bundle_name, $field_name);
          $sources[$group][$bundle_name . '.' . $info->getName()] = $info->getLabel();
        }
      }
    }
    return $sources;
  }

  /***********************************************************************
   *
   * Field Settings
   *
   ***********************************************************************/

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'field_settings' => [],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {

    $elements = [
      '#type' => 'fieldset',
      '#title' => t('Flexfield Items'),
    ];

    $settings = $this->getSettings();
    if ($form_state->isRebuilding()) {
      $field_settings = $form_state->getValue('settings')['field_settings'];
      $settings['field_settings'] = $field_settings;
    }
    else {
      $field_settings = $this->getSetting('field_settings');
    }

    $items = $this->getFlexFieldManager()->getFlexFieldItems($settings);

    $wrapper_id = 'flexfield-settings-wrapper';
    $elements['field_settings'] = [
      '#type' => 'table',
      '#header' => ['', t('Type'), t('Settings'), t('Output Settings'), t('Weight')],
      '#empty' => t('There are no items yet. Add an item.', [
        '@add-url' => Url::fromRoute('mymodule.manage_add'),
      ]),
      '#attributes' => [
        'class' => ['flexfield-settings-table'],
      ],
      '#tableselect' => FALSE,
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'field-settings-order-weight',
        ],
      ],
      '#attached' => [
        'library' => ['flexfield/flexfield-admin']
      ],
      '#weight' => -99,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
    ];

    // Build the table rows and columns.
    foreach ($items as $name => $item) {

      $weight = isset($field_settings[$name]['weight']) ? $field_settings[$name]['weight'] : 0;
      // TableDrag: Mark the table row as draggable.
      $elements['field_settings'][$name]['#attributes']['class'][] = 'draggable';
      // TableDrag: Sort the table row according to its existing/configured weight.
      $elements['field_settings'][$name]['#weight'] = $weight;

      $elements['field_settings'][$name]['handle'] = [
        '#markup' => '<span></span>',
      ];

      $elements['field_settings'][$name]['type'] = [
        '#type' => 'select',
        '#title' => "<em>$name</em> type",
        '#options' => $this->getFlexFieldManager()->getFlexFieldWidgetOptions(),
        '#default_value' => isset($field_settings[$name]['type']) ? $field_settings[$name]['type'] : NULL,
        '#ajax' => [
          'callback' => [$this, 'widgetSelectionCallback'],
          'wrapper' => $wrapper_id,
        ],
      ];

      // Add our plugin widget and formatter settings form
      $elements['field_settings'][$name]['widget_settings'] = $item->widgetSettingsForm($form, $form_state);
      $elements['field_settings'][$name]['formatter_settings'] = $item->formatterSettingsForm($form, $form_state);

      // TableDrag: Weight column element.
      $elements['field_settings'][$name]['weight'] = [
        '#type' => 'weight',
        '#title' => t('Weight for @title', ['@title' => $name]),
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        // Classify the weight element for #tabledrag.
        '#attributes' => ['class' => ['field-settings-order-weight']],
      ];

    }


    return $elements;
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function widgetSelectionCallback(array &$form, FormStateInterface $form_state) {
    return $form['settings']['field_settings'];
  }

  /**
   * Get the flexfield_type manager plugin.
   */
  public function getFlexFieldManager() {
    return \Drupal::service('plugin.manager.flexfield_type');
  }

}
