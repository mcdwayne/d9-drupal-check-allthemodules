<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting a SecurityGroup entity.
 *
 * @ingroup aws_cloud
 */
class SecurityGroupDeleteForm extends AwsDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $entity = $this->entity;
    $this->awsEc2Service->setCloudContext($entity->getCloudContext());

    if ($this->awsEc2Service->deleteSecurityGroup([
      'GroupId'   => $entity->getGroupId(),
    ]) != NULL) {

      $message = $this->t('The @type "@group_name" has been deleted.', [
        '@type'       => $entity->getEntityType()->getLabel(),
        '@group_name' => $entity->getGroupName(),
      ]);

      $entity->delete();
      $this->messenger->addMessage($message);
    }
    else {
      $message = $this->t('The @type "@group_name" couldn\'t delete.', [
        '@type'       => $entity->getEntityType()->getLabel(),
        '@group_name' => $entity->getGroupName(),
      ]);
      $this->messenger->addError($message);
    }

    $form_state->setRedirect('view.aws_security_group.page_1', ['cloud_context' => $entity->getCloudContext()]);
  }

}
