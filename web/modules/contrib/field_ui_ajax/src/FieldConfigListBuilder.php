<?php

/**
 * @file
 * Contains \Drupal\field_ui_ajax\FieldConfigListBuilder.
 */

namespace Drupal\field_ui_ajax;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Url;
use Drupal\field\FieldConfigInterface;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides lists of field config entities.
 */
class FieldConfigListBuilder extends ConfigEntityListBuilder {

  /**
   * The name of the entity type the listed fields are attached to.
   *
   * @var string
   */
  protected $targetEntityTypeId;

  /**
   * The name of the bundle the listed fields are attached to.
   *
   * @var string
   */
  protected $targetBundle;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type manager
   */
  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, FieldTypePluginManagerInterface $field_type_manager) {
    parent::__construct($entity_type, $entity_manager->getStorage($entity_type->id()));

    $this->entityManager = $entity_manager;
    $this->fieldTypeManager = $field_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static($entity_type, $container->get('entity.manager'), $container->get('plugin.manager.field.field_type'));
  }

  /**
   * {@inheritdoc}
   */
  public function render($target_entity_type_id = NULL, $target_bundle = NULL) {
    $this->targetEntityTypeId = $target_entity_type_id;
    $this->targetBundle = $target_bundle;

    $build = parent::render();
    $build['table']['#attributes']['class'][] = 'js-field-ui-ajax-overview';
    $build['table']['#empty'] = $this->t('No fields are present yet.');
    $build['#attached']['library'][] = 'field_ui_ajax/field_ui_ajax';

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entities = array_filter($this->entityManager->getFieldDefinitions($this->targetEntityTypeId, $this->targetBundle), function ($field_definition) {
      return $field_definition instanceof FieldConfigInterface;
    });

    // Sort the entities using the entity class's sort() method.
    // See \Drupal\Core\Config\Entity\ConfigEntityBase::sort().
    uasort($entities, [$this->entityType->getClass(), 'sort']);
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'label' => $this->t('Label'),
      'field_name' => [
        'data' => $this->t('Machine name'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'field_type' => $this->t('Field type'),
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $field_config) {
    /** @var \Drupal\field\FieldConfigInterface $field_config */
    $field_storage = $field_config->getFieldStorageDefinition();
    $route_parameters = [
      'field_config' => $field_config->id(),
    ] + FieldUI::getRouteBundleParameter($this->entityManager->getDefinition($this->targetEntityTypeId), $this->targetBundle);

    $selector = 'js-' . str_replace(['.', '_'], '-', $field_config->id());
    $row = [
      'id' => Html::getClass($field_config->getName()),
      'data' => [
        'label' => [
          'data' => ['#markup' => '<div class="field-ui-transition">' . $field_config->getLabel() .'</div>'],
          'class' => 'js-field-label',
        ],
        'field_name' => [
          'data' => ['#markup' => '<div class="field-ui-transition">' . $field_config->getName() .'</div>'],
        ],
        'field_type' => [
          'data' => [
            [
              '#type' => 'link',
              '#prefix' => '<div class="field-ui-transition">',
              '#suffix' => '</div>',
              '#title' => $this->fieldTypeManager->getDefinitions()[$field_storage->getType()]['label'],
              '#url' => Url::fromRoute("entity.field_config.{$this->targetEntityTypeId}_storage_edit_form", $route_parameters),
              '#options' => ['attributes' => [
                'title' => $this->t('Edit field settings.'),
                'class' => ['use-ajax', 'use-ajax-once', $selector . '-storage-form-trigger'],
                'data-field-ui-hide' => '.' . $selector,
                'data-field-ui-show' => '.' . $selector . '-storage-form',
              ]],
            ],
          ],
          'class' => ['storage-settings'],
        ],
      ],
      'class' => [$selector],
    ];

    // Add the operations.
    $row['data'] = $row['data'] + parent::buildRow($field_config);
    $row['data']['operations']['data']['#prefix'] = '<div class="field-ui-transition">';
    $row['data']['operations']['data']['#suffix'] = '</div>';

    if ($field_storage->isLocked()) {
      $row['data']['operations'] = ['data' => ['#markup' => $this->t('Locked')]];
      $row['class'][] = 'menu-disabled';
    }

    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\field\FieldConfigInterface $entity */
    $operations = parent::getDefaultOperations($entity);

    $selector = 'js-' . str_replace(['.', '_'], '-', $entity->id());

    if ($entity->access('update') && $entity->hasLinkTemplate("{$entity->getTargetEntityTypeId()}-field-edit-form")) {
      $operations['edit'] = [
        'title' => $this->t('Edit'),
        'weight' => 10,
        'url' => $entity->urlInfo("{$entity->getTargetEntityTypeId()}-field-edit-form"),
        'attributes' => [
          'class' => ['use-ajax', 'use-ajax-once', $selector . '-edit-form-trigger'],
          'data-field-ui-hide' => '.action-links, .tableresponsive-toggle-columns, .js-field-ui-ajax-overview',
          'data-field-ui-show' => '.' . $selector . '-edit-form',
        ],
      ];
    }
    if ($entity->access('delete') && $entity->hasLinkTemplate("{$entity->getTargetEntityTypeId()}-field-delete-form")) {
      $operations['delete'] = [
        'title' => $this->t('Delete'),
        'weight' => 100,
        'url' => $entity->urlInfo("{$entity->getTargetEntityTypeId()}-field-delete-form"),
        'attributes' => [
          'class' => ['use-ajax', 'use-ajax-once', $selector . '-delete-form-trigger'],
          'data-field-ui-hide' => '.' . $selector,
          'data-field-ui-show' => '.' . $selector . '-delete-form',
        ],
      ];
    }

    $operations['storage-settings'] = [
      'title' => $this->t('Storage settings'),
      'weight' => 20,
      'attributes' => [
        'title' => $this->t('Edit storage settings.'),
        'class' => ['use-ajax', 'use-ajax-once', $selector . '-storage-form-trigger'],
        'data-field-ui-hide' => '.' . $selector,
        'data-field-ui-show' => '.' . $selector . '-storage-form',
      ],
      'url' => $entity->urlInfo("{$entity->getTargetEntityTypeId()}-storage-edit-form"),
    ];
    $operations['edit']['attributes']['title'] = $this->t('Edit field settings.');
    $operations['delete']['attributes']['title'] = $this->t('Delete field.');

    return $operations;
  }

}
