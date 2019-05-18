<?php

/**
 * @file
 * Contains Drupal\ip\IpPosts
 */

namespace Drupal\ip;

use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\EntityInterface;

/* 
 * @file
 * IpTracker Manager.
 */

class IpPosts {

  private $connection;
  private $entity;
  private $request;

  function __construct(Connection $connection, Request $request, EntityInterface $entity) {
    $this->connection = $connection;
    $this->request = $request;
    $this->entity = $entity;
  }

  /**
   * Save the IpTrack
   * @return type
   */
  function save() {

    $ip  = $this->request->getClientIp();

    $iplong = ip2long($ip);

    if (!empty($iplong) && is_numeric($this->entity->id())) {

      return $this->connection->insert('ip_posts')
        ->fields(
          array(
            'type' => $this->entity->getEntityTypeId(),
            'id' => $this->entity->id(),
            'ip' => $iplong,
          )
        )
        ->execute();
    }
    
  }

  /**
   * Remove records in the ip_posts table for a certain entity.
   */
  function remove() {
    return $this->connection->delete('ip_posts')
      ->condition('type', $this->entity->getEntityTypeId())
      ->condition('id', $this->entity->id())
      ->execute();
  }

}
