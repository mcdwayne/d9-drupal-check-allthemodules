<?php

namespace Drupal\plus;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\plus\Core\Extension\ModuleHandler;
use Drupal\plus\Core\Extension\ThemeHandler;
use Drupal\plus\Core\Form\FormBuilder;
use Drupal\plus\Core\Render\Renderer;
use Drupal\plus\Core\Theme\Registry;
use Drupal\plus\Core\Theme\ThemeManager;
use Drupal\plus\Http\ClientFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class PlusServiceProvider.
 */
class PlusServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Drupal version specific services.
    $services = ((int) \Drupal::VERSION[0]) < 8 ? $this->servicesForDrupal7() : $this->servicesForDrupal8();

    // All Drupal versions.
    $services['http_client_factory']['class'] = ClientFactory::class;

    foreach ($services as $service => $info) {
      $info += ['class' => FALSE, 'arguments' => []];
      $class = $info['class'];
      $args = $info['arguments'];
      $definition = $container->getDefinition($service);
      if ($class) {
        $definition->setClass($class);
      }
      if ($args) {
        $arguments = $definition->getArguments();
        foreach ($args as $arg) {
          $arguments[] = new Reference($arg, ContainerInterface::IGNORE_ON_INVALID_REFERENCE);
        }
        $definition->setArguments($arguments);
      }
      if ($class || $args) {
        $container->setDefinition($service, $definition);
      }
    }
  }

  protected function servicesForDrupal7() {
    return [
    ];
  }

  protected function servicesForDrupal8() {
    return [
//      'form_builder' => [
//        'class' => FormBuilder::class,
//        'arguments' => ['plugin.manager.alter'],
//      ],
//      'module_handler' => [
//        'class' => ModuleHandler::class,
//        'arguments' => ['plugin.manager.alter.module'],
//      ],
//      'renderer' => [
//        'class' => Renderer::class,
//        'arguments' => [],
//      ],
//      'theme_handler'   => [
//        'class' => ThemeHandler::class,
//      ],
//      'theme.manager'   => [
//        'class' => ThemeManager::class,
//        'arguments' => [
//          'controller_resolver',
//          'event_dispatcher',
//          'renderer',
//          'plugin.manager.theme',
//          'plugin.manager.alter.theme',
//        ],
//      ],
//      'theme.registry'  => [
//        'class' => Registry::class,
//        'arguments' => ['plugin.manager.template'],
//      ],
    ];
  }

}
