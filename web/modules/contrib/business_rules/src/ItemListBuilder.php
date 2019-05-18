<?php

namespace Drupal\business_rules;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Business rules Items entities.
 */
abstract class ItemListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label']       = ['data' => ['#markup' => $this->t('Label')]];
    $header['id']          = ['data' => ['#markup' => $this->t('Machine name')]];
    $header['type']        = ['data' => ['#markup' => $this->t('Type')]];
    $header['description'] = ['data' => ['#markup' => $this->t('Description')]];
    $header['tags']        = $this->t('Tags');
    $header['filter']      = [
      'data'  => ['#markup' => 'filter'],
      'style' => 'display: none',
    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label']       = ['data' => ['#markup' => $entity->label()]];
    $row['id']          = ['data' => ['#markup' => $entity->id()]];
    $row['type']        = ['data' => ['#markup' => $entity->getTypeLabel()]];
    $row['description'] = ['data' => ['#markup' => $entity->getDescription()]];
    $row['tags']        = implode(', ', $entity->getTags());

    $search_string = $entity->label() . ' ' .
      $entity->id() . ' ' .
      $entity->getTypeLabel() . ' ' .
      $entity->getTypeLabel() . ' ' .
      $entity->getDescription() . ' ' .
      implode(' ', $entity->getTags());

    $row['filter'] = [
      'data'  => [['#markup' => '<span class="table-filter-text-source">' . $search_string . '</span>']],
      'style' => ['display: none'],
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {

    $view_mode                        = \Drupal::request()->get('view_mode');
    $output['#attached']['library'][] = 'system/drupal.system.modules';

    $output['filters'] = [
      '#type'       => 'container',
      '#attributes' => [
        'class' => ['table-filter', 'js-show'],
      ],
    ];

    $output['filters']['text'] = [
      '#type'        => 'search',
      '#title'       => $this->t('Search'),
      '#size'        => 30,
      '#placeholder' => $this->t('Search for a item'),
      '#attributes'  => [
        'class'        => ['table-filter-text'],
        'data-table'   => '.searchable-list',
        'autocomplete' => 'off',
        'title'        => $this->t('Enter a part of the item to filter by.'),
      ],
    ];

    $class = $this->entityType->getClass();
    if ($view_mode == 'tags') {
      $tags = $class::loadAllTags();
      $table = parent::render();

      foreach ($tags as $tag) {
        $tagged_items              = $table;
        $output["tags_table_$tag"] = [
          '#type'  => 'details',
          '#title' => $tag,
          '#open'  => FALSE,
        ];

        foreach ($tagged_items['table']['#rows'] as $key => $tagged_item) {
          $item      = $class::load($key);
          $item_tags = $item->getTags();
          if (!in_array($tag, $item_tags)) {
            unset($tagged_items['table']['#rows'][$key]);
          }
        }

        $output["tags_table_$tag"][$tag] = $tagged_items;
        if (!isset($output['table']['#attributes']['class'])) {
          $output["tags_table_$tag"][$tag]['table']['#attributes']['class'] = ['searchable-list'];
        }
        else {
          $output["tags_table_$tag"][$tag]['table']['#attributes']['class'][] = ['searchable-list'];
        }

      }

      $untagged_items = $table;
      foreach ($untagged_items['table']['#rows'] as $key => $tagged_item) {
        $item      = $class::load($key);
        $item_tags = $item->getTags();
        if (count($item_tags)) {
          unset($untagged_items['table']['#rows'][$key]);
        }
      }

      $output['tags_table_no_tags']     = [
        '#type'  => 'details',
        '#title' => $this->t('Untagged items'),
        '#open'  => FALSE,
      ];
      $output['tags_table_no_tags'][''] = $untagged_items;

      if (!isset($output['table']['#attributes']['class'])) {
        $output['tags_table_no_tags']['']['table']['#attributes']['class'] = ['searchable-list'];
      }
      else {
        $output['tags_table_no_tags']['']['table']['#attributes']['class'][] = ['searchable-list'];
      }
    }
    else {
      $output += parent::render();
      if (!isset($output['table']['#attributes']['class'])) {
        $output['table']['#attributes']['class'] = ['searchable-list'];
      }
      else {
        $output['table']['#attributes']['class'][] = ['searchable-list'];
      }
    }

    return $output;
  }

}
