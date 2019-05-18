<?php

namespace Drupal\hn\Plugin\HnPathResolver;

use Drupal\Core\Url;
use Drupal\hn\HnPathResolverResponse;
use Drupal\hn\Plugin\HnPathResolverBase;

/**
 * This provides a 404 resolver.
 *
 * @HnPathResolver(
 *   id = "hn_redirect"
 * )
 */
class RedirectResolver extends HnPathResolverBase {

  /**
   * {@inheritdoc}
   */
  public function resolve($path) {
    $redirect_service = \Drupal::service('redirect.repository');
    // Source path has no leading /.
    $source_path = trim($path, '/');
    /** @var \Drupal\redirect\Entity\Redirect $redirect */
    // Get all redirects by original url.
    $redirect = $redirect_service->findMatchingRedirect($source_path, []);
    if (empty($redirect)) {
      return NULL;
    }
    // Get 301/302.
    $status = (int) $redirect->getStatusCode();
    // Get the redirect uri.
    $uri = $redirect->getRedirect()['uri'];

    // Check if it is an internal url.
    $url = Url::fromUri($uri);

    if ($url->isExternal()) {
      return new HnPathResolverResponse($redirect, $status);
    }

    $internal_path = $url->getInternalPath();

    /** @var \Drupal\hn\Plugin\HnPathResolverManager $path_resolver */
    $path_resolver = \Drupal::service('hn.path_resolver');
    $entity = $path_resolver->resolve($internal_path)->getEntity();

    return new HnPathResolverResponse($entity, $status);
  }

}
