<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting a Instance entity.
 *
 * @ingroup aws_cloud
 */
class InstanceDeleteForm extends AwsDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {

    return t('Delete | Terminate');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $entity = $this->entity;
    $this->awsEc2Service->setCloudContext($entity->getCloudContext());

    $result = $this->awsEc2Service->terminateInstance([
      'InstanceIds' => [$entity->getInstanceId()],
    ]);

    if (isset($result['TerminatingInstances'][0]['InstanceId'])) {

      $message = $this->t('The @type "@label" has been terminated.', [
        '@type'  => $entity->getEntityType()->getLabel(),
        '@label' => $entity->label(),
      ]);

      $entity->delete();
      $this->messenger->addMessage($message);
      $form_state->setRedirect('view.aws_instances.page_1', ['cloud_context' => $entity->getCloudContext()]);
    }
    else {
      $message = $this->t('The @type "@label" couldn\'t terminate.', [
        '@type'  => $entity->getEntityType()->getLabel(),
        '@label' => $entity->label(),
      ]);
      $this->messenger->addError($message);
    }

    $this->awsEc2Service->updateInstances();

    $form_state->setRedirect('view.aws_instances.page_1', ['cloud_context' => $entity->getCloudContext()]);
  }

}
