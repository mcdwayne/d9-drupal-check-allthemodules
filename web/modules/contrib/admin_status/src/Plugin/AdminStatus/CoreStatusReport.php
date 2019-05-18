<?php

namespace Drupal\admin_status\Plugin\AdminStatus;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a System Report Error message.
 *
 * @Plugin(
 *   id = "core_status_report",
 *   name = "Core Status Report",
 *   admin_permission = "administer admin status",
 * )
 */
class CoreStatusReport extends AdminStatusPluginBase {

  public $systemManager = NULL;

  /**
   * {@inheritdoc}
   */
  public function description() {
    return $this->t('This is the Core Status Report for Admin Status.');
  }

  /**
   * {@inheritdoc}
   */
  public function configForm(array $form,
                             FormStateInterface $form_state,
                             array $configValues) {
    $form['message_type'] = [
      '#type' => 'checkboxes',
      '#options' => [
        'error' => $this->t('Errors'),
        'warning' => $this->t('Warnings'),
      ],
      '#title' => $this->t('Types of Core Status Report Errors/Warnings to show'),
      '#default_value' => empty($configValues['message_type']) ? '' : $configValues['message_type'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function configValidateForm(array $form,
                                     FormStateInterface $form_state,
                                     array $configValues) {
    // No validation needed for the checkboxes since they can all be empty.
  }

  /**
   * {@inheritdoc}
   */
  public function configSubmitForm(array $form,
                                   FormStateInterface $form_state,
                                   array $configValues) {
    $config = $form_state->getValue([
      'plugins',
      'core_status_report',
      'config',
    ]);
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  protected function translateSeverityToStatus($severity) {
    $system_manager = $this->systemManager;
    $status = '';
    switch ($severity) {
      case $system_manager::REQUIREMENT_WARNING:
        $status = 'warning';
        break;

      case $system_manager::REQUIREMENT_ERROR:
        $status = 'error';
        break;
    }
    return $status;
  }

  /**
   * {@inheritdoc}
   */
  public function message(array $configValues) {
    $this->systemManager = \Drupal::service('system.manager');
    $system_manager = $this->systemManager;
    $requirements = $system_manager->listRequirements();
    $options = array_filter($configValues['message_type']);
    $systemSeverities = [];
    foreach ($options as $option) {
      switch ($option) {
        case 'warning':
          $systemSeverities[] = $system_manager::REQUIREMENT_WARNING;
          break;

        case 'error':
          $systemSeverities[] = $system_manager::REQUIREMENT_ERROR;
          break;
      }
    }
    $messageParts = [];
    $renderer = \Drupal::service('renderer');
    foreach ($requirements as $requirement) {
      if (isset($requirement['severity'])) {
        if (in_array($requirement['severity'], $systemSeverities)) {
          $severity = $requirement['severity'];
          unset($requirement['severity']);
          $renderArray = [
            '#theme' => 'status_report',
            '#requirements' => [$requirement],
          ];
          $renderedOutput = $renderer->renderPlain($renderArray);
          $messageParts[] = [
            'message' => $renderedOutput,
            'status' => $this->translateSeverityToStatus($severity),
          ];
        }
      }
    }
    return $messageParts;
  }

}
