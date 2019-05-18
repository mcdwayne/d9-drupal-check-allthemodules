<?php

namespace Drupal\aws_cloud\Entity\Ec2;

use Drupal\cloud\Entity\CloudContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the Security Group entity.
 *
 * @ingroup aws_cloud
 *
 * @ContentEntityType(
 *   id = "aws_cloud_security_group",
 *   label = @Translation("AWS Cloud Security Group"),
 *   handlers = {
 *     "view_builder" = "Drupal\aws_cloud\Entity\Ec2\SecurityGroupViewBuilder",
 *     "list_builder" = "Drupal\aws_cloud\Controller\Ec2\SecurityGroupListBuilder",
 *     "views_data"   = "Drupal\aws_cloud\Entity\Ec2\SecurityGroupViewsData",
 *     "form" = {
 *       "default"    = "Drupal\aws_cloud\Form\Ec2\SecurityGroupEditForm",
 *       "add"        = "Drupal\aws_cloud\Form\Ec2\SecurityGroupCreateForm",
 *       "edit"       = "Drupal\aws_cloud\Form\Ec2\SecurityGroupEditForm",
 *       "delete"     = "Drupal\aws_cloud\Form\Ec2\SecurityGroupDeleteForm",
 *       "revoke"     = "Drupal\aws_cloud\Form\Ec2\SecurityGroupRevokeForm",
 *     },
 *     "access"       = "Drupal\aws_cloud\Controller\Ec2\SecurityGroupAccessControlHandler",
 *   },
 *   base_table = "aws_cloud_security_group",
 *   admin_permission = "administer aws cloud security group",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id"    = "id",
 *     "label" = "name",
 *     "uuid"  = "uuid"
 *   },
 *   links = {
 *     "canonical"   = "/clouds/aws_cloud/{cloud_context}/security_group/{aws_cloud_security_group}",
 *     "edit-form"   = "/clouds/aws_cloud/{cloud_context}/security_group/{aws_cloud_security_group}/edit",
 *     "delete-form" = "/clouds/aws_cloud/{cloud_context}/security_group/{aws_cloud_security_group}/delete",
 *     "collection"  = "/clouds/aws_cloud/{cloud_context}/security_group",
 *     "revoke-form" = "/clouds/aws_cloud/{cloud_context}/security_group/{aws_cloud_security_group}/revoke",
 *   },
 *   field_ui_base_route = "aws_cloud_security_group.settings"
 * )
 */
class SecurityGroup extends CloudContentEntityBase implements SecurityGroupInterface {

  /**
   * {@inheritdoc}
   */
  public function getGroupId() {
    return $this->get('group_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setGroupId($group_id = '') {
    return $this->set('group_id', $group_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupName() {
    return $this->get('group_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupDescription() {
    return $this->get('group_description')->value;
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
  public function getVpcId() {
    return $this->get('vpc_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isDefaultVpc() {
    return $this->get('default_vpc')->value;
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
  public function getIpPermission() {
    return $this->get('ip_permission');
  }

  /**
   * {@inheritdoc}
   */
  public function getOutboundPermission() {
    return $this->get('outbound_permission');
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultVpc($default) {
    return $this->set('default_vpc', $default);
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the AwsCloudEc2SecurityGroup entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the AwsCloudEc2SecurityGroup entity.'))
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
      ->setDescription(t('The name of the AwsCloudEc2Volume entity.'))
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

    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('Description of security group.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ]);

    $fields['group_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of your security group.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['group_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Security Group Name'))
      ->setDescription(t('The name given to your security group.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['group_description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setDescription(t('The description of your security group.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['vpc_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('VPC ID'))
      ->setDescription(t('The ID of the virtual private cloud (VPC) the security group belongs to, if applicable. A VPC is an isolated portion of the AWS cloud.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['default_vpc'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Default VPC'))
      ->setDescription(t('Whether the VPC is a default VPC'))
      ->setDefaultValue(FALSE)
      ->setReadOnly(TRUE);

    // Inbound permissions.
    $fields['ip_permission'] = BaseFieldDefinition::create('ip_permission')
      ->setLabel(t('Inbound Rules'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDescription(t('Ingress rules'))
      ->setDisplayOptions('view', [
        'type' => 'ip_permission_formatter',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'ip_permission_item',
      ]);

    // Outbound permissions.
    $fields['outbound_permission'] = BaseFieldDefinition::create('ip_permission')
      ->setLabel(t('Outbound Rules'))
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDescription(t('Egress rules.  Egress is only available for VPC security groups.'))
      ->setDisplayOptions('view', [
        'type' => 'ip_zpermission_formatter',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'ip_permission_item',
        'weight' => 0,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['refreshed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Refreshed'))
      ->setDescription(t('The time that the entity was last refreshed.'));

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of the AwsCloudEc2SecurityGroup entity author.'))
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
