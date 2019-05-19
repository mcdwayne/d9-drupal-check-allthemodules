<?php

namespace Drupal\user_bundle;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\user\RegisterForm;

/**
 * A bundle-aware form handler for user register forms.
 */
class TypedRegisterForm extends RegisterForm {

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    return $this->entityTypeManager->getStorage($entity_type_id)->create([
      'type' => \Drupal::config('user_bundle.settings')->get('registration_user_type'),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // Our module's settings can change the content of the registration form.
    // Add cache tags so this form is rebuilt any time our settings change.
    $form = parent::form($form, $form_state);
    $form['#cache']['tags'] += \Drupal::config('user_bundle.settings')->getCacheTags();
    return $form;
  }

}
