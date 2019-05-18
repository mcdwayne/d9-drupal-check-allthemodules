<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\Core\Form\FormStateInterface;
use Drupal\aws_cloud\Entity\Ec2\PublicIpEntityLinkHtmlGenerator;

/**
 * Form controller for the CloudScripting entity edit forms.
 *
 * @ingroup aws_cloud
 */
class NetworkInterfaceEditForm extends AwsCloudContentForm {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state, $cloud_context = '') {
    /* @var $entity \Drupal\aws_cloud\Entity\Ec2\NetworkInterface */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    $weight = -50;

    $form['network_interface'] = [
      '#type' => 'details',
      '#title' => $this->t('Network Interface'),
      '#open' => TRUE,
      '#weight' => $weight++,
    ];

    $form['network_interface']['name'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Name'),
      '#maxlength'     => 255,
      '#size'          => 60,
      '#default_value' => $entity->label(),
      '#required'      => TRUE,
    ];

    $form['network_interface']['description'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Description'),
      '#maxlength'     => 255,
      '#cols'          => 60,
      '#rows'          => 3,
      '#default_value' => $entity->getDescription(),
      '#required'      => FALSE,
    ];

    $form['network_interface']['network_interface_id'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Network Interface ID')),
      '#markup'        => $entity->getNetworkInterfaceId(),
    ];

    $form['network_interface']['instance_id'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Instance ID')),
      '#markup'        => $entity->getInstanceId(),
    ];

    $form['network_interface']['allocation_id'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Allocation ID')),
      '#markup'        => $entity->getAllocationId(),
    ];

    $form['network_interface']['mac_address'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Mac Address')),
      '#markup'        => $entity->getMacAddress(),
    ];

    $form['network_interface']['device_index'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Device Index')),
      '#markup'        => $entity->getDeviceIndex(),
    ];

    $form['network_interface']['status'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Status')),
      '#markup'        => $entity->getStatus(),
    ];

    $form['network_interface']['delete_on_termination'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Delete on Termination')),
      '#markup'        => $entity->getDeleteOnTermination(),
    ];

    $form['network'] = [
      '#type' => 'details',
      '#title' => $this->t('Network'),
      '#open' => TRUE,
      '#weight' => $weight++,
    ];

    $form['network']['security_groups'] = $this->entityLinkRenderer->renderFormElements(
      $entity->getSecurityGroups(),
      'aws_cloud_security_group',
      'group_name',
      ['#title' => $this->getItemTitle($this->t('Security Group'))]
    );

    $form['network']['vpc_id'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('VPC ID')),
      '#markup'        => $entity->getVpcId(),
    ];

    $form['network']['subnet_id'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Subnet ID')),
      '#markup'        => $entity->getSubnetId(),
    ];

    $form['network']['public_ips'] = $this->entityLinkRenderer->renderFormElements(
      $entity->getPublicIps(),
      'aws_cloud_elastic_ip',
      'public_ip',
      ['#title' => $this->getItemTitle($this->t('Public IPs'))],
      '',
      PublicIpEntityLinkHtmlGenerator::class
    );

    $form['network']['primary_private_ip'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Primary Private IP')),
      '#markup'        => $entity->getPrimaryPrivateIp(),
    ];

    $form['network']['secondary_private_ips'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Secondary Private IPs')),
      '#markup'        => $entity->getSecondaryPrivateIps(),
    ];

    $form['network']['private_dns'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Private DNS')),
      '#markup'        => $entity->getPrivateDns(),
    ];

    $form['attachment'] = [
      '#type' => 'details',
      '#title' => $this->t('Attachment'),
      '#open' => FALSE,
      '#weight' => $weight++,
    ];

    $form['attachment']['attachment_id'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Attachment ID')),
      '#markup'        => $entity->getAttachmentId(),
    ];

    $form['attachment']['attachment_owner'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Attachment Owner')),
      '#markup'        => $entity->getAttachmentOwner(),
    ];

    $form['attachment']['attachment_status'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Attachment Status')),
      '#markup'        => $entity->getAttachmentStatus(),
    ];

    $form['owner'] = [
      '#type' => 'details',
      '#title' => $this->t('Owner'),
      '#open' => FALSE,
      '#weight' => $weight++,
    ];

    $form['owner']['account_id'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('AWS Account ID')),
      '#markup'        => $entity->getAccountId(),
    ];

    $this->addOthersFieldset($form, $weight++);

    $form['actions'] = $this->actions($form, $form_state, $cloud_context);

    return $form;
  }

}
