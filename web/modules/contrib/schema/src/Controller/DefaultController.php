<?php
/**
 * @file
 * Contains \Drupal\schema\Controller\DefaultController.
 */

namespace Drupal\schema\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Default controller for the schema module.
 */
class DefaultController extends ControllerBase {

  public function schema_sql($engine = NULL) {
    $schema = schema_get_schema(TRUE);
    $sql = '';
    foreach ($schema as $name => $table) {
      if (substr($name, 0, 1) == '#') {
        continue;
      }
      if ($engine) {
        $stmts = call_user_func('schema_' . $engine . '_create_table_sql', $table);
      }
      else {
        $stmts = schema_dbobject()->getCreateTableSql($name, $table);
      }
      $sql .= implode(";\n", $stmts) . ";\n\n";
    }

    return array(
      '#type' => 'textarea',
      '#rows' => 30,
      '#value' => $sql,
      '#attributes' => array(
        'style' => 'width:100%;'
      )
    );
  }

  public function schema_show() {
    $schema = schema_get_schema(TRUE);
    return array(
      '#type' => 'textarea',
      '#rows' => 30,
      '#value' => var_export($schema, TRUE),
      '#attributes' => array(
        'style' => 'width:100%;'
      )
    );
  }

  public function schema_compare() {
    $build = array();

    $states = array(
      'same' => t('Match'),
      'different' => t('Mismatch'),
      'missing' => t('Missing'),
    );
    $descs = array(
      'same' => t('Tables for which the schema and database agree.'),
      'different' => t('Tables for which the schema and database are different.'),
      'missing' => t('Tables in the schema that are not present in the database.'),
    );

    $schema = schema_get_schema(TRUE);
    $info = schema_compare_schemas($schema);

    // The info array is keyed by state (same/different/missing/extra/warn). For missing,
    // the value is a simple array of table names. For warn, it is a simple array of warnings.
    // Get those out of the way first
    if (isset($info['warn'])) {
      foreach ($info['warn'] as $message) {
        drupal_set_message($message, 'warning');
      }
      unset($info['warn']);
    }

    $build['extra'] = array(
      '#type' => 'details',
      '#title' => t('Extra (@count)', array('@count' => isset($info['extra']) ? count($info['extra']) : 0)),
      '#description' => t('Tables in the database that are not present in the schema. This indicates previously installed modules that are disabled but not un-installed or modules that do not use the Schema API.'),
      '#weight' => 50,
    );
    $build['extra']['tablelist'] = array(
      '#theme' => 'item_list',
      '#items' => isset($info['extra']) ? $info['extra'] : array(),
    );
    unset($info['extra']);

    // For the other states, the value is an array keyed by module name. Each value
    // in that array is an array keyed by tablename, and each of those values is an
    // array containing 'status' (same as the state), an array of reasons, and an array of notes.
    $weight = 0;
    foreach ($info as $state => $modules) {
      // We'll fill in the fieldset title below, once we have the counts
      $build[$state] = array(
        '#type' => 'details',
        '#description' => $descs[$state],
        '#weight' => $weight++,
      );
      $counts[$state] = 0;

      foreach ($modules as $module => $tables) {
        $counts[$state] += count($tables);
        $build[$state][$module] = array(
          '#type' => 'details',
          '#title' => $module,
        );
        switch ($state) {
          case 'same':
          case 'missing':
            $build[$state][$module]['tablelist'] = array(
              '#theme' => 'item_list',
              '#items' => array_keys($tables),
            );
            break;

          case 'different':
            $items = array();
            foreach ($tables as $name => $stuff) {
              $build[$state][$module][$name] = array(
                '#type' => 'details',
                '#title' => $name,
              );
              $build[$state][$module][$name]['reasons'] = array(
                '#theme' => 'item_list',
                '#items' => array_merge($tables[$name]['reasons'], $tables[$name]['notes']),
              );
            }
            break;
        }
      }
    }

    // Fill in counts in titles
    foreach ($states as $state => $description) {
      $build[$state]['#title'] = t('@state (@count)', array(
        '@state' => $states[$state],
        '@count' => isset($counts[$state]) ? $counts[$state] : 0
      ));
    }

    return $build;
  }

  public function schema_describe() {
    $build = array();

    $schema = schema_get_schema(TRUE);
    ksort($schema);
    $row_hdrs = array(t('Name'), t('Type[:Size]'), t('Null?'), t('Default'));

    $default_table_description = t('TODO: please describe this table!');
    $default_field_description = t('TODO: please describe this field!');
    foreach ($schema as $t_name => $t_spec) {
      $rows = array();
      foreach ($t_spec['fields'] as $c_name => $c_spec) {
        $row = array();
        $row[] = $c_name;
        $type = $c_spec['type'];
        if (!empty($c_spec['length'])) {
          $type .= '(' . $c_spec['length'] . ')';
        }
        if (!empty($c_spec['scale']) && !empty($c_spec['precision'])) {
          $type .= '(' . $c_spec['precision'] . ', ' . $c_spec['scale'] . ' )';
        }
        if (!empty($c_spec['size']) && $c_spec['size'] != 'normal') {
          $type .= ':' . $c_spec['size'];
        }
        if ($c_spec['type'] == 'int' && !empty($c_spec['unsigned'])) {
          $type .= ', unsigned';
        }
        $row[] = $type;
        $row[] = !empty($c_spec['not null']) ? 'NO' : 'YES';
        $row[] = isset($c_spec['default']) ? (is_string($c_spec['default']) ? '\'' . $c_spec['default'] . '\'' : $c_spec['default']) : '';
        $rows[] = $row;
        if (!empty($c_spec['description']) && $c_spec['description'] != $default_field_description) {
          $desc = _schema_process_description($c_spec['description']);
          $rows[] = array(
            array(
              'colspan' => count($row_hdrs),
              'data' => $desc
            )
          );
        }
        else {
          drupal_set_message(_schema_process_description(t('Field {!table}.@field has no description.', array(
            '!table' => $t_name,
            '@field' => $c_name
          ))), 'warning');
        }
      }

      if (empty($t_spec['description']) || $t_spec['description'] == $default_table_description) {
        drupal_set_message(_schema_process_description(t('Table {!table} has no description.', array('!table' => $t_name))), 'warning');
      }

      $build[$t_name] = array(
        '#type' => 'fieldset',
        '#title' => t('@table (@module module)',
          array(
            '@table' => $t_name,
            '@module' => isset($t_spec['module']) ? $t_spec['module'] : ''
          )),
        '#description' => !empty($t_spec['description']) ? _schema_process_description($t_spec['description']) : '',
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
        '#attributes' => array('id' => 'table-' . $t_name),
      );
      $build[$t_name]['content'] = array(
        '#theme' => 'table',
        '#header' => $row_hdrs,
        '#rows' => $rows,
      );
    }

    return $build;
  }

  public function schema_inspect() {
    $build = array();
    $schema = schema_get_schema(TRUE);
    $inspect = schema_dbobject()->inspect();

    foreach ($inspect as $name => $table) {
      $module = isset($schema[$name]['module']) ? $schema[$name]['module'] : 'Unknown';
      if (!isset($build[$module])) {
        $build[$module] = array(
          '#type' => 'details',
          '#title' => $module,
        );
      }
      $build[$module][$name] = array(
        '#type' => 'textarea',
        '#rows' => 10,
        '#value' => schema_phpprint_table($name, $table),
        '#attributes' => array(
          'style' => 'width:100%;'
        )
      );
    }

    // Sort by keys, i.e. the module name, then insert weights respectively.
    ksort($build);
    $i = 0;
    array_map(function ($v) use ($i) {
      if ($v['#title'] == 'Unknown') $weight = -50;
      else $weight = $i++;
      $v['#weight'] = $weight;
    }, $build);

    return $build;
  }
}
