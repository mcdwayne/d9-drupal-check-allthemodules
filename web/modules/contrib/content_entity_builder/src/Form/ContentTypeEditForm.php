<?php

namespace Drupal\content_entity_builder\Form;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\content_entity_builder\ConfigurableBaseFieldConfigInterface;
use Drupal\content_entity_builder\BaseFieldConfigManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Database\Database;

/**
 * Controller for content entity type edit form.
 */
class ContentTypeEditForm extends ContentTypeFormBase {

  /**
   * The service.
   *
   * @var \Drupal\content_entity_builder\BaseFieldConfigManager
   */
  protected $baseFieldConfigManager;

  /**
   * Constructs an BlockTabsEditForm object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The storage.
   * @param \Drupal\content_entity_builder\BaseFieldConfigManager $base_field_config_manager
   *   The base_field_config manager service.
   */
  public function __construct(EntityStorageInterface $entity_storage, BaseFieldConfigManager $base_field_config_manager) {
    parent::__construct($entity_storage);
    $this->baseFieldConfigManager = $base_field_config_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('content_type'),
      $container->get('plugin.manager.content_entity_builder.base_field_config')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['id']['#disabled'] = TRUE;

    $user_input = $form_state->getUserInput();
    $form['#title'] = $this->t('Edit content entity %name', ['%name' => $this->entity->label()]);
    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'content_entity_builder/admin';

    $has_data = $this->entity->hasData();

    // Build the list of existing tabs for this blocktabs.
    $form['base_fields'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Label'),
        $this->t('Name'),
        $this->t('Type'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'base-field-order-weight',
        ],
      ],
      '#attributes' => [
        'id' => 'content-type-base-fields',
      ],
      '#empty' => $this->t('There are currently no base fields in this entity type. Add one by selecting an option below.'),
      // Render base_fields below parent elements.
      '#weight' => 5,
    ];

    foreach ($this->entity->getBaseFields() as $base_field) {
      $key = $base_field->getFieldName();

      $form['base_fields'][$key]['#attributes']['class'][] = 'draggable';
      $form['base_fields'][$key]['#weight'] = isset($user_input['base_fields']) ? $user_input['base_fields'][$key]['weight'] : NULL;
      $form['base_fields'][$key]['base_field'] = [
        '#tree' => FALSE,
        'data' => [
          'label' => [
            '#plain_text' => $base_field->getLabel(),
          ],
        ],
      ];
      $form['base_fields'][$key]['name'] = [
        '#markup' => $base_field->getFieldName(),
      ];
      $form['base_fields'][$key]['type'] = [
        '#markup' => $base_field->getFieldType(),
      ];
      $form['base_fields'][$key]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $base_field->getLabel()]),
        '#title_display' => 'invisible',
        '#default_value' => $base_field->getWeight(),
        '#delta' => 100,
        '#attributes' => [
          'class' => ['base-field-order-weight'],
        ],
      ];

      $links = [];
      $is_configurable = $base_field instanceof ConfigurableBaseFieldConfigInterface;
      if ($is_configurable) {
        $links['edit'] = [
          'title' => $this->t('Edit'),
          'url' => Url::fromRoute('content_entity_builder.base_field_edit_form', [
            'content_type' => $this->entity->id(),
            'base_field' => $key,
          ]),
        ];
      }

      $links['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('content_entity_builder.base_field_delete', [
          'content_type' => $this->entity->id(),
          'base_field' => $key,
        ]),
      ];

      $form['base_fields'][$key]['operations'] = [
        '#type' => 'operations',
        '#links' => $links,
      ];
    }

    // Build the new base field addition form and add it to the base field list.
    $new_base_field_options = [];
    $base_fields = $this->baseFieldConfigManager->getDefinitions();
    uasort($base_fields, function ($a, $b) {
      return strcasecmp($a['id'], $b['id']);
    });

    foreach ($base_fields as $key => $definition) {
      // Skip it if dependency module does not exist.
      if (!empty($definition['dependency'])) {
        $exist = \Drupal::moduleHandler()->moduleExists($definition['dependency']);
        if (empty($exist)) {
          continue;
        }
      }
      $new_base_field_options[$key] = $definition['label'];
    }
    $form['base_fields']['new'] = [
      '#tree' => FALSE,
      '#weight' => isset($user_input['weight']) ? $user_input['weight'] : NULL,
      '#attributes' => ['class' => ['draggable']],
    ];
    $form['base_fields']['new']['base_field'] = [
      'data' => [
        'field_label' => [
          '#type' => 'textfield',
          '#title' => $this->t('Label'),
          '#size' => 15,
        ],
        'field_name' => [
          '#type' => 'machine_name',
          '#title' => $this->t('Machine name'),
          '#size' => 15,
          '#maxlength' => 32,
          '#machine_name' => [
            'source' => [
              'base_fields',
              'new',
              'base_field',
              'data',
              'field_label',
            ],
            'exists' => [$this, 'fieldNameExists'],
          ],
          '#required' => FALSE,
        ],
        'field_type' => [
          '#type' => 'select',
          '#title' => $this->t('Base field'),
          '#title_display' => 'invisible',
          '#options' => $new_base_field_options,
          '#empty_option' => $this->t('Select a new field type'),
        ],
        [
          'add' => [
            '#type' => 'submit',
            '#value' => $this->t('Add base field'),
            '#validate' => ['::baseFieldValidate'],
            '#submit' => ['::baseFieldSave'],
          ],
        ],
      ],
      '#prefix' => '<div class="new-base-field">',
      '#suffix' => '</div>',
      '#wrapper_attributes' => [
        'colspan' => 3,
      ],
    ];

    $form['base_fields']['new']['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight for new base field'),
      '#title_display' => 'invisible',
      '#default_value' => count($this->entity->getBaseFields()) + 1,
      '#attributes' => ['class' => ['base-field-order-weight']],
      '#delta' => 100,
    ];
    $form['base_fields']['new']['operations'] = [
      'data' => [],
    ];

    $form['settings'] = [
      '#type' => 'details',
      '#title' => t('Entity type settings'),
      '#weight' => 100,
    ];

    $keys = $this->entity->getEntityKeys();
    $form['settings']['entity_keys'] = [
      '#type' => 'details',
      '#title' => t('Entity keys'),
      '#open' => TRUE,
    ];
    $form['settings']['entity_keys']['id'] = [
      '#type' => 'textfield',
      '#title' => t('id'),
      '#default_value' => isset($keys['id']) ? $keys['id'] : 'id',
      '#required' => TRUE,
      '#disabled' => $has_data,
    ];
    $form['settings']['entity_keys']['uuid'] = [
      '#type' => 'textfield',
      '#title' => t('uuid'),
      '#default_value' => isset($keys['uuid']) ? $keys['uuid'] : '',
      '#disabled' => $has_data,
    ];

    $label_options = [];
    foreach ($this->entity->getBaseFields() as $base_field) {
      $key = $base_field->getFieldName();
      $label_options[$key] = $key;
    }
    $form['settings']['entity_keys']['label'] = [
      '#type' => 'select',
      '#title' => t('label'),
      '#default_value' => isset($keys['label']) ? $keys['label'] : '',
      '#options' => $label_options,
      '#empty_option' => $this->t('Select a base field as entity label'),
    ];
    $paths = $this->entity->getEntityPaths();
    $type_id = $this->entity->id();
    $form['settings']['entity_paths'] = [
      '#type' => 'details',
      '#title' => t('Entity paths'),
      '#open' => TRUE,
    ];
    $form['settings']['entity_paths']['add'] = [
      '#type' => 'textfield',
      '#title' => t('Add'),
      '#default_value' => !empty($paths['add']) ? $paths['add'] : "/$type_id/add",
      '#required' => TRUE,
    ];
    $form['settings']['entity_paths']['view'] = [
      '#type' => 'textfield',
      '#title' => t('View'),
      '#default_value' => !empty($paths['view']) ? $paths['view'] : "/$type_id/{" . $type_id . "}",
      '#required' => TRUE,
    ];
    $form['settings']['entity_paths']['edit'] = [
      '#type' => 'textfield',
      '#title' => t('Edit'),
      '#default_value' => !empty($paths['edit']) ? $paths['edit'] : "/$type_id/{" . $type_id . "}/edit",
      '#required' => TRUE,
    ];
    $form['settings']['entity_paths']['delete'] = [
      '#type' => 'textfield',
      '#title' => t('Delete'),
      '#default_value' => !empty($paths['delete']) ? $paths['delete'] : "/$type_id/{" . $type_id . "}/delete",
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * Validate handler for base field.
   */
  public function baseFieldValidate($form, FormStateInterface $form_state) {
    if (!$form_state->getValue('field_label')) {
    }
  }

  /**
   * Submit handler for base field.
   */
  public function baseFieldSave($form, FormStateInterface $form_state) {
    //$this->save($form, $form_state);
    $base_field_config = $this->baseFieldConfigManager->getDefinition($form_state->getValue('field_type'));
    $base_field = [
      'field_name' => $form_state->getValue('field_name'),
      'label' => $form_state->getValue('field_label'),
      'id' => $base_field_config['id'],
      'settings' => [],
      'field_type' => $base_field_config['field_type'],
      'weight' => $form_state->getValue('weight'),
    ];
    $this->entity->addBaseField($base_field);
    $this->entity->save();

    $form_state->setRedirect(
      'content_entity_builder.base_field_edit_form',
      [
        'content_type' => $this->entity->id(),
        'base_field' => $form_state->getValue('field_name'),
      ]
    );

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validation is optional.
    $settings = $form_state->getValue('settings');
    $entity_paths = $settings['entity_paths'] ? $settings['entity_paths'] : [];
    foreach ($entity_paths as $key => $value) {
      if (substr($value, 0, 1) != '/') {
        $form_state->setErrorByName("settings][entity_paths][" . $key, $this->t('Please input valid path.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Update base field weights.
    if (!$form_state->isValueEmpty('base_fields')) {
      $this->updateBaseFieldWeights($form_state->getValue('base_fields'));
    }

    $settings = $form_state->getValue('settings');
    $entity_keys = $settings['entity_keys'] ? $settings['entity_keys'] : [];
    $this->entity->setEntityKeys($entity_keys);

    $entity_paths = $settings['entity_paths'] ? $settings['entity_paths'] : [];
    $this->entity->setEntityPaths($entity_paths);
    parent::submitForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    //parent::save($form, $form_state);
    $this->entity->setApplied(TRUE);
    foreach ($this->entity->getBaseFields() as $base_field) {
      $base_field->setApplied(TRUE);
    }
    parent::save($form, $form_state);
    \Drupal::entityDefinitionUpdateManager()->applyUpdates();
    \Drupal::service('router.builder')->rebuild();
    \Drupal::cache('discovery')->deleteAll();
  }

  /**
   * {@inheritdoc}
   */
  public function saveAndApplyUpdates(array $form, FormStateInterface $form_state) {
    $this->entity->setApplied(TRUE);
    foreach ($this->entity->getBaseFields() as $base_field) {
      $base_field->setApplied(TRUE);
    }
    parent::save($form, $form_state);
    \Drupal::entityDefinitionUpdateManager()->applyUpdates();
    \Drupal::service('router.builder')->rebuild();
    \Drupal::cache('discovery')->deleteAll();
  }

  /**
   * {@inheritdoc}
   */
  public function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save');
	/*
    $actions['save_apply'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save and apply updates'),
      '#submit' => ['::submitForm', '::saveAndApplyUpdates'],
    ];
	*/

    return $actions;
  }

  /**
   * Checks if a field machine name is taken.
   *
   * @param string $value
   *   The machine name, not prefixed.
   * @param array $element
   *   An array containing the structure of the 'field_name' element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   Whether or not the field machine name is taken.
   */
  public function fieldNameExists($value, array $element, FormStateInterface $form_state) {
    // Add the field prefix.
    return FALSE;
  }

  /**
   * Updates base field weights.
   *
   * @param array $base_fields
   *   Associative array with base_fields having base field name as keys and
   *   array with base_field data as values.
   */
  protected function updateBaseFieldWeights(array $base_fields) {
    foreach ($base_fields as $base_field_name => $base_field_data) {
      if ($this->entity->getBaseFields()->has($base_field_name)) {
        $this->entity->getBaseField($base_field_name)->setWeight($base_field_data['weight']);
      }
    }
  }

}
