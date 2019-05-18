<?php

namespace Drupal\baguettebox\Commands;

use Drush\Commands\DrushCommands;
use Masterminds\HTML5\Exception;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Drush integration for BaguetteBox module.
 */
class BaguetteboxCommands extends DrushCommands {

  const BAGUETTEBOX_LIBRARY_DIR = 'libraries/baguettebox';
  const BAGUETTEBOX_DOWNLOAD_JS_URL = 'https://raw.githubusercontent.com/feimosi/baguetteBox.js/master/dist/baguetteBox.min.js';
  const BAGUETTEBOX_DOWNLOAD_CSS_URL = 'https://raw.githubusercontent.com/feimosi/baguetteBox.js/master/dist/baguetteBox.min.css';

  /**
   * Download and install the most recent version of BaguetteBox library.
   *
   * @command baguettebox:download
   * @alias baguettebox-download
   */
  public function download() {

    $libraries_dir = 'libraries';

    if (!is_dir($libraries_dir)) {
      throw new \Exception(sprintf('Directory %s does not exist.', $libraries_dir));
    }

    if (is_dir(self::BAGUETTEBOX_LIBRARY_DIR)) {
      $question_text = 'Install location %s already exists. Do you want to overwrite it?';
      $question = new ConfirmationQuestion(sprintf($question_text, self::BAGUETTEBOX_LIBRARY_DIR));
      if ($this->io()->askQuestion($question)) {
        drush_delete_dir(self::BAGUETTEBOX_LIBRARY_DIR, TRUE);
      }
      else {
        return 0;
      }
    }
    drush_mkdir(self::BAGUETTEBOX_LIBRARY_DIR);

    drush_shell_exec('wget --timeout=15 -O %s %s', self::BAGUETTEBOX_LIBRARY_DIR . '/baguetteBox.min.js', self::BAGUETTEBOX_DOWNLOAD_JS_URL);
    drush_shell_exec('wget --timeout=15 -O %s %s', self::BAGUETTEBOX_LIBRARY_DIR . '/baguetteBox.min.css', self::BAGUETTEBOX_DOWNLOAD_CSS_URL);

    $js_file_found = drush_file_not_empty(self::BAGUETTEBOX_LIBRARY_DIR . '/baguetteBox.min.js');
    $css_file_found = drush_file_not_empty(self::BAGUETTEBOX_LIBRARY_DIR . '/baguetteBox.min.css');
    if (!$js_file_found || !$css_file_found) {
      throw new Exception('Could not download baguetteBox library from GitHub.');
    }
    else {
      $this->io()->success(sprintf('BaguetteBox library has been installed in %s directory.', self::BAGUETTEBOX_LIBRARY_DIR));
    }

  }

}
