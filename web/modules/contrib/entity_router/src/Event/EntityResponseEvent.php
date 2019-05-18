<?php

namespace Drupal\entity_router\Event;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The event to dispatch for notifying about entity response.
 */
class EntityResponseEvent extends Event {

  /**
   * The name of the event.
   */
  public const NAME = 'entity_router.response';

  /**
   * The inbound HTTP request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The response to send.
   *
   * @var \Symfony\Component\HttpFoundation\Response
   */
  protected $response;

  /**
   * The requested entity.
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function __construct(Request $request, Response $response, ?EntityInterface $entity) {
    $this->request = $request;
    $this->response = $response;
    $this->entity = $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequest(): Request {
    return $this->request;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse(): Response {
    return $this->response;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity(): ?EntityInterface {
    return $this->entity;
  }

}
