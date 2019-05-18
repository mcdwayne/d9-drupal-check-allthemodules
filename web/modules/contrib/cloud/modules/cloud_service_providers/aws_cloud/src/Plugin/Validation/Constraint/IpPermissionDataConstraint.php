<?php

namespace Drupal\aws_cloud\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * IpPermission field validation.
 *
 * @Constraint(
 *   id = "ip_permission_data",
 *   label = @Translation("IP Permission", context = "Validation"),
 * )
 */
class IpPermissionDataConstraint extends Constraint {

  public $toPortNotNumeric = "The To Port is not numeric.";

  public $fromPortNotNumeric = "The From Port is not numeric.";

  public $ip4IsEmpty = "CIDR ip is empty.";

  public $ip4Value = "CIDR ip is not valid. Single ip addresses must be in x.x.x.x/32 notation.";

  public $ip6Value = "CIDR ipv6 is not valid. Single ip addresses must be in x.x.x.x/32 notation.";

  public $ip6IsEmpty = "CIDR ipv6 is empty.";

  public $groupNameIsEmpty = "Group name is empty.";

  public $groupIdIsEmpty = "Group id is empty.";

  public $noSecurityGroup = "No security group found.";

  public $toPortGreater = "From Port is greater than To Port.";

}
