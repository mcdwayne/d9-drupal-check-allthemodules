<?php

namespace Drupal\job;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form handler for the job edit forms.
 */
class JobForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $job = $this->entity;
    $insert = $job->isNew();
    $job->save();
    $job_link = $job->link($this->t('View'));
    $context = ['%title' => $job->label(), 'link' => $job_link];
    $t_args = ['%title' => $job->link($job->label())];

    if ($insert) {
      $this->logger('job')->notice('Job: added %title.', $context);
      drupal_set_message($this->t('Job %title has been created.', $t_args));
    }
    else {
      $this->logger('job')->notice('Job: updated %title.', $context);
      drupal_set_message($this->t('Job %title has been updated.', $t_args));
    }
  }

}
