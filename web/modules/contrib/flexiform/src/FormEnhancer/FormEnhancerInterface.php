<?php

namespace Drupal\flexiform\FormEnhancer;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\flexiform\FlexiformEntityFormDisplayInterface;

/**
 * Interface for form enhancer plugins.
 */
interface FormEnhancerInterface extends PluginInspectionInterface {

  /**
   * Set the form display object.
   *
   * @param \Drupal\flexiform\FlexiformEntityFormDisplayInterface $form_display
   *   The form display.
   *
   * @return \Drupal\flexiform\FormEnhancer\FormEnhancerInterface
   *   The form enhancer with the form display set. (For chaining).
   */
  public function setFormDisplay(FlexiformEntityFormDisplayInterface $form_display);

  /**
   * Whether this enhancer applies to a particular event.
   *
   * @param string $event
   *   The enhancer event name.
   *
   * @return bool
   *   True if the enhancer applies to a particular event.
   */
  public function applies($event);

}
