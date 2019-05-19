<?php

namespace Drupal\simple_entity_merge\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Url;

/**
 * Form class to confirm the action of merging an entity.
 *
 * @package Drupal\simple_entity_merge\Form
 */
class Merge extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_entity_merge_merge';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $form = [];
    $form['help'] = [
      '#markup' => $this->t('Use this form to replace all references to "@label" with references to another entity of the same type. For example, this functionality can be used to merge two synonymous taxonomy terms.', [
        '@label' => $entity->label(),
      ]),
    ];
    $form['simple_entity_merge'] = [
      '#title' => $this->t('Replace all references to "@label" (id: @id) with references to:', [
        '@label' => $entity->label(),
        '@id' => $entity->id(),
      ]),
      '#required' => TRUE,
      '#type' => 'entity_autocomplete',
      '#target_type' => $entity->getEntityTypeId(),
    ];
    if ($this->entityTypeHasBundles($entity)) {
      $form['simple_entity_merge']['#selection_settings']['target_bundles'] = [
        $entity->bundle(),
      ];
    }
    return $form + parent::buildForm($form, $form_state);
  }

  /**
   * Whether the entity for this form has bundles.
   */
  private function entityTypeHasBundles($entity) {
    return !empty($entity->getEntityType()->getBundleEntityType());
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();
    $entity_merge_id = $form_state->getValue('simple_entity_merge');

    $merger = \Drupal::service('simple_entity_merge.merge');
    $success = $merger->mergeReferences($entity->getEntityTypeId(), $entity->id(), $entity_merge_id);

    if ($success) {
      drupal_set_message($this->t('All references to this content have been changed to the new one, now you can delete this one.'));
    }
    else {
      drupal_set_message($this->t('There has been a problem merging references to your entity'));
    }
    $form_state->setRedirect('entity.' . $entity->getEntityTypeId() . '.edit_form', [$entity->getEntityTypeId() => $entity->id()]);
  }

  /**
   * Returns the question to ask the user for confirmation.
   *
   * @return string
   *   The form question. The page title will be set to this value.
   */
  public function getQuestion() {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();
    return $this->t('Are you sure you want to merge "@label" with another entity', [
      '@label' => $entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    $entity = $this->getEntity();
    // On cancel go back to the entity.
    return Url::fromRoute('entity.' . $entity->getEntityTypeId() . '.canonical', [
      $entity->getEntityTypeId() => $this->getEntity()->id(),
    ]);
  }

}
