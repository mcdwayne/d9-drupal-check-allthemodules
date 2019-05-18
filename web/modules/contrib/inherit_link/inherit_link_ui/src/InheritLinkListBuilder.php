<?php

namespace Drupal\inherit_link_ui;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of Inherit link entities.
 */
class InheritLinkListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Inherit link');
    $header['id'] = $this->t('Machine name');
    $header['element_selector'] = $this->t('Element selector');
    $header['link_selector'] = $this->t('Link selector');
    $header['prevent_selector'] = $this->t('Prevent selector');
    $header['hide_element'] = $this->t('Hide element');
    $header['auto_external'] = $this->t('Auto external');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['element_selector'] = $entity->getElementSelector();
    $row['link_selector'] = $entity->getLinkSelector();
    $row['prevent_selector'] = $entity->getPreventSelector();
    $row['hide_element'] = $entity->getHideElement() ? $this->t('Yes') : $this->t('No');
    $row['auto_external'] = $entity->getAutoExternal() ? $this->t('Yes') : $this->t('No');
    return $row + parent::buildRow($entity);
  }

}
