<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\Core\Form\FormStateInterface;
use Drupal\aws_cloud\Entity\Ec2\SecurityGroup;
use Drupal\aws_cloud\Plugin\Field\FieldType\IpPermission;

/**
 * Provides a form for revoking an individual permission.
 */
class SecurityGroupRevokeForm extends AwsDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $type = $this->getRequest()->query->get('type');
    return t('Are you sure you want to revoke the following @type permission?',
      [
        '@type' => $type == 'outbound_permission' ? 'outbound' : 'inbound',
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Revoke');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $output = '';
    $permission = $this->getPermission($this->getRequest()->query->get('type'), $this->getRequest()->query->get('position'), $this->entity);

    if ($permission) {
      $source = $permission->source;
      $output .= '<ul>';
      $output .= '<li>Ip protocol: ' . $permission->ip_protocol . '</li>';
      $output .= '<li>From port: ' . $permission->from_port . '</li>';
      $output .= '<li>To port: ' . $permission->to_port . '</li>';
      $output .= '<li>Source: ' . $source . '</li>';
      if ($source == 'ip4') {
        $output .= '<li>CIDR Ip: ' . $permission->cidr_ip . '</li>';
      }
      elseif ($source == 'ip6') {
        $output .= '<li>CIDR IPv6: ' . $permission->cidr_ip_v6 . '</li>';
      }
      else {
        $output .= '<li>Group Name: ' . $permission->group_name . '</li>';
        $output .= '<li>Group Id: ' . $permission->group_id . '</li>';
      }
      $output .= '</ul>';
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $this->awsEc2Service->setCloudContext($entity->getCloudContext());
    $type = $this->getRequest()->query->get('type');
    $position = $this->getRequest()->query->get('position');
    $permission = $this->getPermission($type, $position, $entity);
    if ($permission != FALSE) {
      $perm_array = [
        'GroupId' => $entity->getGroupId(),
      ];

      if ($type == 'outbound_permission') {
        $perm_array['IpPermissions'][] = $this->formatIpPermissionForRevoke($permission);
        $this->awsEc2Service->revokeSecurityGroupEgress($perm_array);
      }
      else {
        $perm_array['IpPermissions'][] = $this->formatIpPermissionForRevoke($permission);
        $this->awsEc2Service->revokeSecurityGroupIngress($perm_array);
      }

      // Have the system refresh the security group.
      $this->awsEc2Service->updateSecurityGroups([
        'GroupIds' => [$entity->getGroupId()],
      ], FALSE);

      if ($this->validateRevoke($type, $entity)) {
        $this->messenger->addMessage($this->t('Permission revoked'));
      }
      else {
        $this->messenger->addError($this->t('Permission not revoked'));
      }

      $form_state->setRedirect('entity.aws_cloud_security_group.canonical', [
        'cloud_context' => $entity->getCloudContext(),
        'aws_cloud_security_group' => $entity->id(),
      ]);
    }
  }

  /**
   * Verify that the revoke was successful.
   *
   * Since Ec2 does not return any error codes in the revoke api calls, the
   * only way to verify is to count the permissions array from the current
   * entity, and the entity that is newly updated from the updateSecurityGroups
   * API call.
   *
   * @param string $type
   *   The permission type.
   * @param \Drupal\aws_cloud\Entity\Ec2\SecurityGroup $group
   *   The security group.
   *
   * @return bool
   *   True or false.
   */
  private function validateRevoke($type, SecurityGroup $group) {
    $verified = FALSE;
    /* @var \Drupal\aws_cloud\Entity\Ec2\SecurityGroup $updated_group */
    $updated_group = SecurityGroup::load($group->id());
    if ($type == 'outbound_permission') {
      if ($group->getIpPermission()->count() == $updated_group->getIpPermission()->count()) {
        $verified = TRUE;
      }
    }
    else {
      if ($group->getOutboundPermission()->count() == $updated_group->getOutboundPermission()->count()) {
        $verified = TRUE;
      }
    }
    return $verified;
  }

  /**
   * Format the ip permission array for use with revoke security group.
   *
   * @param \Drupal\aws_cloud\Plugin\Field\FieldType\IpPermission $ip_permission
   *   IP Permission object.
   *
   * @return array
   *   Permissions array.
   */
  private function formatIpPermissionForRevoke(IpPermission $ip_permission) {
    $permission = [
      'FromPort' => (int) $ip_permission->from_port,
      'ToPort' => (int) $ip_permission->to_port,
      'IpProtocol' => $ip_permission->ip_protocol,
    ];
    if ($ip_permission->source == 'ip4') {
      $permission['IpRanges'][]['CidrIp'] = $ip_permission->cidr_ip;
    }
    else {
      if ($ip_permission->source == 'ip6') {
        $permission['Ipv6Ranges'][]['CidrIpv6'] = $ip_permission->cidr_ip_v6;
      }
      else {
        // Use GroupID if nondefault VPC or EC2-Classic.
        // For other permissions, use Group Name.
        $vpc_id = $this->entity->getVpcId();
        if ($this->entity->isDefaultVpc() == TRUE || !isset($vpc_id)) {
          $group['GroupName'] = $ip_permission->group_name;
        }
        else {
          $group['GroupId'] = $ip_permission->group_id;
        }
        $group['UserId'] = $ip_permission->user_id;
        $group['PeeringStatus'] = $ip_permission->peering_status;
        $group['VpcId'] = $ip_permission->vpc_id;
        $group['VpcPeeringConnectionId'] = $ip_permission->peering_connection_id;

        $permission['UserIdGroupPairs'][] = $group;
      }
    }
    return $permission;
  }

  /**
   * Get an individual permission from outbound or inbound permission.
   *
   * @param string $type
   *   Outbound_permission or ip_permission.
   * @param int $position
   *   Position in the multivalued list of permissions.
   * @param \Drupal\aws_cloud\Entity\Ec2\SecurityGroup $entity
   *   Security group entity.
   *
   * @return bool
   *   FALSE or permission object.
   */
  private function getPermission($type, $position, SecurityGroup $entity) {
    $permission = FALSE;
    switch ($type) {
      case 'outbound_permission':
        $permission = $entity->getOutboundPermission()->get($position);
        break;

      case 'ip_permission':
        $permission = $entity->getIpPermission()->get($position);
        break;

      default:
        break;
    }
    return $permission;
  }

}
