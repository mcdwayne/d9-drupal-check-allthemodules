<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\Core\Form\FormStateInterface;
use Drupal\cloud\Service\Util\EntityLinkWithNameHtmlGenerator;

/**
 * Form controller for the CloudScripting entity edit forms.
 *
 * @ingroup aws_cloud
 */
class VolumeEditForm extends AwsCloudContentForm {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state, $cloud_context = '') {
    /* @var $entity \Drupal\aws_cloud\Entity\Ec2\Volume */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    $weight = -50;

    $form['volume'] = [
      '#type' => 'details',
      '#title' => $this->t('Volume'),
      '#open' => TRUE,
      '#weight' => $weight++,
    ];

    $form['volume']['name'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Name'),
      '#maxlength'     => 255,
      '#size'          => 60,
      '#default_value' => $entity->label(),
      '#required'      => TRUE,
    ];

    $form['volume']['attachment_information'] = $this->entityLinkRenderer->renderFormElements(
      $entity->getAttachmentInformation(),
      'aws_cloud_instance',
      'instance_id',
      ['#title' => $this->getItemTitle($this->t('Instance ID'))],
      '',
      EntityLinkWithNameHtmlGenerator::class
    );

    $form['volume']['volume_id'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Volume ID')),
      '#markup'        => $entity->getVolumeId(),
    ];

    $form['volume']['snapshot_id'] = $this->entityLinkRenderer->renderFormElements(
      $entity->getSnapshotId(),
      'aws_cloud_snapshot',
      'snapshot_id',
      ['#title' => $this->getItemTitle($this->t('Snapshot ID'))]
    );

    $form['volume']['snapshot_name'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Snapshot Name')),
      '#markup'        => $entity->getSnapshotName(),
    ];

    $form['volume']['size'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Size (GB)')),
      '#markup'        => $entity->getSize(),
    ];

    $form['volume']['availability_zone'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Availability Zone')),
      '#markup'        => $entity->getAvailabilityZone(),
    ];

    $form['volume']['volume_type'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Volume Type')),
      '#markup'        => $entity->getVolumeType(),
    ];

    $form['volume']['iops'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('IOPS')),
      '#markup'        => $entity->getIops(),
    ];

    $form['volume']['encrypted'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Encrypted')),
      '#markup'        => $entity->getEncrypted() == 0 ? 'Off' : 'On',
    ];

    $form['volume']['state'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Status')),
      '#markup'        => $entity->getState(),
    ];

    $form['volume']['created'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Created')),
      '#markup'        => format_date($entity->created(), 'short'),
    ];

    $this->addOthersFieldset($form, $weight++);

    $form['actions'] = $this->actions($form, $form_state, $cloud_context);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    // Store the drupal uid in Aws.
    $this->setUidInAws(
      $this->entity->getVolumeId(),
      'volume_created_by_uid',
      $this->entity->getOwner()->id()
    );

  }

}
