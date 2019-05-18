<?php

namespace Drupal\bibcite_entity;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the reference entity type.
 */
class ReferenceViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['bibcite_reference']['citation'] = [
      'title' => $this->t('Citation'),
      'help' => $this->t('Render reference as citation'),
      'field' => [
        'id' => 'bibcite_citation',
      ],
    ];

    $data['bibcite_reference']['links'] = [
      'title' => $this->t('Links'),
      'help' => $this->t('Render reference links'),
      'field' => [
        'id' => 'bibcite_links',
      ],
    ];

    $data['bibcite_reference']['bulk_form'] = [
      'title' => $this->t('Operations bulk form'),
      'help' => $this->t('Add a form element that lets you run operations on multiple reference entries.'),
      'field' => [
        'id' => 'bulk_form',
      ],
    ];

    /*
     * @todo Optimize structure of fields handlers.
     */

    $data['bibcite_reference__keywords'] = [
      'keywords_target_id' => [
        'title' => $this->t('Keywords'),
        'field' => [
          'id' => 'field',
        ],
        'argument' => [
          'id' => 'numeric',
        ],
        'filter' => [
          'id' => 'numeric',
        ],
        'sort' => [
          'id' => 'standard',
        ],
        'entity field' => 'keywords',
      ],
      'table' => [
        'group' => $this->t('Reference'),
        'provider' => 'bibcite_entity',
        'entity type' => 'bibcite_reference',
        'join' => [
          'bibcite_reference' => [
            'left_field' => 'id',
            'field' => 'entity_id',
            'extra' => [
              [
                'field' => 'deleted',
                'value' => 0,
                'numeric' => TRUE,
              ],
            ],
          ],
        ],
      ],
    ];

    $entity_type = $this->entityManager->getDefinition('bibcite_keyword');
    $data['bibcite_reference__keywords']['keywords_target_id']['relationship'] = [
      'base' => $this->getViewsTableForEntityType($entity_type),
      'base field' => $entity_type->getKey('id'),
      'label' => $entity_type->getLabel(),
      'title' => $entity_type->getLabel(),
      'id' => 'standard',
    ];

    $data['bibcite_reference__author'] = [
      'author_target_id' => [
        'title' => $this->t('Author'),
        'field' => [
          'id' => 'field',
        ],
        'argument' => [
          'id' => 'numeric',
        ],
        'filter' => [
          'id' => 'numeric',
        ],
        'sort' => [
          'id' => 'standard',
        ],
        'entity field' => 'author',
      ],
      'author_category' => [
        'title' => $this->t('Author (Category)'),
        'field' => [
          'id' => 'standard',
        ],
        'argument' => [
          'id' => 'string',
        ],
        'filter' => [
          'id' => 'string',
        ],
        'sort' => [
          'id' => 'standard',
        ],
      ],
      'author_role' => [
        'title' => $this->t('Author (Role)'),
        'field' => [
          'id' => 'standard',
        ],
        'argument' => [
          'id' => 'string',
        ],
        'filter' => [
          'id' => 'string',
        ],
        'sort' => [
          'id' => 'standard',
        ],
      ],
      'table' => [
        'group' => $this->t('Reference'),
        'provider' => 'bibcite_entity',
        'entity type' => 'bibcite_reference',
        'join' => [
          'bibcite_reference' => [
            'left_field' => 'id',
            'field' => 'entity_id',
            'extra' => [
              [
                'field' => 'deleted',
                'value' => 0,
                'numeric' => TRUE,
              ],
            ],
          ],
        ],
      ],
    ];

    $entity_type = $this->entityManager->getDefinition('bibcite_contributor');
    $data['bibcite_reference__author']['author_target_id']['relationship'] = [
      'base' => $this->getViewsTableForEntityType($entity_type),
      'base field' => $entity_type->getKey('id'),
      'label' => $entity_type->getLabel(),
      'title' => $entity_type->getLabel(),
      'id' => 'standard',
    ];

    return $data;
  }

}
