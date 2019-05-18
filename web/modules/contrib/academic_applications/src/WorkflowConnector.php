<?php

namespace Drupal\academic_applications;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Connects forms in configured workflows.
 */
class WorkflowConnector {

  /**
   * A config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * WorkflowConnector constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   A config factory.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  /**
   * Determines the IDs of forms in configured workflows.
   *
   * @return array
   *   Form IDs.
   */
  public function workflowFormIds() {
    $workflow_forms = [];
    foreach ($this->configFactory->listAll('academic_applications.workflow.') as $workflow_config_id) {
      $workflow_config = $this->configFactory->get($workflow_config_id);
      $application_config_id = $workflow_config->get('application');
      $workflow_forms[] = 'webform_submission_' . $this->configFactory->get($application_config_id)->get('id') . '_form';
      $upload_config_id = $workflow_config->get('upload');
      $workflow_forms[] = 'webform_submission_' . $this->configFactory->get($upload_config_id)->get('id') . '_form';
    }
    return $workflow_forms;
  }

  /**
   * Returns the map of application forms to upload forms.
   *
   * @return array
   *   Keys are the application form and values are the upload form.
   */
  public function workflowMap() {
    $map = [];
    foreach ($this->configFactory->listAll('academic_applications.workflow.') as $workflow_config_id) {
      $workflow_config = $this->configFactory->get($workflow_config_id);
      $application_config_id = $workflow_config->get('application');
      $upload_config_id = $workflow_config->get('upload');
      $map[$this->configFactory->get($application_config_id)->get('id')] = $this->configFactory->get($upload_config_id)->get('id');
    }
    return $map;
  }

}
