<?php

namespace Drupal\shield_pages;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Shield page entities.
 */
class ShieldPageListBuilder extends DraggableListBuilder {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shield_page_list';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['pattern'] = $this->t('Path');
    $header['type'] = $this->t('Passwords');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\shield_pages\ShieldPageInterface $entity */
    $row['label'] = $entity->label();
    $row['path']['#markup'] = check_markup($entity->getPath());
    $row['passwords']['#theme'] = 'item_list';
    $row['passwords']['#items'] = $entity->getPasswords();
    return $row + parent::buildRow($entity);
  }
}
