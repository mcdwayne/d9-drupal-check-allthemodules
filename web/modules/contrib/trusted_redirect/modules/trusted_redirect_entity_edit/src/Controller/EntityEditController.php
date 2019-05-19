<?php

namespace Drupal\trusted_redirect_entity_edit\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\trusted_redirect_entity_edit\Service\EntityEditUrlResolver;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Redirects to entity edit form based on uuid of given entity.
 */
class EntityEditController implements ContainerInjectionInterface {

  /**
   * The entity edit url resolver.
   *
   * @var \Drupal\trusted_redirect_entity_edit\Service\EntityEditUrlResolver
   */
  protected $editUrlResolver;

  /**
   * Constructor.
   *
   * @param \Drupal\trusted_redirect_entity_edit\Service\EntityEditUrlResolver $edit_url_resolver
   *   The entity edit url resolver.
   */
  public function __construct(EntityEditUrlResolver $edit_url_resolver) {
    $this->editUrlResolver = $edit_url_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('trusted_redirect_entity_edit.edit_url_resolver')
    );
  }

  /**
   * Redirect to entity edit form based on uuid of that entity.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $uuid
   *   Uuid of entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   Return not found if entity (or route for it) cannot be found.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect to the extract edit form of identified entity.
   */
  public function resolveEntityEditUrl(Request $request, $uuid) {
    $entity_edit_url = $this->editUrlResolver->resolveEditUrlByUuid($uuid);
    if ($entity_edit_url) {
      parse_str($request->getQueryString(), $query);
      $entity_edit_url->setOption('query', $query);
      $generatedUrl = $entity_edit_url->toString();
      $response = new RedirectResponse($generatedUrl);
      return $response;
    }
    throw new NotFoundHttpException();
  }

}
