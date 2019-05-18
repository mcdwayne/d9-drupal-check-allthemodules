<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a form for deleting a ElasticIp entity.
 *
 * @ingroup aws_cloud
 */
class ElasticIpDeleteForm extends AwsDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /* @var \Drupal\aws_cloud\Entity\Ec2\ElasticIp $entity */
    $entity = $this->entity;
    $this->awsEc2Service->setCloudContext($entity->getCloudContext());

    $allocation_id = $entity->getAllocationId();
    $public_ip = $entity->getPublicIp();
    $params = [];
    if ($entity->getDomain() == 'standard' && !empty($public_ip)) {
      $params['PublicIp'] = $public_ip;
    }
    elseif ($entity->getDomain() == 'vpc' && !empty($allocation_id)) {
      $params['AllocationId'] = $allocation_id;
    }
    if (!empty($params) && $this->awsEc2Service->releaseAddress($params) != NULL
    ) {

      // Update instances after the elastic ip is deleted.
      $this->awsEc2Service->updateInstances();

      $message = $this->t('The @type "@label" has been deleted.', [
        '@type' => $entity->getEntityType()->getLabel(),
        '@label' => $entity->label(),
      ]);

      $entity->delete();
      $this->messenger->addMessage($message);
    }
    else {
      $message = $this->t('The @type "@label" couldn\'t delete.', [
        '@type' => $entity->getEntityType()->getLabel(),
        '@label' => $entity->label(),
      ]);
      $this->messenger->addError($message);
    }

    $form_state->setRedirect('view.aws_elastic_ip.page_1', ['cloud_context' => $entity->getCloudContext()]);
  }

}
