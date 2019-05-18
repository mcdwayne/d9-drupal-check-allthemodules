<?php

namespace Drupal\competition\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure competition settings for this site.
 */
class CompetitionSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'competition_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['competition.settings'];
  }

  /**
   * Administrative settings for Competitions.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $cycles = $this
      ->config('competition.settings')
      ->get('cycles');

    $cycles_options = [];
    foreach ($cycles as $value => $label) {
      $cycles_options[] = $value . '|' . $label;
    }

    $statuses = $this
      ->config('competition.settings')
      ->get('statuses');

    $status_options = [];
    foreach ($statuses as $value => $label) {
      $status_options[] = $value . '|' . $label;
    }

    $queues = $this
      ->config('competition.settings')
      ->get('queues');

    $queues_options = [];
    foreach ($queues as $value => $label) {
      $queues_options[] = $value . '|' . $label;
    }

    $form['group'] = array(
      '#type' => 'details',
      '#title' => $this->t('Settings'),
      '#description' => $this->t("Configure global settings for Competitions here."),
      '#open' => TRUE,
    );

    $form['group']['cycles'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Competition cycles'),
      '#description' => $this->t("<p>Cycles are used to separate a single competition in to groups, based on some interval (e.g. a year, month, season). This is useful for reporting, archiving and judging particular sets of entries.</p><p>These options will be shown on the competition configuration screen, and allow you to control the competition's currently active cycle.</p><p>Provide the possible values this field can contain; enter one value per line, in the format key|label.</p>"),
      '#default_value' => implode("\n", $cycles_options),
      '#required' => TRUE,
    );

    $form['group']['statuses'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Competition entry statuses'),
      '#description' => $this->t('<p>Set the possible status options for competition entries. Note: "Created", "Updated", "Finalized", and "Archived" must always be part of this configuration.</p><p>Provide the possible values this field can contain; enter one value per line, in the format number|label.</p>'),
      '#default_value' => implode("\n", $status_options),
      '#required' => TRUE,
    );

    $form['group']['queues'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Competition judging queues'),
      '#description' => $this->t('<p>Provide the possible values this field can contain; enter one value per line, in the format number|label.</p>'),
      '#default_value' => implode("\n", $queues_options),
      '#required' => TRUE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Cycles
    // There should be no HTML in these keys and labels - sanitize user input.
    $cycles = explode("\n", trim(Html::escape($form_state->getValue('cycles'))));
    $cycle_options = array();
    foreach ($cycles as $cycle) {
      $cycle = explode('|', $cycle);
      $cycle_options[$cycle[0]] = $cycle[1];
    }

    // Statuses
    // There should be no HTML in these labels - sanitize user input.
    $statuses = explode("\n", trim(Html::escape($form_state->getValue('statuses'))));
    $status_options = array(
      'Created',
      'Updated',
      'Finalized',
      'Archived',
    );
    foreach ($statuses as $status) {
      $status = explode('|', $status);
      if (!in_array(trim($status[1]), $status_options)) {
        $status_options[] = trim($status[1]);
      }
    }

    // Queue labels
    // There should be no HTML in these keys and labels - sanitize user input.
    $queues = explode("\n", trim(Html::escape($form_state->getValue('queues'))));
    $queues_options = array();
    foreach ($queues as $queue) {
      $queue = explode('|', $queue);
      $queues_options[$queue[0]] = $queue[1];
    }

    $this->config('competition.settings')
      ->set('cycles', $cycle_options)
      ->set('statuses', $status_options)
      ->set('queues', $queues_options)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
