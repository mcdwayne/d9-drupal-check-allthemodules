<?php

namespace Drupal\entity_router\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\AccessAwareRouterInterface;
use Drupal\entity_router\Response\EntityResponse;
use Drupal\redirect\RedirectRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The controller.
 */
class EntityRouterController extends ControllerBase {

  /**
   * An instance of the "router" service.
   *
   * @var \Drupal\Core\Routing\AccessAwareRouterInterface
   */
  protected $router;
  /**
   * An instance of the "redirect.repository" service.
   *
   * @var \Drupal\redirect\RedirectRepository|null
   */
  protected $redirectRepository;

  /**
   * {@inheritdoc}
   */
  public function __construct(AccessAwareRouterInterface $router, ?RedirectRepository $redirect_repository) {
    $this->router = $router;
    $this->redirectRepository = $redirect_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static($container->get('router'), $container->get('redirect.repository', $container::NULL_ON_INVALID_REFERENCE));
  }

  /**
   * Returns a JSON API resource by the path alias or redirect.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The inbound request.
   *
   * @return \Drupal\entity_router\Response\EntityResponse
   *   The response.
   *
   * @throws \Drupal\Core\Entity\EntityMalformedException
   * @throws \Drupal\Core\Entity\Exception\UndefinedLinkTemplateException
   *
   * @example
   * GET /ROUTE?format=jsonapi&path=/node/1
   * GET /ROUTE?format=jsonapi&path=/node-one-alias
   * GET /ROUTE?format=jsonapi&path=/node-one-redirect
   */
  public function get(Request $request): EntityResponse {
    $path = ltrim($request->query->get('path'), '/');
    $entity = $this->getEntityByPath($path);
    $status = 200;

    if ($entity === NULL) {
      [$entity, $status] = $this->getEntityByRedirect($path);
    }
    // The entity has been found by the path a user has sent. Let's check
    // if it's an internal one (e.g. "node/3") and provide a redirect.
    elseif ($path !== ltrim($entity->toUrl()->toString(FALSE), '/')) {
      $status = 301;
    }

    if ($entity === NULL) {
      $status = 404;
    }

    return new EntityResponse($entity, $request->getRequestFormat(), $status);
  }

  /**
   * Returns an entity for a given path or its alias.
   *
   * @param string $path
   *   The internal path or alias of an entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity.
   */
  protected function getEntityByPath(string $path): ? EntityInterface {
    // Check whether the path is known.
    try {
      foreach ($this->router->match($path) as $value) {
        if ($value instanceof EntityInterface) {
          return $value;
        }
      }
    }
    // An exception may be thrown if we have no access to the route.
    catch (\Exception $e) {
    }

    return NULL;
  }

  /**
   * Returns an entity for a given path, its alias or a redirect.
   *
   * @param string $path
   *   The internal path, alias or redirect of an entity.
   *
   * @return array
   *   - [0] \Drupal\Core\Entity\EntityInterface|null: The entity.
   *   - [1] int|null: The HTTP status code.
   */
  protected function getEntityByRedirect(string $path): array {
    if ($this->redirectRepository !== NULL) {
      foreach ($this->redirectRepository->findBySourcePath($path) as $redirect) {
        $path = $redirect
          ->getRedirectUrl()
          // The bubleable metadata MUST BE COLLECTED, otherwise the request
          // will end up with "The controller result claims to be providing
          // relevant cache metadata, but leaked metadata was detected." since
          // "toString(FALSE)" will call the rendering system and the response
          // normalization no longer be a "root call".
          /* @see \Drupal\Core\Render\Renderer::render() */
          /* @see \Drupal\Core\EventSubscriber\EarlyRenderingControllerWrapperSubscriber::wrapControllerExecutionInRenderContext() */
          ->toString(TRUE)
          ->getGeneratedUrl();

        if ($entity = $this->getEntityByPath($path)) {
          return [$entity, $redirect->getStatusCode()];
        }
      }
    }

    return [NULL, NULL];
  }

}
