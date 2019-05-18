<?php

declare(strict_types = 1);

namespace Drupal\sendwithus\Resolver\Template;

use Drupal\sendwithus\Context;
use Drupal\sendwithus\Resolver\Variable\VariableCollector;
use Drupal\sendwithus\Template;

/**
 * Provides a base template resolver class.
 */
abstract class BaseTemplateResolver implements TemplateResolverInterface {

  protected $variableCollector;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\sendwithus\Resolver\Variable\VariableCollector $collector
   *   The variable resolver.
   */
  public function __construct(VariableCollector $collector) {
    $this->variableCollector = $collector;
  }

  /**
   * Collect variables for given template.
   *
   * @param \Drupal\sendwithus\Template $template
   *   The template to collect variables for.
   * @param \Drupal\sendwithus\Context $context
   *   The context.
   */
  protected function doCollectVariables(Template $template, Context $context) : void {
    $this->variableCollector->collect($template, $context);
  }

}
