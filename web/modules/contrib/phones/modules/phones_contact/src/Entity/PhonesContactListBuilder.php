<?php

namespace Drupal\phones_contact\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Phones contact entities.
 *
 * @ingroup phones_contact
 */
class PhonesContactListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Phones contact ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\phones_contact\Entity\PhonesContact */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.phones_contact.edit_form',
      ['phones_contact' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
