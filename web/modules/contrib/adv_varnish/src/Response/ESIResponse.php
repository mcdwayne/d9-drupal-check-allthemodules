<?php
/**
 * @file
 * Contains \Drupal\adv_varnish\Controller\BlockESIController.
 */
namespace Drupal\adv_varnish\Response;

use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Entity\EntityInterface;

class ESIResponse extends CacheableResponse {

  protected $entity;

  public function getEntity() {
    return $this->entity;
  }

  public function setEntity(EntityInterface $entity) {
    $this->entity = $entity;
  }

}