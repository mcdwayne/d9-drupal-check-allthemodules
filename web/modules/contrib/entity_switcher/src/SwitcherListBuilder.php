<?php

namespace Drupal\entity_switcher;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of switcher setting entities.
 *
 * @see \Drupal\entity_switcher\Entity\Switcher
 */
class SwitcherListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['description'] = $this->t('Description');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupalentity_switcher\Entity\SwitcherInterface $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['description'] = $entity->getDescription();

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    $build['table']['#empty'] = $this->t('No switcher available. <a href=":link">Add switcher</a>.', [
      ':link' => Url::fromRoute('entity.entity_switcher_setting.add_form')->toString()
    ]);

    return $build;
  }

}
