<?php

namespace Drupal\patchinfo_drupalorg\Commands;

use Drupal\patchinfo\Commands\PatchInfoCommands;
use Drupal\patchinfo_drupalorg\PatchinfoDrupalorgService;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Consolidation\OutputFormatters\Options\FormatterOptions;
use Drupal\update\UpdateManagerInterface;

/**
 * A Drush commandfile for the patchinfo_drupalorg:list command.
 */
class PatchInfoDrupalorgCommands extends PatchInfoCommands {

  protected static $issuePriorities = [
    100 => 'Minor',
    200 => 'Normal',
    300 => 'Major',
    400 => 'Critical',
  ];

  protected static $issueStatus = [
    1 => 'active',
    2 => 'fixed',
    3 => 'closed (duplicate)',
    4 => 'postponed',
    5 => 'closed (won\'t fix)',
    6 => 'closed (works as designed)',
    7 => 'closed (fixed)',
    8 => 'needs review',
    13 => 'needs work',
    14 => 'reviewed & tested by the community',
    15 => 'patch (to be ported)',
    16 => 'postponed (maintainer needs more info)',
    17 => 'closed (outdated)',
    18 => 'closed (cannot reproduce)',
  ];

  protected static $issueCategories = [
    1 => 'Bug report',
    2 => 'Task',
    3 => 'Feature request',
    4 => 'Support request',
    5 => 'Plan',
  ];

  /**
   * Show a report of patches applied to Drupal core and contrib projects.
   *
   * @command patchinfo_drupalorg:list
   * @field-labels
   *   project: Project
   *   project_label: Project label
   *   delta: Delta
   *   info: Info
   *   url: URL
   *   issue_number: Issue number
   *   issue_url: Issue URL
   *   issue_title: Issue Title
   *   issue_status: Issue Status
   *   issue_priority: Issue Priority
   *   issue_category: Issue Category
   *   issue_author_name: Issue Author Name
   *   issue_created: Issue Created
   *   issue_changed: Issue Changed
   * @default-string-field name
   * @usage patchinfo-do-list --projects=drupal
   * @usage patchinfo-do-list --projects=drupal,pathauto
   * @usage patchinfo-do-list --format=yaml
   * @usage patchinfo-do-list --format=csv
   * @usage patchinfo-do-list --fields=name,info
   * @usage patchinfo-do-list --fields=Label,Delta
   * @aliases patchinfo-do-list
   * @hidden
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   Patch information as rows of fields.
   */
  public function list(array $options = [
    'projects' => NULL,
    'format' => 'table',
    'fields' => 'project,project_label,delta,url,issue_number,issue_title,issue_url,issue_status,issue_priority,issue_category,issue_author_name,issue_created,issue_changed',
  ]) {

    $table = $this->getTableData($options['projects']);
    $pattern = '/(Issue\ |issue\ |#)(?P<issuenumber>\d+)(\||,|\.|\ |:|$)/';
    foreach ($table as $key => $patchinfo_list_row) {

      $patchinfo_list_row['project'] = $patchinfo_list_row['name'];
      $patchinfo_list_row['project_label'] = $patchinfo_list_row['label'];
      $patchinfo_list_row['issue_number'] = '';
      $patchinfo_list_row['issue_url'] = '';
      $patchinfo_list_row['issue_title'] = $patchinfo_list_row['info'];
      $patchinfo_list_row['issue_status'] = '';
      $patchinfo_list_row['issue_priority'] = '';
      $patchinfo_list_row['issue_category'] = '';
      $patchinfo_list_row['issue_author_name'] = '';
      $success = preg_match($pattern, $patchinfo_list_row['info'], $match);
      if ($success && isset($match['issuenumber'])) {
        $drupalorg_id = $match['issuenumber'];
        $composer_module_issue = (object) $this->patchinfoDrupalorgService->getIssue($drupalorg_id);
        if ($composer_module_issue) {
          $patchinfo_list_row['issue_number'] = $composer_module_issue->nid;
          $patchinfo_list_row['issue_url'] = $composer_module_issue->url;
          $patchinfo_list_row['issue_title'] = $composer_module_issue->title;
          $patchinfo_list_row['issue_status'] = self::$issueStatus[$composer_module_issue->field_issue_status];
          $patchinfo_list_row['issue_priority'] = self::$issuePriorities[$composer_module_issue->field_issue_priority];
          $patchinfo_list_row['issue_category'] = self::$issueCategories[$composer_module_issue->field_issue_category];
          $patchinfo_list_row['issue_author_name'] = $composer_module_issue->author->name;
          $patchinfo_list_row['issue_created'] = date('Y-m-d\TH:i:s', $composer_module_issue->created);
          $patchinfo_list_row['issue_changed'] = date('Y-m-d\TH:i:s', $composer_module_issue->changed);
        }
      }
      $table[$key] = $patchinfo_list_row;
    }
    $data = new RowsOfFields($table);

    $data->addRendererFunction(
    // n.b. There is a fourth parameter $rowData that may be added here.
      function ($key, $cellData, FormatterOptions $options, $rowData) {
        if ($key === 'project') {
          return "<comment>$cellData</>";
        }
        return $cellData;
      }
    );

    return $data;
  }

  /**
   * Update manager service.
   *
   * @var \Drupal\patchinfo_drupalorg\PatchinfoDrupalorgService
   */
  protected $patchinfoDrupalorgService;

  /**
   * Constructs update status data.
   *
   * @param \Drupal\update\UpdateManagerInterface $update_manager
   *   Update Manager Service.
   * @param \Drupal\patchinfo_drupalorg\PatchinfoDrupalorgService $patchinfo_drupalorg_service
   *   Patchinfo Drupal.org Service.
   */
  public function __construct(UpdateManagerInterface $update_manager, PatchinfoDrupalorgService $patchinfo_drupalorg_service) {
    PatchInfoCommands::__construct($update_manager);
    $this->updateManager = $update_manager;
    $this->patchinfoDrupalorgService = $patchinfo_drupalorg_service;
  }

}
