<?php

/**
 * @file
 * Drush integration for the nettybot module.
 */

/**
 * Implements hook_drush_command().
 */
function nettybot_drush_command() {
  $update_options = array(
    'lock' => array(
      'description' => 'Add a persistent lock to remove the specified projects from consideration during updates. Locks may be removed with the --unlock parameter, or overridden by specifically naming the project as a parameter to pm-update or pm-updatecode. The lock does not affect pm-download. See also the update_advanced project for similar and improved functionality.',
      'example-value' => 'foo,bar',
    ),
  );
  $update_suboptions = array(
    'lock' => array(
      'lock-message' => array(
        'description' => 'A brief message explaining why a project is being locked; displayed during pm-updatecode. Optional.',
        'example-value' => 'message',
      ),
      'unlock' => array(
        'description' => 'Remove the persistent lock from the specified projects so that they may be updated again.',
        'example-value' => 'foo,bar',
      ),
    ),
  );
  $items['nb-updatestatus'] = array(
    'description' => 'Show a report of available minor updates to Drupal core and contrib projects.',
    'arguments' => array(
      'projects' => 'Optional. A list of installed projects to show.',
    ),
    'options' => array(
      'pipe' => 'Return a list of the projects with any extensions enabled that need updating, one project per line.',
    ) + $update_options,
    'sub-options' => $update_suboptions,
    'engines' => array(
      'update_status',
    ),
    'outputformat' => array(
      'default' => 'table',
      'pipe-format' => 'list',
      'field-labels' => array('name' => 'Short Name', 'label' => 'Name', 'existing_version' => 'Installed Version', 'status' => 'Status', 'status_msg' => 'Message', 'candidate_version' => 'Proposed version'),
      'fields-default' => array('label', 'existing_version', 'candidate_version', 'status_msg' ),
      'fields-pipe' => array('name', 'existing_version', 'candidate_version', 'status_msg'),
      'output-data-type' => 'format-table',
    ),
  );
  return $items;
}

/**
 * Command callback. Displays update status info of installed projects.
 *
 * Pass specific projects as arguments, otherwise we show all that are
 * updateable.
 */
function drush_nettybot_nb_updatestatus() {
  drush_errors_off();
  drush_command_include('pm-updatestatus');
  // Get specific requests.
  $args = pm_parse_arguments(func_get_args(), FALSE);

  // Get installed extensions and projects.
  $extensions = drush_get_extensions();
  $projects = nettybot_get_projects($extensions);

  // Parse out project name and version.
  $requests = array();
  foreach ($args as $request) {
    $request = pm_parse_request($request, NULL, $projects);
    $requests[$request['name']] = $request;
  }

  // Get the engine instance.
  $update_status = drush_get_engine('update_status');

  // If the user doesn't provide a value for check-disabled option,
  // and the update backend is 'drupal', use NULL, so the engine
  // will respect update.module defaults.
  $check_disabled_default = ($update_status->engine == 'drupal') ? NULL : FALSE;
  $check_disabled = drush_get_option('check-disabled', $check_disabled_default);

  // D8 - NB disable projects found in settings.php.
  $project_exceptions = \Drupal::config('nb-updatestatus-project-exceptions')->get();
  $project_exceptions = (empty($project_exceptions) ? [] : $project_exceptions);
  foreach ($project_exceptions as $project_exception) {
    if (isset($projects[$project_exception])) {
      unset($projects[$project_exception]);
    }
  }
  // Before going to the more detailed check of available release do a shallow check first
  // and remove project if shallow check indicates problem.
  $available = nettybot_getavailablereleases($projects);
  foreach ($projects as $project_name => $project) {
    if (!in_array($project_name, $available)) {
      unset($projects[$project_name]);
    }
  }
  $update_info = $update_status->getStatus($projects, $check_disabled);

  foreach ($extensions as $name => $extension) {
    // Add an item to $update_info for each enabled extension which was obtained
    // from cvs or git and its project is unknown (because of cvs_deploy or
    // git_deploy is not enabled).
    if (!isset($extension->info['project'])) {
      if ((isset($extension->vcs)) && ($extension->status)) {
        $update_info[$name] = array(
          'name' => $name,
          'label' => $extension->label,
          'existing_version' => 'Unknown',
          'status' => DRUSH_UPDATESTATUS_PROJECT_NOT_PACKAGED,
          'status_msg' => dt('Project was not packaged by drupal.org but obtained from !vcs. You need to enable !vcs_deploy module', array('!vcs' => $extension->vcs)),
        );
        // The user may have requested to update a project matching this
        // extension. If it was by coincidence or error we don't mind as we've
        // already added an item to $update_info. Just clean up $requests.
        if (isset($requests[$name])) {
          unset($requests[$name]);
        }
      }
    }
    // Additionally if the extension name is distinct to the project name and
    // the user asked to update the extension, fix the request.
    elseif ((isset($requests[$name])) && ($name != $extension->info['project'])) {
      $requests[$extension->info['project']] = $requests[$name];
      unset($requests[$name]);
    }
  }
  // If specific project updates were requested then remove releases for all
  // others.
  $requested = func_get_args();
  if (!empty($requested)) {
    foreach ($update_info as $name => $project) {
      if (!isset($requests[$name])) {
        unset($update_info[$name]);
      }
    }
  }
  // Add an item to $update_info for each request not present in $update_info.
  foreach ($requests as $name => $request) {
    if (!isset($update_info[$name])) {
      // Disabled projects.
      if ((isset($projects[$name])) && ($projects[$name]['status'] == 0)) {
        $update_info[$name] = array(
          'name' => $name,
          'label' => $projects[$name]['label'],
          'existing_version' => $projects[$name]['version'],
          'status' => DRUSH_UPDATESTATUS_REQUESTED_PROJECT_NOT_UPDATEABLE,
        );
        unset($requests[$name]);
      }
      // At this point we are unable to find matching installed project.
      // It does not exist at all or it is misspelled,...
      else {
        $update_info[$name] = array(
          'name' => $name,
          'label' => $name,
          'existing_version' => 'Unknown',
          'status'=> DRUSH_UPDATESTATUS_REQUESTED_PROJECT_NOT_FOUND,
        );
      }
    }
  }

  // If specific versions were requested, match the requested release.
  foreach ($requests as $name => $request) {
    if (!empty($request['version'])) {
      if (empty($update_info[$name]['releases'][$request['version']])) {
        $update_info[$name]['status'] = DRUSH_UPDATESTATUS_REQUESTED_VERSION_NOT_FOUND;
      }
      elseif ($request['version'] == $update_info[$name]['existing_version']) {
        $update_info[$name]['status'] = DRUSH_UPDATESTATUS_REQUESTED_VERSION_CURRENT;
      }
      // TODO: should we warn/reject if this is a downgrade?
      else {
        $update_info[$name]['status'] = DRUSH_UPDATESTATUS_REQUESTED_VERSION_NOT_CURRENT;
        $update_info[$name]['candidate_version'] = $request['version'];
      }
    }
  }
  // Process locks specified on the command line.
  $locked_list = drush_pm_update_lock($update_info, drush_get_option_list('lock'), drush_get_option_list('unlock'), drush_get_option('lock-message'));

  // Build project updatable messages, set candidate version and mark
  // 'updateable' in the project.
  foreach ($update_info as $key => $project) {
    switch($project['status']) {
      case DRUSH_UPDATESTATUS_NOT_SECURE:
        $status = dt('SECURITY UPDATE available');
        pm_release_recommended($project);
        break;
      case DRUSH_UPDATESTATUS_REVOKED:
        $status = dt('Installed version REVOKED');
        pm_release_recommended($project);
        break;
      case DRUSH_UPDATESTATUS_NOT_SUPPORTED:
        $status = dt('Installed version not supported');
        pm_release_recommended($project);
        break;
      case DRUSH_UPDATESTATUS_NOT_CURRENT:
        $status = dt('Update available');
        pm_release_recommended($project);
        break;
      case DRUSH_UPDATESTATUS_CURRENT:
        $status = dt('Up to date');
        pm_release_recommended($project);
        $project['updateable'] = FALSE;
        break;
      case DRUSH_UPDATESTATUS_NOT_CHECKED:
      case DRUSH_UPDATESTATUS_NOT_FETCHED:
      case DRUSH_UPDATESTATUS_FETCH_PENDING:
        $status = dt('Unable to check status');
        break;
      case DRUSH_UPDATESTATUS_PROJECT_NOT_PACKAGED:
        $status = $project['status_msg'];
        break;
      case DRUSH_UPDATESTATUS_REQUESTED_PROJECT_NOT_UPDATEABLE:
        $status = dt('Project has no enabled extensions and can\'t be updated');
        break;
      case DRUSH_UPDATESTATUS_REQUESTED_PROJECT_NOT_FOUND:
        $status = dt('Specified project not found');
        break;
      case DRUSH_UPDATESTATUS_REQUESTED_VERSION_NOT_FOUND:
        $status = dt('Specified version not found');
        break;
      case DRUSH_UPDATESTATUS_REQUESTED_VERSION_CURRENT:
        $status = dt('Specified version already installed');
        break;
      case DRUSH_UPDATESTATUS_REQUESTED_VERSION_NOT_CURRENT:
        $status = dt('Specified version available');
        $project['updateable'] = TRUE;
        break;
      default:
        $status = dt('Unknown');
        break;
    }

    if (isset($project['locked'])) {
      $status = $project['locked'] . " ($status)";
    }
    // Persist candidate_version in $update_info (plural).
    if (empty($project['candidate_version'])) {
      $update_info[$key]['candidate_version'] = $project['existing_version']; // Default to no change
    }
    else {
      $update_info[$key]['candidate_version'] = $project['candidate_version'];
    }
    $update_info[$key]['status_msg'] = $status;
    if (isset($project['updateable'])) {
      $update_info[$key]['updateable'] = $project['updateable'];
    }
  }
  // Filter projects to show.
  return pm_project_filter($update_info, drush_get_option('security-only'));
}

/**
 * Obtain an array of installed projects off the extensions available.
 *
 * A project is considered to be 'enabled' when any of its extensions is
 * enabled.
 * If any extension lacks project information and it is found that the
 * extension was obtained from drupal.org's cvs or git repositories, a new
 * 'vcs' attribute will be set on the extension. Example:
 *   $extensions[name]->vcs = 'cvs';
 *
 * @param array $extensions
 *   Array of extensions as returned by drush_get_extensions().
 *
 * @return
 *   Array of installed projects with info of version, status and provided
 * extensions.
 */
function nettybot_get_projects(&$extensions = NULL) {
  if (!isset($extensions)) {
    $extensions = drush_get_extensions();
  }
  $projects = array(
    'drupal' => array(
      'label'      => 'Drupal',
      'version'    => drush_drupal_version(),
      'type'       => 'core',
      'extensions' => array(),
    )
  );
  if (isset($extensions['system']->info['datestamp'])) {
    $projects['drupal']['datestamp'] = $extensions['system']->info['datestamp'];
  }
  foreach ($extensions as $extension) {
    $extension_name = drush_extension_get_name($extension);
    $extension_path = drush_extension_get_path($extension);

    // Obtain the project name. It is not available in this cases:
    //   1. the extension is part of drupal core.
    //   2. the project was checked out from CVS/git and cvs_deploy/git_deploy
    //      is not installed.
    //   3. it is not a project hosted in drupal.org.
    if (empty($extension->info['project'])) {
      if (isset($extension->info['version']) && ($extension->info['version'] == drush_drupal_version())) {
        $project = 'drupal';
      }
      else {
        if (is_dir($extension_path . '/CVS') && (!drush_module_exists('cvs_deploy'))) {
          $extension->vcs = 'cvs';
          drush_log(dt('Extension !extension is fetched from cvs. Ignoring.', array('!extension' => $extension_name)), Drush\Log\LogLevel::DEBUG);
        }
        elseif (is_dir($extension_path . '/.git') && (!drush_module_exists('git_deploy'))) {
          $extension->vcs = 'git';
          drush_log(dt('Extension !extension is fetched from git. Ignoring.', array('!extension' => $extension_name)), Drush\Log\LogLevel::DEBUG);
        }
        continue;
      }
    }
    else {
      $project = $extension->info['project'];
    }

    // Create/update the project in $projects with the project data.
    if (!isset($projects[$project])) {
      $projects[$project] = array(
        // If there's an extension with matching name, pick its label.
        // Otherwise use just the project name. We avoid $extension->label
        // for the project label because the extension's label may have
        // no direct relation with the project name. For example,
        // "Text (text)" or "Number (number)" for the CCK project.
        'label'      => isset($extensions[$project]) ? $extensions[$project]->label : $project,
        'type'       => drush_extension_get_type($extension),
        'version'    => $extension->info['version'],
        'status'     => $extension->status,
        'extensions' => array(),
      );
      if (isset($extension->info['datestamp'])) {
        $projects[$project]['datestamp'] = $extension->info['datestamp'];
      }
      if (isset($extension->info['project status url'])) {
        $projects[$project]['status url'] = $extension->info['project status url'];
      }
    }
    else {
      // If any of the extensions is enabled, consider the project is enabled.
      if ($extension->status != 0) {
        $projects[$project]['status'] = $extension->status;
      }
    }
    $projects[$project]['extensions'][] = drush_extension_get_name($extension);
  }

  // Obtain each project's path and try to provide a better label for ones
  // with machine name.
  $reserved = array('modules', 'sites', 'themes');
  foreach ($projects as $name => $project) {
    if ($name == 'drupal') {
      continue;
    }

    // If this project has no human label, see if we can find
    // one "main" extension whose label we could use.
    if ($project['label'] == $name)  {
      // If there is only one extension, construct a label based on
      // the extension name.
      if (count($project['extensions']) == 1) {
        $extension = $extensions[$project['extensions'][0]];
        $projects[$name]['label'] = $extension->info['name'] . ' (' . $name . ')';
      }
      else {
        // Make a list of all of the extensions in this project
        // that do not depend on any other extension in this
        // project.
        $candidates = array();
        foreach ($project['extensions'] as $e) {
          $has_project_dependency = FALSE;
          if (isset($extensions[$e]->info['dependencies']) && is_array($extensions[$e]->info['dependencies'])) {
            foreach ($extensions[$e]->info['dependencies'] as $dependent) {
              if (in_array($dependent, $project['extensions'])) {
                $has_project_dependency = TRUE;
              }
            }
          }
          if ($has_project_dependency === FALSE) {
            $candidates[] = $extensions[$e]->info['name'];
          }
        }
        // If only one of the modules is a candidate, use its name in the label
        if (count($candidates) == 1) {
          $projects[$name]['label'] = reset($candidates) . ' (' . $name . ')';
        }
      }
    }

    drush_log(dt('Obtaining !project project path.', array('!project' => $name)), Drush\Log\LogLevel::DEBUG);
    $path = _drush_pm_find_common_path($project['type'], $project['extensions']);
    // Prevent from setting a reserved path. For example it may happen in a case
    // where a module and a theme are declared as part of a same project.
    // There's a special case, a project called "sites", this is the reason for
    // the second condition here.
    if ($path == '.' || (in_array(basename($path), $reserved) && !in_array($name, $reserved))) {
      drush_log(dt('Error while trying to find the common path for enabled extensions of project !project. Extensions are: !extensions.', array('!project' => $name, '!extensions' => implode(', ', $project['extensions']))), Drush\Log\LogLevel::DEBUG);
    }
    else {
      $projects[$name]['path'] = $path;
    }
  }

  return $projects;
}

/**
 * Shallow check of available releases for list of projects.
 */
function nettybot_getavailablereleases($projects) {
  $release_info = drush_include_engine('release_info', 'updatexml');
  $available = array();
  foreach ($projects as $project_name => $project) {
    // Discard projects with unknown installation path.
    if ($project_name != 'drupal' && !isset($project['path'])) {
      continue;
    }
    $request = $project_name . (isset($project['core']) ? '-' . $project['core'] : '');
    $request = pm_parse_request($request, NULL, $projects);
    $status_url = isset($request['status url']) ? $request['status url'] : Drush\UpdateService\ReleaseInfo::DEFAULT_URL;
    $url = $status_url . '/' . $request['name'] . '/' . $request['drupal_version'];
    $path = drush_download_file($url, drush_tempnam($request['name']), Drush\UpdateService\ReleaseInfo::CACHE_LIFETIME);
    $xml = simplexml_load_file($path);
    if (empty($xml->releases)) {
      continue;
    }
    $available[] = $project_name;
  }
  return $available;
}
