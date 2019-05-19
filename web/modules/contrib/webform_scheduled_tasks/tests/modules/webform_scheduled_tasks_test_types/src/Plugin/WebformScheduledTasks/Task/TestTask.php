<?php

namespace Drupal\webform_scheduled_tasks_test_types\Plugin\WebformScheduledTasks\Task;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform_scheduled_tasks\Plugin\WebformScheduledTasks\TaskPluginBase;

/**
 * A debug task plugin.
 *
 * @WebformScheduledTask(
 *   id = "test_task",
 *   label = @Translation("Test task"),
 * )
 */
class TestTask extends TaskPluginBase {

  /**
   * {@inheritdoc}
   */
  public function executeTask(\Iterator $submissions) {
    \Drupal::messenger()->addStatus('Run test_task ::executeTask');

    foreach ($submissions as $submission) {
      \Drupal::messenger()->addStatus('Processed submission ' . $submission->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onSuccess() {
    \Drupal::messenger()->addStatus('Run test_task ::onSuccess');
  }

  /**
   * {@inheritdoc}
   */
  public function onFailure() {
    \Drupal::messenger()->addStatus('Run test_task ::onFailure');
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'test_option' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['test_option'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Test option'),
      '#default_value' => $this->getConfiguration()['test_option'],
    ];
    return $form;
  }

}
