<?php

namespace Drupal\module_maker;

use Drupal\Core\Extension\ModuleHandler;

/**
 * Provides an function for create diffrent files.
 */
class CreateFiles {
  protected $moduleMakerPath;

  /**
   * Default constructor.
   */
  public function __construct(ModuleHandler $modulehandler) {
    $this->moduleMakerPath = $modulehandler->getModule('module_maker')->getPath();
  }

  /**
   * Implements add_default_files().
   */
  public function buildDefaultFiles($values, $path) {
    $name = $values['module_name'];
    $module_name = preg_replace('@[^a-z0-9-]+@', '_', strtolower($name));
    $module_desc = $values['module_description'];
    $package = $values['package'];
    $readme_file = 'README.txt';
    $module_file = $module_name . '.module';
    $install_file = $module_name . '.install';
    $module_data = '<?php  /*** @file* This is     ' . $name . '     module.*/';
    $module_file = file_put_contents($path . '/' . $module_file, $module_data);
    $install_file = file_put_contents($path . '/' . $install_file, $module_data);
    $readme_file = file_put_contents($path . '/' . $readme_file, $module_data);
    $info_file = $module_name . '.info.yml';
    $info_data = file_get_contents($this->moduleMakerPath . '/default_files/default_info.yml');
    $info_data = str_replace("[[module_name]]", $name, $info_data);
    $info_data = str_replace("[[module_desc]]", $module_desc, $info_data);
    $info_data = str_replace("[[module_package]]", $package, $info_data);
    $info_data = str_replace("[[module_type]]", 'module', $info_data);
    $info_file = file_put_contents($path . '/' . $info_file, $info_data);
  }

  /**
   * Implements addcontroller().
   */
  public function buildController($values, $path, $module_name) {
    $controller_name = ucfirst(preg_replace('/[^a-zA-Z]+/', '', $values['default_controller_name']));
    if (!empty($controller_name)) {
      $src_path = $path . '/src/Controller';
      if (file_prepare_directory($src_path, FILE_CREATE_DIRECTORY)) {
        $controller_file = $controller_name . '.php';
        $contoller_data = file_get_contents($this->moduleMakerPath . '/default_files/default_conroller.php');
        $contoller_data = str_replace("[[module_name]]", $module_name, $contoller_data);
        $contoller_data = str_replace("[[controller_name]]", $controller_name, $contoller_data);
        $contoller_data = str_replace("[[phtag]]", '<?php', $contoller_data);
        $controller_file = file_put_contents($src_path . '/' . $controller_file, $contoller_data);
      }
    }
  }

  /**
   * Implements addform().
   */
  public function buildForm($values, $path, $module_name) {
    $form_name = ucfirst(preg_replace('/[^a-zA-Z]+/', '', $values['default_form_name']));
    if (!empty($form_name)) {
      $form_path = $path . '/src/Form';
      if (file_prepare_directory($form_path, FILE_CREATE_DIRECTORY)) {
        $form_file = $form_name . '.php';
        $form_data = file_get_contents($this->moduleMakerPath . '/default_files/default_form.php');
        $form_data = str_replace("[[module_name]]", $module_name, $form_data);
        $form_data = str_replace("[[default_form_name]]", $form_name, $form_data);
        $form_data = str_replace("[[phtag]]", '<?php', $form_data);
        $form_data = file_put_contents($form_path . '/' . $form_file, $form_data);
      }
    }
  }

  /**
   * Implements addblock().
   */
  public function buildBlock($values, $path, $module_name) {
    $block_name = ucfirst(preg_replace('/[^a-zA-Z]+/', '', $values['default_block_name']));
    if (!empty($block_name)) {
      $block_path = $path . '/src/Plugin/Block';
      if (file_prepare_directory($block_path, FILE_CREATE_DIRECTORY)) {
        $block_file = $block_name . '.php';
        $block_data = file_get_contents($this->moduleMakerPath . '/default_files/default_block.php');
        $block_data = str_replace("[[module_name]]", $module_name, $block_data);
        $block_data = str_replace("[[default_block_name]]", $block_name, $block_data);
        $block_data = str_replace("[[phtag]]", '<?php', $block_data);
        $block_data = file_put_contents($block_path . '/' . $block_file, $block_data);
      }
    }
  }

  /**
   * Implements addRoutingFile().
   */
  public function buildRoutingFile($values, $path, $module_name) {
    $routing_file = $module_name . '.routing.yml';
    $routing_data = file_get_contents($this->moduleMakerPath . '/default_files/default.routing.yml');
    $controller_name = preg_replace('/[^a-zA-Z]+/', '', $values['default_controller_name']);
    if (!empty($controller_name)) {
      $crouting_data = file_get_contents($this->moduleMakerPath . '/default_files/controller.routing.yml');
      $crouting_data = str_replace("[[module_name]]", $module_name, $crouting_data);
      $crouting_data = str_replace("[[controller_name]]", $controller_name, $crouting_data);
    }
    $form_name = preg_replace('/[^a-zA-Z]+/', '', $values['default_form_name']);
    if (!empty($form_name)) {
      $frouting_data = file_get_contents($this->moduleMakerPath . '/default_files/form.routing.yml');
      if ($frouting_data) {
        $frouting_data = str_replace("[[module_name]]", $module_name, $frouting_data);
        $frouting_data = str_replace("[[form_name]]", $form_name, $frouting_data);
      }
    }
    if (empty($crouting_data)) {
      $crouting_data = '';
    }
    if (empty($frouting_data)) {
      $frouting_data = '';
    }
    $route_data = $crouting_data . $frouting_data;
    $routing_file = file_put_contents($path . '/' . $routing_file, $route_data);
  }

}
