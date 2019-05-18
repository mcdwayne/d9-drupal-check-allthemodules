<?php

namespace Drupal\aws_cloud\Entity\Ec2;

use Drupal\cloud\Entity\CloudContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the Instance entity.
 *
 * @ingroup aws_cloud
 *
 * @ContentEntityType(
 *   id = "aws_cloud_instance",
 *   label = @Translation("AWS Cloud Instance"),
 *   handlers = {
 *     "view_builder" = "Drupal\aws_cloud\Entity\Ec2\InstanceViewBuilder"    ,
 *     "list_builder" = "Drupal\aws_cloud\Controller\Ec2\InstanceListBuilder",
 *     "views_data"   = "Drupal\aws_cloud\Entity\Ec2\InstanceViewsData"      ,
 *
 *     "form" = {
 *       "default"    = "Drupal\aws_cloud\Form\Ec2\InstanceEditForm"  ,
 *       "add"        = "Drupal\aws_cloud\Form\Ec2\InstanceLaunchForm",
 *       "edit"       = "Drupal\aws_cloud\Form\Ec2\InstanceEditForm"  ,
 *       "delete"     = "Drupal\aws_cloud\Form\Ec2\InstanceDeleteForm",
 *       "stop"       = "Drupal\aws_cloud\Form\Ec2\InstanceStopForm",
 *       "start"      = "Drupal\aws_cloud\Form\Ec2\InstanceStartForm",
 *       "create_image" = "Drupal\aws_cloud\Form\Ec2\InstanceCreateImageForm",
 *       "reboot"     = "Drupal\aws_cloud\Form\Ec2\InstanceRebootForm",
 *       "associate_elastic_ip" = "Drupal\aws_cloud\Form\Ec2\InstanceAssociateElasticIpForm"
 *     },
 *     "access"       = "Drupal\aws_cloud\Controller\Ec2\InstanceAccessControlHandler",
 *   },
 *   base_table = "aws_cloud_instance",
 *   admin_permission = "administer aws cloud instance",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id"    = "id"  ,
 *     "label" = "name",
 *     "uuid"  = "uuid"
 *   },
 *   links = {
 *    "canonical" = "/clouds/aws_cloud/{cloud_context}/instance/{aws_cloud_instance}",
 *    "edit-form" = "/clouds/aws_cloud/{cloud_context}/instance/{aws_cloud_instance}/edit",
 *    "delete-form" = "/clouds/aws_cloud/{cloud_context}/instance/{aws_cloud_instance}/terminate",
 *    "collection" = "/clouds/aws_cloud/{cloud_context}/instance",
 *    "stop-form" = "/clouds/aws_cloud/{cloud_context}/instance/{aws_cloud_instance}/stop",
 *    "start-form" = "/clouds/aws_cloud/{cloud_context}/instance/{aws_cloud_instance}/start",
 *    "reboot-form" = "/clouds/aws_cloud/{cloud_context}/instance/{aws_cloud_instance}/reboot",
 *    "create-image-form" = "/clouds/aws_cloud/{cloud_context}/instance/{aws_cloud_instance}/create_image",
 *    "associate-elastic-ip-form" = "/clouds/aws_cloud/{cloud_context}/instance/{aws_cloud_instance}/associate_elastic_ip"
 *   },
 *   field_ui_base_route = "aws_cloud_instance.settings"
 * )
 */
class Instance extends CloudContentEntityBase implements InstanceInterface {

  /**
   * {@inheritdoc}
   */
  public function setParams(array $form) {
    $this->set('image_id', $form['image_id']['#value']);
    $this->set('max_count', $form['max_count']['#value']);
    $this->set('min_count', $form['min_count']['#value']);
    $this->set('key_pair_name', $form['key_pair_name']['#value']);
    $this->set('is_monitoring', $form['is_monitoring']['#value'] ? TRUE : FALSE);
    $this->set('availability_zone', $form['availability_zone']['#value']);
    $this->set('security_groups', $form['security_groups']['#value']);
    $this->set('instance_type', $form['instance_type']['#value']);
    $this->set('kernel_id', $form['kernel_id']['#value']);
    $this->set('ramdisk_id', $form['ramdisk_id']['#value']);
    $this->set('user_data', $form['user_data']['#value']);
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
  public function getInstanceType() {
    return $this->get('instance_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setInstanceType($instance_type) {
    return $this->set('instance_type', $instance_type);
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailabilityZone() {
    return $this->get('availability_zone')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstanceState() {
    return $this->get('instance_state')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getInstanceLock() {
    return $this->get('instance_lock')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setInstanceId($instance_id = '') {
    return $this->set('instance_id', $instance_id);
  }

  /**
   * {@inheritdoc}
   */
  public function setInstanceState($state = '') {
    return $this->set('instance_state', $state);
  }

  /**
   * {@inheritdoc}
   */
  public function setElasticIp($elastic_ip = '') {
    return $this->set('elastic_ip', $elastic_ip);
  }

  /**
   * {@inheritdoc}
   */
  public function setPublicIp($public_ip = '') {
    return $this->set('public_ip', $public_ip);
  }

  /**
   * {@inheritdoc}
   */
  public function setInstanceLock($lock = FALSE) {
    return $this->set('instance_lock', $lock);
  }

  /**
   * {@inheritdoc}
   */
  public function getPublicDns() {
    return $this->get('public_dns')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPublicDns($dns) {
    return $this->set('public_dns', $dns);
  }

  /**
   * {@inheritdoc}
   */
  public function getPublicIp() {
    return $this->get('public_ip')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getElasticIp() {
    return $this->get('elastic_ip')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrivateDns() {
    return $this->get('private_dns')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPrivateDns($private_dns) {
    return $this->set('private_dns', $private_dns);
  }

  /**
   * {@inheritdoc}
   */
  public function getPrivateIps() {
    return $this->get('private_ips')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPrivateIps($private_ips) {
    return $this->set('private_ips', $private_ips);
  }

  /**
   * {@inheritdoc}
   */
  public function getPrivateSecondaryIp() {
    return $this->get('private_secondary_ip')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPrivateSecondaryIp($private_ip) {
    return $this->set('private_secondary_ip', $private_ip);
  }

  /**
   * {@inheritdoc}
   */
  public function getKeyPairName() {
    return $this->get('key_pair_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setKeyPairName($key_pair_name = '') {
    return $this->set('key_pair_name', $key_pair_name);
  }

  /**
   * {@inheritdoc}
   */
  public function isMonitoring() {
    return $this->get('is_monitoring')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMonitoring($is_monitoring = FALSE) {
    return $this->set('is_monitoring', $is_monitoring);
  }

  /**
   * {@inheritdoc}
   */
  public function getMonitoring() {
    return $this->get('monitoring')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLaunchTime($launch_time) {
    return $this->set('launch_time', $launch_time);
  }

  /**
   * {@inheritdoc}
   */
  public function getLaunchTime() {
    return $this->get('launch_time')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSecurityGroups() {
    return $this->get('security_groups')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSecurityGroups($securityGroups) {
    return $this->set('security_groups', $securityGroups);
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
  public function getSubnetId() {
    return $this->get('subnet_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getNetworkInterfaces() {
    return $this->get('network_interfaces')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceDestCheck() {
    return $this->get('source_dest_check')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getEbsOptimized() {
    return $this->get('ebs_optimized')->value;
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
  public function getRootDevice() {
    return $this->get('root_device')->value;
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
  public function getScheduledEvents() {
    return $this->get('scheduled_events')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getImageId() {
    return $this->get('image_id')->value;
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
  public function getIamRole() {
    return $this->get('iam_role')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setIamRole($iam_role) {
    return $this->set('iam_role', $iam_role);
  }

  /**
   * {@inheritdoc}
   */
  public function getTerminationProtection() {
    return $this->get('termination_protection')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTerminationProtection($termination_protection) {
    return $this->set('termination_protection', $termination_protection);
  }

  /**
   * {@inheritdoc}
   */
  public function getLifecycle() {
    return $this->get('lifecycle')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getAlarmStatus() {
    return $this->get('alarm_status')->value;
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
  public function getRamdiskId() {
    return $this->get('ramdisk_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlacementGroup() {
    return $this->get('placement_group')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getVirtualization() {
    return $this->get('virtualization')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getReservation() {
    return $this->get('reservation')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmiLaunchIndex() {
    return $this->get('ami_launch_index')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getTenancy() {
    return $this->get('tenancy')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getHostId() {
    return $this->get('host_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getAffinity() {
    return $this->get('affinity')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getStateTransitionReason() {
    return $this->get('state_transition_reason')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getLoginUsername() {
    return $this->get('login_username')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCloudType() {
    return $this->get('cloud_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserData() {
    return $this->get('user_data')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getMinCount() {
    return $this->get('min_count')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getMaxCount() {
    return $this->get('max_count')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreated($created = '' /* Y/m/d H:i:s */) {
    return $this->set('created', strtotime($created));
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
  public function getTerminationTimestamp() {
    return $this->get('termination_timestamp');
  }

  /**
   * {@inheritdoc}
   */
  public function setTerminationTimestamp($timestamp) {
    return $this->set('termination_timestamp', $timestamp);
  }

  /**
   * {@inheritdoc}
   */
  public function setSchedule($schedule) {
    return $this->set('schedule', $schedule);
  }

  /**
   * {@inheritdoc}
   */
  public function getSchedule() {
    return $this->get('schedule')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getTags() {
    return $this->get('tags')->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function setTags(array $tags) {
    return $this->set('tags', $tags);
  }

  /**
   * {@inheritdoc}
   */
  public function setNetworkInterfaces(array $interfaces) {
    return $this->set('network_interfaces', $interfaces);
  }

  /**
   * {@inheritdoc}
   */
  public function getCost() {
    return $this->get('cost')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCost($cost) {
    return $this->set('cost', $cost);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Instance entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Instance entity.'))
      ->setReadOnly(TRUE);

    $fields['cloud_context'] = BaseFieldDefinition::create('string')
      ->setRequired(TRUE)
      ->setLabel(t('Cloud ID'))
      ->setDescription(t('A unique machine name for the cloud provider.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Instance entity.'))
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

    $fields['instance_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Instance ID'))
      ->setDescription(t('The Instance ID.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['instance_state'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Instance State'))
      ->setDescription(t('The state of the instance; for example, running, pending, or terminated.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['instance_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Instance Type'))
      ->setDescription(t("The type of instance determines your instance's CPU capacity, memory, and storage (e.g., m1.small, c1.xlarge)."))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['cost'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Cost'))
      ->setDescription(t('The Instance Cost.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setSettings([
        'prefix' => '$',
      ])
      ->setReadOnly(TRUE);

    $fields['iam_role'] = BaseFieldDefinition::create('string')
      ->setLabel(t('IAM Role'))
      ->setDescription(t('The IAM roles associated with the instance, if applicable.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['image_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('AMI Image'))
      ->setDescription(t('The ID of the AMI with which the instance was launched.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['virtualization'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Virtualization'))
      ->setDescription(t('The type of virtual machine running, e.g. paravirtual or hvm.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['reservation'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Reservation'))
      ->setDescription(t('The reservation ID used to launch the instance.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['account_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('AWS Account ID'))
      ->setDescription(t('The AWS account number of the AMI owner, without dashes.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['launch_time'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Launch Time'))
      ->setDescription(t('The time the instance launched.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => -5,
        'settings' => [
          'date_format' => 'short',
        ],
      ])
      ->setReadOnly(TRUE);

    $fields['public_ip'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Public IP'))
      ->setDescription(t('The Public IP.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_link',
        'settings' => [
          'target_type' => 'aws_cloud_elastic_ip',
          'field_name' => 'public_ip',
          'html_generator_class' => PublicIpEntityLinkHtmlGenerator::class,
        ],
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['elastic_ip'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Elastic IP'))
      ->setDescription(t('The Elastic IP address assigned to the instance, if applicable. Elastic IP addresses are static IP addresses assigned to your account that you can quickly remap to other instances.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['private_ips'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Private IPs'))
      ->setDescription(t('The private IP address of the instance (multiple IP addresses are listed if there is more than one network interface to the instance).'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['public_dns'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Public DNS'))
      ->setDescription(t('The public hostname of the instance, which resolves to the public IP address or Elastic IP address of the instance.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['private_dns'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Private DNS'))
      ->setDescription(t("The private, internal hostname of the instance, which resolves to the instance's private IP address."))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['security_groups'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Security Groups'))
      ->setDescription(t('The security groups to which the instance belongs. A security group is a collection of firewall rules that restrict the network traffic for the instance. Click View rules to see the rules for the specific group.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_link',
        'settings' => [
          'target_type' => 'aws_cloud_security_group',
          'field_name' => 'group_name',
          'comma_separated' => TRUE,
        ],
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['key_pair_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Key Pair Name'))
      ->setDescription(t('The name of the key pair that you must use to log in to the instance securely.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_link',
        'settings' => [
          'target_type' => 'aws_cloud_key_pair',
          'field_name' => 'key_pair_name',
        ],
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['vpc_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('VPC ID'))
      ->setDescription(t('The ID of the virtual private cloud (VPC) the instance was launched into, if applicable. A VPC is an isolated portion of the AWS cloud.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['subnet_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subnet ID'))
      ->setDescription(t('The ID of the subnet that the instance was launched into, if applicable. A subnet is a range of IP addresses in a VPC.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['availability_zone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Availability Zone'))
      ->setDescription(t('The availability zone in which the instance is located. Availability Zones are distinct locations within a region that are engineered to be insulated from failures in other Availability Zones.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['root_device_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Root Device Type'))
      ->setDescription(t('The root volume is either an EBS volume or instance store volume. The Create Image, Start and Stop actions only apply to instances with an EBS root device type.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['root_device'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Root Device'))
      ->setDescription(t('System device name that contains the boot volume (e.g., /dev/sda1).'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['block_devices'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Block Devices'))
      ->setDescription(t('The Amazon EBS volumes attached to this instance.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['ebs_optimized'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('EBS Optimized'))
      ->setDescription(t('Indicates whether EBS optimization (additional, dedicated throughput between Amazon EC2 and Amazon EBS,) has been enabled for the instance.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['private_secondary_ip'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Private Secondary IP'))
      ->setDescription(t('Any secondary private IP addresses assigned to a network interface attached to the instance.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['network_interfaces'] = BaseFieldDefinition::create('string')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setLabel(t('Network Interfaces'))
      ->setDescription(t('The network interface devices attached to the instance, if launched into a VPC. Click the device index for more information, such as its ID and IP addresses.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'entity_link',
        'settings' => [
          'target_type' => 'aws_cloud_network_interface',
          'field_name' => 'network_interface_id',
        ],
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['scheduled_events'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Scheduled Events'))
      ->setDescription(t('The number of scheduled events associated with this instance, if applicable. Click the link to go to the Events screen.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['platform'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Platform'))
      ->setDescription(t('The operating system platform, such as Windows. This is not returned for all platform types.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['lifecycle'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Lifecycle'))
      ->setDescription(t('The lifecycle of the instance (normal, spot, scheduled), which controls how the instance runs.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['alarm_status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Alarm Status'))
      ->setDescription(t('The status of the CloudWatch alarms that monitor metrics for this instance.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['kernel_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Kernel Image'))
      ->setDescription(t('The operating system kernel associated with the AMI.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['ramdisk_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Ramdisk Image'))
      ->setDescription(t('The RAM disk associated with the image, if a specific one was selected.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['placement_group'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Placement Group'))
      ->setDescription(t('The cluster group to which the instance belongs, if it is a cluster instance.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['endpoint'] = BaseFieldDefinition::create('string')
      ->setLabel(t('API Endpoint URI'))
      ->setDescription(t('Select the Dedicated host you want to launch onto.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['affinity'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Affinity'))
      ->setDescription(t("Affinity creates a persistent relationship between an instance and a Dedicated host. The default setting is 'Off'. If you launch instances onto a Dedicated host using the host ID, Affinity is enabled."))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['state_transition_reason'] = BaseFieldDefinition::create('string')
      ->setLabel(t('State Transition Reason'))
      ->setDescription(t('The reason for the change of instance state; if the instance was terminated, for example, the reason might be ‘User initiated shutdown’.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['login_username'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Login Username'))
      ->setDescription(t('A login username for Linux’.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ]);

    $fields['cloud_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Cloud Type'))
      ->setDescription(t('AWS EC2 compatible cloud service provider.'))
      ->setReadOnly(TRUE);

    $fields['min_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Min Count'))
      ->setDescription(t('Minimum count of launching instances.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ]);

    $fields['max_count'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Max Count'))
      ->setDescription(t('Maximum count of launching instances.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'))
      ->setReadOnly(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['refreshed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Refreshed'))
      ->setDescription(t('The time that the entity was last refreshed.'));

    $fields['termination_protection'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Termination Protection'))
      ->setDescription(t('Indicates whether termination protection is enabled. If enabled, this instance cannot be terminated using the console, API, or CLI until termination protection is disabled.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ]);

    $fields['is_monitoring'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Monitoring'))
      ->setDescription(t('The level of CloudWatch monitoring that is enabled for this instance (basic or detailed).'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['monitoring'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Monitoring'))
      ->setDescription(t('The level of CloudWatch monitoring that is enabled for this instance (basic or detailed).'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of the Instance entity author.'))
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

    $fields['termination_timestamp'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Termination Date'))
      ->setDescription('Termination Timestamp')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => -1,
      ]);

    $fields['schedule'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Instance Schedule'))
      ->setDescription('Store AWS instance scheduler tag')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ]);

    $fields['ami_launch_index'] = BaseFieldDefinition::create('string')
      ->setLabel(t('AMI Launch Index'))
      ->setDescription(t('A number indicating the order in which the instance was launched. The first or only instance has an index of 0.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['tenancy'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Tenancy'))
      ->setDescription(t('Type of tenancy (dedicated or default). If dedicated, the instance is running on single-tenant, dedicated hardware.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setReadOnly(TRUE);

    $fields['user_data'] = BaseFieldDefinition::create('string')
      ->setLabel(t('User Data'))
      ->setDescription(t('User Data to pass to an instance when launching.'))
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -5,
      ]);

    $fields['tags'] = BaseFieldDefinition::create('tag')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDescription(t('Tags'))
      ->setDisplayOptions('view', [
        'type' => 'tag_formatter',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'tag_item',
      ])
      ->addConstraint('tags_data');

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByInstanceId($instance_id) {
    $entity = FALSE;
    $entity_type_repository = \Drupal::service('entity_type.repository');
    $entity_type_manager = \Drupal::entityTypeManager();
    $storage = $entity_type_manager->getStorage($entity_type_repository->getEntityTypeFromClass(get_called_class()));
    $entities = $storage->loadByProperties(['instance_id' => $instance_id]);
    if (count($entities) == 1) {
      $entity = array_shift($entities);
    }
    return $entity;
  }

}
