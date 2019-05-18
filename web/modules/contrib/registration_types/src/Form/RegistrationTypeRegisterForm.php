<?php

namespace Drupal\registration_types\Form;

use Drupal\user\RegisterForm;
use Drupal\registration_types\Entity\RegistrationType;
use Drupal\registration_types\Entity\RegistrationTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Cache\Cache;

/**
 * Class RegistrationTypeRegisterForm.
 *
 */
class RegistrationTypeRegisterForm extends RegisterForm {

  /**
   * {@inheritdoc}
   */
  protected function init(FormStateInterface $form_state) {
    // remove prefix (see registration_types_entity_type_build()), get form display mode
    $registration_type_id = str_replace(RegistrationTypeInterface::DISPLAY_MODE_PREFIX, '', $this->getOperation());
    $registration_type = RegistrationType::load($registration_type_id);
    $form_type = $registration_type->getDisplay();

    // and set the real form display mode for further form generation
    $this->setOperation($form_type);
    parent::init($form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    // Attach cache tags to reset cache when making changes to registration
    // types (especially changing display mode)
    $form['#cache']['tags'] =  Cache::mergeTags($form['#cache']['tags'], ['registration_type']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // @see RegistrationTypesController::page()
    $registration_type_id = $form_state->get('registration_type');
    $registration_type = RegistrationType::load($registration_type_id);

    // assign roles if any set for the registration type
    foreach ($registration_type->getRoles() as $rid) {
      $this->entity->addRole($rid);
    }
    parent::save($form, $form_state);
  }

}
