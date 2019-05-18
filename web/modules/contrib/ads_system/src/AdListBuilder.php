<?php

namespace Drupal\ads_system;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Ad entities.
 *
 * @ingroup ads_system
 */
class AdListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['type'] = $this->t('Type');
    $header['name'] = $this->t('Name');
    $header['id'] = $this->t('Ad ID');
    $header['size'] = $this->t('Size');
    $header['breakpoint_min'] = $this->t('Breakpoint min');
    $header['breakpoint_max'] = $this->t('Breakpoint max');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\ads_system\Entity\Ad */
    $row['type'] = $entity->getType();
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.ad.edit_form', [
          'ad' => $entity->id(),
        ]
      )
    );

    $row['id'] = $entity->id();
    $row['size'] = $entity->getSize();
    $row['breakpoint_min'] = $entity->getBreakpointMin();
    $row['breakpoint_max'] = $entity->getBreakpointMax();

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['table'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#title' => $this->getTitle(),
      '#rows' => [],
      '#empty' => $this->t('There is no @label yet.', ['@label' => $this->entityType->getLabel()]),
      '#cache' => [
        'contexts' => $this->entityType->getListCacheContexts(),
        'tags' => $this->entityType->getListCacheTags(),
      ],
      '#responsive' => TRUE,
      '#sticky' => TRUE,
    ];

    foreach ($this->load() as $entity) {
      if ($row = $this->buildRow($entity)) {
        $build['table']['#rows'][$entity->id()] = $row;
      }
    }

    // Sort rows by type.
    $build['table']['#rows'] = $this->arraySort($build['table']['#rows'], 'type', SORT_ASC);

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $build['pager'] = [
        '#type' => 'pager',
      ];
    }
    return $build;
  }

  /**
   * Sort rows by type.
   */
  private function arraySort($array, $on, $order = SORT_ASC) {
    $new_array = [];
    $sortable_array = [];

    if (count($array) > 0) {
      foreach ($array as $k => $v) {
        if (is_array($v)) {
          foreach ($v as $k2 => $v2) {
            if ($k2 == $on) {
              $sortable_array[$k] = $v2;
            }
          }
        }
        else {
          $sortable_array[$k] = $v;
        }
      }

      switch ($order) {
        case SORT_ASC:
          asort($sortable_array);
          break;

        case SORT_DESC:
          arsort($sortable_array);
          break;
      }

      foreach ($sortable_array as $k => $v) {
        $new_array[$k] = $array[$k];
      }
    }

    return $new_array;
  }

}
