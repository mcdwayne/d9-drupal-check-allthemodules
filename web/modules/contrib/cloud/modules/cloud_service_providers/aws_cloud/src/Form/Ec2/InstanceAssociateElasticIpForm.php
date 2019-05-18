<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\aws_cloud\Entity\Ec2\ElasticIp;
use Drupal\aws_cloud\Entity\Ec2\NetworkInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Associate an elastic ip form.
 *
 * This form is instance specific and accessed from the Instance operations.
 */
class InstanceAssociateElasticIpForm extends AwsDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    /* @var \Drupal\aws_cloud\Entity\Ec2\Instance $instance */
    $instance = $this->entity;
    return $this->t('Select elastic ip to which you want to associate with this instance @instance_name (@instance_id)', [
      '@instance_name' => $instance->getName(),
      '@instance_id' => $instance->getInstanceId(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Associate');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['allocation_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Elastic IP'),
      '#options' => $this->getAvailableElasticIps(),
    ];

    $form['network_interface_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Private Ip'),
      '#options' => $this->getAvailablePrivateIps(),
      '#description' => $this->t('The private IP address to which to associate the Elastic IP address. Only private IP addresses that do not already have an Elastic IP associated with them are available.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->awsEc2Service->setCloudContext($this->entity->getCloudContext());
    $allocation_id = $form_state->getValue('allocation_id');
    $network_interface_id = $form_state->getValue('network_interface_id');

    $result = $this->awsEc2Service->associateAddress([
      'AllocationId' => $allocation_id,
      'NetworkInterfaceId' => $network_interface_id,
    ]);

    if ($result != NULL) {
      $this->awsEc2Service->updateElasticIp();
      $this->awsEc2Service->updateInstances();
      $this->awsEc2Service->updateNetworkInterfaces();

      $elastic_ip = $this->getElasticIp($allocation_id);
      if ($elastic_ip != FALSE) {
        $message = $this->t('Elastic IP @ip_address associated with @private_ip for instance: @instance', [
          '@ip_address' => $elastic_ip->getPublicIp(),
          '@instance' => $this->entity->getName(),
          '@private_ip' => $elastic_ip->getPrivateIpAddress(),
        ]);
        $this->messenger->addMessage($message);
        $this->clearCacheValues();
      }
    }
    else {
      $this->messenger->addError($this->t('Unable to associate elastic ip'));
    }
    $form_state->setRedirect('entity.aws_cloud_instance.canonical', [
      'cloud_context' => $this->entity->getCloudContext(),
      'aws_cloud_instance' => $this->entity->id(),
    ]);
  }

  /**
   * Helper function that gets the available private ip addresses.
   *
   * Used as the #options array in a select field.
   *
   * @return array
   *   An array of private ip addresses.
   */
  private function getAvailablePrivateIps() {
    $private_ips = [];
    $results = $this->entityTypeManager
      ->getStorage('aws_cloud_network_interface')
      ->getQuery()
      ->condition('instance_id', $this->entity->getInstanceId())
      ->notExists('association_id')
      ->execute();
    foreach ($results as $result) {
      $network_interface = NetworkInterface::load($result);
      $private_ips[$network_interface->getNetworkInterfaceId()] = $network_interface->getPrimaryPrivateIp();
    }
    return $private_ips;
  }

  /**
   * Helper function that gets the available elastic ip addresses.
   *
   * Used as the #options array in a select field.
   *
   * @return array
   *   An array of elastic ip addresses.
   */
  private function getAvailableElasticIps() {
    $elastic_ips = [];
    $results = $this->entityTypeManager
      ->getStorage('aws_cloud_elastic_ip')
      ->getQuery()
      ->condition('cloud_context', $this->entity->getCloudContext())
      ->notExists('association_id')
      ->execute();

    foreach ($results as $result) {
      /* @var \Drupal\aws_cloud\Entity\Ec2\ElasticIp $elastic_ip */
      $elastic_ip = ElasticIp::load($result);
      $elastic_ips[$elastic_ip->getAllocationId()] = $elastic_ip->getPublicIp();
    }
    return $elastic_ips;
  }

  /**
   * Helper function that loads an aws_cloud_elastic_ip entity.
   *
   * @param string $allocation_id
   *   The allocation id to look up.
   *
   * @return \Drupal\aws_cloud\Entity\Ec2\ElasticIp
   *   The loaded aws_cloud_elastic_ip entity.
   */
  private function getElasticIp($allocation_id) {
    $elastic_ip = FALSE;
    $results = $this->entityTypeManager
      ->getStorage('aws_cloud_elastic_ip')
      ->loadByProperties([
        'allocation_id' => $allocation_id,
      ]);
    if (count($results) == 1) {
      $elastic_ip = array_shift($results);
    }
    return $elastic_ip;
  }

}
