<?php

namespace Drupal\field_encrypt\Plugin\QueueWorker;

use Drupal\field_encrypt\Plugin\QueueWorker\EncryptedFieldUpdateBase;

/**
 * A Queue Worker that updates field encryption on cron run.
 *
 * @QueueWorker(
 *   id = "cron_encrypted_field_update",
 *   title = @Translation("Cron encrypted field updates."),
 *   cron = {"time" = 15}
 * )
 */
class CronEncryptedFieldUpdate extends EncryptedFieldUpdateBase {}
