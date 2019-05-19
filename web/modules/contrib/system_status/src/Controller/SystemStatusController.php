<?php

/**
 * @file
 * Contains \Drupal\pants\Controller\SystemStatusController.
 */

namespace Drupal\system_status\Controller;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\system_status\Services\SystemStatusEncryption;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;


/**
 * Returns responses for Sensei's Pants routes.
 */
class SystemStatusController extends ControllerBase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $module_handler;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $theme_handler;

  /**
   * The system status encrypt service
   *
   * @var \Drupal\system_status\Services\SystemStatusEncryption
   */
  protected $encrypt;

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('theme_handler'),
      $container->get('system_status.encrypt')
    );
  }

  /**
   * SystemStatusController constructor.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   * @param \Drupal\system_status\Services\SystemStatusEncryption $encrypt
   */
  public function __construct(ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler, SystemStatusEncryption $encrypt) {
    $this->module_handler = $module_handler;
    $this->theme_handler = $theme_handler;
    $this->encrypt = $encrypt;
  }

  /**
   * Changes Sensei's pants and returns the display of the new status.
   */
  function load($system_status_token) {

    // Needless initialisation, but hey.
    $res = [
      "core" => [],
      "contrib" => [],
      "theme" => [],
    ];

    $drupal_modules = $this->module_handler->getModuleList();
    $drupal_themes = $this->theme_handler->listInfo();

    foreach($drupal_modules as $name => $module) {

      $filename = $module->getPath() . '/' . $module->getFilename();
      $module_info = Yaml::decode(file_get_contents($filename));

      // this can happen when you install using composer
      if(isset($module_info['version']) && $module_info['version'] == "VERSION") {
          $module_info['version'] = \Drupal::VERSION;
      }

      if(!isset($module_info['version']))
	$module_info['version'] = null;

      // do our best to guess the correct drupal version
      if($name == "system" && $module_info['package'] == "Core")
        $res['core']['drupal'] = ["version" => $module_info['version']];

      // Skip Core and Field types
      if((isset($module_info['package']) && $module_info['package'] == "Core") || (isset($module_info['package']) && $module_info['package'] == "Field types") || (isset($module_info['project']) && $module_info['project'] == 'drupal'))
         continue;

      // TODO:
      // if(!isset($module['version']))
      // we can be 90% sure it's not contrib, so we can put it in custom
      // hard to test as system_status is not released yet so no version
      // let's put all the rest in 'contrib' for now

      if(isset($module_info['project'])) {
          $res['contrib'][$module_info['project']] = ["version" => $module_info['version']];
      } else {
          $res['contrib'][$name] = ["version" => $module_info['version']];
      }
    }

    foreach($drupal_themes as $name => $theme) {
      $filename = $theme->getPath() . '/' . $theme->getFilename();
      $theme_info = Yaml::decode(file_get_contents($filename));

      if (!isset($theme_info['version'])) {
        $theme_info['version'] = NULL;
      }

      // this can happen when you install using composer
      if($theme_info['version'] == "VERSION") {
          $theme_info['version'] = \Drupal::VERSION;
      }

      if(isset($theme_info['project']) && $theme_info['project'] == 'drupal')
        continue;

      if(isset($theme_info['project'])) {
        $res['theme'][$theme_info['project']] = ["version" => $theme_info['version']];
      } else {
        $res['theme'][$name] = ["version" => $theme_info['version']];
      }
    }

    $config = $this->config('system_status.settings');
    if(function_exists('openssl_random_pseudo_bytes')) {
      $res = SystemStatusEncryption::encrypt_openssl(json_encode(["system_status" => $res]));
      return new JsonResponse(["system_status" => "encrypted_openssl", "data" => $res, "drupal_version" => "8", "engine_version" => "DRUPAL8", "php_version" => phpversion()]);
    }
    else if (extension_loaded('mcrypt')) {
      $res = SystemStatusEncryption::encrypt_mcrypt(json_encode(["system_status" => $res]));
      return new JsonResponse(["system_status" => "encrypted", "data" => $res, "drupal_version" => "8", "engine_version" => "DRUPAL8", "php_version" => phpversion()]);
    }
    else {
      return new JsonResponse(["system_status" => $res, "drupal_version" => "8", "engine_version" => "DRUPAL8", "php_version" => phpversion()]);
    }
  }

  public function access($system_status_token) {
    $token = $this->config('system_status.settings')->get('system_status_token');
    if($token == $system_status_token) {
      return AccessResult::allowed();
    }
    else {
      return AccessResult::forbidden();
    }
  }

}
