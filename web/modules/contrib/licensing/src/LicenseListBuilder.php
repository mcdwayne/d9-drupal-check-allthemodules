<?php

namespace Drupal\licensing;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of License entities.
 *
 * @ingroup license
 */
class LicenseListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('License ID');
    $header['user'] = $this->t('Owner');
    $header['licensed_entity'] = $this->t('Licensed Entity');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\licensing\Entity\License */
    $row['id'] = $this->l(
      $entity->id(),
      new Url(
        'entity.license.edit_form', array(
          'license' => $entity->id(),
        )
      )
    );
    $owner = $entity->getOwner();
    if ($owner) {
      $row['owner'] = $owner->getDisplayName();
    }
    else {
      $row['owner'] = $this->t('User :id missing.', [':id' => $entity->get('user_id')->target_id]);
    }

    $licensed_entity = $entity->getLicensedEntity();
    if ($licensed_entity) {
      $row['licensed_entity'] = $licensed_entity->label();
    }
    else {
      $row['licensed_entity'] = $this->t('Entity :id missing.', [':id' => $entity->get('licensed_entity')->target_id]);
    }

    return $row + parent::buildRow($entity);
  }

}
