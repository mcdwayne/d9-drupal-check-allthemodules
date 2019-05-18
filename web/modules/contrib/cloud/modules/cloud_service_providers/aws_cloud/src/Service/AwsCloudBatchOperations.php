<?php

namespace Drupal\aws_cloud\Service;

use Drupal\aws_cloud\Entity\Ec2\Volume;
use Drupal\aws_cloud\Entity\Ec2\Snapshot;
use Drupal\aws_cloud\Entity\Ec2\KeyPair;
use Drupal\aws_cloud\Entity\Ec2\ElasticIp;
use Drupal\aws_cloud\Entity\Ec2\NetworkInterface;
use Drupal\aws_cloud\Entity\Ec2\Image;
use Drupal\aws_cloud\Entity\Ec2\Instance;
use Drupal\aws_cloud\Entity\Ec2\SecurityGroup;

/**
 * Entity update methods for Batch API processing.
 */
class AwsCloudBatchOperations {

  /**
   * The finish callback function.
   *
   * Deletes stale entities from the database.
   *
   * @param string $entity_type
   *   The entity type.
   * @param array $stale
   *   The stale entities to delete.
   * @param bool $clear
   *   TRUE to clear entities, FALSE keep them.
   */
  public static function finished($entity_type, array $stale, $clear = TRUE) {
    $entity_type_manager = \Drupal::entityTypeManager();
    if (count($stale) && $clear == TRUE) {
      $entity_type_manager->getStorage($entity_type)->delete($stale);
    }
  }

  /**
   * Update or create an instance entity.
   *
   * @param string $cloud_context
   *   The cloud context.
   * @param array $instance
   *   The instance array.
   */
  public static function updateInstance($cloud_context, array $instance) {
    /* @var \Drupal\aws_cloud\Service\AwsEc2ServiceInterface */
    $awsEc2Service = \Drupal::service('aws_cloud.ec2');
    $awsEc2Service->setCloudContext($cloud_context);
    $timestamp = time();

    $config_factory = \Drupal::configFactory();

    // Get instance IAM roles associated to instances.
    $instance_iam_roles = [];
    $associations_result = $awsEc2Service->describeIamInstanceProfileAssociations();
    foreach ($associations_result['IamInstanceProfileAssociations'] as $association) {
      $instance_iam_roles[$association['InstanceId']]
        = $association['IamInstanceProfile']['Arn'];
    }

    $instanceName = '';
    $uid = 0;
    $termination_timestamp = NULL;
    $schedule = '';
    $tags = [];
    if (!isset($instance['Tags'])) {
      $instance['Tags'] = [];
    }
    foreach ($instance['Tags'] as $tag) {
      if ($tag['Key'] == 'Name') {
        $instanceName = $tag['Value'];
      }
      if ($tag['Key'] == 'cloud_launched_by_uid') {
        $uid = $tag['Value'];
      }
      if ($tag['Key'] == 'cloud_termination_timestamp') {
        if ($tag['Value'] != '') {
          $termination_timestamp = (int) $tag['Value'];
        }
      }
      if ($tag['Key'] == $config_factory->get('aws_cloud.settings')->get('aws_cloud_scheduler_tag')) {
        $schedule = $tag['Value'];
      }

      $tags[] = ['tag_key' => $tag['Key'], 'tag_value' => $tag['Value']];
    }

    usort($tags, function ($a, $b) {
      if ($a['tag_key'] == 'Name') {
        return -1;
      }

      if ($b['tag_key'] == 'Name') {
        return 1;
      }

      return strcmp($a['tag_key'], $b['tag_key']);
    });

    // Default to instance_id.
    if (empty($instanceName)) {
      $instanceName = $instance['InstanceId'];
    }

    $security_groups = [];
    foreach ($instance['SecurityGroups'] as $security_group) {
      $security_groups[] = $security_group['GroupName'];
    }

    // Termination protection.
    $attribute_result = $awsEc2Service->describeInstanceAttribute([
      'InstanceId' => $instance['InstanceId'],
      'Attribute' => 'disableApiTermination',
    ]);
    $termination_protection = $attribute_result['DisableApiTermination']['Value'];

    // Instance IAM roles.
    $iam_role = NULL;
    if (isset($instance_iam_roles[$instance['InstanceId']])) {
      $iam_role = $instance_iam_roles[$instance['InstanceId']];
    }

    // Use NetworkInterface to look up private ips.  In EC2-VPC,
    // an instance can have more than one private ip.
    $network_interfaces = [];
    $private_ips = FALSE;

    if (isset($instance['NetworkInterfaces'])) {
      $private_ips = $awsEc2Service->getPrivateIps($instance['NetworkInterfaces']);
      foreach ($instance['NetworkInterfaces'] as $interface) {
        $network_interfaces[] = $interface['NetworkInterfaceId'];
      }
    }

    // Get instance types.
    $instance_types = aws_cloud_get_instance_types($cloud_context);
    $entity_id = $awsEc2Service->getEntityId('aws_cloud_instance', 'instance_id', $instance['InstanceId']);
    $cost = $awsEc2Service->calculateInstanceCost($instance, $instance_types);
    // Skip if $entity already exists, by updating 'refreshed' time.
    if (!empty($entity_id)) {
      /* @var \Drupal\aws_cloud\Entity\Ec2\Instance $entity */
      $entity = Instance::load($entity_id);
      $entity->setName($instanceName);
      $entity->setInstanceState($instance['State']['Name']);

      // Set attributes that are available when system starts up.
      $public_ip = NULL;

      if ($private_ips != FALSE) {
        $entity->setPrivateIps($private_ips);
      }
      if (isset($instance['PublicIpAddress'])) {
        $public_ip = $instance['PublicIpAddress'];
      }
      if (isset($instance['PublicDnsName'])) {
        $entity->setPublicDns($instance['PublicDnsName']);
      }
      if (isset($instance['PrivateDnsName'])) {
        $entity->setPrivateDns($instance['PrivateDnsName']);
      }

      $entity->setPublicIp($public_ip);
      $entity->setSecurityGroups(implode(', ', $security_groups));
      $entity->setInstanceType($instance['InstanceType']);
      $entity->setRefreshed($timestamp);
      $entity->setLaunchTime(strtotime($instance['LaunchTime']->__toString()));
      $entity->setTerminationTimestamp($termination_timestamp);
      $entity->setTerminationProtection($termination_protection);
      $entity->setSchedule($schedule);
      $entity->setTags($tags);
      $entity->setIamRole($iam_role);
      $entity->setNetworkInterfaces($network_interfaces);
      $entity->setCost($cost);

      if ($uid != 0) {
        $entity->setOwnerById($uid);
      }
      $entity->save();
    }
    else {
      $entity = Instance::create([
        'cloud_context' => $cloud_context,
        'name' => !empty($instanceName) ? $instanceName : $instance['InstanceId'],
        'account_id' => $instance['reservation_ownerid'],
        'security_groups' => implode(', ', $security_groups),
        'instance_id' => $instance['InstanceId'],
        'instance_type' => $instance['InstanceType'],
        'availability_zone' => $instance['Placement']['AvailabilityZone'],
        'tenancy' => $instance['Placement']['Tenancy'],
        'instance_state' => $instance['State']['Name'],
        'public_dns' => $instance['PublicDnsName'],
        'public_ip' => isset($instance['PublicIpAddress']) ? $instance['PublicIpAddress'] : NULL,
        'private_dns' => $instance['PrivateDnsName'],
        'key_pair_name' => $instance['KeyName'],
        'is_monitoring' => $instance['Monitoring']['State'],
        'vpc_id' => $instance['VpcId'],
        'subnet_id' => $instance['SubnetId'],
        'source_dest_check' => $instance['SourceDestCheck'],
        'ebs_optimized' => $instance['EbsOptimized'],
        'root_device_type' => $instance['RootDeviceType'],
        'root_device' => $instance['RootDeviceName'],
        'image_id' => $instance['ImageId'],
        'placement_group' => $instance['Placement']['GroupName'],
        'virtualization' => $instance['VirtualizationType'],
        'reservation' => $instance['reservation_id'],
        'ami_launch_index' => $instance['AmiLaunchIndex'],
        'host_id' => isset($instance['Placement']['HostId']) ? $instance['Placement']['HostId'] : NULL,
        'affinity' => isset($instance['Placement']['Affinity']) ? $instance['Placement']['Affinity'] : NULL,
        'state_transition_reason' => $instance['StateTransitionReason'],
        'instance_lock' => FALSE,
        'launch_time' => strtotime($instance['LaunchTime']->__toString()),
        'created' => strtotime($instance['LaunchTime']->__toString()),
        'changed' => $timestamp,
        'refreshed' => $timestamp,
        'uid' => $uid,
        'termination_timestamp' => $termination_timestamp,
        'termination_protection' => $termination_protection,
        'schedule' => $schedule,
        'tags' => $tags,
        'iam_role' => $iam_role,
        'cost' => $cost,
      ]);

      if ($private_ips != FALSE) {
        $entity->setPrivateIps($private_ips);
      }
      $entity->setNetworkInterfaces($network_interfaces);
      $entity->save();
    }
  }

  /**
   * Update or create a image entity.
   *
   * @param string $cloud_context
   *   The cloud context.
   * @param array $image
   *   The image array.
   */
  public static function updateImage($cloud_context, array $image) {
    /* @var \Drupal\aws_cloud\Service\AwsEc2ServiceInterface */
    $awsEc2Service = \Drupal::service('aws_cloud.ec2');
    $awsEc2Service->setCloudContext($cloud_context);
    $timestamp = time();

    $block_devices = [];
    foreach ($image['BlockDeviceMappings'] as $block_device) {
      $block_devices[] = $block_device['DeviceName'];
    }

    $uid = $awsEc2Service->getUidTagValue($image, 'image_created_by_uid');
    $entity_id = $awsEc2Service->getEntityId('aws_cloud_image', 'image_id', $image['ImageId']);

    // Skip if $entity already exists, by updating 'refreshed' time.
    if (!empty($entity_id)) {
      $entity = Image::load($entity_id);
      $entity->setRefreshed($timestamp);
      $entity->setVisibility($image['Public']);
      $entity->setStatus($image['State']);

      if ($uid != 0) {
        $entity->setOwnerById($uid);
      }
      $entity->save();
    }
    else {
      $entity = Image::create([
        'cloud_context' => $cloud_context,
        'image_id' => $image['ImageId'],
        'account_id' => $image['OwnerId'],
        'architecture' => $image['Architecture'],
        'virtualization_type' => $image['VirtualizationType'],
        'root_device_type' => $image['RootDeviceType'],
        'root_device_name' => $image['RootDeviceName'],
        'ami_name' => $image['Name'],
        'name' => $image['Name'],
        'kernel_id' => isset($image['KernelId']) ? $image['KernelId'] : '',
        'ramdisk_id' => isset($image['RamdiskId']) ? $image['RamdiskId'] : '',
        'image_type' => $image['ImageType'],
        'product_code' => isset($image['ProductCodes']) ? implode(',', array_column($image['ProductCodes'], 'ProductCode')) : '',
        'source' => $image['ImageLocation'],
        'state_reason' => isset($image['StateReason']) ? $image['StateReason']['Message'] : '',
        'platform' => isset($image['Platform']) ? $image['Platform'] : '',
        'description' => isset($image['Description']) ? $image['Description'] : '',
        'visibility' => $image['Public'],
        'block_devices' => implode(', ', $block_devices),
        'status' => $image['State'],
        'created' => strtotime($image['CreationDate']),
        'changed' => $timestamp,
        'refreshed' => $timestamp,
        'uid' => $uid,
      ]);
      $entity->save();
    }
  }

  /**
   * Update or create a security group entity.
   *
   * @param string $cloud_context
   *   The cloud context.
   * @param array $security_group
   *   The security_group array.
   */
  public static function updateSecurityGroup($cloud_context, array $security_group) {
    /* @var \Drupal\aws_cloud\Service\AwsEc2ServiceInterface */
    $awsEc2Service = \Drupal::service('aws_cloud.ec2');
    $awsEc2Service->setCloudContext($cloud_context);

    $timestamp = time();
    $entity_id = $awsEc2Service->getEntityId('aws_cloud_security_group', 'group_id', $security_group['GroupId']);

    // Skip if $entity already exists, by updating 'refreshed' time.
    if (!empty($entity_id)) {
      /* @var \Drupal\aws_cloud\Entity\Ec2\SecurityGroup $entity */
      $entity = SecurityGroup::load($entity_id);
      $entity->setRefreshed($timestamp);
    }
    else {
      // Create a brand new SecurityGroup entity.
      $entity = SecurityGroup::create([
        'cloud_context' => $cloud_context,
        'name' => !empty($security_group['GroupName']) ? $security_group['GroupName'] : $security_group['GroupId'],
        'group_id' => $security_group['GroupId'],
        'group_name' => $security_group['GroupName'],
        'group_description' => $security_group['Description'],
        'vpc_id' => $security_group['VpcId'],
        'account_id' => $security_group['OwnerId'],
        'created' => $timestamp,
        'changed' => $timestamp,
        'refreshed' => $timestamp,
      ]);
    }

    if (isset($security_group['VpcId']) && !empty($security_group['VpcId'])) {
      // Check if VPC is default.  This involves another API call.
      $vpcs = $awsEc2Service->describeVpcs([
        'VpcIds' => [$security_group['VpcId']],
      ]);
      if ($vpcs['Vpcs']) {
        $default = $vpcs['Vpcs'][0]['IsDefault'];
        $entity->setDefaultVpc($default);
      }
    }

    // Setup the Inbound permissions.
    if (isset($security_group['IpPermissions'])) {
      $awsEc2Service->setupIpPermissions($entity, 'ip_permission', $security_group['IpPermissions']);
    }

    // Setup outbound permissions.
    if (isset($security_group['VpcId']) && isset($security_group['IpPermissionsEgress'])) {
      $awsEc2Service->setupIpPermissions($entity, 'outbound_permission', $security_group['IpPermissionsEgress']);
    }
    $entity->save();
  }

  /**
   * Update or create a network interface entity.
   *
   * @param string $cloud_context
   *   The cloud context.
   * @param array $network_interface
   *   The network interface array.
   */
  public static function updateNetworkInterface($cloud_context, array $network_interface) {
    /* @var \Drupal\aws_cloud\Service\AwsEc2ServiceInterface */
    $awsEc2Service = \Drupal::service('aws_cloud.ec2');
    $awsEc2Service->setCloudContext($cloud_context);
    $timestamp = time();

    // Set up the primary and secondary private ip addresses.
    // Setup the allocation_ids.  The allocation_ids are used during Elastic
    // Ip assignment.
    $primary_private_ip = NULL;
    $secondary_private_ip = NULL;
    $primary_association_id = NULL;
    $secondary_association_id = NULL;
    $public_ips = NULL;

    foreach ($network_interface['PrivateIpAddresses'] as $private_ip_address) {
      if ($private_ip_address['Primary'] == TRUE) {
        $primary_private_ip = $private_ip_address['PrivateIpAddress'];
        if (isset($private_ip_address['Association'])) {
          if (!empty($private_ip_address['Association']['AssociationId'])) {
            $primary_association_id = $private_ip_address['Association']['AssociationId'];
          }
          if (!empty($private_ip_address['Association']['PublicIp'])) {
            $public_ips[] = $private_ip_address['Association']['PublicIp'];
          }
        }
      }
      else {
        $secondary_private_ip = $private_ip_address['PrivateIpAddress'];
        if (isset($private_ip_address['Association'])) {
          if (!empty($private_ip_address['Association']['AssociationId'])) {
            $secondary_association_id = $private_ip_address['Association']['AssociationId'];
          }
          if (!empty($private_ip_address['Association']['PublicIp'])) {
            $public_ips[] = $private_ip_address['Association']['PublicIp'];
          }
        }
      }
    }

    $security_groups = [];
    foreach ($network_interface['Groups'] as $security_group) {
      $security_groups[] = $security_group['GroupName'];
    }

    $entity_id = $awsEc2Service->getEntityId('aws_cloud_network_interface', 'network_interface_id', $network_interface['NetworkInterfaceId']);

    // Skip if $entity already exists, by updating 'refreshed' time.
    if (!empty($entity_id)) {
      /* @var \Drupal\aws_cloud\Entity\Ec2\NetworkInterface $entity */
      $entity = NetworkInterface::load($entity_id);
      $entity->setRefreshed($timestamp);
      $entity->setPrimaryPrivateIp($primary_private_ip);
      $entity->setSecondaryPrivateIp($secondary_private_ip);
      $entity->setAssociationId($primary_association_id);
      $entity->setSecondaryAssociationId($secondary_association_id);
      if ($public_ips != NULL) {
        $public_ips = implode(', ', $public_ips);
      }

      $entity->setPublicIps($public_ips);
      $entity->save();
    }
    else {
      $entity = NetworkInterface::create([
        'cloud_context' => $cloud_context,
        'name' => $network_interface['NetworkInterfaceId'],
        'network_interface_id' => $network_interface['NetworkInterfaceId'],
        'vpc_id' => $network_interface['VpcId'],
        'mac_address' => $network_interface['MacAddress'],
        'security_groups' => implode(', ', $security_groups),
        'status' => $network_interface['Status'],
        'private_dns' => $network_interface['PrivateDnsName'],
        'primary_private_ip' => $primary_private_ip,
        'secondary_private_ips' => $secondary_private_ip,
        'attachment_id' => $network_interface['Attachment']['AttachmentId'],
        'attachment_owner' => $network_interface['Attachment']['InstanceOwnerId'],
        'attachment_status' => $network_interface['Attachment']['Status'],
        'account_id' => $network_interface['OwnerId'],
        'association_id' => $primary_association_id,
        'secondary_association_id' => $secondary_association_id,
        'subnet_id' => $network_interface['SubnetId'],
        'description' => $network_interface['Description'],
        'public_ips' => $public_ips,
        'source_dest_check' => $network_interface['SourceDestCheck'],
        'instance_id' => $network_interface['Attachment']['InstanceId'],
        'device_index' => $network_interface['Attachment']['DeviceIndex'],
        'delete_on_termination' => $network_interface['Attachment']['DeleteOnTermination'],
        'allocation_id' => $network_interface['Association']['AllocationId'],
        'created' => $timestamp,
        'changed' => $timestamp,
        'refreshed' => $timestamp,
      ]);
      $entity->save();
    }
  }

  /**
   * Update or create a elastic ip entity.
   *
   * @param string $cloud_context
   *   The cloud context.
   * @param array $elastic_ip
   *   The elastic ip array.
   */
  public static function updateElasticIp($cloud_context, array $elastic_ip) {
    /* @var \Drupal\aws_cloud\Service\AwsEc2ServiceInterface */
    $awsEc2Service = \Drupal::service('aws_cloud.ec2');
    $awsEc2Service->setCloudContext($cloud_context);
    $timestamp = time();

    $elastic_ip_name = '';

    if (isset($elastic_ip['Tags'])) {
      foreach ($elastic_ip['Tags'] as $tag) {
        if ($tag['Key'] == 'Name') {
          $elastic_ip_name = $tag['Value'];
        }
      }
    }
    // Default to instance_id.
    if (empty($elastic_ip_name)) {
      $elastic_ip_name = $elastic_ip['PublicIp'];
    }

    $entity_id = $awsEc2Service->getEntityId('aws_cloud_elastic_ip', 'public_ip', $elastic_ip['PublicIp']);

    // Skip if $entity already exists, by updating 'refreshed' time.
    if (!empty($entity_id)) {
      $entity = ElasticIp::load($entity_id);

      // Update fields.
      $entity->setName($elastic_ip_name);
      $entity->setInstanceId(!empty($elastic_ip['InstanceId']) ? $elastic_ip['InstanceId'] : '');
      $entity->setNetworkInterfaceId(!empty($elastic_ip['NetworkInterfaceId']) ? $elastic_ip['NetworkInterfaceId'] : '');
      $entity->setPrivateIpAddress(!empty($elastic_ip['PrivateIpAddress']) ? $elastic_ip['PrivateIpAddress'] : '');
      $entity->setNetworkInterfaceOwner(!empty($elastic_ip['NetworkInterfaceOwnerId']) ? $elastic_ip['NetworkInterfaceOwnerId'] : '');
      $entity->setAllocationId(!empty($elastic_ip['AllocationId']) ? $elastic_ip['AllocationId'] : '');
      $entity->setAssociationId(!empty($elastic_ip['AssociationId']) ? $elastic_ip['AssociationId'] : '');
      $entity->setDomain(!empty($elastic_ip['Domain']) ? $elastic_ip['Domain'] : '');

      $entity->setRefreshed($timestamp);
      $entity->save();
    }
    else {
      $entity = ElasticIp::create([
        'cloud_context' => $cloud_context,
        'name' => $elastic_ip_name,
        'public_ip' => $elastic_ip['PublicIp'],
        'instance_id' => !empty($elastic_ip['InstanceId']) ? $elastic_ip['InstanceId'] : '',
        'network_interface_id' => !empty($elastic_ip['NetworkInterfaceId']) ? $elastic_ip['NetworkInterfaceId'] : '',
        'private_ip_address' => !empty($elastic_ip['PrivateIpAddress']) ? $elastic_ip['PrivateIpAddress'] : '',
        'network_interface_owner' => !empty($elastic_ip['NetworkInterfaceOwnerId']) ? $elastic_ip['NetworkInterfaceOwnerId'] : '',
        'allocation_id' => !empty($elastic_ip['AllocationId']) ? $elastic_ip['AllocationId'] : '',
        'association_id' => !empty($elastic_ip['AssociationId']) ? $elastic_ip['AssociationId'] : '',
        'domain' => !empty($elastic_ip['Domain']) ? $elastic_ip['Domain'] : '',
        'created' => $timestamp,
        'changed' => $timestamp,
        'refreshed' => $timestamp,
      ]);
      $entity->save();
    }
  }

  /**
   * Update or create a key pair entity.
   *
   * @param string $cloud_context
   *   The cloud context.
   * @param array $key_pair
   *   The key_pair array.
   */
  public static function updateKeyPair($cloud_context, array $key_pair) {
    /* @var \Drupal\aws_cloud\Service\AwsEc2ServiceInterface */
    $awsEc2Service = \Drupal::service('aws_cloud.ec2');
    $awsEc2Service->setCloudContext($cloud_context);
    $timestamp = time();

    $entity_id = $awsEc2Service->getEntityId('aws_cloud_key_pair', 'key_pair_name', $key_pair['KeyName']);

    if (!empty($entity_id)) {
      $entity = KeyPair::load($entity_id);
      $entity->setRefreshed($timestamp);
      $entity->save();
    }
    else {
      $entity = KeyPair::create([
        'cloud_context' => $cloud_context,
        'key_pair_name' => $key_pair['KeyName'],
        'key_fingerprint' => $key_pair['KeyFingerprint'],
        'created' => $timestamp,
        'changed' => $timestamp,
        'refreshed' => $timestamp,
      ]);
      $entity->save();
    }
  }

  /**
   * Update or create a snapshot entity.
   *
   * @param string $cloud_context
   *   The cloud context.
   * @param array $snapshot
   *   The snapshot array.
   */
  public static function updateSnapshot($cloud_context, array $snapshot) {
    /* @var \Drupal\aws_cloud\Service\AwsEc2ServiceInterface */
    $awsEc2Service = \Drupal::service('aws_cloud.ec2');
    $awsEc2Service->setCloudContext($cloud_context);

    $timestamp = time();
    $name = $awsEc2Service->getTagName($snapshot, $snapshot['SnapshotId']);
    $entity_id = $awsEc2Service->getEntityId('aws_cloud_snapshot', 'snapshot_id', $snapshot['SnapshotId']);
    $uid = $awsEc2Service->getUidTagValue($snapshot, 'snapshot_created_by_uid');

    if (!empty($entity_id)) {
      $entity = Snapshot::load($entity_id);
      $entity->setName($name);
      $entity->setStatus($snapshot['State']);
      $entity->setRefreshed($timestamp);
      if ($uid != 0) {
        $entity->setOwnerById($uid);
      }
      $entity->setCreated(strtotime($snapshot['StartTime']));
      $entity->save();
    }
    else {
      $entity = Snapshot::create([
        'cloud_context' => $cloud_context,
        'name' => $name,
        'snapshot_id' => $snapshot['SnapshotId'],
        'size' => $snapshot['VolumeSize'],
        'description' => $snapshot['Description'],
        'status' => $snapshot['State'],
        'volume_id' => $snapshot['VolumeId'],
        'progress' => $snapshot['Progress'],
        'encrypted' => $snapshot['Encrypted'] == FALSE ? 'Not Encrypted' : 'Encrypted',
        'kms_key_id' => $snapshot['KmsKeyId'],
        'account_id' => $snapshot['OwnerId'],
        'owner_aliases' => $snapshot['OwnerAlias'],
        'state_message' => $snapshot['StateMessage'],
        'created' => strtotime($snapshot['StartTime']),
        'changed' => $timestamp,
        'refreshed' => $timestamp,
        'uid' => $uid,
      ]);
      $entity->save();
    }
  }

  /**
   * Update or create a volume entity.
   *
   * @param string $cloud_context
   *   The cloud context.
   * @param array $volume
   *   The volume array.
   * @param array $snapshot_id_name_map
   *   The snapshot map.
   */
  public static function updateVolume($cloud_context, array $volume, array $snapshot_id_name_map) {
    /* @var \Drupal\aws_cloud\Service\AwsEc2ServiceInterface */
    $awsEc2Service = \Drupal::service('aws_cloud.ec2');
    $awsEc2Service->setCloudContext($cloud_context);

    $timestamp = time();

    $attachments = [];
    foreach ($volume['Attachments'] as $attachment) {
      $attachments[] = $attachment['InstanceId'];
    }

    $name = $awsEc2Service->getTagName($volume, $volume['VolumeId']);
    $entity_id = $awsEc2Service->getEntityId('aws_cloud_volume', 'volume_id', $volume['VolumeId']);
    $uid = $awsEc2Service->getUidTagValue($volume, 'volume_created_by_uid');

    if ($uid == 0) {
      // Inherit the volume uid from the instance that launched it.
      if (count($attachments)) {
        $uid = $awsEc2Service->getInstanceUid($attachments[0]);
      }
    }

    // Skip if $entity already exists, by updating 'refreshed' time.
    if (!empty($entity_id)) {
      /* @var \Drupal\aws_cloud\Entity\Ec2\Volume $entity */
      $entity = Volume::load($entity_id);
      $entity->setName($name);
      $entity->setRefreshed($timestamp);
      $entity->setState($volume['State']);
      $entity->setAttachmentInformation(implode(', ', $attachments));
      $entity->setCreated(strtotime($volume['CreateTime']));
      $entity->setSnapshotId($volume['SnapshotId']);
      $entity->setSnapshotName(empty($volume['SnapshotId'])
        ? ''
        : $snapshot_id_name_map[$volume['SnapshotId']]);

      if ($uid != 0) {
        $entity->setOwnerById($uid);
      }
      $entity->save();
    }
    else {
      $entity = Volume::create([
        'cloud_context' => $cloud_context,
        'name' => $name,
        'volume_id' => $volume['VolumeId'],
        'size' => $volume['Size'],
        'state' => $volume['State'],
        'volume_status' => $volume['VirtualizationType'],
        'attachment_information' => implode(', ', $attachments),
        'volume_type' => $volume['VolumeType'],
        'iops' => $volume['Iops'],
        'snapshot_id' => $volume['SnapshotId'],
        'snapshot_name' => empty($volume['SnapshotId']) ? '' : $snapshot_id_name_map[$volume['SnapshotId']],
        'availability_zone' => $volume['AvailabilityZone'],
        'encrypted' => $volume['Encrypted'],
        'kms_key_id' => $volume['KmsKeyId'],
        'created' => strtotime($volume['CreateTime']),
        'changed' => $timestamp,
        'refreshed' => $timestamp,
        'uid' => $uid,
      ]);
      $entity->save();
    }
  }

}
