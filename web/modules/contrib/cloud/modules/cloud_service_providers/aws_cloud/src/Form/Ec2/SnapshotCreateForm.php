<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;

/**
 * Form controller for the Snapshot entity create form.
 *
 * @ingroup aws_cloud
 */
class SnapshotCreateForm extends AwsCloudContentForm {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state, $cloud_context = '') {
    /* @var $entity \Drupal\aws_cloud\Entity\Ec2\Snapshot */
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
      '#required'      => FALSE,
      '#weight'        => -5,
    ];

    $volume_id = $this->getRequest()->query->get('volume_id');

    $form['volume_id'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Volume ID'),
      '#options'       => $this->getVolumeOptions($cloud_context),
      '#default_value' => $volume_id,
      '#weight'        => -5,
      '#required'      => TRUE,
    ];

    $form['description'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Description'),
      '#maxlength'     => 255,
      '#cols'          => 60,
      '#rows'          => 3,
      '#default_value' => $entity->getDescription(),
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
    $this->trimTextfields($form, $form_state);

    $entity = $this->entity;

    $result = $this->awsEc2Service->createSnapshot([
      'VolumeId'    => $entity->getVolumeId(),
      'Description' => $entity->getDescription(),
    ]);

    if (isset($result['SnapshotId'])
    && ($entity->setSnapshotId($result['SnapshotId']))
    && ($entity->setStatus($result['State']))
    && ($entity->setStarted(strtotime($result['StartTime'])))
    && ($entity->setEncrypted($result['Encrypted']))
    && ($entity->save())) {

      $this->setUidInAws(
        $this->entity->getSnapshotId(),
        'snapshot_created_by_uid',
        $entity->getOwner()->id()
      );

      $message = $this->t('The @label "%label (@snapshot_id)" has been created.', [
        '@label'       => $entity->getEntityType()->getLabel(),
        '%label'       => $entity->label(),
        '@snapshot_id' => $result['SnapshotId'],
      ]);

      $this->messenger->addMessage($message);
      $form_state->setRedirect('view.aws_snapshot.page_1', ['cloud_context' => $entity->getCloudContext()]);
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
   * Helper function to get volume options.
   *
   * @param string $cloud_context
   *   Cloud context to use in the query.
   *
   * @return array
   *   Volume options.
   */
  private function getVolumeOptions($cloud_context) {
    $options = [];
    $params = [
      'cloud_context' => $cloud_context,
    ];
    if (!$this->currentUser->hasPermission('view any aws cloud volume')) {
      $params['uid'] = $this->currentUser->id();
    }

    $volumes = $this->entityTypeManager
      ->getStorage('aws_cloud_volume')
      ->loadByProperties($params);
    foreach ($volumes as $volume) {
      if ($volume->getName() != $volume->getVolumeId()) {
        $options[$volume->getVolumeId()] = "{$volume->getName()} ({$volume->getVolumeId()})";
      }
      else {
        $options[$volume->getVolumeId()] = $volume->getVolumeId();
      }
    }
    return $options;
  }

}
