<?php

namespace Drupal\users_export;

use AKlump\LoftDataGrids\ExporterInterface;

/**
 * User Export Class.
 */
class UsersExport {

  private $db;

  /**
   * Core constructor.
   *
   * @param object $db
   *   Database.
   */
  public function __construct($db) {
    $this->db = $db;
  }

  /**
   * Load users into an Exporter object.
   *
   * @param \AKlump\LoftDataGrids\ExporterInterface $exporter
   *   Exporter.
   * @param array $options
   *   - limit int Limit to the first n items
   *   - status int|null Null for both blocked and active users, 1 for active,
   *   0 for blocked.
   *   - date_format string Suitable for DateTime::format().
   *   - uids array|null To only export certain uids set this to an array
   *   containing those uids.  Otherwise all but 0 will be exported.
   *
   * @return $this
   */
  public function exporterLoadUsers(ExporterInterface $exporter, array $options) {
    $config = \Drupal::config('users_export.settings');
    $options += [
      'with_access' => $config->get('users_export_with_access'),
      'limit'       => $config->get('users_export_test_mode') ? 10 : NULL,
      'order'       => $config->get('users_export_order'),
      'roles'       => NULL,
      'status'      => $config->get('users_export_user_status'),
      'date_format' => $config->get('users_export_date_format'),
      'uids'        => NULL,
    ];

    // Load the users.
    $query = $this->db->select('users', 'u');
    $query->addField('u', 'uuid', 'uuid');
    $query->addField('u', 'uid');
    $query->addField('d', 'name', 'username');
    $query->addField('d', 'status');
    $query->addField('d', 'mail', 'email');
    $query->addField('d', 'created');
    $query->addField('d', 'changed');
    $query->addField('d', 'access', 'last_access');
    $query->addField('d', 'login', 'last_login');
    $query->addField('d', 'timezone');
    $query->addField('d', 'langcode');
    $query->join('users_field_data', 'd', 'u.uid = d.uid');

    if ($options['order']) {

      $order = $options['order'];

      switch ($order) {
        case 1:
          $query->orderBy('d.name', 'asc');
          break;

        case 2:
          $query->orderBy('d.mail', 'asc');
          break;

        default:
          $query->orderBy('u.uid');
      }
    }

    if (is_array($options['uids']) && !empty($options['uids'])) {
      $query->condition('u.uid', $options['uids'], 'IN');
    }
    else {
      // Exclude anonymous.
      $query->condition('u.uid', 0, '<>');
    }

    if ($options['limit']) {
      $query->range(0, $options['limit']);
    }

    if (isset($options['with_access']) && $options['with_access'] != 2) {

      switch ($options['with_access']) {
        case 0:
          $operator = '=';
          break;

        case 1:
          $operator = '<>';
          break;

        default:
          $operator = '<>';
      }

      $query->condition('d.access', 0, $operator);
    }

    if (!empty($options['roles'])) {

      $roles = array_filter($options['roles']);

      // If selected "authenticated user" option return all users.
      if (!array_search(DRUPAL_AUTHENTICATED_RID, $roles)) {

        $query->leftJoin('user__roles', 'ur', 'ur.entity_id = u.uid');

        $query->condition('ur.roles_target_id', $roles, 'in');
      }
    }

    // Add status condition based on setting.
    if (is_numeric($options['status']) && ($options['status'] != 2)) {
      $query->condition('status', $options['status']);
    };

    $result = $query->execute()->fetchAllAssoc('uid');
    $options['uids'] = array_keys($result);

    $data = $exporter->getData();
    $context = [
      'settings' => $options,
      'data'     => $data,
    ];

    // Go through and create a row for each user.
    foreach ($result as $row) {
      $row = $context['original_row'] = (array) $row;
      $row['changed'] = empty($row['changed']) ? '' : $this->formatDate($row['changed'], $options);
      $row['last_login'] = empty($row['last_login']) ? '' : $this->formatDate($row['last_login'], $options);
      $row['last_access'] = empty($row['last_access']) ? '' : $this->formatDate($row['last_access'], $options);
      $row['created'] = empty($row['created']) ? '' : $this->formatDate($row['created'], $options);
      \Drupal::moduleHandler()
        ->alter('users_export_row', $row, $row['uid'], $context);
      foreach ($row as $key => $value) {
        $data->add($key, $value);
      }
      $data->next();
    }
    $data->normalize('');
    $exporter->setData($data);
    \Drupal::moduleHandler()->alter('users_export_exporter', $exporter);

    return $this;
  }

  /**
   * Format Date.
   */
  protected function formatDate($value, $options) {
    return $options['date_format'] ? date($options['date_format'], $value) : $value;
  }

}
