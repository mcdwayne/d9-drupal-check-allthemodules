<?php

/**
 * @file
 * Provide views data for views_system module.
 */


/**
 * Implements hook_views_data().
 */
function views_system_views_data() {

  $data['views_system']['table']['group'] = t('System');
  $data['views_system']['table']['base'] = array(
    'field' => 'filename',
    'title' => t('Module/Theme/Theme engine'),
    'help' => t('Modules/Themes/Theme engines in your codebase.'),
  );

  $data['views_system']['filename'] = array(
    'title' => t('Filename'),
    'help' => t('The path of the primary file for this item, relative to the Drupal root; e.g. modules/node/node.module.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['views_system']['admin_theme'] = array(
    'title' => t('Administration theme'),
    'help' => t('Boolean indicating whether or not this theme is the administration theme.'),
    'field' => array(
      'id' => 'boolean',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'boolean',
      'label' => t('Is administration theme'),
      'type' => 'yes-no',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['views_system']['base_theme'] = array(
    'title' => t('Base theme'),
    'help' => t('The base theme of this theme.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['views_system']['bootstrap'] = array(
    'title' => t('Bootstrap'),
    'help' => t("Boolean indicating whether this module is loaded during Drupal's early bootstrapping phase (e.g. even before the page cache is consulted)."),
/*
    'field' => array(
      'id' => 'boolean',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'boolean',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
*/
  );

  $data['views_system']['core'] = array(
    'title' => t('Core'),
    'help' => t('The version of Drupal that this item is for.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['views_system']['datestamp'] = array(
    'title' => t('Datestamp'),
    'help' => t('The release date of this item.'),
    'field' => array(
      'id' => 'date',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'date',
    ),
    'argument' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date',
    ),
  );

  $data['views_system']['default_theme'] = array(
    'title' => t('Default theme'),
    'help' => t('Boolean indicating whether or not this theme is the default theme.'),
    'field' => array(
      'id' => 'boolean',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'boolean',
      'label' => t('Is default theme'),
      'type' => 'yes-no',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['views_system']['dependencies'] = array(
    'title' => t('Dependencies'),
    'help' => t('A list of other modules that this module requires.'),
    'field' => array(
      'id' => 'views_system_dependencies',
      'no group by' => TRUE,
      'click sortable' => FALSE,
    ),
  );

  $data['views_system']['description'] = array(
    'title' => t('Description'),
    'help' => t('The brief description of this item.'),
    'field' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['views_system']['engine'] = array(
    'title' => t('Engine'),
    'help' => t('The engine of this theme.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['views_system']['features'] = array(
    'title' => t('Features'),
    'help' => t('A list of available features of this theme.'),
    'field' => array(
      'id' => 'views_system_features',
      'no group by' => TRUE,
      'click sortable' => FALSE,
    ),
  );

  $data['views_system']['info'] = array(
    'title' => t('Info'),
    'help' => t("A serialized array containing information from this item's .info file; keys can include name, description, package, version, core, dependencies, and php."),
    'field' => array(
      'id' => 'serialized',
      'click sortable' => FALSE,
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['views_system']['label'] = array(
    'title' => t('Label'),
    'help' => t('The human readable name of this item.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['views_system']['libraries'] = array(
    'title' => t('Libraries'),
    'help' => t('A list of libraries of this theme.'),
    'field' => array(
      'id' => 'views_system_libraries',
      'no group by' => TRUE,
      'click sortable' => FALSE,
    ),
  );

  $data['views_system']['mtime'] = array(
    'title' => t('Modification time'),
    'help' => t('The info file modification time of this item.'),
    'field' => array(
      'id' => 'date',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'date',
    ),
    'argument' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date',
    ),
  );

  $data['views_system']['name'] = array(
    'title' => t('Name'),
    'help' => t('The name of this item; e.g. node.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['views_system']['origin'] = array(
    'title' => t('Origin'),
    'help' => t('The origin of this item, either core or extension.'),
    'field' => array(
      'id' => 'machine_name',
      'options callback' => 'views_system_get_origin_list',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'in_operator',
      'options callback' => 'views_system_get_origin_list',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['views_system']['owner'] = array(
    'title' => t('Owner'),
    'help' => t("This theme's 'parent'. Can be either a theme or an engine."),
    'field' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['views_system']['package'] = array(
    'title' => t('Package'),
    'help' => t('The name of the package this module belongs to.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['views_system']['pathname'] = array(
    'title' => t('Pathname'),
    'help' => t('The relative path and filename of this item.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['views_system']['php'] = array(
    'title' => t('PHP'),
    'help' => t('The minimum PHP version that this item requires.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['views_system']['project'] = array(
    'title' => t('Project'),
    'help' => t('The name of the project this module belongs to.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['views_system']['project_status_url'] = array(
    'title' => t('Project Status Url'),
    'help' => t('The URL to check for updates for the custom item.'),
    'field' => array(
      'id' => 'url',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['views_system']['regions'] = array(
    'title' => t('Regions'),
    'help' => t('A list of regions of this theme.'),
    'field' => array(
      'id' => 'views_system_regions',
      'no group by' => TRUE,
      'click sortable' => FALSE,
    ),
  );

  $data['views_system']['regions_hidden'] = array(
    'title' => t('Hidden regions'),
    'help' => t('A list of hidden regions of this theme.'),
    'field' => array(
      'id' => 'views_system_regions_hidden',
      'no group by' => TRUE,
      'click sortable' => FALSE,
    ),
  );

  $data['views_system']['required'] = array(
    'title' => t('Required'),
    'help' => t('Boolean indicating whether or not this item is absolutely required.'),
    'field' => array(
      'id' => 'boolean',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'boolean',
      'label' => t('Is required'),
      'type' => 'yes-no',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['views_system']['required_by'] = array(
    'title' => t('Required by'),
    'help' => t('A full list of other items that depends on this item.'),
    'field' => array(
      'id' => 'views_system_required_by',
      'no group by' => TRUE,
      'click sortable' => FALSE,
    ),
  );

  $data['views_system']['requires'] = array(
    'title' => t('Requires'),
    'help' => t('A full list of other items that this item requires.'),
    'field' => array(
      'id' => 'views_system_requires',
      'no group by' => TRUE,
      'click sortable' => FALSE,
    ),
  );

  $data['views_system']['schema_version'] = array(
    'title' => t('Schema version'),
    'help' => t("The module's database schema version number. -1 if the module is not installed (its tables do not exist); 0 or the largest N of the module's hook_update_N() function that has either been run or existed when the module was first installed."),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['views_system']['screenshot'] = array(
    'title' => t('Screenshot'),
    'help' => t('The thumbnail image of this theme.'),
    'field' => array(
      'id' => 'views_system_screenshot',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['views_system']['status'] = array(
    'title' => t('Status'),
    'help' => t('Boolean indicating whether or not this item is enabled.'),
    'field' => array(
      'id' => 'boolean',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'boolean',
      'label' => t('Status'),
      'type' => 'enabled-disabled',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['views_system']['type'] = array(
    'title' => t('Type'),
    'help' => t('The type of this item, either module, theme, or theme_engine.'),
    'field' => array(
      'id' => 'machine_name',
      'options callback' => 'views_system_get_type_list',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'in_operator',
      'options callback' => 'views_system_get_type_list',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['views_system']['version'] = array(
    'title' => t('Version'),
    'help' => t('The version of this item.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['views_system']['visible'] = array(
    'title' => t('Visible'),
    'help' => t('Boolean indicating whether or not this item is visible on the modules and themes page.'),
    'field' => array(
      'id' => 'boolean',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'boolean',
      'label' => t('Is visible'),
      'type' => 'yes-no',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['views_system']['weight'] = array(
    'title' => t('Weight'),
    'help' => t("The order in which this module's hooks should be invoked relative to other modules. Equal-weighted modules are ordered by name."),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  return $data;
}
