<?php

namespace Drupal\custom_messages;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Custom Message entities.
 *
 * @ingroup custom_messages
 */
class CustomMessageListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['title'] = $this->t('Title');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\custom_messages\Entity\CustomMessage */
    $row['id'] = $entity->id();
    $row['title'] = $this->l(
      $entity->label(),
      new Url(
        'entity.custom_message.edit_form', array(
          'custom_message' => $entity->id(),
        )
      )
    );
    return $row + parent::buildRow($entity);
  }

}
