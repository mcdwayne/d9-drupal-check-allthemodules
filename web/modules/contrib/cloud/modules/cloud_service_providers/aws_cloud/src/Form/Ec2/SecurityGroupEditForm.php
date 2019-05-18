<?php

namespace Drupal\aws_cloud\Form\Ec2;

use Drupal\Core\Form\FormStateInterface;
use Drupal\aws_cloud\Entity\Ec2\SecurityGroup;
use Drupal\aws_cloud\Plugin\Field\FieldType\IpPermission;

use Aws\Result;

/**
 * Form controller for the CloudScripting entity edit forms.
 *
 * @ingroup aws_cloud
 */
class SecurityGroupEditForm extends AwsCloudContentForm {

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state, $cloud_context = '') {
    $this->awsEc2Service->setCloudContext($cloud_context);

    /* @var $entity \Drupal\aws_cloud\Entity\Ec2\SecurityGroup */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    $weight = -50;

    $form['security_group'] = [
      '#type' => 'details',
      '#title' => $this->t('Security Group'),
      '#open' => TRUE,
      '#weight' => $weight++,
    ];

    $form['security_group']['name'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Name'),
      '#maxlength'     => 255,
      '#size'          => 60,
      '#default_value' => $entity->label(),
      '#required'      => TRUE,
    ];

    $form['security_group']['group_id'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('ID')),
      '#markup'        => $entity->getGroupId(),
    ];

    $form['security_group']['group_name'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Security Group Name')),
      '#markup'        => $entity->getGroupName(),
    ];

    $form['security_group']['description'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('Description')),
      '#markup'        => $entity->getDescription(),
    ];

    $form['security_group']['vpc_id'] = [
      '#type'          => 'item',
      '#title'         => $this->getItemTitle($this->t('VPC ID')),
      '#markup'        => $entity->getVpcId(),
    ];

    // Put all rules into HTML5 details.
    $form['rules'] = [
      '#type' => 'details',
      '#title' => $this->t('Rules'),
      '#open' => FALSE,
      '#weight' => $weight++,
    ];

    $form['rules'][] = $form['ip_permission'];

    if (!empty($entity->getVpcId())) {
      $form['rules'][] = $form['outbound_permission'];

    }
    unset($form['ip_permission']);
    unset($form['outbound_permission']);

    $this->addOthersFieldset($form, $weight++);

    $form['#attached']['library'][] = 'aws_cloud/aws_cloud_security_groups';

    if (isset($form['actions'])) {
      $form['actions']['submit']['#weight'] = $weight++;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Call copyFormItemValues() to ensure the form array is intact.
    $this->copyFormItemValues($form);

    $this->trimTextfields($form, $form_state);

    $entity = $this->entity;

    $this->awsEc2Service->setCloudContext($entity->getCloudContext());
    if ($entity->save()) {
      // Fetch the most up to date security group data from Ec2.
      $existing_group = $this->awsEc2Service->describeSecurityGroups([
        'GroupIds' => [$entity->getGroupId()],
      ]);

      // Update the inbound permissions.
      $this->updateInboundPermissions($existing_group);

      // Update the outbound permissions.  This only applies to
      // VPC security groups.
      if (!empty($entity->getVpcId())) {
        $this->updateOutboundPermissions($existing_group);
      }

      // Have the system refresh the security group.
      $this->awsEc2Service->updateSecurityGroups([
        'GroupIds' => [$this->entity->getGroupId()],
      ], FALSE);

      // Check api calls, see if the permissions updates were
      // successful or not.
      $this->validateAuthorize($entity);
    }
    else {
      $this->messenger->addError($this->t('Unable to update security group.'));
    }

    $form_state->setRedirect('entity.aws_cloud_security_group.canonical', [
      'cloud_context' => $entity->getCloudContext(),
      'aws_cloud_security_group' => $entity->id(),
    ]);
  }

  /**
   * Helper method to update the inbound permissions.
   *
   * @param \Aws\Result $existing_group
   *   Existing group.
   */
  private function updateInboundPermissions(Result $existing_group) {
    $permissions = [];

    if (isset($existing_group['SecurityGroups']) && isset($existing_group['SecurityGroups'][0]['IpPermissions'])) {
      $security_group = $this->formatIpPermissionForRevoke($existing_group['SecurityGroups'][0]['IpPermissions']);

      if (count($security_group) > 0) {
        // Delete the existing permissions.
        $this->awsEc2Service->revokeSecurityGroupIngress([
          'GroupId' => $this->entity->getGroupId(),
          'IpPermissions' => $security_group,
        ]);
      }
    }

    // Setup the ip_permissions array.
    $iterator = $this->entity->getIpPermission()->getIterator();
    while ($iterator->valid()) {
      // Add the permission to the IpPerimssions objects.
      $permissions['IpPermissions'][] = $this->formatIpPermissionForAuthorize($iterator->current());
      $iterator->next();
    }

    // Re-add the permissions if they are specified.
    if (isset($permissions['IpPermissions']) && count($permissions['IpPermissions'])) {
      // Setup permissions array for AuthorizeSecurityGroupIngress.
      $permissions['GroupId'] = $this->entity->getGroupId();
      $this->awsEc2Service->authorizeSecurityGroupIngress($permissions);
    }
  }

  /**
   * Helper method to update the outbound permissions.
   *
   * @param \Aws\Result $existing_group
   *   Existing group.
   */
  private function updateOutboundPermissions(Result $existing_group) {
    $permissions = [];
    if (isset($existing_group['SecurityGroups']) && isset($existing_group['SecurityGroups'][0]['IpPermissionsEgress'])) {
      $security_group = $this->formatIpPermissionForRevoke($existing_group['SecurityGroups'][0]['IpPermissionsEgress']);

      if (count($security_group) > 0) {
        // Delete the existing egress permissions.
        $this->awsEc2Service->revokeSecurityGroupEgress([
          'GroupId' => $this->entity->getGroupId(),
          'IpPermissions' => $security_group,
        ]);
      }
      // Setup the ip_permissions array.
      $iterator = $this->entity->getOutboundPermission()->getIterator();
      while ($iterator->valid()) {
        // Add the permission to the IPPerimssions objects.
        $permissions['IpPermissions'][] = $this->formatIpPermissionForAuthorize($iterator->current());
        $iterator->next();
      }

      if (isset($permissions['IpPermissions']) && count($permissions['IpPermissions'])) {
        // Setup permissions array for AuthorizeSecurityGroupIngress.
        $permissions['GroupId'] = $this->entity->getGroupId();
        $this->awsEc2Service->authorizeSecurityGroupEgress($permissions);
      }
    }
  }

  /**
   * Format the IpPermission object.
   *
   * Format returned from the DescribeSecurityGroup
   * EC2 api call.  This method unset array objects that have no values.
   *
   * @param array $security_group
   *   The security group.
   *
   * @return array
   *   Formatted IpPermission object.
   */
  private function formatIpPermissionForRevoke(array $security_group) {
    foreach ($security_group as $key => $group) {
      if (!isset($group['IpRanges']) || count($group['IpRanges']) == 0) {
        unset($security_group[$key]['IpRanges']);
      }
      if (!isset($group['IpV6Ranges']) || count($group['Ipv6Ranges']) == 0) {
        unset($security_group[$key]['Ipv6Ranges']);
      }
      if (!isset($group['UserIdGroupPairs']) || count($group['UserIdGroupPairs']) == 0) {
        unset($security_group[$key]['UserIdGroupPairs']);
      }
      if (!isset($group['PrefixListIds']) || count($group['PrefixListIds']) == 0) {
        unset($security_group[$key]['PrefixListIds']);
      }
      if (isset($group['UserIdGroupPairs']) && count($group['UserIdGroupPairs']) > 0) {
        // Loop them and unset GroupName.
        foreach ($group['UserIdGroupPairs'] as $pair_keys => $pairs) {
          unset($security_group[$key]['UserIdGroupPairs'][$pair_keys]['GroupName']);
        }
      }
    }
    return $security_group;
  }

  /**
   * Format the IpPermission object.
   *
   * Format the IpPermission object for use with
   * the AuthorizeSecurityGroup[Ingress and Egress] EC2 api call.
   *
   * @param \Drupal\aws_cloud\Plugin\Field\FieldType\IpPermission $ip_permission
   *   The ip permission object.
   *
   * @return array
   *   The permission.
   */
  private function formatIpPermissionForAuthorize(IpPermission $ip_permission) {
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
   * Verify the authorize call was successful.
   *
   * Since Ec2 does not return any error codes from any of the authorize
   * api calls, the only way to verify is to count the permissions array
   * from the current entity,  and the entity that is newly updated from
   * the updateSecurityGroups API call.
   *
   * @param \Drupal\aws_cloud\Entity\Ec2\SecurityGroup $group
   *   The security group.
   */
  private function validateAuthorize(SecurityGroup $group) {
    /* @var \Drupal\aws_cloud\Entity\Ec2\SecurityGroup $updated_group */
    $updated_group = SecurityGroup::load($group->id());

    if ($group->getIpPermission()->count() != $updated_group->getIpPermission()->count()) {
      $this->messenger->addError(
        $this->t('Error updating inbound permissions for security group @name', [
          '@name' => $this->entity->label(),
        ])
      );
    }

    if (!empty($group->getVpcId())) {
      if ($group->getOutboundPermission()->count() != $updated_group->getOutboundPermission()->count()) {
        $this->messenger->addError(
          $this->t('Error updating outbound permissions for security group @name', [
            '@name' => $this->entity->label(),
          ])
        );
      }
    }

    if (count($this->messenger->messagesByType('error')) == 0) {
      // No errors, success.
      $this->messenger->addMessage(
        $this->t('The AWS Cloud Security Group "@name" has been saved.', [
          '@name' => $this->entity->label(),
        ])
      );
    }

  }

}
