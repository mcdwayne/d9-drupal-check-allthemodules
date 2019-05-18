<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\aws_cloud\Entity\Ec2\Instance;
use Drupal\Core\Form\FormStateInterface;

/**
 * Stops an AWS Instance.
 *
 * This form confirms with the user if they really
 * want to stop the instance.
 *
 * @package Drupal\aws_cloud\Form\Ec2
 */
class InstanceStopForm extends AwsDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $entity = $this->entity;

    return t('Are you sure you want to stop instance: %name?', [
      '%name' => $entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Stop');
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
    $result = $this->awsEc2Service->stopInstances($params);
    if ($result != NULL) {
      $current_state = $result['StoppingInstances'][0]['CurrentState']['Name'];
      $instance = Instance::load($this->entity->id());
      $instance->setInstanceState($current_state);
      $instance->save();

      $message = $this->t('@name stopping.', [
        '@name'  => $this->entity->label(),
      ]);
      $this->messenger->addMessage($message);
    }

    $form_state->setRedirect('view.aws_instances.page_1', ['cloud_context' => $this->entity->getCloudContext()]);

  }

}
