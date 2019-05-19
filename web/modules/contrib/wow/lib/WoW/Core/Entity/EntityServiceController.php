<?php

/**
 * @file
 * Definition of EntityServiceController
 */

namespace WoW\Core\Entity;

use \EntityAPIControllerInterface;

use Symfony\Component\DependencyInjection\ContainerAware;

use WoW\Core\Response;
use WoW\Core\ServiceInterface;

/**
 * Base class for entity service controller.
 *
 * This communicate to the service, adding required special handling for remote
 * entity objects.
 */
class EntityServiceController extends ContainerAware {

  /**
   * The entity type name.
   *
   * @var string
   */
  protected $entityType;

  /**
   * The entity info array.
   *
   * @var array
   *
   * @see entity_get_info()
   */
  protected $entityInfo;

  /**
   * Name of the entity's ID field in the entity database table.
   *
   * @var string
   */
  protected $idKey;

  /**
   * The entity storage controller.
   *
   * @var EntityAPIControllerInterface
   */
  protected $storage;

  /**
   * Constructs an EntityServiceController abstract class.
   *
   * @param string $entityType
   *   The entity type.
   * @param EntityAPIControllerInterface $storage
   *   The entity storage controller.
   */
  public function __construct($entityType, EntityAPIControllerInterface $storage) {
    $this->entityType = $entityType;
    $this->entityInfo = entity_get_info($entityType);
    $this->idKey = $this->entityInfo['entity keys']['id'];
    $this->storage = $storage;
  }

  /**
   * Gets a service by region.
   *
   * @param string $region
   *   The region of the service.
   *
   * @return ServiceInterface
   *   A service.
   */
  protected function service($region) {
    return $this->container->get("wow.service.$region");
  }

  /**
   * Gets a service by language.
   *
   * @param string $language
   *   The language of the service.
   *
   * @return ServiceInterface
   *   A service which support the language.
   */
  protected function serviceByLanguage($language) {
    return $this->container->get("wow.service.lang.$language");
  }

}
