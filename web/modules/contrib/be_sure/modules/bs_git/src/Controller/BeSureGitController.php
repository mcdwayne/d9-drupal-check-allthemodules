<?php

namespace Drupal\bs_git\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Url;

/**
 * Class BeSureGitController.
 *
 * @package Drupal\bs_git\Controller
 */
class BeSureGitController extends ControllerBase {
  /**
   * Files_status.
   *
   * @return string
   *   Return Hello string.
   */
  public function files_status() {
    $info = _bs_git_get_status();
    $items = [];

    if (!$info) {
      $output = '<div class="messages status">';
      $output .= t('There are no changed files.');
      $output .= '</div>';
    }
    else {
      $output[] = [
        '#markup' => '<div class="messages error">' .
        t('There are changed files.') . '</div>',
      ];

      foreach ($info as $file) {
        $file = explode(' ', trim($file));
        $file = array_values(array_filter($file));

        list($status, $file_path) = $file;

        $status = Unicode::strlen($status) > 1
          ? Unicode::substr($status, -1, 1)
          : $status;

        $items[$status][] = $status == BS_GIT_FILE_CHANGED
          ? $file_path . ' ' . \Drupal::l(
            t('(show diff)'),
            Url::fromRoute('bs_git.file_diff', [],
              ['query' => ['file' => $file_path]]
            )
          )
          : $file_path;
      }

      $titles = [
        BS_GIT_FILE_ADDED => t('Added'),
        BS_GIT_FILE_NEW => t('Created'),
        BS_GIT_FILE_CHANGED => t('Modified'),
        BS_GIT_FILE_DELETED => t('Deleted'),
      ];

      foreach ($items as $status => $value) {
        $title = $titles[$status];

        $output[] = [
          '#theme' => 'item_list',
          '#items' => $value,
          '#title' => $title,
        ];
      }
    }

    return $output;
  }
  /**
   * File_diff.
   *
   * @return string
   *   Return Hello string.
   */
  public function file_diff() {
    $file = isset($_GET['file']) ? (string) $_GET['file'] : '';

    if (!$file || !file_exists($file)) {
      $output = Response::HTTP_NOT_FOUND;
    }
    else {
      $git_command = bs_git_get_command();
      exec(escapeshellcmd($git_command) . ' diff ' . escapeshellarg($file), $diff, $retval);

      $output = '<pre>';
      $output .= implode($diff, PHP_EOL);
      $output .= '</pre>';
    }

    return $output;
  }

}
