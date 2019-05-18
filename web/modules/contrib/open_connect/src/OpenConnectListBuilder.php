<?php

namespace Drupal\open_connect;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of open connect entities.
 *
 * @ingroup open_connect
 */
class OpenConnectListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['provider'] = $this->t('Provider');
    $header['openid'] = $this->t('Open ID');
    $header['unionid'] = $this->t('unionid');
    $header['user'] = $this->t('User');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['provider'] = $entity->getProvider();
    $row['openid'] = $entity->getOpenid();
    $row['unionid'] = $entity->getUnionid();
    $row['user'] = NULL;
    if (($user = $entity->getAccount())) {
      $row['user'] = $user->toLink($user->label());
    }
    return $row + parent::buildRow($entity);
  }

}
