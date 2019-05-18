<?php

namespace Drupal\bibcite_entity\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Builds the form to delete Contributor role entities.
 */
class ContributorRoleDeleteForm extends EntityConfirmFormBase {

  /**
   * If author of this role is in reference entity.
   *
   * @var bool
   */
  private $inUse = FALSE;

  /**
   * Find if author of this role is in reference entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  private function checkInReference() {
    $storage = $this->entityTypeManager->getStorage('bibcite_reference');
    $query = $storage->getQuery();
    return !empty($query->condition('author.role', $this->entity->id())->range(0, 1)->execute());
  }

  /**
   * {@inheritdoc}
   */
  public function init(FormStateInterface $form_state) {
    parent::init($form_state);
    $this->inUse = $this->checkInReference();
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    if (!$this->inUse) {
      return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
    }
    else {
      return $this->t('Role %name used in reference(s) and cannot be deleted.', ['%name' => $this->entity->label()]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.bibcite_contributor_role.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->entity->delete();

    $this->messenger()->addStatus(
      $this->t('content @type: deleted @label.',
        [
          '@type' => $this->entity->bundle(),
          '@label' => $this->entity->label(),
        ]
        )
    );

    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($this->inUse) {
      $form_state->setError($form, $this->t('Role %name used in reference(s) and cannot be deleted.', ['%name' => $this->entity->label()]));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#disabled'] = $this->inUse;
    return $actions;
  }

}
