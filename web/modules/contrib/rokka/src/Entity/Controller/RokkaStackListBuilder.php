<?php

namespace Drupal\rokka\Entity\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Provides a listing of Rokka stack entities.
 */
class RokkaStackListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   *
   */
  public function render() {

    $build['header'] = [
      '#markup' => $this->t('Use \'drush rokka-mim\' to migrate your existing image styles to rokka.io stacks.') . '<br> <br>',
    ];

    $build += parent::render();

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Image Style');
    $header['id'] = $this->t('Rokka stack');
    $header['operations'] = $this->t('Operations');
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = Link::createFromRoute(
      $entity->label(),
      'entity.image_style.edit_form',
      ['image_style' => $entity->id()]
    );
    $row['id'] = $entity->id();

    $operations = [
      '#type' => 'operations',
      '#links' => [
        'edit' => [
          'title' => $this->t('Edit'),
          'weight' => 10,
          'url' => Url::fromRoute('entity.image_style.edit_form', ['image_style' => $entity->id()]),
        ],
        'delete' => [
          'title' => $this->t('Delete'),
          'weight' => 10,
          'url' => Url::fromRoute('entity.rokka_stack.delete_form', ['rokka_stack' => $entity->id()]),
        ],
      ],
    ];

    $row['operations']['data'] = $operations;
    return $row;
  }

}
