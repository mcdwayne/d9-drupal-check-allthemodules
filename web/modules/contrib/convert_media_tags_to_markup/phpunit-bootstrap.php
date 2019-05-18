<?php

/**
 * @file
 * PHPUnit bootstrap: our bare-bones version of Drupal.
 *
 * PHPUnit knows nothing about Drupal, so provide PHPUnit with the bare
 * minimum it needs to know in order to test classes which use Drupal
 * core and contrib classes.
 *
 * Used by the PHPUnit test runner and referenced in ./phpunit-autoload.xml.
 */

namespace Drupal\filter {
  class FilterProcessResult {}
}

namespace Drupal\filter\Plugin {
  class FilterBase {}
}

namespace Drupal\Component\Serialization {
  class Json {}
}

namespace Drupal\file\Entity {
  class File {}
}

namespace Drupal\Component\Utility {
  class Html {}
}
