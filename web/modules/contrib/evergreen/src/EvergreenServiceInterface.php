<?php

namespace Drupal\evergreen;

use Drupal\Core\Entity\ContentEntityInterface;

interface EvergreenServiceInterface {
  public function entityHasExpired(ContentEntityInterface $entity);
}
