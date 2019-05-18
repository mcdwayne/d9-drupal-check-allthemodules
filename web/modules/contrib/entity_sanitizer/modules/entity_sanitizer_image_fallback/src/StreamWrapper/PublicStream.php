<?php

namespace Drupal\entity_sanitizer_image_fallback\StreamWrapper;

use Drupal\Core\StreamWrapper\PublicStream as CorePublicStream;

class PublicStream extends CorePublicStream {
  use GeneratorStreamTrait;
}