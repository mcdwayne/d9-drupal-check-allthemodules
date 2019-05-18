<?php

namespace Drupal\entity_sanitizer_image_fallback\StreamWrapper;

use Drupal\Core\StreamWrapper\PrivateStream as CorePrivateStream;

class PrivateStream extends CorePrivateStream {
  use GeneratorStreamTrait;
}