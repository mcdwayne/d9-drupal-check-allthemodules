<?php

/**
 * @file
 * Contains \Drupal\widget_on_demand\Plugin\Field\FieldWidget\text\WidgetOnDemandForTextFormatTrait.
 */

namespace Drupal\widget_on_demand\Plugin\Field\FieldWidget\text;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\widget_on_demand\Plugin\Field\FieldWidget\WidgetOnDemandTrait;

/**
 * Provides a trait for the on demand widgets using text format.
 */
trait WidgetOnDemandForTextFormatTrait {

  use WidgetOnDemandTrait {
    formElement as formElementOnDemand;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // If the current user does not have access to the specified format then
    // we have to let the normal form element to be built so that the #process
    // function of \Drupal\filter\Element\TextFormat takes care of the error
    // message to be shown instead of the form element.
    if (($items[$delta]->format && $this->userHasAccessToFormat($items[$delta]->format)) || (!$items[$delta]->format && $items[$delta]->isEmpty())) {
      $element = $this->formElementOnDemand($items, $delta, $element, $form, $form_state);
    }
    else {
      $element = parent::formElement($items, $delta, $element, $form, $form_state);
    }

    return $element;
  }

  /**
   * Checks if the current user has access to a specific text format.
   *
   * @param string $format
   *   The format for which to access for.
   *
   * @return bool
   *   TRUE if the current user has access to the format, FALSE otherwise.
   */
  protected function userHasAccessToFormat($format) {
    // Get a list of formats that the current user has access to.
    $formats = filter_formats(\Drupal::currentUser());
    return isset($formats[$format]);
  }

}
