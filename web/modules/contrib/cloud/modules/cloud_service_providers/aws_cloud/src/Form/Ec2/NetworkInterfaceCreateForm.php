<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;

/**
 * Form controller for the NetworkInterface entity create form.
 *
 * @ingroup aws_cloud
 */
class NetworkInterfaceCreateForm extends AwsCloudContentForm {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state, $cloud_context = '') {
    /* @var $entity \Drupal\aws_cloud\Entity\Ec2\NetworkInterface */
    $form = parent::buildForm($form, $form_state);

    $this->awsEc2Service->setCloudContext($cloud_context);

    $entity = $this->entity;

    $form['cloud_context'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Cloud ID'),
      '#maxlength'     => 255,
      '#size'          => 60,
      '#default_value' => !$entity->isNew()
      ? $entity->getCloudContext()
      : $cloud_context,
      '#required'      => TRUE,
      '#weight'        => -5,
      '#disabled'      => TRUE,
    ];

    $form['name'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Name'),
      '#maxlength'     => 255,
      '#size'          => 60,
      '#default_value' => $entity->label(),
      '#required'      => TRUE,
      '#weight'        => -5,
    ];

    $form['description'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Description'),
      '#cols'          => 60,
      '#rows'          => 3,
      '#default_value' => $entity->getDescription(),
      '#weight'        => -5,
      '#required'      => FALSE,
    ];

    $form['subnet_id'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Subnet'),
      '#size'          => 60,
      '#default_value' => $entity->getSubnetId(),
      '#weight'        => -5,
      '#required'      => TRUE,
    ];

    $form['primary_private_ip'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Primary Private IP'),
      '#size'          => 60,
      '#default_value' => $entity->getPrimaryPrivateIp(),
      '#weight'        => -5,
      '#required'      => TRUE,
    ];

    $form['secondary_private_ips'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Secondary Private IPs (Comma (,) separated.)'),
      '#size'          => 60,
      '#default_value' => $entity->getSecondaryPrivateIps(),
      '#weight'        => -5,
      '#required'      => TRUE,
    ];

    $form['is_primary'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Primary or Not'),
      '#size'          => 60,
      '#default_value' => $entity->getPrimary(),
      '#weight'        => -5,
    ];

    $form['security_groups'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Security Groups'),
      '#size'          => 60,
      '#default_value' => $entity->getSecurityGroups(),
      '#weight'        => -5,
      '#required'      => TRUE,
    ];

    $form['langcode'] = [
      '#title' => t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getUntranslated()->language()->getId(),
      '#languages' => Language::STATE_ALL,
    ];

    $form['actions'] = $this->actions($form, $form_state, $cloud_context);

    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->trimTextfields($form, $form_state);

    $entity = $this->entity;

    $result = $this->awsEc2Service->createNetworkInterface([
      'SubnetId'                       => $entity->getSubnetId(),
      // PrivateIpAddresses is an array and required.
      'PrivateIpAddress'               => $entity->getPrimaryPrivateIp(),
      // Groups is an array.
      'Groups'                         => [$entity->getSecurityGroups()],
      // PrivateIpAddresses is an array and PrivateIpAddress is required.
      'PrivateIpAddresses'             => [
        [
          // REQUIRED.
          'PrivateIpAddress' => $entity->getSecondaryPrivateIps(),
          // TRUE or FALSE.
          'Primary'          => $entity->getPrimary() ? TRUE : FALSE,
        ],
      ],
      'SecondaryPrivateIpAddressCount' => count(explode(',', $entity->getSecondaryPrivateIps())),
      'Description'                    => $entity->getDescription(),
    ]);

    if (isset($result['NetworkInterfaceId'])
    && ($entity->setNetworkInterfaceId($result['NetworkInterfaceId']))
    && ($entity->setStatus($result['Status']))
    && ($entity->setVpcId($result['VpcId']))
    && ($entity->save())) {

      $message = $this->t('The @type "@label (@network_interface_id)" has been created.', [
        '@type'                 => $entity->getEntityType()->getLabel(),
        '@label'                => $entity->label(),
        '@network_interface_id' => $result['NetworkInterfaceId'],
      ]);
      $this->messenger->addMessage($message);
      $form_state->setRedirect('view.aws_network_interfaces.page_1', ['cloud_context' => $entity->getCloudContext()]);
    }
    else {
      $message = $this->t('The @type "@label" couldn\'t create.', [
        '@type' => $entity->getEntityType()->getLabel(),
        '@label' => $entity->label(),
      ]);
      $this->messenger->addError($message);
    }
  }

}
