<?php

namespace Drupal\micro_site\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks if a micro site has well a vhost set before register it.
 *
 * @Constraint(
 *   id = "RegisteredField",
 *   label = @Translation("Domain site registered field constraint", context = "Validation"),
 * )
 */
class RegisteredFieldConstraint extends Constraint {

  public $message = "Seems that your site url %value is not yet configured. Please check your DNS and Web server configuration. You can not register your site until the DNS and/or Web server issue is fixed. See error below : <pre>%error</pre>";

}
