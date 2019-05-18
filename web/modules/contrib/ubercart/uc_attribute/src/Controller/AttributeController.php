<?php

namespace Drupal\uc_attribute\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Controller routines for product attribute routes.
 */
class AttributeController extends ControllerBase {

  /**
   * Displays a paged list and overview of existing product attributes.
   */
  public function overview() {
    $header = [
      ['data' => $this->t('Name'), 'field' => 'a.name', 'sort' => 'asc'],
      ['data' => $this->t('Label'), 'field' => 'a.label'],
      $this->t('Required'),
      ['data' => $this->t('List position'), 'field' => 'a.ordering'],
      $this->t('Number of options'),
      $this->t('Display type'),
      ['data' => $this->t('Operations'), 'colspan' => 1],
    ];

    $display_types = _uc_attribute_display_types();

    $query = db_select('uc_attributes', 'a')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->fields('a', ['aid', 'name', 'label', 'required', 'ordering', 'display'])
      ->orderByHeader($header)
      ->limit(30);

    $build['attributes'] = [
      '#type' => 'table',
      '#header' => $header,
      '#empty' => $this->t('No product attributes have been added yet.'),
    ];

    $result = $query->execute();
    foreach ($result as $attr) {
      $attr->options = db_query('SELECT COUNT(*) FROM {uc_attribute_options} WHERE aid = :aid', [':aid' => $attr->aid])->fetchField();
      if (empty($attr->label)) {
        $attr->label = $attr->name;
      }
      $build['attributes'][] = [
        'name' => ['#plain_text' => $attr->name],
        'label' => ['#plain_text' => $attr->label],
        'required' => [
          '#plain_text' => $attr->required == 1 ? $this->t('Yes') : $this->t('No'),
        ],
        'ordering' => ['#markup' => $attr->ordering],
        'options' => ['#markup' => $attr->options],
        'display' => ['#markup' => $display_types[$attr->display]],
        'operations' => [
          '#type' => 'operations',
          '#links' => [
            'edit' => [
              'title' => $this->t('Edit'),
              'url' => Url::fromRoute('uc_attribute.edit', ['aid' => $attr->aid]),
            ],
            'options' => [
              'title' => $this->t('Options'),
              'url' => Url::fromRoute('uc_attribute.options', ['aid' => $attr->aid]),
            ],
            'delete' => [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('uc_attribute.delete', ['aid' => $attr->aid]),
            ],
          ],
        ],
      ];
    }

    $build['pager'] = [
      '#type' => 'pager',
    ];

    return $build;
  }

}
