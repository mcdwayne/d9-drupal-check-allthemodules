<?php

namespace Drupal\aws_cloud\Entity\Ec2;

use Drupal\cloud\Entity\CloudContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Snapshot entity.
 *
 * @ingroup aws_cloud
 *
 * @ContentEntityType(
 *   id = "aws_cloud_snapshot",
 *   label = @Translation("AWS Cloud Snapshot"),
 *   handlers = {
 *     "view_builder" = "Drupal\aws_cloud\Entity\Ec2\SnapshotViewBuilder"    ,
 *     "list_builder" = "Drupal\aws_cloud\Controller\Ec2\SnapshotListBuilder",
 *     "views_data"   = "Drupal\aws_cloud\Entity\Ec2\SnapshotViewsData"      ,
 *
 *     "form" = {
 *       "default"    = "Drupal\aws_cloud\Form\Ec2\SnapshotEditForm"  ,
 *       "add"        = "Drupal\aws_cloud\Form\Ec2\SnapshotCreateForm",
 *       "edit"       = "Drupal\aws_cloud\Form\Ec2\SnapshotEditForm"  ,
 *       "delete"     = "Drupal\aws_cloud\Form\Ec2\SnapshotDeleteForm",
 *     },
 *     "access"       = "Drupal\aws_cloud\Controller\Ec2\SnapshotAccessControlHandler",
 *   },
 *   base_table = "aws_cloud_snapshot",
 *   admin_permission = "administer aws cloud snapshot",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id"    = "id"  ,
 *     "label" = "name",
 *     "uuid"  = "uuid"
 *   },
 *   links = {
 *     "canonical"   = "/clouds/aws_cloud/{cloud_context}/snapshot/{aws_cloud_snapshot}",
 *     "edit-form"   = "/clouds/aws_cloud/{cloud_context}/snapshot/{aws_cloud_snapshot}/edit",
 *     "delete-form" = "/clouds/aws_cloud/{cloud_context}/snapshot/{aws_cloud_snapshot}/delete",
 *     "collection"  = "/clouds/aws_cloud/{cloud_context}/snapshot",
 *   },
 *   field_ui_base_route = "aws_cloud.snapshot.settings"
 * )
 */
class Snapshot extends CloudContentEntityBase implements SnapshotInterface {

  /**
   * {@inheritdoc}
   */
  public function getSnapshotId() {
    return $this->get('snapshot_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSnapshotId($snapshot_id = '') {
    return $this->set('snapshot_id', $snapshot_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getSize() {
    return $this->get('size')->value;
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
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status = 'unknown') {
    return $this->set('status', $status);
  }

  /**
   * {@inheritdoc}
   */
  public function getVolumeId() {
    return $this->get('volume_id')->value;
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
  public function getOwnerAliases() {
    return $this->get('owner_aliases')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getEncrypted() {
    return $this->get('encrypted')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEncrypted($encrypted = FALSE) {
    return $this->set('encrypted', $encrypted);
  }

  /**
   * {@inheritdoc}
   */
  public function getKmsKeyId() {
    return $this->get('kms_key_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getStateMessage() {
    return $this->get('state_message')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getStarted() {
    return $this->get('started')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStarted($started = '') {
    return $this->set('started', $started);
  }

  /**
   * {@inheritdoc}
   */
  public function getProgress() {
    return $this->get('progress')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getKmsKeyAliases() {
    return $this->get('kms_key_aliases')->value;
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
  public function setCreated($created = 0) {
    return $this->set('created', $created);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the AwsCloudEc2Snapshot entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the AwsCloudEc2Snapshot entity.'))
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
      ->setDescription(t('The name of the AwsCloudEc2ElasticIp entity.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('Description of source snapshot.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ]);

    $fields['snapshot_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Snapshot ID'))
      ->setDescription(t('The Snapshot ID.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['volume_id'] = BaseFieldDefinition::create('string')
      ->setRequired(TRUE)
      ->setLabel(t('Volume ID'))
      ->setDescription(t('The volume ID from which the snapshot was created. A snapshot is a copy of an Amazon EBS volume at a point in time.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_link',
        'settings' => [
          'target_type' => 'aws_cloud_volume',
          'field_name' => 'volume_id',
        ],
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['size'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Size (GB)'))
      ->setDescription(t('The size of the snapshot.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Status'))
      ->setDescription(t('The current state of the snapshot; for example, pending, completed, or error.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['progress'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Progress'))
      ->setDescription(t('The portion (percentage) of the snapshot that has been created.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['encrypted'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Encrypted'))
      ->setDescription(t('Indicates whether the snapshot is encrypted.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['account_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('AWS Account ID'))
      ->setDescription(t('The AWS account ID of the snapshot owner, without dashes.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['started'] = BaseFieldDefinition::create('integer')
      ->setRequired(TRUE)
      ->setLabel(t('Started'))
      ->setDescription(t('The date and time when the snapshot started.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['capacity'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Capacity'))
      ->setDescription(t('The size of the Amazon EBS volume from which the snapshot was created, in GiB.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['product_codes'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Product Code'))
      ->setDescription(t('AWS Marketplace product codes associated with the snapshot, if any.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['kms_key_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('KMS Key ID'))
      ->setDescription(t('KMS Key ID.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['kms_key_aliases'] = BaseFieldDefinition::create('string')
      ->setLabel(t('KMS Key Aliases'))
      ->setDescription(t('KMS Key Aliases.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['kms_key_arn'] = BaseFieldDefinition::create('string')
      ->setLabel(t('KMS Key ARN'))
      ->setDescription(t('KMS Key ARN.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('Date/time the Amazon snapshot was created.'))
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
      ->setDescription(t('The user ID of the AwsCloudEc2Snapshot entity author.'))
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
