<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\Core\Form\FormStateInterface;

/**
 * Reboots an AWS Instance.
 */
class InstanceRebootForm extends AwsDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $entity = $this->entity;

    return t('Are you sure you want to reboot instance: %name?', [
      '%name' => $entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Reboot');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $params = [
      'InstanceIds' => [
        $this->entity->getInstanceId(),
      ],
    ];

    $this->awsEc2Service->setCloudContext($this->entity->getCloudContext());
    $this->awsEc2Service->rebootInstances($params);

    $this->messenger->addMessage($this->t('@name rebooting.', [
      '@name' => $this->entity->label(),
    ]));

    $form_state->setRedirect('view.aws_instances.page_1', ['cloud_context' => $this->entity->getCloudContext()]);
  }

}
