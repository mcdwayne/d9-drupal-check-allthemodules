<?php

/**
 * @file
 * Contains \Drupal\entity_base\EntityBaseSimpleListBuilder.
 */

namespace Drupal\entity_base;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Defines a class to build a listing of entities.
 */
class EntityBaseSimpleListBuilder extends EntityBaseBasicListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = parent::buildHeader();

    $this->arrayInsert($header, 'created', [
      'label' => [
        'data' => $this->t('Name'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
        'field' => 'name',
        'specifier' => 'name',
        ]
    ]);

    $this->arrayInsert($header, 'created', [
      'owner' => [
        'data' => $this->t('Owner'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
        'field' => 'uid',
        'specifier' => 'uid',
      ]
    ]);

    $this->arrayInsert($header, 'created', [
      'status' => [
        'data' => $this->t('Status'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
        'field' => 'status',
        'specifier' => 'status',
      ]
    ]);

    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = parent::buildRow($entity);

    $this->arrayInsert($row, 'created', [
      'label' => [
        'data' => $this->buildLabel($entity),
      ]
    ]);

    $this->arrayInsert($row, 'created', [
      'owner' => [
        'data' => [
          '#theme' => 'username',
          '#account' => $entity->getOwner(),
        ],
      ]
    ]);

    $this->arrayInsert($row, 'created', [
      'status' => $entity->isActive() ? $this->t('active') : $this->t('not active'),
    ]);

    return $row;
  }

  /**
   * Builds label for the entity.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @return array
   */
  protected function buildLabel(EntityInterface $entity) {
    $langcode = $entity->language()->getId();
    $uri = $entity->toUrl();
    $options = $uri->getOptions();
    $options += ($langcode != LanguageInterface::LANGCODE_NOT_SPECIFIED && isset($languages[$langcode]) ? array('language' => $languages[$langcode]) : array());
    $uri->setOptions($options);
    $label = [
      '#type' => 'link',
      '#title' => $entity->label(),
      '#url' => $uri,
    ];
    return $label;
  }

}
