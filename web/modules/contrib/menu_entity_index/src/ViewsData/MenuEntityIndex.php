<?php

namespace Drupal\menu_entity_index\ViewsData;

use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\menu_entity_index\TrackerInterface;

/**
 * Provides Menu Entity Index views integration.
 */
class MenuEntityIndex {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Menu Entity Index Tracker service.
   *
   * @var \Drupal\menu_entity_index\TrackerInterface
   */
  protected $tracker;

  /**
   * Creates a new ViewsData instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\menu_entity_index\TrackerInterface $tracker
   *   The tracker service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TrackerInterface $tracker) {
    $this->entityTypeManager = $entity_type_manager;
    $this->tracker = $tracker;
  }

  /**
   * Returns the views data.
   *
   * @return array
   *   The views data.
   *
   * @see hook_views_data()
   */
  public function getViewsData() {
    $data = [];

    $data['menu_entity_index']['table']['group'] = $this->t('Menu Entity Index');
    $data['menu_entity_index']['table']['provider'] = 'menu_entity_index';
    $data['menu_entity_index']['table']['entity revision'] = FALSE;

    $data['menu_entity_index']['menu_name'] = [
      'title' => $this->t('Menu name'),
      'help' => $this->t('Menu name menu link belongs to.'),
      'field' => [
        'id' => 'menu',
      ],
      'filter' => [
        'id' => 'menu',
        'tracked_only' => TRUE,
      ],
      'argument' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['menu_entity_index']['level'] = [
      'title' => $this->t('Menu level'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['menu_entity_index']['entity_type'] = [
      'title' => $this->t('Menu link type'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'string',
      ],
      'argument' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['menu_entity_index']['entity_subtype'] = [
      'title' => $this->t('Menu link subtype'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'bundle',
      ],
      'argument' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['menu_entity_index']['entity_id'] = [
      'title' => $this->t('Menu link ID'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['menu_entity_index']['entity_uuid'] = [
      'title' => $this->t('Menu link parent UUID'),
      'help' => $this->t('Drupal UUID of entity.'),
      'field' => [
        'id' => 'standard',
        'click sortable' => FALSE,
      ],
      'filter' => [
        'id' => 'string',
      ],
      'argument' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['menu_entity_index']['parent_type'] = [
      'title' => $this->t('Parent menu link type'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'string',
      ],
      'argument' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['menu_entity_index']['parent_id'] = [
      'title' => $this->t('Parent menu link ID'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['menu_entity_index']['parent_uuid'] = [
      'title' => $this->t('Parent menu link UUID'),
      'help' => $this->t('Drupal UUID of parent menu link.'),
      'field' => [
        'id' => 'standard',
        'click sortable' => FALSE,
      ],
      'filter' => [
        'id' => 'string',
      ],
      'argument' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['menu_entity_index']['langcode'] = [
      'title' => $this->t('Menu link language'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'language',
      ],
      'argument' => [
        'id' => 'language',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['menu_entity_index']['target_type'] = [
      'title' => $this->t('Target entity type'),
      'field' => [
        'id' => 'menu_entity_index_target_type',
      ],
      'filter' => [
        'id' => 'menu_entity_index_target_type',
      ],
      'argument' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['menu_entity_index']['target_subtype'] = [
      'title' => $this->t('Target entity subtype'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'bundle',
      ],
      'argument' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['menu_entity_index']['target_id'] = [
      'title' => $this->t('Target entity ID'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['menu_entity_index']['target_uuid'] = [
      'title' => $this->t('Target entity UUID'),
      'help' => $this->t('Drupal UUID of entity.'),
      'field' => [
        'id' => 'standard',
        'click sortable' => FALSE,
      ],
      'filter' => [
        'id' => 'string',
      ],
      'argument' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['menu_entity_index']['target_langcode'] = [
      'title' => $this->t('Target entity language'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'language',
      ],
      'argument' => [
        'id' => 'language',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    // Get entity types we track and host entity type.
    $tracked_entity_types = array_filter($this->entityTypeManager->getDefinitions(), function (EntityTypeInterface $type) {
      return $this->tracker->isTrackedEntityType($type) || $type->id() == 'menu_link_content';
    });

    // Extend views data for each targeted entity type.
    foreach ($tracked_entity_types as $entity_type_id => $entity_type) {
      $is_host_entity = $entity_type_id == 'menu_link_content';
      /** @var \Drupal\views\EntityViewsDataInterface $views_data */
      if ($this->entityTypeManager->hasHandler($entity_type_id, 'views_data') && $views_data = $this->entityTypeManager->getHandler($entity_type_id, 'views_data')) {
        // Add a join from the entity base table to the menu_entity_index table.
        $base_table = $views_data->getViewsTableForEntityType($entity_type);
        $data['menu_entity_index']['table']['join'][$base_table] = [
          'left_field' => $entity_type->getKey('id'),
          'field' => $is_host_entity ? 'entity_id' : 'target_id',
          'extra' => [
            [
              'field' => $is_host_entity ? 'entity_type' : 'target_type',
              'value' => $entity_type_id,
            ],
          ],
        ];

        // Some entity types might not be translatable.
        if ($entity_type->hasKey('langcode')) {
          $data['menu_entity_index']['table']['join'][$base_table]['extra'][] = [
            'field' => $is_host_entity ? 'langcode' : 'target_langcode',
            'left_field' => $entity_type->getKey('langcode'),
            'operation' => '=',
          ];
        }

        // Add a relationship pseudo field for each entity type. For the host
        // entity, add it to the existing field.
        if (!$is_host_entity) {
          $data['menu_entity_index'][$entity_type_id . '_id'] = [
            'title' => $this->t('@type', [
              '@type' => $entity_type->getLabel(),
            ]),
            'real field' => 'target_id',
            'relationship' => [
              'id' => 'standard',
              'base' => $entity_type->getDataTable() ? $entity_type->getDataTable() : $base_table,
              'base field' => $entity_type->getKey('id'),
              'label' => $entity_type->getLabel(),
            ],
          ];
        }
        else {
          $data['menu_entity_index']['entity_id']['relationship'] = [
            'id' => 'standard',
            'base' => $entity_type->getDataTable() ? $entity_type->getDataTable() : $base_table,
            'base field' => $entity_type->getKey('id'),
            'label' => $entity_type->getLabel(),
          ];
          $data['menu_entity_index']['parent_id']['relationship'] = [
            'id' => 'standard',
            'base' => $entity_type->getDataTable() ? $entity_type->getDataTable() : $base_table,
            'base field' => $entity_type->getKey('id'),
            'label' => $entity_type->getLabel(),
          ];
        }
      }
    }

    return $data;
  }

}
