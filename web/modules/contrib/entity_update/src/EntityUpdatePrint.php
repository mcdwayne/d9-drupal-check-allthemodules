<?php

namespace Drupal\entity_update;

/**
 * EntityCheck CLI Print class.
 */
class EntityUpdatePrint {

  /**
   * Check is CLI then run drush_print.
   */
  public static function drushPrint($message = '', $indent = 0, $handle = NULL, $newline = TRUE) {
    if (php_sapi_name() == 'cli') {
      drush_print($message, $indent, $handle, $newline);
    }
  }

  /**
   * Check is CLI then run drush_log.
   */
  public static function drushLog($message, $type = LogLevel::NOTICE, $error = NULL, $ui_print = FALSE) {
    if (php_sapi_name() == 'cli') {
      drush_log($message, $type, $error);
    }
    elseif ($ui_print) {
      drupal_set_message($message, $type);
    }
  }

  /**
   * Show the summary of an entity type.
   *
   * @param string $type
   *   The entity type id.
   */
  public static function displaySummery($type) {
    try {
      $entity_type = \Drupal::entityTypeManager()->getDefinition($type);

      $query = \Drupal::entityQuery($type);
      $ids = $query->execute();

      drush_print("Entity type  : " . $type);
      drush_print("Label        : " . $entity_type->getLabel());
      drush_print("Group        : " . $entity_type->getGroupLabel());
      drush_print("Class        : " . $entity_type->getClass());
      drush_print("Nb of Items  : " . count($ids));
      drush_print("Base table   : " . $entity_type->getBaseTable());
      drush_print("Data table   : " . $entity_type->getDataTable());
      drush_print("Bundle Label : " . $entity_type->getBundleLabel());
      drush_print("Bundle Of    : " . $entity_type->getBundleOf());
      drush_print("Bundle Type  : " . $entity_type->getBundleEntityType());
      drush_print("Admin perm   : " . $entity_type->getAdminPermission());
    }
    catch (\Exception $ex) {
      drush_log($ex->getMessage(), 'error');
    }
  }

  /**
   * Print a table to drush terminal.
   *
   * @param array $table
   *   The table to print.
   */
  public static function drushPrintTable(array $table) {

    // Check execution from CLI.
    if (php_sapi_name() != 'cli') {
      return;
    }

    $cols = exec('tput cols');
    $line_empty = "|" . str_repeat("-", $cols - 2) . "|";
    drush_print($line_empty);

    $header = empty($table['#header']) ? NULL : $table['#header'];
    $rows = empty($table['#rows']) ? NULL : $table['#rows'];

    // Calculate colones size.
    $csizes = [];
    if ($rows) {
      if ($header) {
        $rows['header'] = $header;
      }
      foreach ($rows as $row) {
        $idx = 0;
        foreach ($row as $txt) {
          if (empty($csizes[$idx]) || $csizes[$idx] < strlen($txt)) {
            $csizes[$idx] = strlen($txt);
          }
          $idx++;
        }
      }
      // Remove temporerly added header.
      if ($header) {
        unset($rows['header']);
      }
    }
    elseif ($header) {
      foreach ($header as $txt) {
        $csizes[] = strlen($txt);
      }
    }

    // Print caption.
    if (!empty($table['#caption'])) {
      $t = strlen($table['#caption']);
      if ($t < $cols - 2) {
        $line = str_repeat(" ", ((int) ($cols - $t) / 2));
        $line = $line . $table['#caption'] . $line;
      }
      else {
        $line = $table['#caption'];
      }
      drush_print($line);
      drush_print($line_empty);
    }

    // Print header.
    if ($header) {
      $line = "|";
      $idx = 0;
      foreach ($header as $txt) {
        $line .= " " . $txt . str_repeat(" ", $csizes[$idx] - strlen($txt)) . "|";
        $idx++;
      }
      $line = substr($line, 0, $cols);
      drush_print($line);
      drush_print($line_empty);
    }

    // Print data.
    if ($rows) {
      foreach ($rows as $row) {
        $line = "|";
        $idx = 0;
        foreach ($row as $txt) {
          $line .= " " . $txt . str_repeat(" ", $csizes[$idx] - strlen($txt)) . "|";
          $idx++;
        }
        $line = substr($line, 0, $cols);
        drush_print($line);
      }
    }

    // Print data empty message.
    else {
      $txt = dt('No data to display');
      $t = strlen($txt);
      if ($t < $cols - 2) {
        $line = str_repeat(" ", ((int) ($cols - $t) / 2));
        $line = $line . $txt . $line;
      }
      else {
        $line = $txt;
      }
      drush_print($line);
    }

    drush_print($line_empty);
  }

}
