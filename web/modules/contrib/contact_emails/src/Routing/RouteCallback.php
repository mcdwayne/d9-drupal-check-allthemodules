<?php

namespace Drupal\contact_emails\Routing;

use Drupal\contact\Entity\ContactForm;

/**
 * Defines dynamic routes.
 */
class RouteCallback {

  /**
   * {@inheritdoc}
   */
  public function addFormTitle() {
    $route_match = \Drupal::routeMatch();
    $contact_form = $route_match->getParameter('contact_form');
    return t('Add New Contact Email to "@contact_form"', [
      '@contact_form' => $contact_form->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function editFormTitle() {
    $route_match = \Drupal::routeMatch();
    $contact_email = $route_match->getParameter('contact_email');
    $contact_form_id = $contact_email->get('contact_form')->target_id;
    $contact_form = ContactForm::load($contact_form_id);
    return t('Edit Contact Email @id for "@contact_form"', [
      '@id' => $contact_email->id(),
      '@contact_form' => $contact_form->label(),
    ]);
  }

}
