<?php

namespace Drupal\improvements\TwigExtension;

/**
 * Twig extensions.
 */
class TwigExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('drupal_contact', [$this, 'drupalContact']),
    ];
  }

  /**
   * Return contact form.
   */
  public function drupalContact($contact_form_id) {
    $contact_message = \Drupal::entityTypeManager()
      ->getStorage('contact_message')
      ->create(['contact_form' => $contact_form_id]);

    $form = \Drupal::service('entity.form_builder')->getForm($contact_message);
    $form['#cache']['contexts'][] = 'user.permissions';

    return $form;
  }

}
