<?php

/**
 * @file
 * Contains \Drupal\nodeletter\Plugin\NodeletterSender\RenderedTemplateVariable.
 */

namespace Drupal\nodeletter\Plugin\NodeletterSender;

/**
 * Newsletter template variable necessary to send a newsletter.
 *
 * @see \Drupal\nodeletter\NodeletterSender\NodeletterSenderPluginInterface::send()
 * @see \Drupal\nodeletter\NodeletterSender\NodeletterSenderPluginInterface::sendTest()
 *
 */
class RenderedTemplateVariable {

  /**
   * @var string
   */
  private $var_name;

  /**
   * @var string
   */
  private $var_value;

  /**
   * RenderedTemplateVariable constructor.
   * @param $name
   * @param $value
   */
  public function __construct( $name, $value ) {
    $this->var_name = $name;
    $this->var_value = $value;
  }

  /**
   * Name of template variable.
   *
   * @return string
   */
  public function getName() {
    return $this->var_name;
  }

  /**
   * Rendered template variable value.
   *
   * @return string
   */
  public function getValue() {
    return $this->var_value;
  }
}
