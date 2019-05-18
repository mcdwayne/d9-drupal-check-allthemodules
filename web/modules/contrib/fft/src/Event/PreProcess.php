<?php

namespace Drupal\fft\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class PreProcess.
 *
 * @package Drupal\fft\Event
 */
class PreProcess extends Event {

  /**
   * Pre process event.
   */
  const EVENT = 'pre_process';

  /**
   * The variables.
   *
   * @var array
   */
  public $variables;

  /**
   * The template file.
   *
   * @var string
   */
  private $template;

  /**
   * PreProcess constructor.
   *
   * @param string $template
   *   The template file.
   * @param array $variables
   *   The variables.
   */
  public function __construct($template, array &$variables) {
    $this->variables = &$variables;
    $this->template = basename($template);
  }

  /**
   * Get template file.
   *
   * @return string
   */
  public function getTemplate(): string {
    return $this->template;
  }

}
