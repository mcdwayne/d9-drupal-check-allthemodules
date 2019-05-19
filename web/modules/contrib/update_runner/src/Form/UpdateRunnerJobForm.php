<?php

namespace Drupal\update_runner\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Scheduled site update edit forms.
 *
 * @ingroup update_runner
 */
class UpdateRunnerJobForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\update_runner\Entity\ScheduledSiteUpdate */
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
        drupal_set_message($this->t('Created the %label update runner job.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label update runner job.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.update_runner_job.canonical', ['update_runner_job' => $entity->id()]);
  }

}
