<?php

namespace Drupal\mask\Helper;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for Mask's element helper.
 */
interface ElementHelperInterface {

  /**
   * Alters the element info to add Mask's specific data.
   *
   * @param array $info
   *   The element type's part of the associative array  returned by
   *   value of \Drupal\Core\Render\ElementInfoManagerInterface::getInfo().
   * @param array $defaults
   *   An array with default values for mask options.
   *   [
   *     'value' => '',
   *     'reverse' => FALSE,
   *     'clearifnotmatch' => FALSE,
   *     'selectonfocus' => FALSE,
   *   ]
   *   See default array above.
   */
  public function elementInfoAlter(array &$info, array $defaults = []);

  /**
   * Processes Form API elements with the #mask attribute.
   *
   * @param array $element
   *   The element being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the form the element belongs to.
   * @param array $complete_form
   *   The form the element belongs to.
   *
   * @return array
   *   The processed element.
   */
  public static function processElement(array &$element, FormStateInterface $form_state, array &$complete_form);

}
