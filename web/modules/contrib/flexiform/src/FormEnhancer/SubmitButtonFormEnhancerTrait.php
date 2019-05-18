<?php

namespace Drupal\flexiform\FormEnhancer;

use Drupal\Core\Render\Element;

/**
 * Trait for submit buttons in form enhancers.
 */
trait SubmitButtonFormEnhancerTrait {

  /**
   * Form display.
   *
   * @var \Drupal\flexiform\FlexiformEntityFormDisplayInterface
   */
  protected $formDisplay;

  /**
   * Locate any submit buttons in the form.
   *
   * @return array
   *   The buttons in the form.
   */
  protected function locateSubmitButtons() {
    $form_info = $this->formDisplay->getFormInformation();
    return $this->locateSubmitButtonsR($form_info['form']);
  }

  /**
   * Locate the submit buttons recursively.
   */
  private function locateSubmitButtonsR($elements, $depth = 0) {
    $buttons = [];
    foreach (Element::children($elements) as $key) {
      if (($depth == 0) && $this->formDisplay->getComponent($key)) {
        continue;
      }

      if (isset($elements[$key]['#type']) && ($elements[$key]['#type'] == 'submit')) {
        $buttons[implode('][', $elements[$key]['#array_parents'])] = $elements[$key]['#value'];
      }

      $buttons += $this->locateSubmitButtonsR($elements[$key], $depth + 1);
    }
    return $buttons;
  }

}
