<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting a Volume entity.
 *
 * @ingroup aws_cloud
 */
class VolumeDeleteForm extends AwsDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $entity = $this->entity;
    $this->awsEc2Service->setCloudContext($entity->getCloudContext());

    if ($this->awsEc2Service->deleteVolume([
      'VolumeId' => $entity->getVolumeId(),
    ]) != NULL) {

      $message = $this->t('The @type "@label" has been deleted.', [
        '@type'  => $entity->getEntityType()->getLabel(),
        '@label' => $entity->label(),
      ]);

      $entity->delete();
      $this->messenger->addMessage($message);
    }
    else {
      $message = $this->t('The @type "@label" couldn\'t delete.', [
        '@type'  => $entity->getEntityType()->getLabel(),
        '@label' => $entity->label(),
      ]);
      $this->messenger->addError($message);
    }
    $form_state->setRedirect('view.aws_volume.page_1', ['cloud_context' => $entity->getCloudContext()]);

  }

}
