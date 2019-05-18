<?php

namespace Drupal\crm_core_contact\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OrganizationForm.
 *
 * Provides a form for the Organization entity.
 *
 * @package Drupal\crm_core_contact\Form
 */
class OrganizationForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $organization = $this->entity;

    $status = $organization->save();

    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t('The organization %name has been updated.', ['%name' => $organization->label()]));
      if ($organization->access('view')) {
        $form_state->setRedirect('entity.crm_core_organization.canonical', ['crm_core_organization' => $organization->id()]);
      }
      else {
        $form_state->setRedirect('entity.crm_core_organization.collection');
      }
    }
    elseif ($status == SAVED_NEW) {
      drupal_set_message($this->t('The organization %name has been added.', ['%name' => $organization->label()]));
      \Drupal::logger('crm_core_organization')->notice('Added organization %name.', ['%name' => $organization->label()]);
      $form_state->setRedirect('entity.crm_core_organization.collection');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save @organization_type', [
      '@organization_type' => $this->entity->get('type')->entity->label(),
    ]);
    return $actions;
  }

}
