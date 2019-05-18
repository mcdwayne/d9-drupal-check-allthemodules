<?php

namespace Drupal\entity_router;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The descriptor of the entity response handler plugin.
 */
interface EntityResponseHandlerInterface extends ContainerFactoryPluginInterface {

  /**
   * Returns the response for a given entity.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The inbound request.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity to generate a response for.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response.
   *
   * @see \Drupal\entity_router\Response\EntityResponse
   */
  public function getResponse(Request $request, ?EntityInterface $entity): Response;

}
