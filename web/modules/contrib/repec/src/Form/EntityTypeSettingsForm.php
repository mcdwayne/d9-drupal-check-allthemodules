<?php

namespace Drupal\repec\Form;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\repec\RepecInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Entity type settings form.
 */
class EntityTypeSettingsForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Repec service.
   *
   * @var \Drupal\repec\RepecInterface
   */
  protected $repec;

  /**
   * Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * EntityTypeSettingsForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param \Drupal\repec\RepecInterface $repec
   *   Repec service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RepecInterface $repec, MessengerInterface $messenger, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->repec = $repec;
    $this->messenger = $messenger;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'), $container->get('repec'), $container->get('messenger'), $container->get('entity_field.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'repec_entity_type_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $bundle = NULL) {
    $storage = [
      'entity_type_id' => $entity_type_id,
      'bundle' => $bundle,
    ];
    $form_state->setStorage($storage);

    // @todo add date format options
    // @todo check system wide settings first
    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable RePEc'),
      '#default_value' => $this->repec->getEntityBundleSettings('enabled', $entity_type_id, $bundle),
    ];

    $form['serie'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Serie'),
      '#states' => [
        'visible' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['serie']['serie_type'] = [
      '#type' => 'select',
      '#title' => t('Series'),
      '#options' => $this->repec->availableSeries(),
      '#default_value' => $this->repec->getEntityBundleSettings('serie_type', $entity_type_id, $bundle),
      '#states' => [
        'visible' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['serie']['serie_name'] = [
      '#type' => 'textfield',
      '#title' => t('Serie name'),
      '#description' => t('Name for the serie (example: Working Paper).'),
      '#default_value' => $this->repec->getEntityBundleSettings('serie_name', $entity_type_id, $bundle),
      '#states' => [
        'visible' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['serie']['serie_directory'] = [
      '#type' => 'textfield',
      '#title' => t('Templates directory for this serie'),
      '#description' => t('It must have exactly six letters. Currently limited to Working Paper so defaulting to "wpaper"'),
      '#maxlength' => 6,
      '#size' => 6,
      // The serie_directory is currently not configurable because
      // it is hardcoded as a default value in the
      // RepecInterface::getSeriesTemplate()
      // due to the current limitation to working papers.
      // '#default_value' => $repec->getEntityBundleSettings
      // ('serie_directory', $entity_type_id, $bundle),.
      '#default_value' => RepecInterface::SERIES_WORKING_PAPER,
      '#disabled' => TRUE,
      '#states' => [
        'visible' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['restriction'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Optional restriction'),
      '#states' => [
        'visible' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['restriction']['restriction_by_field'] = [
      '#type' => 'checkbox',
      '#title' => t('Limit shared entities by field'),
      '#description' => t('While enabled, allows to evaluate a boolean field to share the entity on RePEc or not.'),
      '#default_value' => $this->repec->getEntityBundleSettings('restriction_by_field', $entity_type_id, $bundle),
      '#states' => [
        'visible' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['restriction']['restriction_field'] = [
      '#type' => 'select',
      '#title' => 'Restriction field',
      '#description' => t('Select the boolean field that will be used to post on RePEc.'),
      '#options' => $this->getBooleanFields($entity_type_id, $bundle),
      '#default_value' => $this->repec->getEntityBundleSettings('restriction_field', $entity_type_id, $bundle),
      '#states' => [
        'visible' => [
          ':input[name="restriction_by_field"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[name="restriction_by_field"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $bundleFields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
    $fieldOptions = [];
    foreach ($bundleFields as $fieldName => $fieldDefinition) {
      $fieldOptions[$fieldName] = $fieldDefinition->getLabel();
    }

    $repecTemplateFields = $this->repec->getTemplateFields(RepecInterface::SERIES_WORKING_PAPER);

    $form['template_field_mapping'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Template field mapping'),
      '#states' => [
        'visible' => [
          ':input[name="enabled"]' => ['checked' => TRUE],
        ],
      ],
    ];
    foreach ($repecTemplateFields as $fieldKey => $fieldLabel) {
      $form['template_field_mapping'][$fieldKey] = [
        '#type' => 'select',
        '#title' => $fieldLabel,
        '#options' => $fieldOptions,
        '#default_value' => $this->repec->getEntityBundleSettings($fieldKey, $entity_type_id, $bundle),
        '#states' => [
          'visible' => [
            ':input[name="enabled"]' => ['checked' => TRUE],
          ],
          'required' => [
            ':input[name="enabled"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save configuration'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  private function getBooleanFields($entity_type_id, $bundle) {
    $result = [];
    $bundleFields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
    /** @var \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition */
    foreach ($bundleFields as $fieldName => $fieldDefinition) {
      if ($fieldDefinition->getType() === 'boolean') {
        $result[$fieldName] = $fieldDefinition->getLabel();
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // @todo validate selected field types

    // @todo validate multiple bundle configuration for the same serie:
    // an existing serie must have the same value as another
    // potentially used bundle.
    $directory = $form_state->getValue('serie_directory');
    if (strlen($directory) !== 6) {
      $form_state->setErrorByName('serie_directory', t('Serie directory must have exactly 6 letters.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $storage = $form_state->getStorage();
    // Empty configuration if set again to disabled.
    if (!$values['enabled']) {
      $settings = $this->repec->getEntityBundleSettingDefaults();
    }
    else {
      $settings = $this->repec->getEntityBundleSettings('all', $storage['entity_type_id'], $storage['bundle']);
      foreach ($this->repec->availableEntityBundleSettings() as $setting) {
        if (isset($values[$setting])) {
          $settings[$setting] = is_array($values[$setting]) ? array_keys(array_filter($values[$setting])) : $values[$setting];
        }
      }
    }
    $this->repec->setEntityBundleSettings($settings, $storage['entity_type_id'], $storage['bundle']);
    $this->repec->createSeriesTemplate();

    $this->messenger->addMessage(t('Your changes have been saved.'));
  }

}
