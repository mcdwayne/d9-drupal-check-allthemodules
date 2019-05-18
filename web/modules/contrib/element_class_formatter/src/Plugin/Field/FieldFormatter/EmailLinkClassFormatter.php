<?php

namespace Drupal\element_class_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\MailToFormatter;

/**
 * Plugin implementation of the 'email_mailto_class' formatter.
 *
 * @FieldFormatter(
 *   id = "email_link_class",
 *   label = @Translation("Email link (with class)"),
 *   field_types = {
 *     "email"
 *   }
 * )
 */
class EmailLinkClassFormatter extends MailToFormatter {

  use ElementLinkClassTrait;

}
