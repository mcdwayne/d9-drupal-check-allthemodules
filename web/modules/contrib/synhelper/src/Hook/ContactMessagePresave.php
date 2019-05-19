<?php

namespace Drupal\synhelper\Hook;

use Drupal\Core\Controller\ControllerBase;

/**
 * SetMessageTitle.
 */
class ContactMessagePresave extends ControllerBase {

  /**
   * Set Title.
   */
  public static function setTitle($entity) {
    if (empty($entity->subject->getValue())) {
      $formId = $entity->contact_form->getString();
      $entityForm = \Drupal::entityManager()->getStorage('contact_form')->load($formId);
      $formTitle = !empty($entityForm->get('label')) ? $entityForm->get('label') : t('Contact form');
      $formTitle .= " - " . format_date(REQUEST_TIME, 'custom', 'dM H:i:s');
      $entity->subject->setValue($formTitle);
    }
  }

}
