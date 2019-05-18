<?php

declare(strict_types = 1);

namespace Drupal\sendwithus\Event;

use Drupal\sendwithus\Context;
use Drupal\sendwithus\Template;
use Symfony\Component\EventDispatcher\Event;

/**
 * Defines the event for \Drupal\sendwithus\Event\Events.
 */
final class TemplateCollectorAlter extends Event {

  protected $context;
  protected $template;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\sendwithus\Context $context
   *   The context.
   * @param \Drupal\sendwithus\Template $template
   *   The template.
   */
  public function __construct(Context $context, Template $template) {
    $this->context = $context;
    $this->template = $template;
  }

  /**
   * Gets the context.
   *
   * @return \Drupal\sendwithus\Context
   *   The context.
   */
  public function getContext() : Context {
    return $this->context;
  }

  /**
   * Gets the template.
   *
   * @return \Drupal\sendwithus\Template
   *   The template.
   */
  public function getTemplate() : Template {
    return $this->template;
  }

}
