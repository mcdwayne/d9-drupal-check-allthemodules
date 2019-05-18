<?php

namespace Drupal\phones_call\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Phones call entities.
 *
 * @ingroup phones_call
 */
class PhonesCallListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('Name');
    $header['user'] = $this->t('User');
    $header['client'] = $this->t('Client');
    $header['gateway'] = $this->t('Gateway');
    $header['created'] = $this->t('Created');
    $header['duration'] = $this->t('Duration');
    $header['billsec'] = $this->t('Billsec');
    $header['hangup'] = $this->t('Hangup');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\phones_call\Entity\PhonesCall */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.phones_call.canonical',
      ['phones_call' => $entity->id()]
    );
    $row['user'] = $entity->user->value;
    $row['client'] = $entity->client->value;
    $row['gateway'] = $entity->gateway->value;
    $row['created'] = format_date($entity->created->value, 'long');
    $row['duration'] = $entity->duration->value;
    $row['billsec'] = $entity->billsec->value;
    $row['hangup'] = $entity->hangup->value;
    return $row + parent::buildRow($entity);
  }

}
