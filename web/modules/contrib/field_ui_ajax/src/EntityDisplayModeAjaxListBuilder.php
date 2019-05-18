<?php

/**
 * @file
 * Contains \Drupal\field_ui_ajax\EntityDisplayModeAjaxListBuilder.
 */

namespace Drupal\field_ui_ajax;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\field_ui\EntityDisplayModeListBuilder;

/**
 * Defines a class to build a listing of view mode entities.
 *
 * @see \Drupal\Core\Entity\Entity\EntityViewMode
 */
class EntityDisplayModeAjaxListBuilder extends EntityDisplayModeListBuilder {

  /**
   * {@inheritdoc}
   */
  public function render($type = FALSE) {
    $build = [];
    foreach ($this->load() as $entity_type => $entities) {
      if (!isset($this->entityTypes[$entity_type])) {
        continue;
      }
      // When type is set the function is called from an AJAX submit and needs
      // to return only that type.
      if ($type && $type != $entity_type) {
        continue;
      }

      // Filter entities.
      if (!$this->isValidEntity($entity_type)) {
        continue;
      }
      $selector = 'js-' . str_replace(['.', '_'], '-', $entity_type);

      $table = [
        '#type' => 'table',
        '#header' => $this->buildHeader(),
        '#rows' => [],
        '#suffix' => '</div>',
      ];
      $table['#prefix'] = '<div class="' . $selector . '-table">';
      $table['#prefix'] .= '<h2>' . $this->entityTypes[$entity_type]->getLabel() . '</h2>';
      foreach ($entities as $entity) {
        if ($row = $this->buildRow($entity)) {
          $table['#rows'][$entity->id()] = $row;
        }
      }

      // Move content at the top.
      if ($entity_type == 'node') {
        $table['#weight'] = -10;
      }

      $short_type = str_replace(['entity_', '_mode'], '', $this->entityTypeId);
      $table['#rows']['_add_new']['data'][] = [
        'data' => [
          '#type' => 'link',
          '#url' => Url::fromRoute($short_type == 'view' ? 'entity.entity_view_mode.add_form' : 'entity.entity_form_mode.add_form', ['entity_type_id' => $entity_type]),
          '#title' => $this->t('Add new %label @entity-type', ['%label' => $this->entityTypes[$entity_type]->getLabel(), '@entity-type' => $this->entityType->getLowercaseLabel()]),
          '#options' => [
            'attributes' => [
              'class' => ['use-ajax', 'use-ajax-once'],
              'data-field-ui-hide' => '.' . $selector . '-add-new',
              'data-field-ui-show' => '.' . $selector . '-add-form',
            ],
          ],
        ],
        'colspan' => count($table['#header']),
      ];
      $table['#rows']['_add_new']['class'] = [$selector . '-add-new'];
      $build[$entity_type] = $table;
    }
    $build['#attached']['library'][] = 'field_ui_ajax/field_ui_ajax';
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = parent::buildRow($entity);

    $selector = 'js-' . str_replace(['.', '_'], '-', $entity->id());
    // Move row cells to 'data' if that is not set already.
    if (!isset($row['label']['data'])) {
      $label = $row['label'];
      $row['label'] = [
        'data' => $label,
        'class' => 'js-field-label',
      ];
    }
    else {
      $row['label']['class'][] = 'js-field-label';
    }
    if (!isset($row['data'])) {
      $row = ['data' => $row];
    }
    $row['class'][] = $selector;

    return $row;
  }

}
