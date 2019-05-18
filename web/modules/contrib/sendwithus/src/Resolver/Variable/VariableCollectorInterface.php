<?php

declare(strict_types = 1);

namespace Drupal\sendwithus\Resolver\Variable;

use Drupal\sendwithus\Context;
use Drupal\sendwithus\Template;

/**
 * Provides an interface for variable resolver.
 */
interface VariableCollectorInterface {

  /**
   * Resolves the data for given context.
   *
   * @todo Add missing return type when Drupal supports newer
   * phpunit.
   *
   * @param \Drupal\sendwithus\Template $template
   *   The template.
   * @param \Drupal\sendwithus\Context $context
   *   The context.
   */
  public function collect(Template $template, Context $context);

}
