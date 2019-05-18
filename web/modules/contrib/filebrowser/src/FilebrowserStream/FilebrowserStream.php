<?php

namespace Drupal\filebrowser\FilebrowserStream;

use Drupal\Core\StreamWrapper\PublicStream;

//todo: replace this when issue https://www.drupal.org/node/1308152 lands

class FilebrowserStream extends PublicStream{

  /**
   *   to sites/ in a Drupal installation. This allows you to inject the site
   * Returns the base path for filebrowser://.
   *
   *
   * @param \SplString $site_path
   *   (optional) The site.path service parameter, which is typically the path
   *   path using services from the caller. If omitted, this method will use the
   *   global service container or the kernel's default behavior to determine
   *   the site path.
   *
   * @return string
   *   The base path for public:// typically sites/default/files.
   */
  public static function basePath(\SplString $site_path = NULL) {
    return drupal_get_path("module", "filebrowser");
  }

}