<?php

namespace Drupal\sceditor\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Render\FormattableMarkup;

/**
 * Class ChangedFilesController.
 */
class ChangedFilesController extends ControllerBase {

  /**
   * Changedfilescontroller.
   *
   * @return string
   *   Return Hello string.
   */
  public function showChangedFiles() {
    \Drupal::service('page_cache_kill_switch')->trigger(); // @codingStandardsIgnoreLine
    $files_string = shell_exec('git status -suno');
    $file_rows = explode('M', $files_string);
    $files = [];
    foreach ($file_rows as $file_row) {
      $file_name = trim($file_row);
      if (empty($file_name)) {
        continue;
      }
      $files[] = [
        [
          'data' => new FormattableMarkup('<a href="?file=' . $file_name . '">' . $file_row . '</a>', []),
        ],
      ];
    }
    if (!empty($files)) {
      $files[] = [
        [
          'data' => new FormattableMarkup('<a href="?file=clear" class="sceditor-clear-files"><i class="fa fa-eraser fa-fw"></i>' . $this->t('Clear Changes') . '</a>', []),
        ],
      ];
    }

    return [
      '#type' => 'table',
      '#header' => ['Files Changed'],
      '#rows' => empty($files) ? [['No changed files']] : $files,
      '#attributes' => [
        '#id' => 'sceditor-changed-files',
      ],
    ];
  }

}
