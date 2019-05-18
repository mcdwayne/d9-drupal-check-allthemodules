<?php

namespace Drupal\entity_usage_integrity\FormIntegrityValidation;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provide integrity validation on submitted content moderation entity form.
 *
 * This validation happens only for entity content moderation form.
 *
 * This alter displays warnings or errors, when entity edit form is saved
 * and there are invalid relations.
 *
 * If 'block' mode is selected, saving entity with broken usage relations
 * is forbidden. If 'warning' mode is selected, saving entity with broken
 * usage relations is allowed, but warnings will be displayed.
 *
 * @see IntegritySettingsForm::buildForm()
 */
final class SubmittedModerationStateForm extends SubmittedFormBase {

  /**
   * {@inheritdoc}
   */
  protected function buildEntity(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $form_state->get('entity');
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
    $entity = $storage->createRevision($entity, $entity->isDefaultRevision());
    $new_state = $form_state->getValue('new_state');
    $entity->set('moderation_state', $new_state);

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function isApplicable(FormStateInterface $form_state) {
    return $form_state->getFormObject()->getFormId() === 'content_moderation_entity_moderation_form';
  }

}
