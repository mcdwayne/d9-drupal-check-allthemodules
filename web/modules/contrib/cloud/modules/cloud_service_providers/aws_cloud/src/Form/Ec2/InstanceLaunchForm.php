<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\aws_cloud\Entity\Config\Config;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;

/**
 * Form controller for the Instance entity launch form.
 *
 * @TODO: Remove this form.  This is not in use anymore.
 * Use the cloud server templates to launch instances.
 *
 * @ingroup aws_cloud
 */
class InstanceLaunchForm extends AwsCloudContentForm {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::buildForm().
   *
   * @param array $form
   *   Array of form object.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param string $cloud_context
   *   A cloud_context string value from URL "path".
   *
   * @return array
   *   Array of form object.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $cloud_context = '') {

    $cloudContext = Config::load($cloud_context);

    if (isset($cloudContext)) {
      $cloud_type = $cloudContext->getCloudType();
      $this->awsEc2Service->setCloudContext($cloudContext->getCloudContext());
    }
    else {
      $this->messenger->addError($this->t("Not found: AWS Cloud provider '@cloud_context'", [
        '@cloud_context'  => $cloud_context,
      ]));
    }

    /* @var $entity \Drupal\aws_cloud\Entity\Ec2\Instance */
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
      '#attributes'    => ['readonly' => 'readonly'],
      '#disabled'      => TRUE,

    ];

    $form['cloud_type'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Cloud Type'),
      '#size'          => 60,
      '#default_value' => $cloud_type,
      '#weight'        => -5,
      '#required'      => TRUE,
      '#attributes'    => ['readonly' => 'readonly'],
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

    $form['image_id'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('EC2 Image'),
      '#size'          => 60,
      '#default_value' => $entity->getImageId(),
      '#weight'        => -5,
      '#required'      => TRUE,
    ];

    $form['min_count'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Min Count'),
      '#maxlength'     => 3,
      '#size'          => 60,
      '#default_value' => 1,
      '#weight'        => -5,
      '#required'      => TRUE,
    ];

    $form['max_count'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Max Count'),
      '#maxlength'     => 3,
      '#size'          => 60,
      '#default_value' => 1,
      '#weight'        => -5,
      '#required'      => TRUE,
    ];

    $form['key_pair_name'] = [
      '#type'          => 'entity_autocomplete',
      '#target_type'   => 'aws_cloud_key_pair',
      '#title'         => $this->t('Key Pair Name'),
      '#size'          => 60,
      '#default_value' => $entity->getKeyPairName(),
      '#weight'        => -5,
      '#required'      => TRUE,
    ];

    $form['is_monitoring'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Monitoring Enabled'),
      '#options'       => [0 => t('No'), 1 => t('Yes')],
      '#default_value' => 0,
      '#weight'        => -5,
      '#required'      => TRUE,
    ];

    $availability_zones = $this->awsEc2Service->getAvailabilityZones();
    $form['availability_zone'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Availability Zone'),
      '#options'       => $availability_zones,
      // Pick up the first availability zone in the array.
      '#default_value' => array_shift($availability_zones),
      '#weight'        => -5,
      '#required'      => TRUE,
    ];

    $form['security_groups'] = [
      '#type'          => 'entity_autocomplete',
      '#target_type'   => 'aws_cloud_security_group',
      '#title'         => $this->t('Security Groups'),
      '#size'          => 60,
      '#default_value' => $entity->getSecurityGroups(),
      '#weight'        => -5,
      '#required'      => FALSE,
    ];

    $form['instance_type'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Instance Type'),
      '#size'          => 60,
      '#default_value' => $entity->getInstanceType(),
      '#weight'        => -5,
      '#required'      => FALSE,
    ];

    $form['kernel_id'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Kernel Image'),
      '#size'          => 60,
      '#default_value' => $entity->getKernelId(),
      '#weight'        => -5,
      '#required'      => FALSE,
    ];

    $form['ramdisk_id'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Ramdisk Image'),
      '#size'          => 60,
      '#default_value' => $entity->getRamdiskId(),
      '#weight'        => -5,
      '#required'      => FALSE,
    ];

    $form['user_data'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('User Data'),
      '#size'          => 60,
      '#default_value' => $entity->getUserData(),
      '#weight'        => -5,
      '#required'      => FALSE,
    ];

    $form['login_username'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Login Username'),
      '#size'          => 60,
      '#default_value' => $entity->getLoginUsername() ?: 'ec2-user',
      '#weight'        => -5,
      '#required'      => FALSE,
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

    $entity = $this->entity;
    $entity->setParams($form);
    $result = $this->launchInstance($entity);

    if (isset($result['Instances'][0]['InstanceId'])
    && ($entity->setInstanceId($result['Instances'][0]['InstanceId']))
    && ($entity->setPublicIp($result['Instances'][0]['PublicIpAddress']))
    && ($entity->setKeyPairName($result['Instances'][0]['KeyName']))
    && ($entity->setInstanceState($result['Instances'][0]['State']['Name']))
    && ($entity->setCreated($result['Instances'][0]['LaunchTime']))
    && ($entity->save())) {

      $instance_ids = [];
      foreach ($result['Instances'] as $instance) {
        $instance_ids[] = $instance['InstanceId'];
      }

      $message = $this->t('The @type "@label (@instance_id)" request has been initiated. This may take some time. Use Refresh to update the status.', [
        '@type'        => $entity->getEntityType()->getLabel(),
        '@label'       => $entity->label(),
        '@instance_id' => implode(', ', $instance_ids),
      ]);

      $this->messenger->addMessage($message);
      $form_state->setRedirectUrl($entity
        ->urlInfo('collection')
        ->setRouteParameter('cloud_context', $entity->getCloudContext()));
    }
    else {
      $message = $this->t('The @type "@label" failed to launch.', [
        '@type'  => $entity->getEntityType()->getLabel(),
        '@label' => $entity->label(),
      ]);

      $this->messenger->addError($message);
    }

  }

  /**
   * Helper method to launch instance.
   *
   * @param \Drupal\aws_cloud\Form\Ec2\Instance $instance
   *   Instance object.
   *
   * @return array
   *   Results array.
   */
  private function launchInstance(Instance $instance) {
    $key_name       = preg_replace('/ \([^\)]*\)$/', '', $instance->getKeyPairName());
    $security_group = preg_replace('/ \([^\)]*\)$/', '', $instance->getSecurityGroups());

    $params = [
      // The following parameters are required.
      'ImageId'        => $instance->getImageId(),
      'MaxCount'       => $instance->getMaxCount(),
      'MinCount'       => $instance->getMinCount(),
      'InstanceType'   => $instance->getInstanceType(),
      'Monitoring'     => ['Enabled' => $instance->isMonitoring() ? TRUE : FALSE],
      'KeyName'        => $key_name,
      'Placement'      => ['AvailabilityZone' => $instance->getAvailabilityZone()],
      'SecurityGroups' => [$security_group],
    ];

    // The following parameters are optional.
    $params['KernelId'] ?: $instance->getKernelId();
    $params['RamdiskId'] ?: $instance->getRamdiskId();
    $params['UserData'] ?: $instance->getUserData();
    return $this->awsEc2Service->runInstances($params);
  }

}
