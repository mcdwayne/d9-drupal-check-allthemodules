<?php

namespace Drupal\business_rules;

use Drupal\business_rules\Entity\BusinessRule;
use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Rule entities.
 */
class BusinessRuleListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label']       = $this->t('Rule');
    $header['id']          = $this->t('Machine name');
    $header['event']       = $this->t('Reacts on event');
    $header['enabled']     = $this->t('Enabled');
    $header['entity']      = $this->t('Entity');
    $header['bundle']      = $this->t('Bundle');
    $header['description'] = $this->t('Description');
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
    /** @var \Drupal\business_rules\Entity\BusinessRule $entity */
    $status = $entity->isEnabled() ? $this->t('Enabled') : $this->t('Disabled');

    $row['label']       = $entity->label();
    $row['id']          = $entity->id();
    $row['event']       = $entity->getReactsOnLabel();
    $row['enabled']     = $status;
    $row['entity']      = $entity->getTargetEntityTypeLabel();
    $row['bundle']      = $entity->getTargetBundleLabel();
    $row['description'] = $entity->getDescription();
    $row['tags']        = implode(', ', $entity->getTags());

    $search_string = $entity->label() . ' ' .
      $entity->id() . ' ' .
      $entity->getReactsOnLabel() . ' ' .
      $status . ' ' .
      $entity->getTargetEntityTypeLabel() . ' ' .
      $entity->getTargetBundleLabel() . ' ' .
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
      '#placeholder' => $this->t('Search for a rule'),
      '#attributes'  => [
        'class'        => ['table-filter-text'],
        'data-table'   => '.searchable-list',
        'autocomplete' => 'off',
        'title'        => $this->t('Enter a part of the rule to filter by.'),
      ],
    ];

    if ($view_mode == 'tags') {
      $tags = BusinessRule::loadAllTags();
      $table = parent::render();

      foreach ($tags as $tag) {
        $tagged_items              = $table;
        $output["tags_table_$tag"] = [
          '#type'  => 'details',
          '#title' => $tag,
          '#open'  => FALSE,
        ];

        foreach ($tagged_items['table']['#rows'] as $key => $tagged_item) {
          $item      = BusinessRule::load($key);
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
        $item      = BusinessRule::load($key);
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
