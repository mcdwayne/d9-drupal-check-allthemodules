<?php
/**
 * @file
 * Service CommitAuthorMain.
 */
namespace Drupal\commit_author;

use Drupal\Core\Utility\Error;
use Drupal\Core\Render\Markup;
use Drupal\Component\Utility\Xss;

class CommitAuthorMain {
  /**
   * Check git existing.
   *
   * @return bool
   *   If TRUE then Git exists,  if FALSE then Git doesn't exist.
   */
  static function git_existing_check() {
    $project_path = DRUPAL_ROOT;
    if (function_exists('exec')) {
      exec("cd $project_path; git status", $output);
    }
    else {
      // If function exec doesn't exist.
      $output = FALSE;
    }

    return $output ? TRUE : FALSE;
  }

  /**
   * Get commit author by file path and number line.
   *
   * @param string $path
   *   Path to file with notice.
   * @param int $line
   *   Number line in file with notice.
   *
   * @return bool|string
   *   If FALSE then author is empty or else author name.
   */
  private function get_author($path, $line) {
    if (!$this::git_existing_check()) {
      return FALSE;
    }

    exec("git annotate -L $line,$line $path --incremental", $output);
    $author = explode(' ', $output[1]);
    unset($author[0]);
    $author = implode(' ', $author);

    return !empty($author) ? $author : FALSE;
  }

  /**
   * Check show of author in notice by type: contrib, custom, theme.
   *
   * @param string $path
   *   Path to file with notice.
   *
   * @return bool
   *   If TRUE then show, if FALSE then hide.
   */
  private function check_show_by_notice_type($path) {

    $path = str_replace(DRUPAL_ROOT, '', $path);

    $config = \Drupal::config('commit_author.settings');

    switch (TRUE) {
      case strpos($path, '/core/') === 0:
        $show = $config->get('commit_author_show_core');
        break;

      case strpos($path, '/themes') === 0:
        $show = $config->get('commit_author_show_theme');
        break;

      case strpos($path, '/modules/custom') === 0:
        $show = $config->get('commit_author_show_custom');
        break;

      case strpos($path, '/modules/contrib') === 0:
        $show = $config->get('commit_author_show_contrib');
        break;

      default:
        $show = TRUE;
    }

    return (bool) $show;
  }

  /**
   * Provides custom PHP error handling.
   *
   * @param int $error_level
   *   The level of the error raised.
   * @param string $message
   *   The error message.
   * @param string $filename
   *   The filename that the error was raised in.
   * @param int $line
   *   The line number the error was raised at.
   */
  public function error_handler($error_level, $message, $filename, $line, $context) {
    require_once DRUPAL_ROOT . '/core/includes/errors.inc';

    $author = $this->get_author($filename, $line);
    $show = $author && $this->check_show_by_notice_type($filename);

    $config = \Drupal::config('commit_author.settings');

    $show_by_author = $config->get('commit_author_not_author');

    if ($show && $show_by_author && $author == 'Not Committed Yet') {
      $show = FALSE;
    }

    if ($show) {
      $message = t('Author') . ": \"$author\", $message";
    }

    //Copy from function _drupal_error_handler_real().
    if ($error_level & error_reporting()) {
      $types = drupal_error_levels();
      list($severity_msg, $severity_level) = $types[$error_level];
      $backtrace = debug_backtrace();
      $caller = Error::getLastCaller($backtrace);

      // We treat recoverable errors as fatal.
      $recoverable = $error_level == E_RECOVERABLE_ERROR;
      // As __toString() methods must not throw exceptions (recoverable errors)
      // in PHP, we allow them to trigger a fatal error by emitting a user error
      // using trigger_error().
      $to_string = $error_level == E_USER_ERROR && substr($caller['function'], -strlen('__toString()')) == '__toString()';
      _drupal_log_error(array(
        '%type' => isset($types[$error_level]) ? $severity_msg : 'Unknown error',
        // The standard PHP error handler considers that the error messages
        // are HTML. We mimick this behavior here.
        '@message' => Markup::create(Xss::filterAdmin($message)),
        '%function' => $caller['function'],
        '%file' => $caller['file'],
        '%line' => $caller['line'],
        'severity_level' => $severity_level,
        'backtrace' => $backtrace,
      ), $recoverable || $to_string);
    }
  }
}
