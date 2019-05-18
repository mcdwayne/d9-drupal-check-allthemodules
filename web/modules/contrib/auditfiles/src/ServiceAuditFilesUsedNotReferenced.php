<?php

namespace Drupal\auditfiles;

use Drupal\Core\Database\Database;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * List all methods used in files used not managed functionality.
 */
class ServiceAuditFilesUsedNotReferenced {

  use StringTranslationTrait;

  /**
   * Define constructor for string translation.
   */
  public function __construct(TranslationInterface $translation) {
    $this->stringTranslation = $translation;
  }

  /**
   * Retrieves the file IDs to operate on.
   *
   * @return array
   *   The file IDs.
   */
  public function auditfilesUsedNotReferencedGetFileList() {
    $config = \Drupal::config('auditfiles.settings');
    $connection = Database::getConnection();
    $query = 'SELECT DISTINCT fid FROM {file_usage} fu';
    $maximum_records = $config->get('auditfiles_report_options_maximum_records') ? $config->get('auditfiles_report_options_maximum_records') : 250;
    if ($maximum_records > 0) {
      $query .= ' LIMIT ' . $maximum_records;
    }
    $files_in_file_usage = $connection->query($query)->fetchCol();
    $files_in_fields = [];
    $fields[] = \Drupal::service('entity_field.manager')->getFieldMapByFieldType('image');
    $fields[] = \Drupal::service('entity_field.manager')->getFieldMapByFieldType('file');
    $count = 0;
    foreach ($fields as $key => $value) {
      foreach ($value as $table_prefix => $entity_type) {
        foreach ($entity_type as $key1 => $value1) {
          $field_data[$count]['table'] = $table_prefix . '__' . $key1;
          $field_data[$count]['column'] = $key1 . '_target_id';
          $count++;
        }
      }
    }
    foreach ($field_data as $key => $value) {
      $table = $value['table'];
      $column = $value['column'];
      if (Database::getConnection()->schema()->tableExists($table)) {
        $query = "SELECT t.$column FROM {$table} AS t INNER JOIN {file_usage} AS f ON f.fid = t.$column";
        $result = $connection->query($query);
        foreach ($result as $fid) {
          if (in_array($fid->{$column}, $files_in_file_usage)) {
            $files_in_fields[] = $fid->{$column};
          }
        }
      }
    }
    return array_diff($files_in_file_usage, $files_in_fields);
  }

  /**
   * Retrieves information about an individual file from the database.
   *
   * @param int $file_id
   *   The ID of the file to prepare for display.
   *
   * @return array
   *   The row for the table on the report, with the file's
   *   information formatted for display.
   */
  public function auditfilesUsedNotReferencedGetFileData($file_id) {
    $connection = Database::getConnection();
    $file_managed = $connection->query("SELECT * FROM {file_managed} fm WHERE fid = $file_id")->fetchObject();
    if (empty($file_managed)) {
      $url = Url::fromUri('internal:/admin/reports/auditfiles/usednotmanaged');
      $result_link = Link::fromTextAndUrl($this->t('Used not managed'), $url)->toString();
      $row = [
        'fid' => $this->t(
          'This file is not listed in the file_managed table. See the "%usednotmanaged" report.',
          ['%usednotmanaged' => $result_link]
        ),
        'uri' => '',
        'usage' => '',
      ];
    }
    else {
      $usage = '<ul>';
      $results = $connection->query("SELECT * FROM {file_usage} WHERE fid = $file_id");
      foreach ($results as $file_usage) {
        $used_by = $file_usage->module;
        $type = $file_usage->type;
        $url = Url::fromUri('internal:/node/' . $file_usage->id);
        $result_link = Link::fromTextAndUrl($file_usage->id, $url)->toString();
        $used_in = ($file_usage->type == 'node') ? $result_link : $file_usage->id;
        $times_used = $file_usage->count;
        $usage .= '<li>' . $this->t(
          'Used by module: %used_by, as object type: %type, in content ID: %used_in; Times used: %times_used',
          [
            '%used_by' => $used_by,
            '%type' => $type,
            '%used_in' => $used_in,
            '%times_used' => $times_used,
          ]
        ) . '</li>';
      }
      $usage .= '</ul>';
      $usage = new FormattableMarkup($usage, []);
      $row = [
        'fid' => $file_id,
        'uri' => $file_managed->uri,
        'usage' => $usage,
      ];
    }
    return $row;
  }

  /**
   * Returns the header to use for the display table.
   *
   * @return array
   *   The header to use.
   */
  public function auditfilesUsedNotReferencedGetHeader() {
    return [
      'fid' => [
        'data' => $this->t('File ID'),
      ],
      'uri' => [
        'data' => $this->t('File URI'),
      ],
      'usage' => [
        'data' => $this->t('Usages'),
      ],
    ];
  }

  /**
   * Creates the batch for deleting files from the file_usage table.
   */
  public function auditfilesUsedNotReferencedBatchDeleteCreateBatch(array $fileids) {
    $batch['error_message'] = $this->t('One or more errors were encountered processing the files.');
    $batch['finished'] = '\Drupal\auditfiles\AuditFilesBatchProcess::auditfilesUsedNotReferencedBatchFinishBatch';
    $batch['progress_message'] = $this->t('Completed @current of @total operations.');
    $batch['title'] = $this->t('Deleting files from the file_usage table');
    $operations = $file_ids = [];
    foreach ($fileids as $file_id) {
      if ($file_id != 0) {
        $file_ids[] = $file_id;
      }
    }
    foreach ($file_ids as $file_id) {
      $operations[] = [
        '\Drupal\auditfiles\AuditFilesBatchProcess::auditfilesUsedNotReferencedBatchDeleteProcessBatch',
        [$file_id],
      ];
    }
    $batch['operations'] = $operations;
    return $batch;
  }

  /**
   * Deletes the specified file from the file_usage table.
   *
   * @param int $file_id
   *   The ID of the file to delete from the database.
   */
  public function auditfilesUsedNotReferencedBatchDeleteProcessFile($file_id) {
    $connection = Database::getConnection();
    $num_rows = $connection->delete('file_usage')->condition('fid', $file_id)->execute();
    if (empty($num_rows)) {
      drupal_set_message(
        $this->t('There was a problem deleting the record with file ID %fid from the file_usage table. Check the logs for more information.', ['%fid' => $file_id]), 'warning');
    }
    else {
      drupal_set_message(
        $this->t(
          'Sucessfully deleted File ID : %fid from the file_usage table.',
          ['%fid' => $file_id]
        )
      );
    }
  }

}
