<?php

namespace Drupal\aws_cloud\Entity\Ec2;

use Drupal\cloud\Entity\CloudContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Image entity.
 *
 * @ingroup aws_cloud
 *
 * @ContentEntityType(
 *   id = "aws_cloud_image",
 *   label = @Translation("AWS Cloud Image"),
 *   handlers = {
 *     "view_builder" = "Drupal\aws_cloud\Entity\Ec2\ImageViewBuilder"    ,
 *     "list_builder" = "Drupal\aws_cloud\Controller\Ec2\ImageListBuilder",
 *     "views_data"   = "Drupal\aws_cloud\Entity\Ec2\ImageViewsData"      ,
 *     "form" = {
 *       "default"    = "Drupal\aws_cloud\Form\Ec2\ImageEditForm"  ,
 *       "add"        = "Drupal\aws_cloud\Form\Ec2\ImageCreateForm",
 *       "edit"       = "Drupal\aws_cloud\Form\Ec2\ImageEditForm"  ,
 *       "delete"     = "Drupal\aws_cloud\Form\Ec2\ImageDeleteForm",
 *     },
 *     "access"       = "Drupal\aws_cloud\Controller\Ec2\ImageAccessControlHandler",
 *   },
 *   base_table = "aws_cloud_image",
 *   admin_permission = "administer aws cloud image",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id"    = "id",
 *     "label" = "ami_name",
 *     "uuid"  = "uuid"
 *   },
 *   links = {
 *     "canonical"   = "/view.aws_images.page_1"  ,
 *     "edit-form"   = "/entity.aws_cloud_image.edit_form"  ,
 *     "delete-form" = "/entity.aws_cloud_image.delete_form",
 *     "collection"  = "/entity.aws_cloud_image.collection"
 *   },
 *   field_ui_base_route = "aws_cloud_image.settings"
 * )
 */
class Image extends CloudContentEntityBase implements ImageInterface {

  /**
   * {@inheritdoc}
   */
  public function getImageId() {
    return $this->get('image_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setImageId($image_id = '') {
    return $this->set('image_id', $image_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getInstanceId() {
    return $this->get('instance_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getArchitecture() {
    return $this->get('architecture')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getVirtualizationType() {
    return $this->get('virtualization_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getRootDeviceName() {
    return $this->get('root_device_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getRamdiskId() {
    return $this->get('ramdisk_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getProductCode() {
    return $this->get('product_code')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmiName() {
    return $this->get('ami_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSource() {
    return $this->get('source')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getStateReason() {
    return $this->get('state_reason')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlatform() {
    return $this->get('platform')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getImageType() {
    return $this->get('image_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->get('description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getRootDeviceType() {
    return $this->get('root_device_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getKernelId() {
    return $this->get('kernel_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockDevices() {
    return $this->get('block_devices')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccountId() {
    return $this->get('account_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getVisibility() {
    return $this->get('visibility')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getRefreshed() {
    return $this->get('refreshed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRefreshed($time) {
    return $this->set('refreshed', $time);
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    return $this->set('name', $name);
  }

  /**
   * {@inheritdoc}
   */
  public function setAccountId($account_id) {
    return $this->set('account_id', $account_id);
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    return $this->set('status', $status);
  }

  /**
   * {@inheritdoc}
   */
  public function setVisibility($visibility) {
    return $this->set('visibility', $visibility);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the AwsCloudEc2Image entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the AwsCloudEc2Image entity.'))
      ->setReadOnly(TRUE);

    $fields['cloud_context'] = BaseFieldDefinition::create('string')
      ->setRequired(TRUE)
      ->setLabel(t('Cloud ID'))
      ->setDescription(t('A unique machine name for the cloud provider.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ]);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the AwsCloudEc2Image entity.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['ami_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('AMI Name'))
      ->setDescription(t('The name of the AMI that was provided during image creation.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['image_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Image ID'))
      ->setDescription(t('The Amazon Machine Image (AMI) ID is used to uniquely identify an AMI'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['instance_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Instance ID'))
      ->setDescription(t('The EC2 Instance ID this volume is attached to'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_link',
        'settings' => [
          'target_type' => 'aws_cloud_instance',
          'field_name' => 'instance_id',
        ],
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['account_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Owner'))
      ->setDescription(t('The AWS account ID of the image owner, without dashes.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['source'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Source'))
      ->setDescription(t('For AMIs backed by the Amazon instance store, this is the location of the Amazon S3 source manifest. For AMIs backed by Amazon EBS, this is the owner and name of the AMI.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Status'))
      ->setDescription(t('Specifies whether the AMI is available.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['state_reason'] = BaseFieldDefinition::create('string')
      ->setLabel(t('State Reason'))
      ->setDescription(t("Displays any provided message regarding an AMI's state change, e.g. pending to failed."))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['platform'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Platform'))
      ->setDescription(t('Specifies the operating system (e.g, Windows), if applicable.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['architecture'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Architecture'))
      ->setDescription(t('Specifies the architecture of the AMI, e.g. i386 for 32-bit, or x86_64 for 64-bit.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['virtualization_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Virtualization Type'))
      ->setDescription(t('The virtualization type used by this AMI, e.g. Paravirtual or Hardware Virtual Machine (HVM). Instances launched from this AMI will use this type of virtualization.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['product_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Product Codes'))
      ->setDescription(t('The DevPay and Marketplace product codes associated with the AMI, if applicable.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['image_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Image Type'))
      ->setDescription(t('Specifies whether this is a machine, kernel, or RAM disk image type.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['root_device_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Root Device Name'))
      ->setDescription(t('System device name that contains the boot volume (e.g. /dev/sda1)'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['root_device_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Root Device Type'))
      ->setDescription(t('The operating system kernel associated with the AMI, if applicable.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['kernel_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Kernel ID'))
      ->setDescription(t('The operating system kernel associated with the AMI, if applicable.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['ramdisk_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('RAM Disk ID'))
      ->setDescription(t('The RAM disk associated with the image, if applicable.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['block_devices'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Block Devices'))
      ->setDescription(t("Comma separated list of volumes associated with this AMI. Indicates if it's the root device, provides device name, the snapshot ID, capacity of volume in GiB when launched, and whether that volume should be deleted on instance termination."))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t("The description of the AMI that was provided when the image was created. You can click Edit to change your own AMI's description."))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ]);

    $fields['visibility'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Visibility'))
      ->setDescription(t('The AMI visibility'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Creation Date'))
      ->setDescription(t('When the AMI was created.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => -5,
        'settings' => [
          'date_format' => 'short',
        ],
      ]);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['refreshed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Refreshed'))
      ->setDescription(t('The time that the entity was last refreshed.'));

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of the AwsCloudEc2Image entity author.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
