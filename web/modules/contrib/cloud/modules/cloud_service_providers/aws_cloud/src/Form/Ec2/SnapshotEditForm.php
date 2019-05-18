<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the CloudScripting entity edit forms.
 *
 * @ingroup aws_cloud
 */
class SnapshotEditForm extends AwsCloudContentForm {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state, $cloud_context = '') {
    /* @var $entity \Drupal\aws_cloud\Entity\Ec2\Snapshot */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    $weight = -50;

    $form['snapshot'] = [
      '#type' => 'details',
      '#title' => $this->t('Snapshot'),
      '#open' => TRUE,
      '#weight' => $weight++,
    ];

    $form['snapshot']['name'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Name'),
      '#maxlength'     => 255,
      '#size'          => 60,
      '#default_value' => $entity->label(),
      '#required'      => TRUE,
    ];

    $form['snapshot']['description'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Description'),
      '#maxlength'     => 255,
      '#cols'          => 60,
      '#rows'          => 3,
      '#default_value' => $entity->getDescription(),
      '#required'      => FALSE,
    ];

    $form['snapshot']['snapshot_id'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Snapshot ID')),
      '#markup'        => $entity->getSnapshotId(),
    ];

    $form['snapshot']['volume_id'] = $this->entityLinkRenderer->renderFormElements(
      $entity->getVolumeId(),
      'aws_cloud_volume',
      'volume_id',
      ['#title' => $this->getItemTitle($this->t('Volume ID'))]
    );

    $form['snapshot']['size'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Size (GB)')),
      '#markup'        => $entity->getSize(),
    ];

    $form['snapshot']['status'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Status')),
      '#markup'        => $entity->getStatus(),
    ];

    $form['snapshot']['progress'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Progress')),
      '#markup'        => $entity->getProgress(),
    ];

    $form['snapshot']['encrypted'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Encrypted'),
      '#default_value' => FALSE,
      '#required'      => FALSE,
    ];

    $form['snapshot']['created'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Created')),
      '#markup'        => format_date($entity->created(), 'short'),
    ];

    $this->addOthersFieldset($form, $weight++);

    $form['actions'] = $this->actions($form, $form_state, $cloud_context);
    $form['actions']['#weight'] = $weight++;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    parent::save($form, $form_state);

    $this->setUidInAws(
      $this->entity->getSnapshotId(),
      'snapshot_created_by_uid',
      $this->entity->getOwner()->id()
    );

  }

}
