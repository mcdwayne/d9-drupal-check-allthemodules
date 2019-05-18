<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\Core\Form\FormStateInterface;
use Drupal\cloud\Service\Util\EntityLinkWithNameHtmlGenerator;

/**
 * Form controller for the ElasticIp entity edit forms.
 *
 * @ingroup aws_cloud
 */
class ElasticIpEditForm extends AwsCloudContentForm {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state, $cloud_context = '') {
    /* @var $entity \Drupal\aws_cloud\Entity\Ec2\ElasticIp */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    $weight = -50;

    $form['ip_address'] = [
      '#type' => 'details',
      '#title' => $this->t('IP Address'),
      '#open' => TRUE,
      '#weight' => $weight++,
    ];

    $form['ip_address']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#maxlength' => 255,
      '#size' => 60,
      '#default_value' => $entity->label(),
    ];

    $form['ip_address']['public_ip'] = [
      '#type' => 'item',
      '#title' => $this->getItemTitle($this->t('Elastic IP')),
      '#markup' => $entity->getPublicIp(),
    ];

    $form['ip_address']['private_ip_address'] = [
      '#type' => 'item',
      '#title' => $this->getItemTitle($this->t('Private IP Address')),
      '#markup' => $entity->getPrivateIpAddress(),
    ];

    $form['assign'] = [
      '#type' => 'details',
      '#title' => $this->t('Assign'),
      '#open' => TRUE,
      '#weight' => $weight++,
    ];

    $form['assign']['instance_id'] = $this->entityLinkRenderer->renderFormElements(
      $entity->getInstanceId(),
      'aws_cloud_instance',
      'instance_id',
      ['#title' => $this->getItemTitle($this->t('Instance ID'))],
      '',
      EntityLinkWithNameHtmlGenerator::class
    );

    $form['assign']['network_interface_id'] = [
      '#type' => 'item',
      '#title' => $this->getItemTitle($this->t('Network Interface ID')),
      '#markup' => $entity->getNetworkInterfaceId(),
    ];

    $form['assign']['allocation_id'] = [
      '#type' => 'item',
      '#title' => $this->getItemTitle($this->t('Allocation ID')),
      '#markup' => $entity->getAllocationId(),
    ];

    $form['assign']['association_id'] = [
      '#type' => 'item',
      '#title' => $this->getItemTitle($this->t('Association ID')),
      '#markup' => $entity->getAssociationId(),
    ];

    $form['assign']['domain'] = [
      '#type' => 'item',
      '#title' => $this->getItemTitle($this->t('Domain (standard | vpc)')),
      '#markup' => $entity->getDomain(),
    ];

    $form['assign']['network_interface_owner'] = [
      '#type' => 'item',
      '#title' => $this->getItemTitle($this->t('Network Interface Owner')),
      '#markup' => $entity->getNetworkInterfaceOwner(),
    ];

    $this->addOthersFieldset($form, $weight++);

    $form['actions'] = $this->actions($form, $form_state, $cloud_context);
    $association_id = $this->entity->getAssociationId();
    if (isset($association_id)) {
      // Unset the delete button because the ip is allocated.
      unset($form['actions']['delete']);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);
    $this->awsEc2Service->setCloudContext($this->entity->getCloudContext());

    // Update the Name tag.
    $this->awsEc2Service->createTags([
      'Resources' => [$this->entity->getAllocationId()],
      'Tags' => [
        [
          'Key' => 'Name',
          'Value' => $this->entity->getName(),
        ],
      ],
    ]);
  }

}
