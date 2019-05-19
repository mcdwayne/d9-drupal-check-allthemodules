<?php

namespace Drupal\sir_trevor\Controller;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\sir_trevor\IconSvgMergerInterface;
use Drupal\sir_trevor\Plugin\SirTrevorBlockPlugin;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class IconsController implements ContainerInjectionInterface {
  /** @var \Drupal\Component\Plugin\PluginManagerInterface */
  private $blockPluginManager;
  /** @var \Drupal\sir_trevor\IconSvgMergerInterface */
  private $iconSvgMerger;
  /** @var \Drupal\Core\Extension\ModuleHandlerInterface */
  private $moduleHandler;

  /**
   * IconsController constructor.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $blockPluginManager
   * @param \Drupal\sir_trevor\IconSvgMergerInterface $iconSvgMerger
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   */
  public function __construct(PluginManagerInterface $blockPluginManager, IconSvgMergerInterface $iconSvgMerger, ModuleHandlerInterface $moduleHandler) {
    $this->blockPluginManager = $blockPluginManager;
    $this->iconSvgMerger = $iconSvgMerger;
    $this->moduleHandler = $moduleHandler;
  }

  /**
   * @return Response
   */
  public function getIcons() {
    $svg = $this->iconSvgMerger->merge($this->getIconFiles());
    return Response::create($svg, 200, ['Content-Type' => 'image/svg+xml']);
  }

  /**
   * @return array
   */
  private function getIconFiles() {
    $files[] = DRUPAL_ROOT . '/libraries/sir-trevor/build/sir-trevor-icons.svg';

    $files = $this->appendPluginIconFiles($files);

    return array_unique($files);
  }

  /**
   * @param $files
   * @return array
   */
  private function appendPluginIconFiles($files) {
    $moduleDirectories = $this->moduleHandler->getModuleDirectories();

    foreach ($this->blockPluginManager->getDefinitions() as $definition) {
      /** @var SirTrevorBlockPlugin $instance */
      $instance = $this->blockPluginManager->getInstance($definition);
      if ($instance->hasIconsFile()) {
        $files[] = $moduleDirectories[$instance->getDefiningModule()] . '/' . $instance->getIconsFile();
      }
    }
    return $files;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $pluginManager = $container->get('plugin.manager.sir_trevor');
    $iconMerger = $container->get('sir_trevor.icon.svg.merger');
    $moduleHandler = $container->get('module_handler');
    return new static($pluginManager, $iconMerger, $moduleHandler);
  }
}
