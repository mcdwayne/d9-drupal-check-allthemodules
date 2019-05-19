<?php

namespace Drupal\twig_addons;

use Webmozart\PathUtil\Path;

class GetInfo {
  public $namespaceFiles = [];
  public $extensionClasses = [];
  public $themes = [];

  public function __construct() {
    /** @var \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler */
    $theme_handler = \Drupal::service('theme_handler');
    $theme_list = $theme_handler->listInfo();

    /**
     * @var string $name
     * @var \Drupal\Core\Extension\Extension $item
     */
    foreach ($theme_list as $name => $item) {
      if (!isset($item->info['twig_addons'])) {
        continue;
      }
      $this->themes[$name] = $item;

      /**
       * @var array $settings - This would be lifted off the `twig_addons` key in `theme.info.yml`
       *   $settings = [
       *      'twig_namespaces' => (string[]) Paths to Twig Namespace definition JSON file
       *      'twig_extensions' => (string[]) Class name strings that are callable and can be passed to `$twig->addExtension()`
       *   ];
       */
      $settings = $item->info['twig_addons'];

      if (isset($settings['twig_namespaces'])) {
        foreach ($settings['twig_namespaces'] as $namespaceFile) {
          $file = Path::join(DRUPAL_ROOT, $item->getPath(), $namespaceFile);
          if (!file_exists($file)) {
            $errorMsg = 'Twig Namespace file declared in "' . $name . '" not found when looking here: "' . $file . '"';
            \Drupal::logger('twig_addons')->error($errorMsg);
            drupal_set_message($errorMsg, 'error');
          }
          $this->namespaceFiles[] = [
              'path' => $namespaceFile,
              'pathRoot' => Path::join(DRUPAL_ROOT, $item->getPath()),
          ];
        }
      }

      if (isset($settings['twig_extensions'])) {
        foreach ($settings['twig_extensions'] as $extensionClass) {
          if (!class_exists($extensionClass)) {
            $errorMsg = 'Twig Extension class declared in "' . $name . '" not callable: "' . $extensionClass . '"';
            \Drupal::logger('twig_addons')->error($errorMsg);
            drupal_set_message($errorMsg, 'error');
          }
          $this->extensionClasses[] = $extensionClass;
        }
      }
    }

  }

}
