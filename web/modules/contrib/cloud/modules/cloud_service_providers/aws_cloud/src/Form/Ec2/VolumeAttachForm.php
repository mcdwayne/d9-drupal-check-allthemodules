<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\Core\Form\FormStateInterface;

/**
 * Volume Attach form.
 */
class VolumeAttachForm extends AwsDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $entity = $this->entity;

    return $this->t('Are you sure you want to attach volume: %name?', [
      '%name' => $entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Attach');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $entity = $this->entity;
    return $this->t('<h2>Volume Information:</h2><ul><li>Volume id: %id</li><li>Volume name: %name</li></ul>', [
      '%id' => $entity->getVolumeId(),
      '%name' => $entity->getName(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $instances = [];
    $results = $this->getInstances($this->entity->getAvailabilityZone());

    foreach ($results as $result) {
      /* @var \Drupal\aws_cloud\Entity\Ec2\Instance $result */
      $instances[$result->getInstanceId()] = $this->t('%name - %instance_id', [
        '%name' => $result->getName(),
        '%instance_id' => $result->getInstanceId(),
      ]);
    }
    if (count($results) > 0) {
      $form['device_name'] = [
        '#title' => $this->t('Device Name'),
        '#type' => 'textfield',
        '#description' => $this->t('The device name (for example, /dev/sdh or xvdh).'),
        '#required' => TRUE,
      ];

      $form['instance_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Instance Id'),
        '#options' => $instances,
      ];
    }
    else {
      $form['message'] = [
        '#markup' => '<h1>' . $this->t('No instances available in the availability zone: %zone.  Volume cannot be attached.', ['%zone' => $this->entity->getAvailabilityZone()]) . '</h1>',
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $results = $this->getInstances($this->entity->getAvailabilityZone());
    if (count($results) == 0) {
      unset($actions['submit']);
    }
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /* @var \Drupal\aws_cloud\Entity\Ec2\Volume $entity */
    $entity = $this->entity;

    $instance_id = $form_state->getValue('instance_id');
    $volume_id = $entity->getVolumeId();
    $device_name = $form_state->getValue('device_name');

    $this->awsEc2Service->setCloudContext($this->entity->getCloudContext());
    $result = $this->awsEc2Service->attachVolume([
      'InstanceId' => $instance_id,
      'VolumeId' => $volume_id,
      'Device' => $device_name,
    ]);

    if ($result != NULL) {
      // Set the instance_id in the volume entity and save.
      $entity->setAttachmentInformation($instance_id);
      $entity->setState($result['State']);
      $entity->save();

      $this->messenger->addMessage($this->t('Volume %volume is attaching to %instance', ['%volume' => $volume_id, '%instance' => $instance_id]));
      $form_state->setRedirect('view.aws_volume.page_1', ['cloud_context' => $this->entity->getCloudContext()]);
    }
  }

  /**
   * Query DB for aws_cloud_instances that are in the same zone as the volume.
   *
   * This method respects instance visibility.
   *
   * @param string $zone
   *   The Availability Zone String.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The Instance Entity.
   */
  private function getInstances($zone) {
    $account = \Drupal::currentUser();
    $properties = [
      'availability_zone' => $zone,
    ];

    if (!$account->hasPermission('view any aws cloud instance')) {
      $properties['uid'] = $account->id();
    }

    return $this->entityTypeManager->getStorage('aws_cloud_instance')->loadByProperties($properties);
  }

}
