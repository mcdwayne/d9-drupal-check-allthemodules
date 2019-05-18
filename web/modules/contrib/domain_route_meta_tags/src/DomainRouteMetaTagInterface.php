<?php

namespace Drupal\domain_route_meta_tags;

/**
 * Contains \Drupal\domain_route_meta_tags\DomainMetaTagInterface.
 */
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a Custom meta entity.
 *
 * @ingroup domain_route_meta_tags
 */
interface DomainRouteMetaTagInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
