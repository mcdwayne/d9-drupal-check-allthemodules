<?php

namespace Drupal\aws_cloud\Plugin\Validation\Constraint;

use Drupal\aws_cloud\Plugin\Field\FieldType\IpPermission;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\TypedData\Validation\TypedDataAwareValidatorTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates each permission field.
 */
class IpPermissionDataConstraintValidator extends ConstraintValidator {

  use TypedDataAwareValidatorTrait;

  /**
   * {@inheritdoc}
   */
  public function validate($item, Constraint $constraint) {
    /* @var \Drupal\aws_cloud\Plugin\Field\FieldType\IpPermission $item */
    $source = $item->getSource();

    // Validate to and from ports.
    $this->validatePorts($item, $constraint);

    // Validate ip/ipv6 or group configurations.
    if ($source == 'ip4') {
      $this->validateCidrIp($item, $constraint);
    }
    elseif ($source == 'ip6') {
      $this->validateCidrIpv6($item, $constraint);
    }
    else {
      $this->validateGroup($item, $constraint);
    }
  }

  /**
   * Validate to and from port rules.
   *
   * @param \Drupal\aws_cloud\Plugin\Field\FieldType\IpPermission $ip_permission
   *   IP Permission object.
   * @param \Symfony\Component\Validator\Constraint $constraint
   *   Constraint object.
   */
  private function validatePorts(IpPermission $ip_permission, Constraint $constraint) {
    $to_port = $ip_permission->getToPort();
    $from_port = $ip_permission->getFromPort();

    if (!is_numeric($from_port)) {
      $this->context->addViolation($constraint->fromPortNotNumeric, [
        '%value' => $from_port,
        '@field_name' => 'from_port',
      ]);
    }
    if (!is_numeric($to_port)) {
      $this->context->addViolation($constraint->toPortNotNumeric, [
        '%value' => $to_port,
        '@field_name' => 'to_port',
      ]);
    }
    // Validate if from_port is less than to_port.
    if ($from_port > $to_port) {
      $this->context->addViolation($constraint->toPortGreater, [
        '%value' => $from_port,
        '@field_name' => 'from_port',
      ]);
      $this->context->addViolation($constraint->toPortGreater, [
        '%value' => $to_port,
        '@field_name' => 'to_port',
      ]);
    }
  }

  /**
   * Validate cidr_ipv6 addresses.
   *
   * @param \Drupal\aws_cloud\Plugin\Field\FieldType\IpPermission $ip_permission
   *   IP Permission object.
   * @param \Symfony\Component\Validator\Constraint $constraint
   *   Constraint object.
   */
  private function validateCidrIpv6(IpPermission $ip_permission, Constraint $constraint) {
    $cidr_ipv6 = $ip_permission->getCidrIpv6();
    if (empty($cidr_ipv6)) {
      $this->context->addViolation($constraint->ip6IsEmpty, [
        '%value' => $cidr_ipv6,
        '@field_name' => 'cidr_ipv6',
      ]);
    }
    else {
      // Validate ip6.
      if (!$this->validateCidr($cidr_ipv6)) {
        $this->context->addViolation($constraint->ip6Value, [
          '%value' => $cidr_ipv6,
          '@field_name' => 'cidr_ipv6',
        ]);
      }
    }
  }

  /**
   * Validate cidr_ip addresses.
   *
   * @param \Drupal\aws_cloud\Plugin\Field\FieldType\IpPermission $ip_permission
   *   IP Permission object.
   * @param \Symfony\Component\Validator\Constraint $constraint
   *   Constraint object.
   */
  private function validateCidrIp(IpPermission $ip_permission, Constraint $constraint) {
    $cidr_ip = $ip_permission->getCidrIp();
    if (empty($cidr_ip)) {
      $this->context->addViolation($constraint->ip4IsEmpty, [
        '%value' => $cidr_ip,
        '@field_name' => 'cidr_ip',
      ]);
    }
    else {
      // Validate ip4.
      if (!$this->validateCidr($cidr_ip)) {
        $this->context->addViolation($constraint->ip4Value, [
          '%value' => $cidr_ip,
          '@field_name' => 'cidr_ip',
        ]);
      }
    }
  }

  /**
   * Validate group id/name configuration.
   *
   * @param \Drupal\aws_cloud\Plugin\Field\FieldType\IpPermission $ip_permission
   *   IP Permission object.
   * @param \Symfony\Component\Validator\Constraint $constraint
   *   Constraint object.
   */
  private function validateGroup(IpPermission $ip_permission, Constraint $constraint) {
    // Group id or name.
    $security_group = $this->getSecurityGroupEntity();
    if ($security_group != FALSE) {
      /* @var \Drupal\aws_cloud\Entity\Ec2\SecurityGroup $security_group */
      $vpc_id = $security_group->getVpcId();
      if ($security_group->isDefaultVpc() || !isset($vpc_id)) {
        $group_name = $ip_permission->getGroupName();
        if (empty($group_name)) {
          // Check that group_name is not empty.
          $this->context->addViolation($constraint->groupNameIsEmpty, [
            '%value' => $group_name,
            '@field_name' => 'group_name',
          ]);
        }
      }
      else {
        // Check that group_id is not empty.
        $group_id = $ip_permission->getGroupId();
        if (empty($group_id)) {
          $this->context->addViolation($constraint->groupIdIsEmpty, [
            '%value' => $group_id,
            '@field_name' => 'group_id',
          ]);
        }
      }
    }
    else {
      // Cannot load security group.  Error out.
      $this->context->addViolation($constraint->noSecurityGroup, [
        '%value' => $ip_permission->getGroupName(),
        '@field_name' => 'group_name',
      ]);
    }
  }

  /**
   * Helper method that loads the security group entity from the url parameter.
   *
   * @return bool
   *   FALSE if not found | aws_cloud_security_group object if found.
   */
  private function getSecurityGroupEntity() {
    $security_group = FALSE;
    foreach (\Drupal::routeMatch()->getParameters() as $param) {
      if ($param instanceof EntityInterface) {
        $security_group = $param;
      }
    }
    return $security_group;
  }

  /**
   * Validate cidr ip addresses.
   *
   * This method works for cidr_ip and cidr_ipv6.
   *
   * @param string $cidr
   *   The cidr string.
   *
   * @return bool
   *   TRUE or FALSE.
   */
  private function validateCidr($cidr) {
    $parts = explode('/', $cidr);

    if (count($parts) != 2) {
      return FALSE;
    }
    $ip = $parts[0];
    $netmask = intval($parts[1]);

    if ($netmask < 0) {
      return FALSE;
    }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
      return $netmask <= 32;
    }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
      return $netmask <= 128;
    }
    return FALSE;
  }

}
