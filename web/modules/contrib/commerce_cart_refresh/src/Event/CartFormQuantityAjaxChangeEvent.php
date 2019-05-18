<?php

namespace Drupal\commerce_cart_refresh\Event;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when a user click out the quantity input field in AddToCart form.
 */
class CartFormQuantityAjaxChangeEvent extends Event {

  const QUANTITY_AJAX_CHANGE = 'commerce_cart_refresh.cart_form_quantity_ajax_change';

  /**
   * The ajax response.
   *
   * @var \Drupal\Core\Ajax\AjaxResponse
   */
  protected $response;

  /**
   * The quantity price DOM element.
   *
   * @var string
   */
  public $selector;

  /**
   * The current form.
   *
   * @var array
   */
  public $form;

  /**
   * The current form.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  public $formState;

  /**
   * Constructs the object.
   *
   * @param \Drupal\Core\Ajax\AjaxResponse $response
   *   The current response.
   * @param array $form
   *   The current form, passed by reference.
   * @param \Drupal\Core\Form\FormStateInterface $form
   *   The current form.
   */
  public function __construct(AjaxResponse $response, string $selector, array &$form, FormStateInterface $form_state) {
    $this->response = $response;
    $this->selector = $selector;
    $this->form = $form;
    $this->formState = $form_state;
  }

  /**
   * Get element that triggered this event.
   *
   * @return string
   */
  public function getResponse() {
    return $this->response;
  }

  /**
   * Get the Quantity input DOM element selector.
   *
   * @return string
   */
  public function getPriceSelector() {
    return $this->selector;
  }

  /**
   * Get element that triggered this event.
   *
   * @return string
   */
  public function getTriggeringElement() {
    $trigger   = $this->formState->getTriggeringElement()['#array_parents'];
    return $trigger;
  }

  /**
   * Get the selected quantity
   *
   * @return int
   */
  public function getQuantity() {
    $input     = $this->formState->getUserInput();
    return isset($input['quantity'][0]['value']) ? $input['quantity'][0]['value'] : NULL;
  }

  /**
   * Get the form.
   *
   * @return array
   */
  public function getForm() {
    return $this->form;
  }

  /**
   * Get the form state object.
   *
   * @return FormStateInterface
   */
  public function getFormState() {
    return $this->formState;
  }

}
