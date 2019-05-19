<?php

namespace Drupal\user_edit\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;
use Drupal\user\Entity\User;

/**
 * Class UserEditController
 *
 * @package Drupal\user_edit\Controller
 */
class UserEditController extends ControllerBase {

  /**
   * @return array
   */
  function editProfile() {

    // Retrieves the entity manager service for returned the form object that is
    // responsible for building this form.
    $form_object = \Drupal::entityManager()->getFormObject('user', 'default');

    // Loads the current active user object.
    $entity = User::load(\Drupal::currentUser()->id());

    // Sets the entity instance.
    $form_object->setEntity($entity);

    // FormState stores information about the state of a form.
    $form_state = new FormState();

    // Builds and processes a form for a given form ID.
    return \Drupal::formBuilder()->buildForm($form_object, $form_state);
  }

}