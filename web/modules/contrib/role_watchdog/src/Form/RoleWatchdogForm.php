<?php

namespace Drupal\role_watchdog\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Role Watchdog edit forms.
 *
 * @ingroup role_watchdog
 */
class RoleWatchdogForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\role_watchdog\Entity\RoleWatchdog */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Role Watchdog.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Role Watchdog.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.role_watchdog.canonical', ['role_watchdog' => $entity->id()]);
  }

}
