<?php

namespace Drupal\twig_namespaces;

use \BasaltInc\TwigTools;
use \Webmozart\PathUtil\Path;

class TwigNamespaces extends \Twig_Loader_Filesystem {
  public $themes = [];
  public $namespaceFiles = [];
  public $twigLoaderConfig = [];

  public function __construct() {
    /** @var \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler */
    $theme_handler = \Drupal::service('theme_handler');
    $theme_list = $theme_handler->listInfo();

    /**
     * @var string $name
     * @var \Drupal\Core\Extension\Extension $item
     */
    foreach ($theme_list as $name => $item) {
      if (!isset($item->info['twig_namespaces'])) {
        continue;
      }
      $this->themes[$name] = $item;

      /**
       * This would be lifted off the `twig_namespaces` key in `theme.info.yml`
       * @var string[] Paths to Twig Namespace definition JSON file
       */
      $namespaceFilePaths = $item->info['twig_namespaces'];

      foreach ($namespaceFilePaths as $namespaceFile) {
        $file = Path::join(DRUPAL_ROOT, $item->getPath(), $namespaceFile);
        if (!file_exists($file)) {
          $errorMsg = 'Twig Namespace file declared in "' . $name . '" not found when looking here: "' . $file . '"';
          \Drupal::logger('twig_namespaces')->error($errorMsg);
          drupal_set_message($errorMsg, 'error');
        }
        $this->namespaceFiles[] = [
            'path' => $namespaceFile,
            'pathRoot' => Path::join(DRUPAL_ROOT, $item->getPath()),
        ];
      }

    }

    $this->addNamespaces();
  }

  public function addNamespaces() {
    if (empty($this->namespaceFiles)) {
      return;
    }

    foreach ($this->namespaceFiles as $file) {
      try {
        $filePath = Path::join($file['pathRoot'], $file['path']);
        $fileData = TwigTools\Utils::getData($filePath);
        $this->twigLoaderConfig = TwigTools\Namespaces::buildLoaderConfig($fileData, $file['pathRoot']);
        foreach ($this->twigLoaderConfig as $key => $value) {
          foreach ($value['paths'] as $path) {
            if (file_exists($path)) {
              $this->addPath($path, $key);
            } else {
              $message = 'Twig Namespace path does not exist: ' . $path;
              \Drupal::logger('twig_namespaces')->warning($message);
              drupal_set_message($message, 'error');
            }
          }
        }
      } catch (Exception $exception) {
        $errorMsg = 'Error adding Twig Namespaces from: ' . $filePath;
        \Drupal::logger('twig_namespaces')->error($errorMsg);
        drupal_set_message($errorMsg, 'error');
      }
    }
  }

}
