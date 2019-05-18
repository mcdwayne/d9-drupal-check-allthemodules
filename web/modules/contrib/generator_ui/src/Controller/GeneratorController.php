<?php
/**
 * @file
 * Contains \Drupal\GeneratorUI\Controller\GeneratorController.
 *
 */

namespace Drupal\generator_ui\Controller;

//Use the necessary classes
use Drupal\Component\DependencyInjection\Container;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Template\TwigEnvironment;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;

// GeneratorController class manages all the common features between  generators
/**
 * Class GeneratorController
 * @package Drupal\generator_ui\Controller
 */
class GeneratorController extends ControllerBase {

  protected $twig;
  protected $id;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Template\TwigEnvironment $twig
   *   used to load templates from the file system or other locations.
   */
  public function __construct(TwigEnvironment $twig) {
    $this->twig = $twig;
  }

  /**
   * Instantiation the twig service which we can use in this class.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container .
   *   container dependency injection
   * @return
   *   The twig service.
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('twig')
    );
  }

  /**
   * List of all modules.
   * @param  $path
   *   path of the file
   * @return array()
   *   Recuperate all files in the directory.
   */
  public static function listInfoFiles() {
    //path of templates files
    //find directories and files in template directory
    $dir_modules = DRUPAL_ROOT . '/modules';
    $list_generators = array();
    $iter = new \RecursiveDirectoryIterator($dir_modules);
    foreach (new \RecursiveIteratorIterator($iter) as $file) {
      //if the file has a twig extension
      if (strpos($file->getFilename(), '.info.yml')) {
        // declare an array contains the list of modules
        $file_name = substr($file->getFilename(), 0, strpos($file->getFilename(), '.'));
        $list_generators[] = $file_name;
      }
    }
    return $list_generators;
  }

  /**
   * Verify the file existance in a directory.
   *
   * @param string $pathModule
   *   Path of modules.
   * @param string $nameModule
   *  Module name.
   * @param string $type
   *   The file type.Ex.info,routing,controller...
   * @return boolean
   *   return 1 if the modules there,else return 0.
   */
  public static function exist($module_name, $file_name) {
    $name_module = DRUPAL_ROOT . '/' . drupal_get_path('modules', $module_name);
    $dir_modules = $name_module . '/modules/';
    $lists_modules = scandir($dir_modules);
    $exist = 0;
    foreach ($lists_modules as $list) {
      if ($list == $file_name) {
        $exist = 1;
      }
    }
    return $exist;
  }

  public static function exist_file($file, $module_name) {
    $list_files = self::getListFiles($module_name);
    // Retrouner True dés qu'il trouve le fichier en question ..
    if ($list_files != NULL) {
      foreach ($list_files as $list_file) {
        if ($list_file == $file) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

// return true or false , if the file exist then true else false

  public static function getListFiles($module_name) {
    $list = array();
    $new_module = drupal_get_path('module', $module_name);
    if ($new_module != NULL) {
      $iter = new \RecursiveDirectoryIterator($new_module);
      foreach (new \RecursiveIteratorIterator($iter) as $file) {
        // declare an array contains the list of all files
        $list[] = $file->getPath() . '/' . $file->getFilename();
      }
    }
    return $list;
  }

  //get All files in the new modules

  /**
   * it used to do the autocomplete path for all modules.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   return list of modules in the project with json format.
   */
  public function autocomplete(Request $request) {
    $q = $request->query->get('q', NULL);
    $listModules = self::listModules($q);
    return new JsonResponse($listModules);
  }

  /**
   * List of files in directory.
   *
   * @return array()
   *   Recuperate all modules in the project.
   */
  public static function listModules($module_search) {
    $modules = system_rebuild_module_data();
    $matched = [];
    foreach ($modules as $key => $m) {
      if (!preg_match('`^core/`', $m->getPathname()) && FALSE !== strpos($key, $module_search)) {
        $matched[] = $key;
      }
    }
    return $matched;
  }

  /**
   * Get List of modules
   * @return array
   */
 static function getListModules() {
    $modules = system_rebuild_module_data();
    $matched = [];
    foreach ($modules as $key => $m) {
      if (!preg_match('`^core/`', $m->getPathname())) {
        $matched[] = $key;
      }
    }
    return  $matched;
  }

  /**
   * Used to call all form generators.
   *
   * @return markup
   *   returns form from
   */
  public function getGeneratorForm($form_id) {
    $form = \Drupal::formBuilder()
      ->getForm('Drupal\generator_ui\Form\\' . $form_id);
    return $form;
  }

  /**
   * it used for the page presentation of Generator UI.
   *
   * @return markup
   *   return markup which contains the efficiency of Generator Ui and available code generators.
   */
  public function helpGenerator() {
    return array(
      '#theme' => 'generator_ui',
    );
  }

  function autocompleteRoutes(Request $request) {
    $q = $request->query->get('q', NULL);
    $routes_names = $this->listRoutes($q);
    return new JsonResponse($routes_names);
  }

  public function listRoutes($search) {
    $routes = array();
    $routing_file = Yaml::parse(drupal_get_path('module', 'generator_ui') . '/generator_ui.routing.yml');
    foreach ($routing_file as $key => $value) {
      if (substr_count($key, $search) != 0) {
        $routes[] = $key;
      }
    }
    return $routes;
  }

  public function services_autocomplete(Request $request) {
    $q = $request->query->get('q', NULL);
    $listServices = self::listServices($q);
    return new JsonResponse($listServices);
  }

  public static function listServices($service_search) {
    $services = \Drupal::getContainer()->getServiceIds();
    $matched = [];
    foreach ($services as $key => $m) {
      if (FALSE !== strpos($m, $service_search)) {
        $matched[] = $m;
      }
    }
    return $matched;
  }

  public function type_field_autocomplete(Request $request) {
    $q = $request->query->get('q', NULL);
    $listServices = self::getAllTypeFields($q);
    return new JsonResponse($listServices);
  }

  public function getAllTypeFields($service_search) {
    $matched = [];
    foreach (\Drupal::service('plugin.manager.field.field_type')
               ->getDefinitions() as $key => $m) {
      if (FALSE !== strpos($key, $service_search)) {
        $matched[] = $key;
      }
    }
    return $matched;
  }

  protected function loadFromTwig($parameters, $pathTemplate) {
    $template = $this->twig->loadTemplate($pathTemplate);
    $output = array(
      $template->render($parameters)
    );
    $outputFinal = implode("\n", $output);
    return $outputFinal;
  }
}


