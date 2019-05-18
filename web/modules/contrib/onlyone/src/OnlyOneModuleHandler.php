<?php

namespace Drupal\onlyone;

use Drupal\Core\Url;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Extension\ModuleExtensionList;

/**
 * Class OnlyOne.
 */
class OnlyOneModuleHandler implements OnlyOneModuleHandlerInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The render service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The module extension list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list.
   */
  public function __construct(ModuleHandlerInterface $module_handler, RendererInterface $renderer, ModuleExtensionList $module_extension_list) {
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
    $this->moduleExtensionList = $module_extension_list;
  }

  /**
   * {@inheritdoc}
   */
  public function getModuleHelpPageLink($module_machine_name, $module_name_alternate, $emphasize = FALSE) {
    // Getting all the modules information.
    $modules = $this->moduleExtensionList->getAllInstalledInfo();

    // Checking if the module is present in the site.
    if (isset($modules[$module_machine_name])) {
      // Getting the module name.
      $module_name = $modules[$module_machine_name]['name'];

      // If the module is installed and implement the hook_help.
      if (in_array($module_machine_name, $this->moduleHandler->getImplementations('help'))) {
        // Creating the link.
        $build = [
          '#type' => 'link',
          '#title' => $module_name,
          '#url' => Url::fromRoute('help.page', ['name' => $module_machine_name]),
          '#cache' => [
            'tags' => [
              'config:core.extension',
            ],
          ],
        ];
        // Rendering.
        $output = $this->renderer->render($build);
      }
      else {
        $output = $emphasize ? Markup::create('<em>' . $module_name . '</em>') : $module_name;
      }
    }
    else {
      // As the module is not present we use a alternate string.
      $output = $emphasize ? Markup::create('<em>' . $module_name_alternate . '</em>') : $module_name_alternate;
    }

    return $output;
  }

}
