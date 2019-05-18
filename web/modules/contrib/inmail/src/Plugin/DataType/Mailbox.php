<?php

namespace Drupal\inmail\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * Datatype containing an email address and optionally a name.
 *
 * @DataType(
 *   id = "inmail_mailbox",
 *   label = @Translation("Mailbox"),
 *   definition_class = "Drupal\inmail\TypedData\MailboxDefinition"
 * )
 */
class Mailbox extends Map {

}
