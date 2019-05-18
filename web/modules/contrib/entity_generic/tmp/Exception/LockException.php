<?php

namespace Drupal\entity_generic\Exception;

use RuntimeException;

/**
 * Thrown when entity can not obtain a lock.
 */
class LockException extends RuntimeException {}
