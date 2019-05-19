<?php

namespace Drupal\splashify;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;
use Drupal\splashify\Entity\SplashifyGroupEntity;

/**
 * Defines a class to build a listing of Splashify entity entities.
 *
 * @ingroup splashify
 */
class SplashifyEntityListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Name');
    $header['group'] = $this->t('Group');
    $header['weight'] = $this->t('Weight');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\splashify\Entity\SplashifyEntity */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.splashify_entity.edit_form', array(
          'splashify_entity' => $entity->id(),
        )
      )
    );
    $entity_id = $entity->field_group->getValue();
    $entity_id = $entity_id[0]['target_id'];
    $entity_group = SplashifyGroupEntity::load($entity_id);
    if (!empty($entity_group)) {
      $row['group'] = $this->l(
        $entity_group->label(),
        new Url(
          'entity.splashify_group_entity.edit_form', array(
            'splashify_group_entity' => $entity_id,
          )
        )
      );
    }
    else {
      $row['group'] = $this->t('None');
    }

    $row['weight'] = $entity->field_weight->value;
    return $row + parent::buildRow($entity);
  }

  /**
   * Loads entity IDs using a pager sorted by the entity id.
   *
   * @return array
   *   An array of entity IDs.
   */
  protected function getEntityIds() {
    $query = \Drupal::entityQuery('splashify_entity')
      ->sort('field_weight', 'DESC')
      ->sort('id', 'DESC');
    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

}
