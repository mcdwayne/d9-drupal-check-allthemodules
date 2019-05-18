<?php

namespace Drupal\publication_date\Feeds\Target;

use Drupal\feeds\Feeds\Target\Timestamp;

/**
 * Defines a "published at" field mapper.
 *
 * @FeedsTarget(
 *   id = "published_at",
 *   field_types = {
 *     "published_at"
 *   }
 * )
 */
class PublishedAt extends Timestamp {
}
