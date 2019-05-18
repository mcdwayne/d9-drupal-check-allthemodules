<?php

namespace Drupal\google_nl_autotag\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Run Google NL Autotag batch updates of content.
 */
class BatchUpdateForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_nl_autotag_batch_update_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['description'] = [
      '#type' => 'markup',
      '#markup' => '<div>' . $this->t('Click the button below to resave all nodes of the content types which are configured for autotagging. Doing so will cause the tags for the content to be set.') . '</div>',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $types = array_keys($this->config('google_nl_autotag.settings')->get('content_types'));

    $nids = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('type', $types, 'IN')
      ->execute();

    batch_set([
      'title' => $this->t('Tagging Content'),
      'operations' => [
        ['google_nl_autotag_batch_update_content', [$nids]],
      ],
      'finished' => 'google_nl_autotag_batch_update_callback',
      'file' => drupal_get_path('module', 'google_nl_autotag') . '/google_nl_autotag.batch.inc',
    ]);
  }

}
