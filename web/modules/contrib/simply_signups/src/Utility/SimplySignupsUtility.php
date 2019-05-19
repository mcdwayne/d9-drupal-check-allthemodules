<?php

namespace Drupal\simply_signups\Utility;

use Drupal\Component\Utility\Html;

/**
 * Implements signup utilities.
 */
class SimplySignupsUtility {

  /**
   * Sanitize options for radios or checkboxes.
   */
  public static function sanitizeOptions($entityoptions) {
    $lines = explode(PHP_EOL, $entityoptions);
    $options = [];
    foreach ($lines as $line) {
      $containsPipe = preg_match("/\|/", $line);
      if ($containsPipe == 1) {
        $line = explode('|', $line);
        $line[0] = Html::escape($line[0]);
        $line[1] = Html::escape($line[1]);
        $options[$line[0]] = $line[1];
      }
      else {
        $line = Html::escape($line);
        $line = rtrim($line);
        $options[$line] = $line;
      }
    }
    return $options;
  }

  /**
   * Sanitize options created for a select field.
   */
  public static function sanitizeOptionsSelect($entityoptions, $required) {
    $lines = explode(PHP_EOL, $entityoptions);
    $options = [];
    if ($required == 1) {
      $options[''] = '- Select -';
    }
    else {
      $options[''] = '- None -';
    }
    foreach ($lines as $line) {
      $containsPipe = preg_match("/\|/", $line);
      if ($containsPipe == 1) {
        $line = explode('|', $line);
        $line[0] = Html::escape($line[0]);
        $line[1] = Html::escape($line[1]);
        $options[$line[0]] = $line[1];
      }
      else {
        $line = Html::escape($line);
        $line = rtrim($line);
        $options[$line] = $line;
      }
    }
    return $options;
  }

  /**
   * Clean up option parameters generated when creating a form element.
   */
  public static function sanitizeOptionsTextarea($entityoptions) {
    $options = '';
    $entityoptions = array_filter($entityoptions);
    foreach ($entityoptions as $key => $value) {
      if (($value != '- Select -') and ($value != '- None -')) {
        $options .= trim($key) . "|" . trim($value) . "\n";
      }
    }
    return trim($options);
  }

  /**
   * Return a tally of the number of attending colun in the sign data table.
   */
  public static function getNumberOfAttending($node) {
    if (is_numeric($node)) {
      $db = \Drupal::database();
      $query = $db->select('simply_signups_data', 'p');
      $query->fields('p');
      $query->condition('nid', $node, '=');
      $count = $query->countQuery()->execute()->fetchField();
      if ($count > 0) {
        $results = $query->execute()->fetchAll();
        $attending = 0;
        foreach ($results as $row) {
          $attending = ($attending + $row->attending);
        }
        return $attending;
      }
    }
    return '0';
  }

  /**
   * Return the number of rows that are 'signed up'.
   */
  public static function getNumberOfSignups($node) {
    if (is_numeric($node)) {
      $db = \Drupal::database();
      $query = $db->select('simply_signups_data', 'p');
      $query->fields('p');
      $query->condition('nid', $node, '=');
      $count = $query->countQuery()->execute()->fetchField();
      return $count;
    }
    return '0';
  }

  /**
   * Return the number of rows for $node where status is 1.
   */
  public static function getNumberOfCheckIns($node) {
    if (is_numeric($node)) {
      $db = \Drupal::database();
      $query = $db->select('simply_signups_data', 'p');
      $query->fields('p');
      $query->condition('nid', $node, '=');
      $query->condition('status', 1, '=');
      $count = $query->countQuery()->execute()->fetchField();
      return $count;
    }
    return FALSE;
  }

  /**
   * Return the number of attending for $node where status is 1 (checked-in).
   */
  public static function getNumberOfCheckedInsAttending($node) {
    if (is_numeric($node)) {
      $db = \Drupal::database();
      $query = $db->select('simply_signups_data', 'p');
      $query->fields('p');
      $query->condition('nid', $node, '=');
      $query->condition('status', 1, '=');
      $count = $query->countQuery()->execute()->fetchField();
      if ($count > 0) {
        $results = $query->execute()->fetchAll();
        $tally = 0;
        foreach ($results as $row) {
          $tally = ($tally + $row->attending);
        }
        return $tally;
      }
    }
    return 0;
  }

  /**
   * Get the number of fields for a $node.
   */
  public static function getNumberOfFields($node) {
    if (is_numeric($node)) {
      $db = \Drupal::database();
      $query = $db->select('simply_signups_fields', 'p');
      $query->fields('p');
      $query->condition('nid', $node, '=');
      $count = $query->countQuery()->execute()->fetchField();
      return $count;
    }
    return FALSE;
  }

  /**
   * Gets the start date for allowing signups.
   */
  public static function getStartDate($node) {
    if (is_numeric($node)) {
      $db = \Drupal::database();
      $query = $db->select('simply_signups_settings', 'p');
      $query->fields('p');
      $query->condition('nid', $node, '=');
      $count = $query->countQuery()->execute()->fetchField();
      if ($count > 0) {
        $results = $query->execute()->fetchAll();
        foreach ($results as $row) {
          $startDate = $row->start_date;
        }
        return $startDate;
      }
    }
    return FALSE;
  }

  /**
   * Gets the end date for allowing signups.
   */
  public static function getEndDate($node) {
    if (is_numeric($node)) {
      $db = \Drupal::database();
      $query = $db->select('simply_signups_settings', 'p');
      $query->fields('p');
      $query->condition('nid', $node, '=');
      $count = $query->countQuery()->execute()->fetchField();
      if ($count > 0) {
        $results = $query->execute()->fetchAll();
        foreach ($results as $row) {
          $endDate = $row->end_date;
        }
        return $endDate;
      }
    }
    return FALSE;
  }

  /**
   * Gets the max attanding setting for a nid.
   */
  public static function getMaxAttending($node) {
    if (is_numeric($node)) {
      $db = \Drupal::database();
      $query = $db->select('simply_signups_settings', 'p');
      $query->fields('p');
      $query->condition('nid', $node, '=');
      $count = $query->countQuery()->execute()->fetchField();
      if ($count > 0) {
        $results = $query->execute()->fetchAll();
        foreach ($results as $row) {
          $maxSignups = $row->max_signups;
        }
        return $maxSignups;
      }
    }
    return FALSE;
  }

  /**
   * Implements sanitization of telephone numbers.
   */
  public static function formatTelephone($telephone, $format) {
    $pattern = "/[^0-9]/";
    $number = preg_replace($pattern, "", $telephone);
    $formattedTelephone = FALSE;
    if (is_numeric($number)) {
      if (strlen((string) $number) == 10) {
        $telephone_piece = [];
        $telephone_piece[0] = substr($number, 0, 3);
        $telephone_piece[1] = substr($number, 3, 3);
        $telephone_piece[2] = substr($number, 6, 10);
        if ($format == 1) {
          $formattedTelephone = '(' . $telephone_piece[0] . ') ' . $telephone_piece[1] . ' ' . $telephone_piece[2];
        }
        if ($format == 2) {
          $formattedTelephone = '(' . $telephone_piece[0] . ') ' . $telephone_piece[1] . '-' . $telephone_piece[2];
        }
        else {
          $formattedTelephone = $telephone_piece[0] . '-' . $telephone_piece[1] . '-' . $telephone_piece[2];
        }
      }
    }
    return $formattedTelephone;
  }

}
