<?php

/**
 * @file
 * Contains \Drupal\hierarchical_config\HierarchicalConfigurationListBuilder.
 */

namespace Drupal\hierarchical_config;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Hierarchical configuration entities.
 *
 * @ingroup hierarchical_config
 */
class HierarchicalConfigurationListBuilder extends EntityListBuilder {
  use LinkGeneratorTrait;
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Hierarchical configuration ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\hierarchical_config\Entity\HierarchicalConfiguration */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.hierarchical_configuration.edit_form', array(
          'hierarchical_configuration' => $entity->id(),
        )
      )
    );
    return $row + parent::buildRow($entity);
  }

}
