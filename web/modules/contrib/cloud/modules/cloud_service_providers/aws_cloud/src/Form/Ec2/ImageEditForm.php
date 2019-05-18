<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the CloudScripting entity edit forms.
 *
 * @ingroup aws_cloud
 */
class ImageEditForm extends AwsCloudContentForm {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state, $cloud_context = '') {
    /* @var $entity \Drupal\aws_cloud\Entity\Ec2\Image */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    $weight = -50;

    $form['image'] = [
      '#type' => 'details',
      '#title' => $this->t('Image'),
      '#open' => TRUE,
      '#weight' => $weight++,
    ];

    $form['image']['name'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Name'),
      '#maxlength'     => 255,
      '#size'          => 60,
      '#default_value' => $entity->getName(),
      '#required'      => TRUE,
    ];

    $form['image']['description'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Description'),
      '#maxlength'     => 255,
      '#size'          => 60,
      '#default_value' => $entity->getDescription(),
      '#required'      => FALSE,
      '#attributes'    => ['readonly' => 'readonly'],
      '#disabled'      => TRUE,
    ];

    $form['image']['ami_name'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('AMI Name')),
      '#markup'        => $entity->getAmiName(),
    ];

    $form['image']['image_id'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Image ID')),
      '#markup'        => $entity->getImageId(),
    ];

    $form['image']['account_id'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Owner')),
      '#markup'        => $entity->getAccountId(),
    ];

    $form['image']['source'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Source')),
      '#markup'        => $entity->getSource(),
    ];

    $form['image']['status'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Status')),
      '#markup'        => $entity->getStatus(),
    ];

    $form['image']['state_reason'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('State Reason')),
      '#markup'        => $entity->getStateReason(),
    ];

    $form['image']['created'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Created')),
      '#markup'        => format_date($entity->created(), 'short'),
    ];

    $form['type'] = [
      '#type' => 'details',
      '#title' => $this->getItemTitle($this->t('Type')),
      '#open' => TRUE,
      '#weight' => $weight++,
    ];

    $form['type']['platform'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Platform')),
      '#markup'        => $entity->getPlatform(),
    ];

    $form['type']['architecture'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Architecture')),
      '#markup'        => $entity->getArchitecture(),
    ];

    $form['type']['virtualization_type'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Virtualization Type')),
      '#markup'        => $entity->getVirtualizationType(),
    ];

    $form['type']['product_code'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Product Code')),
      '#markup'        => $entity->getProductCode(),
    ];

    $form['type']['image_type'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Image Type')),
      '#markup'        => $entity->getImageType(),
    ];

    $form['device'] = [
      '#type' => 'details',
      '#title' => $this->getItemTitle($this->t('Device')),
      '#open' => TRUE,
      '#weight' => $weight++,
    ];

    $form['device']['root_device_name'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Root Device Name')),
      '#markup'        => $entity->getRootDeviceName(),
    ];

    $form['device']['root_device_type'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Root Device Type')),
      '#markup'        => $entity->getRootDeviceType(),
    ];

    $form['device']['kernel_id'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Kernel ID')),
      '#markup'        => $entity->getKernelId(),
    ];

    $form['device']['ramdisk_id'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Ramdisk ID')),
      '#markup'        => $entity->getRamdiskId(),
    ];

    $form['device']['block_devices'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Block Devices')),
      '#markup'        => $entity->getBlockDevices(),
    ];

    $this->addOthersFieldset($form, $weight++);

    // Customize others fieldset.
    $old_others = $form['others'];
    unset($form['others']['langcode']);
    unset($form['others']['uid']);
    $form['others']['visibility'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Visibility')),
      '#markup'        => $entity->getVisibility(),
    ];
    $form['others']['langcode'] = $old_others['langcode'];
    $form['others']['uid'] = $old_others['uid'];

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
      $this->entity->getImageId(),
      'image_created_by_uid',
      $this->entity->getOwner()->id()
    );
  }

}
