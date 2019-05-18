<?php

namespace Drupal\entity_field_token\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define entity field token form.
 */
class EntityFieldTokenForm extends EntityForm {

  /**
   * @var EntityTypeBundleInfoInterface
   */
  protected $entityBundleInfo;

  /**
   * Entity field token form constructor.
   */
  public function __construct(
    EntityTypeBundleInfoInterface $entity_bundle_info) {
    $this->entityBundleInfo = $entity_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form =  parent::buildForm($form, $form_state);
    $entity = $this->entity;

    $form['label'] = [
      "#type" => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('Input a human-readable label for the field token.'),
      '#default_value' => $entity->label(),
      '#maxlegnth' => 255,
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => [$entity, 'entityExist']
      ],
      '#disabled' => !$entity->isNew(),
    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#description' => $this->t('Input a field token description.'),
      '#default_value' => $entity->description,
    ];
    $form['configurations'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Entity Configurations'),
      '#tree' => FALSE,
      '#attributes' => [
        'id' => 'configurations'
      ]
    ];
    $entity_type = $this->getEntityPropertyValue('entity_type', $form_state);

    $form['configurations']['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Type'),
      '#options' => $this->getFieldEntityOptions(),
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => $entity_type,
      '#required' => TRUE,
      '#ajax' => [
        'wrapper' => 'configurations',
        'callback' => [$this, 'entityFieldTokenAjaxCallback']
      ],
    ];

    if (!empty($entity_type)) {
      $bundles = $this->getEntityPropertyValue('bundles', $form_state);

      $form['configurations']['bundles'] = [
        '#type' => 'select',
        '#title' => $this->t('Bundles'),
        '#description' => $this->t('Select the bundles that the field token 
        is associated with.'),
        '#options' => $this->getEntityBundleOptions($entity_type),
        '#default_value' => $bundles,
        '#multiple' => TRUE,
        '#required' => TRUE,
      ];
      $view_mode_options = $this->getEntityViewModesOptions($entity_type);

      if (!empty($view_mode_options)) {
        $form['configurations']['view_modes'] = [
          '#type' => 'select',
          '#title' => $this->t('View Modes'),
          '#description' => $this->t('Select the view modes on which the field 
          token will be attached. If none are selected, then all views modes 
          will display the field token.'),
          '#options' => $this->getEntityViewModesOptions($entity_type),
          '#multiple' => TRUE,
          '#default_value' => $this->entity->view_modes,
        ];
      }
    }
    $form['token_value'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Token Value'),
      '#tree' => FALSE,
      '#attributes' => [
        'id' => 'token_value'
      ]
    ];
    $field_type = $this->getEntityPropertyValue('field_type', $form_state);

    $form['token_value']['field_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Field Type'),
      '#options' => [
        'textfield' => $this->t('Text Field'),
        'text_format' => $this->t('Text Format')
      ],
      '#default_value' => $field_type,
      '#required' => TRUE,
      '#ajax' => [
        'wrapper' => 'token_value',
        'callback' => [$this, 'entityFieldTokenAjaxCallback']
      ],
    ];

    if (!empty($field_type)) {
      $value_format = NULL;
      $field_value = $this->entity->field_value;

      if (is_array($field_value) && isset($field_value['value'])) {
        $value_format = isset($field_value['format'])
          ? $field_value['format']
          : NULL;
        $field_value = $field_value['value'];
      }
      $form['token_value']['field_value'] = [
        '#type' => $field_type,
        '#title' => $this->t('Field Value'),
        '#required' => TRUE,
        '#description' => $this->t('Input the field token(s) you want to render.'),
        '#default_value' => $field_value,
      ];

      if (isset($value_format) && $field_type === 'text_format') {
        $form['token_value']['field_value']['#format'] = $value_format;
      }

      if ($this->moduleHandler->moduleExists('token')) {
        $form['token_value']['token_tree'] = [
          '#theme' => 'token_tree_link',
          '#token_types' => 'all',
          '#dialog' => TRUE,
        ];

        if (!empty($entity_type)) {
          if (strpos($entity_type, 'taxonomy_') !== FALSE) {
            $entity_type = substr($entity_type, 9);
          }
          $form['token_value']['token_tree']['#token_types'] = [$entity_type];
        }
      }
    }

    return $form;
  }

  /**
   * Entity field token ajax callback.
   *
   * @param $form
   *   The form elements.
   * @param FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   */
  public function entityFieldTokenAjaxCallback($form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    return NestedArray::getValue($form, [reset($trigger['#array_parents'])]);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = $this->entity->save();
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));

    return $status;
  }

  /**
   * Get entity property value.
   *
   * @param $property
   *   The property name.
   * @param FormStateInterface $form_state
   *   The form state object.
   *
   * @return mixed
   */
  protected function getEntityPropertyValue($property, FormStateInterface $form_state) {
    return $form_state->hasValue($property)
      ? $form_state->getValue($property)
      : $this->entity->{$property};
  }

  /**
   * Get entity view mode options.
   *
   * @param $entity_type
   *   The entity type id.
   *
   * @return array
   *   An array of view modes for a particular entity.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getEntityViewModesOptions($entity_type) {
    if (!isset($entity_type)) {
      return [];
    }
    $modes_ids = $this->entityViewModeQuery()
      ->condition('targetEntityType', $entity_type)
      ->execute();

    if (empty($modes_ids)) {
      return [];
    }
    $options = [
      'default' => $this->t('Default')
    ];
    $view_modes = $this
      ->entityViewModeStorage()
      ->loadMultiple($modes_ids);

    /** @var EntityViewMode $definition */
    foreach ($view_modes as $view_mode_id => $definition) {
      $identifier = $definition->id();
      $options[substr($identifier, strpos($identifier, '.') + 1)] = $definition->label();
    }

    return $options;
  }

  /**
   * Get entity bundle options.
   *
   * @param $entity_type
   *   The entity type.
   *
   * @return array
   */
  protected function getEntityBundleOptions($entity_type) {
    if (!isset($entity_type)) {
      return [];
    }
    $options = [];

    foreach ($this->entityBundleInfo->getBundleInfo($entity_type) as $bundle_name => $info) {
      if (!isset($info['label'])) {
        continue;
      }
      $options[$bundle_name] = $info['label'];
    }

    return $options;
  }

  /**
   * Get field entity options.
   *
   * @return array
   */
  protected function getFieldEntityOptions() {
    $options = [];

    /** @var ContentEntityType $definition */
    foreach ($this->getFieldableEntities() as $entity_id => $definition) {
      $options[$entity_id] = $definition->getLabel();
    }

    return $options;
  }

  /**
   * Get fieldable content entities.
   *
   * @return array
   */
  protected function getFieldableEntities() {
    $entities = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type => $definition) {
      if (!$definition instanceof ContentEntityType) {
        continue;
      }
      $interface = 'Drupal\Core\Entity\FieldableEntityInterface';
      $implementations = class_implements($definition->getOriginalClass());

      if (!in_array($interface, $implementations)) {
        continue;
      }
      $entities[$entity_type] = $definition;
    }

    return $entities;
  }

  /**
   * Entity view mode query.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function entityViewModeQuery() {
    return $this->entityViewModeStorage()->getQuery();
  }

  /**
   * Entity view mode storage.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function entityViewModeStorage() {
    return $this->entityTypeManager
      ->getStorage('entity_view_mode');
  }
}
