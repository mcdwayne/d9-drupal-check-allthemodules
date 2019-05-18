<?php

namespace Drupal\aws_cloud\Service;

use Drupal\aws_cloud\Entity\Ec2\SecurityGroup;

/**
 * Interface AwsEc2ServiceInterface.
 */
interface AwsEc2ServiceInterface {

  /**
   * Set the cloud context.
   *
   * @param string $cloud_context
   *   Cloud context string.
   */
  public function setCloudContext($cloud_context);

  /**
   * Calls the Ec2 API endpoint AssociateAddress.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return mixed
   *   An array of results or NULL if there is an error.
   */
  public function associateAddress(array $params = []);

  /**
   * Calls the Ec2 API endpoint AuthorizeSecurityGroupIngress.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return mixed
   *   An array of results or NULL if there is an error.
   */
  public function authorizeSecurityGroupIngress(array $params = []);

  /**
   * Calls the Ec2 API endpoint AuthorizeSecurityGroupEgress.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return mixed
   *   An array of results or NULL if there is an error.
   */
  public function authorizeSecurityGroupEgress(array $params = []);

  /**
   * Calls the Ec2 API endpoint AllocateAddress.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   Array of ElasticIps or NULL if there is an error.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function allocateAddress(array $params = []);

  /**
   * Calls the Ec2 API endpoint AssociateIamInstanceProfile.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   An IamInstanceProfileAssociation or NULL if there is an error.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function associateIamInstanceProfile(array $params = []);

  /**
   * Calls the Ec2 API endpoint DisassociateIamInstanceProfile.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   An IamInstanceProfileAssociation or NULL if there is an error.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function disassociateIamInstanceProfile(array $params = []);

  /**
   * Calls the Ec2 API endpoint ReplaceIamInstanceProfileAssociation.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   An IamInstanceProfileAssociation or NULL if there is an error.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function replaceIamInstanceProfileAssociation(array $params = []);

  /**
   * Calls the Ec2 API endpoint DescribeIamInstanceProfileAssociations.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   Array of IamInstanceProfileAssociation or NULL if there is an error.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function describeIamInstanceProfileAssociations(array $params = []);

  /**
   * Calls the Ec2 API endpoint CreateImage.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   Array of Image or NULL if there is an error.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function createImage(array $params = []);

  /**
   * Calls the Ec2 API endpoint Create Key Pair.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   Array of KeyPair or NULL if there is an error.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function createKeyPair(array $params = []);

  /**
   * Calls the Ec2 API endpoint Create Network Interface.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   Array of NetworkInterface or NULL if there is an error.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function createNetworkInterface(array $params = []);

  /**
   * Calls the Ec2 API endpoint Create Volume.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   Array of Volume or NULL if there is an error.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function createVolume(array $params = []);

  /**
   * Calls the Ec2 API endpoint Create Snapshot.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   Array of Snapshot or NULL if there is an error.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function createSnapshot(array $params = []);

  /**
   * Calls the Ec2 API endpoint Create Security Group.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   Array of SecurityGroup or NULL if there is an error.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function createSecurityGroup(array $params = []);

  /**
   * Calls the Ec2 API endpoint Create Tags.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function createTags(array $params = []);

  /**
   * Calls the Ec2 API endpoint Delete Tags.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function deleteTags(array $params = []);

  /**
   * Calls the Ec2 API endpoint DeregisterImage.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function deregisterImage(array $params = []);

  /**
   * Calls the Ec2 API endpoint DescribeInstances.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   Array of Instances or NULL if there is an error.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function describeInstances(array $params = []);

  /**
   * Calls the Ec2 API endpoint DescribeInstanceAttribute.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   Array of Instances or NULL if there is an error.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function describeInstanceAttribute(array $params = []);

  /**
   * Calls the Ec2 API endpoint DescribeImages.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   Array of Images or NULL if there is an error.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function describeImages(array $params = []);

  /**
   * Calls the Ec2 API endpoint DescribeSecurityGroups.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   Array of SecurityGroups or NULL if there is an error.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function describeSecurityGroups(array $params = []);

  /**
   * Calls the Ec2 API endpoint DescribeNetworkInterfaces.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   Array of NetworkInterfaceList or NULL if there is an error.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function describeNetworkInterfaces(array $params = []);

  /**
   * Calls the Ec2 API endpoint DescribeAccountAttributes.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   Array of Addresses or NULL if there is an error.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function describeAccountAttributes(array $params = []);

  /**
   * Calls the Ec2 API endpoint DescribeAddresses.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   Array of Addresses or NULL if there is an error.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function describeAddresses(array $params = []);

  /**
   * Calls the Ec2 API endpoint DescribeSnapshots.
   *
   * Only snapshots restorable by the user are returned.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   Array of Snapshots or NULL if there is an error.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function describeSnapshots(array $params = []);

  /**
   * Calls the Ec2 API endpoint DescribeKeyPairs.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   Array of KeyPairs or NULL if there is an error.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function describeKeyPairs(array $params = []);

  /**
   * Calls the Ec2 API endpoint DescribeVolumes.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   Array of Volumes or NULL if there is an error.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function describeVolumes(array $params = []);

  /**
   * Calls the Ec2 API endpoint DescribeAvailabilityZones.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   Array of AvailabilityZones or NULL if there is an error.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function describeAvailabilityZones(array $params = []);

  /**
   * Calls the Ec2 API endpoint DescribeVpcs.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   Array of VPCs or NULL if there is an error.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function describeVpcs(array $params = []);

  /**
   * Calls the Ec2 API endpoint DescribeSubnets..
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   Array of Subnets or NULL if there is an error.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function describeSubnets(array $params = []);

  /**
   * Get regions.
   *
   * @return array
   *   Array of regions.
   */
  public function getRegions();

  /**
   * Get endpoint urls.
   *
   * @return array
   *   Array of region endpoint urls.
   */
  public function getEndpointUrls();

  /**
   * Calls the Ec2 API endpoint ImportKeyPair.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   Array of KeyPair or NULL if there is an error.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function importKeyPair(array $params = []);

  /**
   * Calls the Ec2 API endpoint RebootInstances.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   This call does not return anything.
   */
  public function rebootInstances(array $params = []);

  /**
   * Calls the Ec2 API endpoint TerminateInstances.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   Array of Instance or NULL if there is an error.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function terminateInstance(array $params = []);

  /**
   * Calls the Ec2 API endpoint DeleteSecurityGroup.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function deleteSecurityGroup(array $params = []);

  /**
   * Calls the Ec2 API endpoint DeleteNetworkInterface.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function deleteNetworkInterface(array $params = []);

  /**
   * Calls the Ec2 API endpoint ReleaseAddress.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function releaseAddress(array $params = []);

  /**
   * Calls the Ec2 API endpoint DeleteKeyPair.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function deleteKeyPair(array $params = []);

  /**
   * Calls the Ec2 API endpoint DeleteVolume.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function deleteVolume(array $params = []);

  /**
   * Calls the Ec2 API endpoint DeleteSnapshot.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function deleteSnapshot(array $params = []);

  /**
   * Calls the Ec2 API endpoint DisassociateAddress.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function disassociateAddress(array $params = []);

  /**
   * Calls the Ec2 API endpoint RunInstances.
   *
   * @param array $params
   *   Parameters array to send to API.
   * @param array $tags
   *   Optional tags to be sent during the runInstance call.
   *
   * @return array
   *   Array of Instances or NULL if there is an error.
   *
   * @throws \Drupal\aws_cloud\Service\AwsEc2ServiceException
   *   If the $params is empty or Ec2 Client is null.
   */
  public function runInstances(array $params = [], array $tags = []);

  /**
   * Calls the Ec2 API RevokeSecurityGroupIngress.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return mixed
   *   An array of results.
   */
  public function revokeSecurityGroupIngress(array $params = []);

  /**
   * Calls the Ec2 API RevokeSecurityGroupEgress.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return mixed
   *   An array of results.
   */
  public function revokeSecurityGroupEgress(array $params = []);

  /**
   * Update the Ec2 Instances.
   *
   * Delete old Instance entities, query the api for updated entities and store
   * them as Instance entities.
   *
   * @param array $params
   *   Optional parameters array.
   * @param bool $clear
   *   TRUE to clear stale security groups.
   *
   * @return bool
   *   indicates success so failure.
   */
  public function updateInstances(array $params = [], $clear = TRUE);

  /**
   * Update the Ec2 Images.
   *
   * Delete old Images entities, query the api
   * for updated entities and store them as Images entities.
   *
   * @param array $params
   *   Optional parameters array.
   * @param bool $clear
   *   TRUE to delete images entities before importing.
   *
   * @return bool|int
   *   FALSE if nothing is updated.  Number of images imported returned as
   *   integer if successful.
   */
  public function updateImages(array $params = [], $clear = FALSE);

  /**
   * Update the Ec2 Security Groups.
   *
   * Delete old Security Groups entities, query the api for updated entities and
   * store them as Security Groups entities.
   *
   * @params array $params
   *   Optional parameters array
   * @params bool $clear
   *   TRUE to clear stale security groups
   *
   * @return bool
   *   Indicates success so failure.
   */
  public function updateSecurityGroups(array $params = [], $clear = TRUE);

  /**
   * Update the Ec2 Network Interfaces.
   *
   * Delete old Network Interfaces entities, query the api for updated entities
   * and store them as Network Interfaces entities.
   *
   * @return bool
   *   indicates success so failure.
   */
  public function updateNetworkInterfaces();

  /**
   * Update the Ec2 Elastic Ips.
   *
   * Delete old Network Interfaces entities, query the api for updated entities
   * and store them as Network Interfaces entities.
   *
   * @return bool
   *   indicates success so failure.
   */
  public function updateElasticIp();

  /**
   * Update the Ec2 Key Pairs.
   *
   * Delete old Key Pairs entities,
   * query the api for updated entities and store them as Key Pairs entities.
   *
   * @return bool
   *   indicates success so failure.
   */
  public function updateKeyPairs();

  /**
   * Update the Ec2 Volumes.
   *
   * Delete old Volumes entities,
   * query the api for updated entities and store them as Volumes entities.
   *
   * @return bool
   *   indicates success so failure.
   */
  public function updateVolumes();

  /**
   * Update the Ec2 snapshots.
   *
   * Delete old snapshots entities,
   * query the api for updated entities and store them as snapshots entities.
   *
   * @return bool
   *   indicates success so failure.
   */
  public function updateSnapshots();

  /**
   * Method gets all the availability zones in a particular cloud context.
   *
   * @return array
   *   Array of availability zones.
   */
  public function getAvailabilityZones();

  /**
   * Method gets all the VPCs in a particular cloud context.
   *
   * @return array
   *   Array of vpcs.
   */
  public function getVpcs();

  /**
   * Method to clear all entities out of the system.
   */
  public function clearAllEntities();

  /**
   * Stops ec2 instance given an instance array.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   Array of Instances or NULL if there is an error.
   */
  public function stopInstances(array $params = []);

  /**
   * Start ec2 instance given an instance array.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   Array of Instances or NULL if there is an error.
   */
  public function startInstances(array $params = []);

  /**
   * Modifies the specified attribute of a instance.
   *
   * @param array $params
   *   Parameters array to send to API.
   */
  public function modifyInstanceAttribute(array $params = []);

  /**
   * Attaches an EBS volume.
   *
   * Attaches an EBS Volume to a running or stopped
   * instance and exposes it to the instance with the
   * specified device name.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   Array of VolumeAttachment or NULL if there is an error.
   */
  public function attachVolume(array $params = []);

  /**
   * Detaches an EBS volume.
   *
   * @param array $params
   *   Parameters array to send to API.
   *
   * @return array
   *   Array of VolumeAttachment or NULL if there is an error.
   */
  public function detachVolume(array $params = []);

  /**
   * Retrieves the supported platforms supported by a particular ec2 account.
   *
   * @return array
   *   An array of supported accounts.
   */
  public function getSupportedPlatforms();

  /**
   * Helper method to get the name of aws object.
   *
   * @param array $aws_obj
   *   Array of aws pbject.
   * @param string $default_value
   *   Default value of tag name.
   *
   * @return string
   *   Tag name.
   */
  public function getTagName(array $aws_obj, $default_value);

  /**
   * Helper method to get the map of snapshot ID and name.
   *
   * @param array $volumes
   *   Array of volumes.
   *
   * @return array
   *   Map of snapshots.
   */
  public function getSnapshotIdNameMap(array $volumes);

  /**
   * Helper method to load an entity using parameters.
   *
   * @param string $entity_type
   *   Entity Type.
   * @param string $id_field
   *   Entity id field.
   * @param string $id_value
   *   Entity id value.
   *
   * @return int
   *   Entity id.
   */
  public function getEntityId($entity_type, $id_field, $id_value);

  /**
   * Helper function to parse drupal uid value out of the tags array.
   *
   * @param array $tags_array
   *   The tags array.
   * @param string $key
   *   The uid key.
   *
   * @return int
   *   Drupal uid.
   */
  public function getUidTagValue(array $tags_array, $key);

  /**
   * Helper function to get an instance's uid.
   *
   * @param string $instance_id
   *   The instance_id to load.
   *
   * @return int
   *   The uid of the instance.
   */
  public function getInstanceUid($instance_id);

  /**
   * Helper function to loop the network interfaces.
   *
   * Also creates a comma delimited string of private ips. Function returns
   * false if no private ips found.
   *
   * @param array $network_interfaces
   *   Array of network interfaces from the EC2 DescribeInstance api.
   *
   * @return string|false
   *   Imploded string or FALSE if no private ips round.
   */
  public function getPrivateIps(array $network_interfaces);

  /**
   * Setup the ipermission field given the inbound security group array.
   *
   * The array comes from DescribeSecurityGroup EC2 api call.
   *
   * @param array $ec2_permission
   *   An array object of Ec2 permission.
   *
   * @return array
   *   An array of \Drupal\Core\Field\FieldItemInterface.
   */
  public function setupIpPermissionObject(array $ec2_permission);

  /**
   * Setup IP Permisssions.
   *
   * @param \Drupal\aws_cloud\Entity\Ec2\SecurityGroup $security_group
   *   The security group entity.
   * @param string $field
   *   Field to used for lookup.
   * @param array $ec2_permissions
   *   Permissions array from Ec2.
   */
  public function setupIpPermissions(SecurityGroup &$security_group, $field, array $ec2_permissions);

  /**
   * Calculate the cost of a instance.
   *
   * @param array $instance
   *   The instance.
   * @param array $instance_types
   *   All instance types.
   *
   * @return float
   *   Cost of the instance.
   */
  public function calculateInstanceCost(array $instance, array $instance_types);

}
