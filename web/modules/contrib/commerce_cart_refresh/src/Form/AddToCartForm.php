<?php

namespace Drupal\commerce_cart_refresh\Form;

use Drupal\commerce_cart\Form\AddToCartForm as BaseForm;
use Drupal\commerce_cart_refresh\Event\CartFormQuantityAjaxChangeEvent;
use Drupal\commerce_cart_refresh\Event\CartPriceAjaxChangeEvent;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Custom class to act on the order item add to cart form.
 */
class AddToCartForm extends BaseForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form      = parent::buildForm($form, $form_state);
    $ccrm      = \Drupal::service('commerce_cart_refresh.manager');
    $variation = $form_state->getFormObject()->getEntity()->getPurchasedEntity();

    if (isset($form['quantity']['widget'])) {
      // Get our price DOM element.
      // See commerce_cart_refresh_preprocess_field() and our custom Twig.
      $price_selector = $ccrm->getPriceDomSelector($variation);

      foreach (Element::children($form['quantity']['widget']) as $delta) {
        // Add custom ajax behavior on Quantity change.
        $element          = &$form['quantity']['widget'][$delta]['value'];
        $element['#ajax'] = [
          'callback'        => '::ajaxRefresh',
          'event'           => 'focusout',
          'wrapper'         => $price_selector,
          'disable-refocus' => TRUE,
        ];
      }
    }

    return $form;
  }

  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    $ccrm      = \Drupal::service('commerce_cart_refresh.manager');
    $input     = $form_state->getUserInput();
    $trigger   = $form_state->getTriggeringElement()['#array_parents'];
    $variation = $form_state->getFormObject()->getEntity()->getPurchasedEntity();

    // Prepare the $response.
    $response = new AjaxResponse();

    // Calculate new price multiplied when Quantity changes.
    if (isset($trigger[0]) && $trigger[0] == 'quantity') {
      $quantity           = $input['quantity'][0]['value'];
      $calculated_price   = $ccrm->getCalculatedPrice($quantity, $variation);
      $price_dom_selector = $ccrm->getPriceDomSelector($variation);

      // Allow modules to do their things when the quantity changes.
      $event            = new CartFormQuantityAjaxChangeEvent($response, $price_dom_selector, $form, $form_state);
      $event_dispatcher = \Drupal::service('event_dispatcher');
      $event_dispatcher->dispatch(CartFormQuantityAjaxChangeEvent::QUANTITY_AJAX_CHANGE, $event);

      $response->addCommand(new ReplaceCommand('#' . $price_dom_selector, [
        '#prefix' => '<span id="' . $price_dom_selector . '">',
        '#markup' => $calculated_price,
        '#suffix' => '</span>',
      ]));

      // Allow modules to do their things when the quantity changes.
      $event            = new CartPriceAjaxChangeEvent($response, $price_dom_selector, $form, $form_state);
      $event_dispatcher = \Drupal::service('event_dispatcher');
      $event_dispatcher->dispatch(CartPriceAjaxChangeEvent::PRICE_AJAX_CHANGE, $event);
    }

    return $response;
  }

}
