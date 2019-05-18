<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\Core\Form\FormStateInterface;

/**
 * Disassociate elastic ip address form.
 */
class ElasticIpDisassociateForm extends AwsDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    /* @var \Drupal\aws_cloud\Entity\Ec2\ElasticIp $entity */
    $entity = $this->entity;
    return $this->t('Are you sure you want to disassociate this Elastic IP address (@ip_address)', [
      '@ip_address' => $entity->getPublicIp(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $entity = $this->entity;
    $instance_id = $entity->getInstanceId();
    $network_interface_id = $entity->getNetworkInterfaceId();
    $instance = $this->getInstanceById($instance_id);

    $instance_link = $this->entityLinkRenderer->renderViewElement(
      $instance_id,
      'aws_cloud_instance',
      'instance_id',
      [],
      $instance->getName() != $instance->getInstanceId() ? $this->t('@instance_name (@instance_id)', [
        '@instance_name' => $instance->getName(),
        '@instance_id' => $instance_id,
      ]) : $instance_id
    );

    $msg = '<h2>Elastic IP Information:</h2>';
    $msg .= $this->t('<ul><li>Instance Id: :instance_id</li><li>Network Id: @network_id</li></ul>',
      [
        ':instance_id' => $instance_link['#markup'],
        '@network_id' => $network_interface_id,
      ]
    );

    return $msg;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Disassociate Address');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    if ($this->entity->getAssociationId() == NULL) {
      $form['error'] = [
        '#markup' => '<div>' . $this->t('Elastic IP is already disassociated') . '</div>',
      ];
      unset($form['description']);
      unset($form['actions']['submit']);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->awsEc2Service->setCloudContext($this->entity->getCloudContext());
    $entity = $this->entity;

    $result = $this->awsEc2Service->disassociateAddress([
      'AssociationId' => $this->entity->getAssociationId(),
    ]);
    if ($result != NULL) {
      $instance_id = $entity->getInstanceId();
      $network_interface_id = $entity->getNetworkInterfaceId();

      $this->messenger->addMessage($this->t('Elastic IP disassociated from instance: @instance_id and network: @network_id', [
        '@instance_id' => $instance_id,
        '@network_id' => $network_interface_id,
      ]));

      $this->awsEc2Service->updateElasticIp();
      $this->awsEc2Service->updateInstances();
      $this->awsEc2Service->updateNetworkInterfaces();

      $this->clearCacheValues();
    }
    else {
      $this->messenger->addError($this->t('Unable to disassociated elastic ip.'));
    }
    $form_state->setRedirect('view.aws_elastic_ip.page_1', ['cloud_context' => $entity->getCloudContext()]);
  }

  /**
   * Helper method to load instance by id.
   *
   * @param string $instance_id
   *   Instance Id to load.
   *
   * @return \Drupal\aws_cloud\Entity\Ec2\Instance
   *   Instance object.
   */
  private function getInstanceById($instance_id) {
    $instances = $this->entityTypeManager->getStorage('aws_cloud_instance')
      ->loadByProperties([
        'instance_id' => $instance_id,
        'cloud_context' => $this->entity->getCloudContext(),
      ]);
    return array_shift($instances);
  }

}
