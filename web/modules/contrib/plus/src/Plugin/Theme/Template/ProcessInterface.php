<?php

namespace Drupal\plus\Plugin\Theme\Template;

use Drupal\Core\Form\FormStateInterface;
use Drupal\plus\Utility\Element;

/**
 * Defines the interface for a #process callback on a "Template" plugin.
 *
 * @ingroup plugins_template
 */
interface ProcessInterface {

  /**
   * Process a specific form element type.
   *
   * Implementations of this method should check to see if the element has a
   * property named #bootstrap_ignore_process and check if it is set to TRUE.
   * If it is, the method should immediately return with the unaltered element.
   *
   * @param \Drupal\plus\Utility\Element $element
   *   The render array Element object.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The altered element array.
   *
   * @see \Drupal\plus\Plugin\Alter\ElementInfo::alter
   */
  public function process(Element $element, FormStateInterface $form_state, array &$complete_form);

}
