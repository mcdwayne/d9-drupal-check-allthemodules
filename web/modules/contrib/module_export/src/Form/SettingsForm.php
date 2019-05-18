<?php

namespace Drupal\module_export\Form;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Class SettingsForm.
 *
 * @package Drupal\purge_users\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'module_export_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['name'] = array(
      '#title' => $this->t('Exported Module Name'),
      '#description' => $this->t('Choose a module name Example: Studio Machine @suggestion', array('@suggestion' => '(Do not begin name with numbers.)')),
      '#type' => 'textfield',
      '#attributes' => array('class' => array('profile-name')),
      '#field_suffix' => '<span class="field-suffix"></span>',
      '#required' => TRUE,
    );

    $form['description'] = array(
      '#title' => $this->t('Description'),
      '#description' => $this->t('Provide a short description for the module.'),
      '#type' => 'textfield',
      '#required' => TRUE,
    );

    $form['module_type'] = array(
      '#title' => $this->t('Select Module Types'),
      '#description' => $this->t('Choose which type of modules to be selected to export.'),
      '#type' => 'select',
      '#options' => array(
        -1 => $this->t('All'),
        1 => $this->t('Enabled'),
        0 => $this->t('Disabled'),
      ),
      '#default_value' => 1,
    );

    $form['format'] = array(
      '#title' => $this->t('Export format'),
      '#type' => 'radios',
      '#options' => array(
        0 => $this->t('Module'),
        1 => $this->t('CSV'),
      ),
      '#default_value' => 0,
    );

    $form['check_version'] = array(
      '#title' => $this->t('Check Minor Version'),
      '#description' => $this->t('If it checked, the all installable module version should be the same.'),
      '#type' => 'checkbox',
      '#default_value' => 0,
      '#states' => array(
        'visible' => array(
          ':input[name="format"]' => array('value' => 0),
        ),
      ),
    );

    $form['use_module'] = array(
      '#type' => 'markup',
      '#markup' => '<p><h4>How to use the exported module.</h4></p><p>Download and extract the module into the modules folder of another Drupal installation. You can then review the module dependencies to check the status of the module or enable it to enable all the required modules.</p>',
      '#states' => array(
        'visible' => array(
          ':input[name="format"]' => array('value' => 0),
        ),
      ),
    );

    $form['download_module'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Download Module'),
      '#attributes' => array(
        'class' => array(
          'button--primary',
        ),
      ),
      '#states' => array(
        'visible' => array(
          ':input[name="format"]' => array('value' => 0),
        ),
      ),
      '#submit' => array('::moduleExportdownloadModule'),
    );

    $form['download_csv'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Download CSV'),
      '#attributes' => array(
        'class' => array(
          'button--primary',
        ),
      ),
      '#states' => array(
        'visible' => array(
          ':input[name="format"]' => array('value' => 1),
        ),
      ),
      '#submit' => array('::moduleExportdownloadcsv'),
    );

    $settings_form = parent::buildForm($form, $form_state);
    unset($settings_form['actions']['submit']);
    return $settings_form;
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['module_export.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $module_name = $form_state->getValue(['name']);
    // Validate text field to only contain numeric values.
    if (preg_match('/^\d/', $module_name) === 1) {
      $form_state->setErrorByName('name', $this->t('Module name should not start with a number.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }

  /**
   * Submit handler for download module as tar file.
   */
  public function moduleExportdownloadModule($form, FormStateInterface $form_state) {
    // Save form submissions.
    $name = $form_state->getValue('name');
    $description = $form_state->getValue('description');
    $module_type = $form_state->getValue('module_type');
    $check_version = $form_state->getValue('check_version');

    $machine_name = preg_replace('/_+/', '_', preg_replace('/[^a-z0-9]/', '_', strtolower($name)));
    $filename = $machine_name . '.info.yml';
    $dir = 'public://' . $machine_name;
    // Create archive file.
    $this->createArchive($name, $description, $machine_name, $filename, $dir, $module_type, $check_version);

    $response = new BinaryFileResponse($dir . '/' . $machine_name . '.tar.gz');
    $filename = $machine_name . '.tar.gz';
    $response->setContentDisposition('attachment', $filename);
    $form_state->setResponse($response);
  }

  /**
   * Submit handler for download modules information as CSV.
   */
  public function moduleExportdownloadcsv($form, FormStateInterface $form_state) {
    // Save form submissions.
    $name = $form_state->getValue('name');
    $module_type = $form_state->getValue('module_type');
    $machine_name = preg_replace('/_+/', '_', preg_replace('/[^a-z0-9]/', '_', strtolower($name)));
    $dir = 'public://';
    $this->moduleExportGetcsvContent($module_type, $dir, $machine_name);
    $filename = $machine_name . '.csv';
    $response = new BinaryFileResponse($dir . '/' . $filename);
    $response->setContentDisposition('attachment', $filename);
    $form_state->setResponse($response);
  }

  /**
   * Get CSV file content.
   */
  public function moduleExportGetcsvContent($module_type, $dir, $machine_name) {
    // Get enabled modules list.
    $modules_array = $this->moduleExportGetModulesList($module_type);
    $get_available_updates = update_get_available(TRUE);
    $filename = $machine_name . '.csv';
    $output = "Title, Package, Installed Version, Current Version, Is latest, Module Url, Dependency, Status \n";

    foreach ($modules_array as $modules_info) {
      foreach ($modules_info as $module) {
        $parent_module = array();
        // Parse module info yml file.
        $module_path = drupal_get_path('module', $module['filename']) . "/" . $module['filename'] . ".info.yml";
        $info_file = Yaml::decode(file_get_contents($module_path));
        if ($module['package'] == 'Core') {
          $parent_module = $get_available_updates['drupal'];
        }
        else {
          if (array_key_exists('project', $info_file)) {
            $parent_project = $info_file['project'];
          }
          else {
            $parent_project = $module['filename'];
          }
          // Get parent module.
          $parent_module = $get_available_updates[$parent_project];
        }

        // Fetch project dependencies.
        if (array_key_exists('dependencies', $info_file)) {
          $dependencies = $info_file['dependencies'];
        }
        else {
          $dependencies = array();
        }

        // Get project link.
        if (array_key_exists('link', $parent_module)) {
          $url = $parent_module['link'];
        }
        else {
          $url = '';
        }

        // Get project releases.
        if (array_key_exists('releases', $parent_module)) {
          $releases = $parent_module['releases'];
        }
        else {
          $releases = array();
        }

        // Check if current version is latest.
        $latest_release = '';
        if (is_array($releases)) {
          reset($releases);
          $latest_release = key($releases);

          if (isset($latest_release) && $latest_release == $module['version']) {
            $is_latest = $this->t('Yes');
          }
          elseif (isset($latest_release) && $latest_release != $module['version']) {
            $is_latest = $this->t('No');
          }
          else {
            $is_latest = '';
          }
        }

        // Replace comma from package name.
        $current_version = (isset($latest_release)) ? $latest_release : '';
        $module['status'] == 1 ? $status = 'Enabled' : $status = 'Disabled';
        $output .= "{$module['name']}, {$module['package']}, {$module['version']}, {$current_version}, {$is_latest}, {$url},";
        if (!empty($dependencies)) {
          $len = count($dependencies);
          foreach ($dependencies as $key => $dependency) {
            $module_dependency = str_replace('drupal:', '', $dependency);
            if ($key == $len - 1) {
              $output .= $module_dependency . ',';
            }
            else {
              $output .= $module_dependency . '|';
            }
          }
        }
        else {
          $output .= ' ,';
        }
        $output .= $status . "\n";
      }
    }

    return file_save_data($output, $dir . '/' . $filename, FILE_EXISTS_REPLACE);
  }

  /**
   * Get module file content.
   */
  public function moduleExportGetModulesList($module_type) {
    // Get all modules.
    $modules = system_rebuild_module_data();
    // Sort modules by name.
    uasort($modules, 'system_sort_modules_by_info_name');
    $modules_list = array();
    foreach ($modules as $filename => $module) {
      $package = $module->info['package'];
      if ($module_type == 1) {
        if ($module->status == 1) {
          $modules_list[$package][] = array(
            'filename' => $filename,
            'name' => $module->info['name'],
            'package' => $package,
            'version' => $module->info['version'],
            'status' => $module->status,
          );
        }
      }
      elseif ($module_type == 0) {
        if ($module->status == 0) {
          $modules_list[$package][] = array(
            'filename' => $filename,
            'name' => $module->info['name'],
            'package' => $package,
            'version' => $module->info['version'],
            'status' => $module->status,
          );
        }
      }
      else {
        $modules_list[$package][] = array(
          'filename' => $filename,
          'name' => $module->info['name'],
          'package' => $package,
          'version' => $module->info['version'],
          'status' => $module->status,
        );
      }
    }
    return $modules_list;

  }

  /**
   * Create a tarball for module.
   */
  public function createArchive($name, $description, $machine_name, $filename, $dir, $module_type, $check_version) {
    file_unmanaged_delete($dir . '/' . $machine_name . '.tar.gz');
    // Create directory.
    file_prepare_directory($dir, FILE_CREATE_DIRECTORY);
    // Get files.
    $files = array();
    $files[] = $this->moduleExportCreateInfoFile($name, $description, $dir, $machine_name, $module_type);
    $files[] = $this->moduleExportModuleFileContent($name, $machine_name, $dir, $module_type, $check_version);

    $tar_ob = new ArchiveTar($dir . '/' . $machine_name . '.tar.gz');
    $file_path = array();
    foreach ($files as $file) {
      $file_path[] = $file->getFileUri();
    }

    $op = $tar_ob->addModify($file_path, $machine_name, $dir);
    return $op;
  }

  /**
   * Get module file content.
   */
  public function moduleExportModuleFileContent($name, $machine_name, $dir, $module_type, $check_version) {
    $filename = $machine_name . '.module';
    $file_content = "<?php \n";
    $file_content .= "/** \n ";
    $file_content .= "* @file \n";
    $file_content .= " * Module file for {$name} \n";
    $file_content .= " */ \n\n";
    if ($check_version == 1) {
      $file_content .= $this->moduleExportGetModuleCode($machine_name, $module_type);
    }

    return file_save_data($file_content, $dir . '/' . $filename, FILE_EXISTS_REPLACE);
  }

  /**
   * Get module file content if check version is enabled.
   */
  public function moduleExportGetModuleCode($machine_name, $module_type) {
    // Get modules list.
    $modules_list = $this->moduleExportGetModulesList($module_type);
    $module_file = 'function ' . $machine_name . '_module_preinstall($module) {';
    $module_file .= "\n";
    $module_file .= "\t" . '$modules = array();' . "\n";
    foreach ($modules_list as $modules_array) {
      foreach ($modules_array as $module_info) {
        $module_machine_name = "'" . $module_info['filename'] . "'";
        $module_file .= "\t" . '$modules[' . $module_machine_name . '] = "' . $module_info['version'] . '";';
        $module_file .= "\n";
      }
    }
    // Get all modules.
    $module_file .= "\t" . '$site_modules = system_rebuild_module_data();' . "\n";
    $module_file .= "\t" . '$modules_list = array();' . "\n";
    $module_file .= "\t" . 'foreach ($site_modules as $filename => $module_detail) {' . "\n";
    $module_file .= "\t\t" . '$modules_list[$filename] = $module_detail->info["version"];' . "\n";
    $module_file .= "\t" . '}' . "\n";
    $module_file .= "\t" . 'foreach ($modules_list as $name => $module_info) {
      if (array_key_exists($name, $modules) && $module_info != $modules[$name]) {
        drupal_set_message(t($name . " version is not compatible."), "error");
        $uninstall_module = \Drupal::service(\'module_installer\')->uninstall(array($name), $disable_dependents = TRUE);
      }
    }
    ';
    $module_file .= '}';

    return $module_file;
  }

  /**
   * Get info file content.
   */
  public function moduleExportCreateInfoFile($name, $description, $dir, $machine_name, $module_type) {
    $filename = $machine_name . '.info.yml';
    // Get list of active modules.
    $modules_list = $this->moduleExportGetModulesList($module_type);

    $info_file = "name: {$name}\n";
    if (!empty($description)) {
      $info_file .= "description: {$description}\n";
    }
    $info_file .= "core: 8.x\n";
    $info_file .= "type: module\n";

    $info_file .= "dependencies:\n";
    foreach ($modules_list as $package => $module_info) {
      $info_file .= "  # {$package}\n";
      foreach ($module_info as $module_name) {
        $info_file .= "  - {$module_name['filename']}\n";
      }
    }
    $info_file .= "\n";
    return file_save_data($info_file, $dir . '/' . $filename, FILE_EXISTS_REPLACE);;
  }

}
