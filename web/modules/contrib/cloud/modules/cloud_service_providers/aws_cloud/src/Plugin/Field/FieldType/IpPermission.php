<?php

namespace Drupal\aws_cloud\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'ip_permission' field type.
 *
 * @FieldType(
 *   id = "ip_permission",
 *   label = @Translation("IpPermission"),
 *   description = @Translation("AWS Ip permission field"),
 *   default_widget = "ip_permission_item",
 *   default_formatter = "ip_permission_formatter"
 * )
 */
class IpPermission extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'max_length' => 255,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties['source'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Security Group Type'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(TRUE);

    $properties['from_port'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Starting port range'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(TRUE);

    $properties['to_port'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Ending port range'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(TRUE);

    $properties['ip_protocol'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('IP Protocol name (tcp, udp, icmp)'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(TRUE);

    $properties['cidr_ip'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('The IPv4 CIDR range.'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(FALSE);

    $properties['cidr_ip_v6'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('The IPv6 CIDR range.'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(FALSE);

    $properties['prefix_list_id'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('The ID of the prefix.'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(FALSE);

    $properties['group_id'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Group Id'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(FALSE);

    $properties['group_name'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Group Name'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(FALSE);

    $properties['peering_status'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('PeeringS tatus'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(FALSE);

    $properties['user_id'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Group Pair User Id'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(FALSE);

    $properties['vpc_id'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('VPC Id'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(FALSE);

    $properties['peering_connection_id'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Peering Connection Id'))
      ->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'source' => [
          'type' => 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
        ],
        'from_port' => [
          'type' => 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
        ],
        'to_port' => [
          'type' => 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
        ],
        'ip_protocol' => [
          'type' => 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
          'not null' => FALSE,
        ],
        'cidr_ip' => [
          'type' => 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
          'not null' => FALSE,
        ],
        'cidr_ip_v6' => [
          'type' => 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
          'not null' => FALSE,
        ],
        'prefix_list_id' => [
          'type' => 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
          'not null' => FALSE,
        ],
        'group_id' => [
          'type' => 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
          'not null' => FALSE,
        ],
        'group_name' => [
          'type' => 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
          'not null' => FALSE,
        ],
        'peering_status' => [
          'type' => 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
          'not null' => FALSE,
        ],
        'user_id' => [
          'type' => 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
          'not null' => FALSE,
        ],
        'vpc_id' => [
          'type' => 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
          'not null' => FALSE,
        ],
        'peering_connection_id' => [
          'type' => 'varchar',
          'length' => (int) $field_definition->getSetting('max_length'),
          'not null' => FALSE,
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();
    $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
    $constraints[] = $constraint_manager->create('ip_permission_data', []);
    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $elements = [];

    $elements['max_length'] = [
      '#type' => 'number',
      '#title' => t('Maximum length'),
      '#default_value' => $this->getSetting('max_length'),
      '#required' => TRUE,
      '#description' => t('The maximum length of the field in characters.'),
      '#min' => 1,
      '#disabled' => $has_data,
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $to_port = $this->get('to_port')->getValue();
    $from_port = $this->get('from_port')->getValue();
    // If to_port and from_port is not set, then
    // consider it empty.
    return empty($to_port) && empty($from_port);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    // Treat the values as property value of the main property, if no array is
    // given.
    if (isset($values) && !is_array($values)) {
      $values = [static::mainPropertyName() => $values];
    }
    if (isset($values)) {
      $values += [
        'options' => [],
      ];
    }
    // Unserialize the values.
    // @todo The storage controller should take care of this, see
    // SqlContentEntityStorage::loadFieldItems, see
    // https://www.drupal.org/node/2414835 .
    if (is_string($values['options'])) {
      $values['options'] = unserialize($values['options']);
    }
    parent::setValue($values, $notify);
  }

  /**
   * Get the source value.  Values can be ip4 ip6 or group.
   *
   * This is an internal value to identify if the ip4/ip6/group
   * values are being sent from EC2.
   */
  public function getSource() {
    return $this->get('source')->getValue();
  }

  /**
   * Get the from_port value.
   */
  public function getFromPort() {
    return $this->get('from_port')->getValue();
  }

  /**
   * Get the to_port value.
   */
  public function getToPort() {
    return $this->get('to_port')->getValue();
  }

  /**
   * Get the ip_protocol value.
   */
  public function getIpProtocol() {
    return $this->get('ip_protocol')->getValue();
  }

  /**
   * Get the cidr_ip value.
   */
  public function getCidrIp() {
    return $this->get('cidr_ip')->getValue();
  }

  /**
   * Get the cidr_ip_v6 value.
   */
  public function getCidrIpv6() {
    return $this->get('cidr_ip_v6')->getValue();
  }

  /**
   * Get the prefix_list_id value.
   */
  public function getPrefixListId() {
    return $this->get('prefix_list_id')->getValue();
  }

  /**
   * Get te group_id value.
   */
  public function getGroupId() {
    return $this->get('group_id')->getValue();
  }

  /**
   * Get the group_name value.
   */
  public function getGroupName() {
    return $this->get('group_name')->getValue();
  }

  /**
   * Get the peering_status value.
   */
  public function getPeeringStatus() {
    return $this->get('peering_status')->getValue();
  }

  /**
   * Get the user_id value.
   */
  public function getUserId() {
    return $this->get('user_id')->getValue();
  }

  /**
   * Get the vpc_id value.
   */
  public function getVpcId() {
    return $this->get('vpc_id')->getValue();
  }

  /**
   * Get the peering_connection_id value.
   */
  public function getPeeringConnectionId() {
    return $this->get('peering_connection_id')->value();
  }

  /**
   * Set the source value.
   *
   * @param string $source
   *   The source.
   *
   * @return $this
   */
  public function setSource($source) {
    return $this->set('source', $source);
  }

  /**
   * Set the from_port value.
   *
   * @param string $from_port
   *   The from port.
   *
   * @return $this
   */
  public function setFromPort($from_port) {
    return $this->set('from_port', $from_port);
  }

  /**
   * Set the to_port value.
   *
   * @param string $to_port
   *   The to port.
   *
   * @return $this
   */
  public function setToPort($to_port) {
    return $this->set('to_port', $to_port);
  }

  /**
   * Set the ip_protocol value.
   *
   * @param string $ip_protocol
   *   The ip protocol.
   *
   * @return $this
   */
  public function setIpProtocol($ip_protocol) {
    return $this->set('ip_protocol', $ip_protocol);
  }

  /**
   * Set the cidr_ip value.
   *
   * @param string $cidr_ip
   *   The cidr ip address.
   *
   * @return $this
   */
  public function setCidrIp($cidr_ip) {
    return $this->set('cidr_ip', $cidr_ip);
  }

  /**
   * Set the cidr_ip_v6 value.
   *
   * @param string $cidr_ip_v6
   *   The cidr ip v6.
   *
   * @return $this
   */
  public function setCidrIpv6($cidr_ip_v6) {
    return $this->set('cidr_ip_v6', $cidr_ip_v6);
  }

  /**
   * Set the prefix_list_id value.
   *
   * @param string $prefix_list_id
   *   The prefix id.
   *
   * @return $this
   */
  public function setPrefixListId($prefix_list_id) {
    return $this->set('prefix_list_id', $prefix_list_id);
  }

  /**
   * Set the group_id value.
   *
   * @param string $group_id
   *   The group id.
   *
   * @return $this
   */
  public function setGroupId($group_id) {
    return $this->set('group_id', $group_id);
  }

  /**
   * Set the group_name value.
   *
   * @param string $group_name
   *   The group name.
   *
   * @return $this
   */
  public function setGroupName($group_name) {
    return $this->set('group_name', $group_name);
  }

  /**
   * Set the peering_status value.
   *
   * @param string $peering_status
   *   The peering status.
   *
   * @return $this
   */
  public function setPeeringStatus($peering_status) {
    return $this->set('peering_status', $peering_status);
  }

  /**
   * Set the user_id value.
   *
   * @param string $user_id
   *   The user id.
   *
   * @return $this
   */
  public function setUserId($user_id) {
    return $this->set('user_id', $user_id);
  }

  /**
   * Set the vpc_id value.
   *
   * @param string $vpc_id
   *   The vpc id.
   *
   * @return $this
   */
  public function setVpcId($vpc_id) {
    return $this->set('vpc_id', $vpc_id);
  }

  /**
   * Set the peering_connection_id value.
   *
   * @param string $peering_connection_id
   *   The peering connection id.
   *
   * @return $this
   */
  public function setPeeringConnectionId($peering_connection_id) {
    return $this->set('peering_connection_id', $peering_connection_id);
  }

}
