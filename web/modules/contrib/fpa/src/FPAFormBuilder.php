<?php

/**
 * @file
 * Contains Drupal\fpa\FPAFormBuilder.
 */

namespace Drupal\fpa;

use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\Html;
use Drupal\Core\Render\Element;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Render\Element\Checkboxes;
use Drupal\Core\Form\FormState;
use Drupal\Core\Link;

/**
 * Class FPAFormBuilder.
 *
 * @package Drupal\fpa
 */
class FPAFormBuilder{

  /**
   * @return int Approximate number of bytes of ram required to render the permissions form.
   */
  public static function getRequiredMemory($suffix = '') {
    $permission = \Drupal::service('user.permissions');
    $permissions_count = count($permission->getPermissions());
    $user_roles_count = count(user_roles());
    $page_ram_required = (9 * 1024 * 1024);
    // Takes ~26kb per row without any checkboxes.
    $permission_row_overhead = 27261.028783658;
    $permissions_ram_required = $permissions_count * $permission_row_overhead;
    // Determined by checking peak ram on permissions page, over several different number of visible roles.
    $bytes_per_checkbox = 18924.508820799;
    $checkboxes_ram_required = $permissions_count * $user_roles_count * $bytes_per_checkbox;
    $output = (int) ($page_ram_required + $permissions_ram_required + $checkboxes_ram_required);
    if (!empty($suffix)) return $output . $suffix;
    return $output;
  }

  public static function checkMemoryLimit() {
    $permissions_memory_required = static::getRequiredMemory('b');
    $memory_limit = ini_get('memory_limit');
    return ((!$memory_limit) || ($memory_limit == -1) || (Bytes::toInt($memory_limit) >= Bytes::toInt($permissions_memory_required)));
  }

  public static function buildFPAPage() {
    $form = \Drupal::service('form_builder')->getForm('\Drupal\user\Form\UserPermissionsForm');

    $render = static::buildTable($form);
    $render['#attached']['library'][] = 'fpa/fpa.permissions';
    $render['#attached']['drupalSettings'] = array(
      'fpa' => array(
        'attr' => array(
          'permission' =>  FPA_ATTR_PERMISSION,
          'module' =>      FPA_ATTR_MODULE,
          'role' =>        FPA_ATTR_ROLE,

          'checked' =>     FPA_ATTR_CHECKED,
          'not_checked' => FPA_ATTR_NOT_CHECKED,

          'system_name' => FPA_ATTR_SYSTEM_NAME,
        )
      )
    );

    return $render;
  }

  protected static function buildTable($form) {
    $renderer = \Drupal::service('renderer');

    $nameless_checkbox = array(
      '#type' => 'html_tag',
      '#tag' => 'input',
      '#attributes' => array(
        'type' => 'checkbox',
        'class' => array(
          'rid-anonymous', // Prevents Drupal core Drupal.behaviors.permissions.toggle from applying.
          'form-checkbox',
          'fpa-checkboxes-toggle',
        ),
      ),
    );

    $dummy_checkbox = array(
      '#type' => 'html_tag',
      '#tag' => 'input',
      '#attributes' => array(
        'type' => 'checkbox',
        'disabled' => 'disabled',
        'checked' => 'checked',
        'title' => t('This permission is inherited from the authenticated user role.'),
        'class' => array(
          'dummy-checkbox',
        ),
      ),
    );

    $dummy_checkbox_output = $renderer->render($dummy_checkbox);

    $permission_col_template = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array(
          'fpa-permission-container',
        ),
      ),
      'description' => array(),
      'checkbox_cell' => array(
        '#type' => 'container',
        '#attributes' => array(
          'class' => array(
            'fpa-row-toggle-container',
          ),
        ),
        'checkbox_form_item' => array(
          '#type' => 'container',
          '#attributes' => array(
            'title' => t('Toggle visible checkboxes in this row.'),
            'class' => array(
              'form-item',
              'form-type-checkbox',
            ),
          ),
          'label' => array(
            '#type' => 'html_tag',
            '#tag' => 'label',
            '#attributes' => array(
              'class' => array(
                'visually-hidden',
              ),
            ),
            '#value' => 'test',
          ),
          'checkbox' => $nameless_checkbox
        ),
      ),
    );

    $roles = \Drupal::service('entity.manager')->getStorage('user_role')->loadMultiple();
//    $site_modules = array_keys(\Drupal::service('module_handler')->getModuleList());

    // Prepare role names processed by Html::getClass() ahead of time.
    $roles_attr_values = array();

    foreach ($roles as $role) {
      $roles_attr_values[$role->get('id')] = Html::getClass($role->get('label'));
    }

//    reset($array);
//    $first_role_index = key($array);

    // Lists for wrapper.
    $modules = array();
    $user_roles = array();

    // Index of current module row.
    $module = NULL;

    // Row counter.
    $i = 0;

    $rows = array();

    foreach (Element::children($form['permissions']) as $key) {

      // Row template.
      $row = array(
        'data' => array(), // Array of table cells.
        'title' => array(), // HTML attribute on table row tag.
        FPA_ATTR_MODULE => array(), // HTML attribute on table row tag.
        FPA_ATTR_PERMISSION => array(), // HTML attribute on table row tag.
        FPA_ATTR_CHECKED => array(),
        FPA_ATTR_NOT_CHECKED => array(),
      );

      $current_element = $form['permissions'][$key];
      hide($form['permissions'][$key]);
      $sub_children = Element::children($current_element);

      // Determine if row is module or permission.
      if (is_numeric($sub_children[0])) {
        // Module row.

        $row['class'][] = 'fpa-module-row';

        // Mark current row with escaped module name.
        $row[FPA_ATTR_MODULE] = array(
          // System name
          0 => $key,
          // Readable name
          1 => strip_tags($current_element[0]['#markup']),
        );

        // Readable
        hide($form['permissions'][$key][0]);
        $row['data'][] = array(
          'data' => $form['permissions'][$key][0],
          'class' => array('module'),
          'id' => 'module-' . $key,
          'colspan' => count($form['role_names']['#value']) + 1,
        );

        $row['title'] = array($key);

        $row[FPA_ATTR_SYSTEM_NAME] = $row[FPA_ATTR_MODULE][0];

        $classes = array();
        foreach ($row[FPA_ATTR_MODULE] as $item) {
          $classes[] = Html::getClass($item);
        }
        $row[FPA_ATTR_MODULE] = array_unique($classes);

        // Add modules to left-side modules list.
        $modules[$row[FPA_ATTR_MODULE][0]] = array(
          'text' => strip_tags($current_element[0]['#markup']),
          'title' => array($key),
          FPA_ATTR_MODULE => $row[FPA_ATTR_MODULE],
          FPA_ATTR_PERMISSION => array(),
        );

        // Save row number for current module.
        $module = $i;
      }
      else {
        // Permission row.
        $row['class'][] = 'fpa-permission-row';
        $roles_keys = array_keys($roles_attr_values);

        $permission_system_name = $form['permissions'][$key]['description']['#context']['title']->render();
        // TODO: find out why this was done in D7
//        $permission_system_name = '';
//        // Might be empty if no modules are displayed in Permissions Filter module.
//        if (!empty($sub_children[$roles_keys[0]])) {
//          $permission_system_name = $sub_children[$roles_keys[0]['#return_value'];
//        }

        $label = $permission_col_template;

        $label['description'] = $current_element['description'];

        // TODO: work on integration with permission filter module
//        // Permissions filter might cause no Roles to display.
//        if (count(element_children($form['checkboxes'])) == 0) {
//          unset($label['checkbox_cell']);
//        }

        // Readable
        $row['data'][] = array(
          'data' => $label,
          'class' => array('permission'),
        );

        foreach ($roles_keys as $rid) {
          $checkbox = $form['permissions'][$key][$rid];
          hide($form['permissions'][$key][$rid]);
          $checkbox['#title'] = $roles[$rid]->get('label') . ': ' . $checkbox['#title'];
          $checkbox['#title_display'] = 'invisible';

          // Filter permissions strips role id class from checkbox. Used by Drupal core functionality.
          $checkbox['#attributes']['class'][] = 'rid-' . $rid;

          // Set authenticated role behavior class on page load.
          if ($rid == 'authenticated' && $checkbox['#checked'] === TRUE) {
            $row['class'][] = 'fpa-authenticated-role-behavior';
          }

          // For all roles that inherit permissions from 'authenticated user' role, add in dummy checkbox for authenticated role behavior.
          // TODO: needs further testing
          if ($rid != 'anonymous' && $rid != 'authenticated') {
            $checkbox['#suffix'] = $dummy_checkbox_output; // '#suffix' doesn't have wrapping HTML like '#field_suffix'.
          }

          // Add rid's to row attribute for checked status filter.
          if ($checkbox['#checked'] === TRUE) {
            $row[FPA_ATTR_CHECKED][] = $rid;
          }
          else {
            $row[FPA_ATTR_NOT_CHECKED][] = $rid;
          }

          $row['data'][] = array(
            'data' => $checkbox,
            'class' => array(
              'checkbox',
            ),
            'title' => array(
              $roles[$rid]->get('label'),
            ),
            // For role filter
            FPA_ATTR_ROLE => array(
              $rid,
            ),
          );
        }

        if (!empty($rid)) {
          $row['title'] = array(
            $key,
          );

          $row[FPA_ATTR_SYSTEM_NAME] = array(
            $key,
          );
        }

        // Mark current row with escaped permission name.
        $row[FPA_ATTR_PERMISSION] = array(
          // Permission system name.
          0 => $permission_system_name,
          // Readable description.
          1 => $form['permissions'][$key]['description']['#context']['title']->render(),
        );

        // Mark current row with current module.
        $row[FPA_ATTR_MODULE] = $rows[$module][FPA_ATTR_MODULE];

        $classes = array();
        foreach ($row[FPA_ATTR_PERMISSION] as $item) {
          $classes[] = Html::getClass($item);
        }
        $row[FPA_ATTR_PERMISSION] = array_unique($classes);

        // Add current permission to current module row.
        $rows[$module][FPA_ATTR_PERMISSION] = array_merge($rows[$module][FPA_ATTR_PERMISSION], $row[FPA_ATTR_PERMISSION]);

        $rows[$module][FPA_ATTR_CHECKED] = array_unique(array_merge($rows[$module][FPA_ATTR_CHECKED], $row[FPA_ATTR_CHECKED]));
        $rows[$module][FPA_ATTR_NOT_CHECKED] = array_unique(array_merge($rows[$module][FPA_ATTR_NOT_CHECKED], $row[FPA_ATTR_NOT_CHECKED]));

        $modules[$rows[$module][FPA_ATTR_MODULE][0]][FPA_ATTR_PERMISSION][] = $row[FPA_ATTR_PERMISSION];
      }

      $rows[$i++] = $row;
    }

    $reset_button = array(
      '#type' => 'html_tag',
      '#tag' => 'input',
      '#attributes' => array(
        'type' => 'reset',
        'class' => 'form-submit',
        'value' => t('Reset changes'),
      ),
    );

    // If there is no submit button, don't add the reset button.
    if (count(Element::children($form['actions'])) > 0) {

      // Have the reset button appear before the submit button.
      array_unshift($form['actions'], $reset_button);
    }

    $actions_output = [];
    foreach (Element::children($form['actions']) as $key) {
      $actions_output[] = $form['actions'][$key];
    }

    $header = array();

    $header[] = array(
      'data' => [
        'label' => [
          '#type' => 'markup',
          '#markup' => t('Permission'),
        ],
        'actions' => $actions_output
      ],
    );

    foreach ($form['role_names']['#value'] as $rid => $label) {
      hide($form['role_names']['#value'][$rid]);

      $header[] = array(
        'data' => [
          'label' => [
            '#type' => 'markup',
            '#markup' => $label
          ],
          'checkbox' => $nameless_checkbox
        ],
        'class' => array(
          'checkbox',
        ),
        'title' => array(
          $label,
        ),
        FPA_ATTR_ROLE => array(
          $rid,
        ),
      );
      $user_roles[$rid] = $label;
    }

    $table = array(
      'header' => $header,
      'rows' => $rows,
    );

    $table_wrapper = static::buildTableWrapper($table, $modules, $user_roles, $actions_output);

    foreach (Element::children($form) as $key) {
      if ($key == 'actions' || $key == 'permissions') continue;
      $table_wrapper[$key] = $form[$key];
    }

    unset($form['role_names']);
    unset($form['permissions']);
    unset($form['actions']);
    $form['fpa_container'] = $table_wrapper;

    return $form;
  }

  protected static function buildTableWrapper($permissions_table, $modules, $user_roles, $actions_output) {
    $renderer = \Drupal::service('renderer');

    // TODO: find out if there is a sf way to do this
    $same_page = trim(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH), '/') == $_GET['q'];

    $render = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array(
          'fpa-container',
        ),
      ),
    );

    $hiders = array(
      'fpa-hide-descriptions' => array(
        'hide' => t('Hide descriptions'),
        'show' => t('Show descriptions'),
      ),
      'fpa-hide-system-names' => array(
        'hide' => t('Hide system names'),
        'show' => t('Show system names'),
      ),
    );

    $render['#attributes']['class'][] = 'fpa-hide-system-names';

    $hide_container = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array(
          'fpa-toggle-container',
        ),
      ),
    );

    foreach ($hiders as $hide_class => $labels) {
      $hide_container[$hide_class] = array(
        '#theme' => 'link',
        '#text' => '',
        '#path' => '',
        '#options' => array(
          'attributes' => array_merge($labels, array(
            'fpa-toggle-class' => $hide_class,
          )),
          'html' => TRUE,
          'fragment' => ' ',
          'external' => TRUE, // Prevent base path from being added to link.
        ),
      );
    }

    $render['hide_container'] = $hide_container;

    $wrapper = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array(
          'fpa-wrapper',
        ),
      ),
    );

    $render['wrapper'] = &$wrapper;


    /**
     * <style /> block template.
     */
    $style_template = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array(
          'style-wrapper-class-name', // Override on specific block.
        ),
      ),
    );

    $style_template['style'] = array(
      '#type' => 'html_tag',
      '#tag' => 'style',
      '#attributes' => array(
        'type' => array(
          'text/css',
        ),
      ),
      '#value' => '', // #value needed for closing tag.
    );

    /**
     * <style /> block for role filtering.
     */
    $wrapper['role_styles'] = $style_template;
    $wrapper['role_styles']['#attributes']['class'][0] = 'fpa-role-styles';

    /**
     * <style /> block for permission filtering.
     */
    $wrapper['perm_styles'] = $style_template;
    $wrapper['perm_styles']['#attributes']['class'][0] = 'fpa-perm-styles';

    /**
     * Left section contains module list and form submission button.
     */
    $left_section = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array(
          'fpa-left-section',
        ),
      ),
    );

    $wrapper['left_section'] = &$left_section;


    /**
     * Right section contains filter form and permissions table.
     */
    $right_section = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array(
          'fpa-right-section',
        ),
      ),
    );

    $wrapper['right_section'] = &$right_section;

    $module_template = array(
      '#wrapper_attributes' => array(
        FPA_ATTR_MODULE => array(),
        FPA_ATTR_PERMISSION => array()
      ),
      'data' => array(
        '#type' => 'container',
        '#attributes' => array(),

        'link' => NULL,

        'counters' => array(),

        'total' => array(
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => array(
            'class' => array('fpa-perm-total'),
            'fpa-total' => 0,
          ),
          '#value' => '', // #value needed for closing tag.
        ),
      ),
    );

    $counter_template = array(
      '#type' => 'html_tag',
      '#tag' => 'span',
      '#attributes' => array(
        'class' => array('fpa-perm-counter'),
        FPA_ATTR_PERMISSION => array(), // Counters only count permissions match.
      ),
      '#value' => '', // #value required for closing tag.
    );

    $items = array();

    $all_modules = array(
      'text' => t('All modules'),
      FPA_ATTR_MODULE => array(),
      FPA_ATTR_PERMISSION => array(),
    );

    array_unshift($modules, $all_modules);

    $all_modules_counters = array();

    foreach ($modules as $module) {

      $module_item = $module_template;

      $module_item['#wrapper_attributes'][FPA_ATTR_MODULE] = $module[FPA_ATTR_MODULE];
      $module_item['#wrapper_attributes'][FPA_ATTR_PERMISSION] = array_reduce($module[FPA_ATTR_PERMISSION], 'array_merge', array());

      // Use link for accessibility and tabability.
      $options = array(
        'fragment' => 'all',
      );

      if (!empty($module['title'])) {
        $options['fragment'] = 'module-' . $module['title'][0];
        $options['attributes']['title'] = $module['title'][0];
      }

      $module_item['data']['link'] = Link::createFromRoute($module['text'], 'user.admin_permissions', array(), $options)->toRenderable();

      foreach ($module[FPA_ATTR_PERMISSION] as $module_perm) {
        $counter_item = $counter_template;
        $counter_item['#attributes'][FPA_ATTR_PERMISSION] = $module_perm;
        $all_modules_counters[] = $counter_item;
        $module_item['data']['counters'][] = $counter_item;
      }

      $module_item['data']['total']['#attributes']['fpa-total'] = count($module[FPA_ATTR_PERMISSION]);

      $items[] = $module_item;
    }

    $items[0]['data']['counters'] = $all_modules_counters;
    $items[0]['data']['total']['#attributes']['fpa-total'] = count($all_modules_counters);

    $left_section['list'] = array(
      '#items' => $items,
      '#theme' => 'item_list',
    );

    $left_section['buttons'] = $actions_output;

    $filter_form = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array(
          'fpa-filter-form',
        ),
      ),
    );

    $clear_button = array(
      '#type' => 'html_tag',
      '#tag' => 'input',
      '#attributes' => array(
        'type' => array(
          'button',
        ),
        'class' => array(
          'fpa-clear-search',
          'form-submit',
        ),
        'value' => 'Clear filter',
      ),
    );

    $default_filter = '';

    if (!empty($_GET['fpa_perm'])) {
      $default_filter = $_GET['fpa_perm'];
    }

    if (!empty($_COOKIE['fpa_filter']) && $same_page) {
      $default_filter = $_COOKIE['fpa_filter'];
    }


    $filter_form['permission_module_filter'] = array(
      '#type' => 'textfield',
      '#title' => t('Filter:'),
      '#size' => 25,
      '#field_suffix' => $renderer->render($clear_button),
      '#attributes' => array(
        'placeholder' => array(
          'permission@module',
        ),
        'autofocus' => 'autofocus',
      ),
      '#value' => $default_filter,
      '#description' => t('<p>Enter in the format of "permission@module",</p><p>e.g. <em>admin@system</em> will show only permissions with the<br>text "admin" in modules with the text "system".</p><p>This will also match on system name of a permission.</p>'),
    );

    /*
     * Populate the permission filter styles.
     */
    $matches = array();

    preg_match('/^\s*([^@]*)@?(.*?)\s*$/i', $filter_form['permission_module_filter']['#value'], $matches);

    array_shift($matches); // Remove whole match item.

    $safe_matches = array();
    foreach($matches as $match) {
      $safe_matches[] = Html::getClass($match);
    }

    $module_match = !empty($_COOKIE['module_match']) ? $_COOKIE['module_match'] : '*=';

    $filters = array(
      Unicode::strlen($safe_matches[0]) > 0 ? ('[' . FPA_ATTR_PERMISSION .               '*="' . $safe_matches[0] . '"]') : '',
      Unicode::strlen($safe_matches[1]) > 0 ? ('[' . FPA_ATTR_MODULE     . $module_match . '"' . $safe_matches[1] . '"]') : '',
    );

    $filter_styles = array(
      '.fpa-table-wrapper tr[' . FPA_ATTR_MODULE . ']{display: none;}',

      '.fpa-table-wrapper tr[' . FPA_ATTR_MODULE . ']',
      $filters[0],
      $filters[1],
      '{display: table-row;}',


      '.fpa-perm-counter{display: none;}',
      '.fpa-perm-counter',
      $filters[0],
      '{display: inline;}',


      '.fpa-left-section li[' . FPA_ATTR_MODULE . ']',
      Unicode::strlen($filters[1]) > 0 ? $filters[1] : '[' . FPA_ATTR_MODULE . '=""]',
      '{margin-right:-1px; background-color: white; border-right: solid 1px transparent;}',
    );

    $wrapper['perm_styles']['style']['#value'] = implode('', $filter_styles);


    $cookie_roles = (!empty($_COOKIE['fpa_roles']) && $same_page) ? json_decode($_COOKIE['fpa_roles']) : array();

    $options = array(
      '*' => t('--All Roles'),
    );

    if (!empty($user_roles)) {
      $options += $user_roles; // Preserves keys.
    }

    if (in_array('*', $cookie_roles)) {
      $cookie_roles  = array('*');
    }

    $filter_form['role_filter'] = array(
      '#type' => 'select',
      '#title' => t('Roles:'),
      '#description' => t('Select which roles to display.<br>Ctrl+click to select multiple.'),
      '#size' => 5,
      '#options' => $options,
      '#attributes' => array(
        'multiple' => 'multiple',
        'autocomplete' => 'off', // Keep browser from populating this from 'cached' input.
      ),
      '#value' => count(array_intersect($cookie_roles, array_keys($options))) > 0 ? $cookie_roles : array('*'),
    );

    /*
     * Populate the roles styles.
     */
    if (!in_array('*', $filter_form['role_filter']['#value'])) {

      $role_styles = array(
        '.fpa-table-wrapper [' . FPA_ATTR_ROLE . '] {display: none;}',
      );

      foreach ($filter_form['role_filter']['#value'] as $value) {

        $role_styles[] = '.fpa-table-wrapper [' . FPA_ATTR_ROLE . '="' . $value . '"] {display: table-cell;}';
      }

      $role_styles[] = '.fpa-table-wrapper [' . FPA_ATTR_ROLE . '="' . end($filter_form['role_filter']['#value']) . '"] {border-right: 1px solid #bebfb9;}';

      $wrapper['role_styles']['style']['#value'] = implode('', $role_styles);
    }

    $checked_status = array(
      '#type' => 'checkboxes',
      '#title' => t('Display permissions that are:'),
      '#options' => array(
        FPA_ATTR_CHECKED => t('Checked'),
        FPA_ATTR_NOT_CHECKED => t('Not Checked'),
      ),
      '#attributes' => array(),
      '#title_display' => 'before',
      '#description' => t('Applies to all visible roles.<br />Unsaved changes are not counted.<br />Most effective when a single role is visible.<br />Empty module rows sometimes display when used with permission filter.'),
    );

    $checked_status_keys = array_keys($checked_status['#options']);

    $checked_status['#value'] = array_combine($checked_status_keys, $checked_status_keys);

    $pseudo_form = array();
    $filter_form['checked_status'] = Checkboxes::processCheckboxes($checked_status, new FormState(), $pseudo_form);

    foreach (Element::children($filter_form['checked_status']) as $key) {
      $filter_form['checked_status'][$key]['#checked'] = TRUE;
    }

    $right_section['filter_form'] = $filter_form;

    $table_wrapper = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array(
          'fpa-table-wrapper',
        ),
      ),
    );

    $table_wrapper['table'] = array(
      '#theme' => 'table',
      '#header' => $permissions_table['header'],
      '#rows' => $permissions_table['rows'],
      '#attributes' => array(
        'id' => 'permissions',
      ),
    );

    // Show after full table HTML is loaded. Reduces progressive table load reflow/repaint.
    $table_wrapper['show_table'] = array(
      '#type' => 'html_tag',
      '#tag' => 'style',
      '#attributes' => array(
        'type' => array(
          'text/css',
        ),
      ),
      '#value' => '#permissions {display: table;} .fpa-table-wrapper {background: none;}',
    );

    $table_wrapper['buttons'] = $actions_output;

    $right_section['table_wrapper'] = $table_wrapper;

    return $render;
  }
}