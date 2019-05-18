<?php

declare(strict_types = 1);

namespace Drupal\sendwithus\Resolver\Template;

use Drupal\sendwithus\Context;

/**
 * Provides an interface for template resolver.
 */
interface TemplateResolverInterface {

  /**
   * Resolves the data for given context.
   *
   * @todo Add missing return type when Drupal supports newer
   * phpunit.
   *
   * @param \Drupal\sendwithus\Context $context
   *   The context.
   *
   * @return \Drupal\sendwithus\Template|null
   *   The template or null.
   */
  public function resolve(Context $context);

}
