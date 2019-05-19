<?php

namespace Drupal\vuejs\Commands;

use Drush\Commands\DrushCommands;

/**
 * Vuejs Drush commandfile.
 */
class VuejsCommands extends DrushCommands {

  /**
   * Downloads Vue.js libraries.
   *
   * @param $library_name
   *   Library name (vue, vue-router, vue-resource).
   * @usage drush vue-download vue-router
   *   Download vue-router library.
   *
   * @command vuejs:download
   * @aliases vue,vuejs-download
   *
   * @return bool
   */
  public function download($library_name) {
    module_load_include('inc', 'vuejs', 'vuejs.drush');
    return drush_vuejs_download($library_name);
  }

}
