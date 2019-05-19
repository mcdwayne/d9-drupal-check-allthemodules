<?php

namespace Drupal\syncart\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * FormCommerceCheckoutFlowMultistepDefaultAlter.
 */
class FormCommerceCheckoutFlowMultistepDefaultAlter {

  /**
   * Hook preprocess.
   */
  public static function hook(&$form, $form_state) {
    $form['contact_information']['email']['#required'] = FALSE;
    $form['contact_information']['email']['#disabled'] = TRUE;
    $form['contact_information']['email']['#type'] = 'hidden';
    $form['contact_information']['#type'] = 'hidden';
    $form['contact_information']['email']['#default_value'] = 'commerce@example.com';
    $form['actions']['next']['#submit'] = ['\Drupal\syncart\Hook\FormCommerceCheckoutFlowMultistepDefaultAlter::submit_checkout'];
    $form['#attached']['library'][] = 'syncart/checkout';
  }

  /**
   * Callback FormCommerceCheckoutFlowMultistepDefaultAlter.
   */
  public static function submit_checkout(array &$form, FormStateInterface $form_state) {
    $authService = \Drupal::service('syncart.auth');
    $cartService = \Drupal::service('syncart.cart');
    $uid = \Drupal::currentUser()->id();
    $getValues = $form_state->getValues();
    $profile = $getValues['billing_information']['profile'];
    $info = [
      'name' => $profile['field_customer_name'][0]['value'],
      'surname' => $profile['field_customer_surname'][0]['value'],
      'phone' => $profile['field_customer_phone'][0]['value'],
      'email' => $profile['field_customer_email'][0]['value'],
      'comment' => $profile['field_customer_comment'][0]['value'],
    ];

    if (!empty($uid)) {
      $user = User::load($uid);
    }
    else {
      $user = $authService->getUserEmail($info['email']);
      if (!is_object($user)) {
        $user = $authService->createUser($info);
        user_login_finalize($user);
        /*_user_mail_notify('register_admin_created', $user);*/
      }
    }
    /* отправили письмо с одноразоввой ссылкой входа */
    _user_mail_notify('status_activated', $user);
    //$profile = $authService->createProfile($user, $info);
    $inline_form = $form['billing_information']['profile']['#inline_form'];
    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $inline_form->getEntity();
    $profile->set('uid', $user->id());
    $profile->save();

    $cart = $cartService->load();
    $cart->setCustomer($user);
    $cart->setBillingProfile($profile);
    $order = $cartService->cartToOrder();
    $order->save();

    $orderId = $order->id();
    /* отправили чек клиенту */
    //$cartService->sendReceipt($order);

    $url = Url::fromRoute('commerce_checkout.form', ['commerce_order' => $orderId, 'step' => 'complete']);
    return new RedirectResponse($url->toString());
  }

}
