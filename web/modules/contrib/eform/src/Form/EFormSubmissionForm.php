<?php

namespace Drupal\eform\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 */
class EFormSubmissionForm extends ContentEntityForm {
  const MODE_PREVIEW = 'mode_preview';

  /**
   * @var string
   */
  protected $mode;
  /**
   * The entity being used by this form.
   *
   * @var \Drupal\eform\Entity\EFormSubmission
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    if ($this->entity->getEFormType()->preview_page) {
      $form_state->set('preview_entity', $this->entity);
      $form_state->setRebuild();
    }
    return parent::save($form, $form_state);
  }

  /**
   * Form submission handler for the 'save draft' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return int
   *   Either SAVED_NEW or SAVED_UPDATED, depending on the operation performed.
   */
  public function saveDraft(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\eform\Entity\EFormSubmission $entity */
    $entity = $this->entity;

    $entity->setDraft(EFORM_DRAFT);
    return parent::save($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // $route = $this->getRequest()->get('_route');
    // if (!$this->entity->isNew() && $this->currentUser()->id() != $this->entity->getAuthor()->id()) {
    //  $this->setOperation('submit');
    // }

    $form = parent::form($form, $form_state);
    if ($this->entity->isNew()) {
      $form['revision_uid'] = array(
        '#type' => 'value',
        '#value' => $this->currentUser()->id(),
      );
    }
    return $form;
  }

  /**
   * {@inheritDoc}.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    /** @var \Drupal\eform\entity\EFormsubmission $entity */
    $entity = $this->entity;
    // Add redirect function callback.
    if (isset($actions['submit'])) {
      $actions['submit']['#submit'][] = '::eformRedirect';
    }
    if ($entity->getEFormType()->isDraftable()) {
      $actions['draft'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Save Draft'),
        '#validate' => array('::validate'),
        '#submit' => array('::submitForm', '::saveDraft', '::eformRedirect'),
        '#weight' => -100,
      );
    }

    return $actions;
  }

  /**
   * Set redirect for in $form_state according to EForm Type Logic.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function eformRedirect(array $form, FormStateInterface $form_state) {
    if ($this->mode != $this::MODE_PREVIEW) {
      /** @var \Drupal\eform\Entity\EFormSubmission $eform_submission */
      $eform_submission = $this->entity;

      if (!$eform_submission->isDraft()) {
        $redirect_params = [
          'eform_type' => $eform_submission->getType(),
          'eform_submission' => $eform_submission->id(),
        ];
        $form_state->setRedirect('entity.eform_submission.confirm', $redirect_params);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // @todo Remove require fields for drafts. This would require JS too.
    parent::submitForm($form, $form_state);
    $eform_type = $this->entity->getEFormType();
    if ($eform_type->preview_page) {
      $this->setMode($this::MODE_PREVIEW);

    }
  }

  /**
   * Set the form mode.
   *
   * @param $mode
   */
  protected function setMode($mode) {
    $this->mode = $mode;
  }

}
