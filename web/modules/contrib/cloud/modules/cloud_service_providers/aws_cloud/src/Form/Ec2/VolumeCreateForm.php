<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;

/**
 * Form controller for the Volume entity create form.
 *
 * @ingroup aws_cloud
 */
class VolumeCreateForm extends AwsCloudContentForm {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state, $cloud_context = '') {

    $this->awsEc2Service->setCloudContext($cloud_context);

    /* @var $entity \Drupal\aws_cloud\Entity\Ec2\Volume */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    // Use the value of parameter snapshot_id as the default value.
    $snapshot_id = $this->getRequest()->query->get('snapshot_id');
    $snapshot = NULL;
    if (!empty($snapshot_id)) {
      $snapshots = $this->entityTypeManager
        ->getStorage('aws_cloud_snapshot')
        ->loadByProperties([
          'cloud_context' => $cloud_context,
          'snapshot_id' => $snapshot_id,
        ]);

      if (!empty($snapshots)) {
        $snapshot = reset($snapshots);
      }
    }

    $form['cloud_context'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Cloud ID'),
      '#maxlength'     => 255,
      '#size'          => 60,
      '#default_value' => !$entity->isNew() ? $entity->getCloudContext() : $cloud_context,
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

    $form['snapshot_id'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Snapshot ID'),
      '#options'       => $this->getSnapshotOptions($cloud_context),
      '#default_value' => $snapshot_id,
      '#weight'        => -5,
      '#required'      => FALSE,
      '#empty_value'   => '',
    ];

    $form['volume_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Volume Type'),
      '#options' => [
        'standard' => $this->t('Magnetic (standard)'),
        'io1' => $this->t('Provisioned IOPS SSD (io1)'),
        'gp2' => $this->t('General Purpose SSD (gp2)'),
        'sc1' => $this->t('Cold HDD (sc1)'),
        'st1' => $this->t('Throughput Optimized HDD (st1)'),
      ],
      '#weight' => -5,
    ];

    $form['size'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Size (GiB)'),
      '#size'          => 60,
      '#default_value' => $snapshot ? $snapshot->getSize() : '',
      '#weight'        => -5,
      '#required'      => FALSE,
    ];

    $form['iops'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('IOPS'),
      '#size'          => 60,
      '#default_value' => $entity->getIops(),
      '#weight'        => -5,
      '#required'      => FALSE,
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

    $form['encrypted'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Encrypted'),
      '#size'          => 60,
      '#default_value' => $entity->getEncrypted(),
      '#weight'        => -5,
      '#required'      => FALSE,

    ];
    $form['kms_key_id'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('KMS Key ID'),
      '#size'          => 60,
      '#default_value' => $entity->getKmsKeyId(),
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
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Add validation for volume types.  for io1 type,
    // iops must be set.  For other volume types, iops cannot
    // be set.
    if ($form_state->getValue('volume_type') == 'io1') {
      // Check if there is an iops value.
      if (empty($form_state->getValue('iops'))) {
        $form_state->setErrorByName('iops', $this->t('Please specify an iops value.  The value must be a minimum of 100.'));
      }

      // Check if iops is an integer.
      if (!is_numeric($form_state->getValue('iops'))) {
        $form_state->setErrorByName('iops', $this->t('IOPS must be an integer.'));
      }
      // Check if iops is greater than 100.
      $iops = (int) $form_state->getValue('iops');
      if ($iops < 100) {
        $form_state->setErrorByName('iops', $this->t('IOPS must be a minimum of 100.'));
      }
    }
    else {
      if (!empty($form_state->getValue('iops'))) {
        $form_state->setErrorByName('iops', $this->t('IOPS cannot be set unless volume type is "Provisioned IOPS SSD".'));
      }
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::save().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->trimTextfields($form, $form_state);

    $entity = $this->entity;

    $params = [
      'Size'             => $entity->getSize(),
      'SnapshotId'       => $entity->getSnapshotId(),
      'AvailabilityZone' => $entity->getAvailabilityZone(),
      'VolumeType'       => $entity->getVolumeType(),
      'Encrypted'        => $entity->getEncrypted() ? TRUE : FALSE,
    ];

    if ($entity->getVolumeType() == 'io1') {
      $params['Iops'] = (int) $entity->getIops();
    }

    if (!empty($entity->getKmsKeyId())) {
      $params['KmsKeyId'] = $entity->getKmsKeyId();
    }

    $result = $this->awsEc2Service->createVolume($params);

    if (isset($result['VolumeId'])
      && ($entity->setVolumeId($result['VolumeId']))
      && ($entity->setCreated($result['CreateTime']))
      && ($entity->setState($result['State']))
      && ($entity->setSnapshotName($this->getSnapshotName($entity->getSnapshotId())))
      && ($entity->save())) {

      $message = $this->t('The @label "%label (@volume_id)" has been created.', [
        '@label'     => $entity->getEntityType()->getLabel(),
        '%label'     => $entity->label(),
        '@volume_id' => $entity->getVolumeId(),
      ]);

      // Store the drupal uid in Aws.
      $this->setUidInAws(
        $this->entity->getVolumeId(),
        'volume_created_by_uid',
        $this->entity->getOwner()->id()
      );

      $this->messenger->addMessage($message);
      $form_state->setRedirect('view.aws_volume.page_1', ['cloud_context' => $entity->getCloudContext()]);
    }
    else {
      $message = $this->t('The @label "%label" couldn\'t create.', [
        '@label' => $entity->getEntityType()->getLabel(),
        '%label' => $entity->label(),
      ]);
      $this->messenger->addError($message);
    }

  }

  /**
   * Get Snapshot Name.
   *
   * @param string $snapshot_id
   *   Snapshot ID.
   *
   * @return string
   *   Snapshot Name.
   */
  private function getSnapshotName($snapshot_id) {
    $snapshot_name = '';

    $result = $this->awsEc2Service->describeSnapshots(['SnapshotIds' => [$snapshot_id]]);
    if (isset($result['Snapshots'][0])) {
      $snapshot = $result['Snapshots'][0];
      foreach ($snapshot['Tags'] as $tag) {
        if ($tag['Key'] == 'Name') {
          $snapshot_name = $tag['Value'];
          break;
        }
      }
    }

    return $snapshot_name;
  }

  /**
   * Helper function to get snapshot options.
   *
   * @param string $cloud_context
   *   Cloud context to use in the query.
   *
   * @return array
   *   Snapshot options.
   */
  private function getSnapshotOptions($cloud_context) {
    $options = [];
    $params = [
      'cloud_context' => $cloud_context,
    ];
    if (!$this->currentUser->hasPermission('view any aws cloud snapshot')) {
      $params['uid'] = $this->currentUser->id();
    }

    $snapshots = $this->entityTypeManager
      ->getStorage('aws_cloud_snapshot')
      ->loadByProperties($params);
    foreach ($snapshots as $snapshot) {
      if ($snapshot->getName() != $snapshot->getSnapshotId()) {
        $options[$snapshot->getSnapshotId()] = "{$snapshot->getName()} ({$snapshot->getSnapshotId()})";
      }
      else {
        $options[$snapshot->getSnapshotId()] = $snapshot->getSnapshotId();
      }
    }
    return $options;
  }

}
