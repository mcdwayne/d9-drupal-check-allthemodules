<?php

namespace Drupal\entity_extra\Form;

use Drupal\Core\Entity\ContentEntityForm as EntityContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * This form shows a message and redirects the user upon form submission.
 */
class ContentEntityForm extends EntityContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    // Shows a message after submission.
    $entity_type = $this->entity->getEntityType();
    $t_args = array(
      '@entity_type' => $entity_type->getLowercaseLabel(),
      '%entity' => $this->entity->label(),
    );
    $message = t('The @entity_type %entity has been saved.', $t_args);
    drupal_set_message($message);

    // Redirects to the entity's page.
    $form_state->setRedirectUrl($this->entity->toUrl());
  }
}
