<?php

namespace Drupal\micro_site\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if an site entity has a value and unique url.
 *
 * @Constraint(
 *   id = "SiteUrlField",
 *   label = @Translation("site url field constraint", context = "Validation"),
 * )
 */
class SiteUrlFieldConstraint extends Constraint {

  public $message = "This site URL %value already exists. Please choose another URL.";

  public $message_sub_domain = "The sub-domain %value is not valid. The sub-domain must contains only lowercase alphanumeric characters or dashes, without dot.";

  public $message_domain = "The domain %value is not valid. The domain must contains only lowercase alphanumeric characters or dashes, or dot.";

  public $message_reserved = "This URL %value has a keyword reserved you can't use for your domain. Please choose another URL.";

}
