<?php

namespace Drupal\agreement\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * Provides simple operations list.
 */
class AgreementListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'label' => $this->t('Name'),
      'path' => $this->t('Agreement page'),
      'roles' => $this->t('Roles'),
      'visibility' => $this->t('Visibility Setting'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {

    $settings = $entity->getSettings();
    $roles = array_reduce($settings['roles'], function (&$result, $item) {
      $result[] = $item;
      return $result;
    }, []);
    $visibility_options = [
      $this->t('Show on every page except the listed pages'),
      $this->t('Show on only the listed pages'),
    ];

    return [
      'label' => $entity->label(),
      'path' => [
        'data' => [
          '#type' => 'link',
          '#url' => Url::fromUserInput($entity->get('path')),
          '#title' => $entity->get('path'),
        ],
      ],
      'roles' => [
        'data' => [
          '#theme' => 'item_list',
          '#items' => $roles,
        ],
      ],
      'visibility' => $visibility_options[$settings['visibility']['settings']],
    ] + parent::buildRow($entity);
  }

}
