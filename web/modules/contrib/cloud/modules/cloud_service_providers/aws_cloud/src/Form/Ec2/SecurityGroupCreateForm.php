<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;

/**
 * Form controller for the SecurityGroup entity create form.
 *
 * @ingroup aws_cloud
 */
class SecurityGroupCreateForm extends AwsCloudContentForm {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state, $cloud_context = '') {

    $this->awsEc2Service->setCloudContext($cloud_context);

    /* @var $entity \Drupal\aws_cloud\Entity\Ec2\SecurityGroup */
    $form = parent::buildForm($form, $form_state);

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

    $form['group_name'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Security Group Name'),
      '#size'          => 60,
      '#default_value' => $entity->getGroupName(),
      '#weight'        => -5,
      '#required'      => TRUE,
    ];

    $vpcs = $this->awsEc2Service->getVpcs();
    $vpcs[$entity->getVpcId()] = 'N/A';
    ksort($vpcs);
    $form['vpc_id'] = [
      '#type'          => 'select',
      '#title'         => $this->t('VPC CIDR (ID)'),
      '#options'       => $vpcs,
      '#default_value' => $entity->getVpcId(),
      '#weight'        => -5,
      '#required'      => FALSE,
    ];

    $form['description'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Description'),
      '#cols'          => 60,
      '#rows'          => 3,
      '#default_value' => $entity->getDescription(),
      '#weight'        => -5,
      '#required'      => TRUE,
    ];

    $form['langcode'] = [
      '#title' => t('Language'),
      '#type' => 'language_select',
      '#default_value' => $entity->getUntranslated()->language()->getId(),
      '#languages' => Language::STATE_ALL,
    ];

    // Unset these until and present them on the edit security group form.
    unset($form['ip_permission']);
    unset($form['outbound_permission']);

    if (isset($form['actions'])) {
      $form['actions']['submit']['#weight'] = 1;
    }
    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->trimTextfields($form, $form_state);

    $entity = $this->entity;

    $result = $this->awsEc2Service->createSecurityGroup([
      'GroupName'   => $entity->getGroupName(),
      'VpcId'       => $entity->getVpcId(),
      'Description' => $entity->getDescription(),
    ]);

    if (isset($result['GroupId'])
    && ($entity->setGroupId($result['GroupId']))
    && ($entity->set('name', $entity->getGroupName()))
    && ($entity->save())) {

      $message = $this->t('The @label "@group_name" has been created.  Please setup the ip permissions', [
        '@label'      => $entity->getEntityType()->getLabel(),
        '@group_name' => $entity->getGroupName(),
      ]);

      $form_state->setRedirect('entity.aws_cloud_security_group.edit_form', [
        'cloud_context' => $entity->getCloudContext(),
        'aws_cloud_security_group' => $entity->id(),
      ]);
      $this->messenger->addMessage($message);
    }
    else {
      $message = $this->t('The @label "@group_name" couldn\'t create.', [
        '@label'      => $entity->getEntityType()->getLabel(),
        '@group_name' => $entity->getGroupName(),
      ]);
      $this->messenger->addError($message);
    }
  }

}
