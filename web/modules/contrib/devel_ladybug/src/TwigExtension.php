<?php

namespace Drupal\devel_ladybug;

use Drupal\devel\DevelDumperPluginManagerInterface;
use Twig_Extension as OriginalTwigExtension;

/**
 * Enables use of Ladybug from Twig template files.
 */
class TwigExtension extends OriginalTwigExtension {

  protected $dumperManager;

  /**
   * Initializes class.
   *
   * @param \Drupal\devel\DevelDumperPluginManagerInterface $dumper_manager
   *   A Devel dumper manager class to initialize ladybug plugin.
   */
  public function __construct(DevelDumperPluginManagerInterface $dumper_manager) {
    $this->dumperManager = $dumper_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'ladybug';
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('ladybug', [$this, 'ladybug'], [
        'is_safe' => ['html'],
        'needs_environment' => TRUE,
        'needs_context' => TRUE,
        'is_variadic' => TRUE,
      ]),
    ];
  }

  /**
   * Dumps variables using Ladybug.
   *
   * @param \Twig_Environment $env
   *   The twig environment instance.
   * @param array $context
   *   An array of parameters passed to the template.
   * @param array $args
   *   An array of parameters passed the function.
   *
   * @return string
   *   The Twig output.
   */
  public function ladybug(\Twig_Environment $env, array $context, array $args = []) {
    // Don't do anything unless twig_debug is enabled.
    if (!$env->isDebug()) {
      return '';
    }
    $ladybug = $this->dumperManager->createInstance('ladybug', []);
    return call_user_func([$ladybug, 'export'], $args);
  }

  /**
   * Uses devel's own implementation for retrieving twig function parameters.
   *
   * @return array
   *   The detected twig function parameters.
   */
  protected function getTwigFunctionParameters() {
    $callee = NULL;
    $template = NULL;

    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS | DEBUG_BACKTRACE_PROVIDE_OBJECT);

    foreach ($backtrace as $index => $trace) {
      if (isset($trace['object']) && $trace['object'] instanceof \Twig_Template && 'Twig_Template' !== get_class($trace['object'])) {
        $template = $trace['object'];
        $callee = $backtrace[$index - 1];
        break;
      }
    }

    $parameters = [];

    /** @var \Twig_Template $template */
    if (NULL !== $template && NULL !== $callee) {
      $line_number = $callee['line'];
      $debug_infos = $template->getDebugInfo();

      if (isset($debug_infos[$line_number])) {
        $source_line = $debug_infos[$line_number];
        $source_file_name = $template->getTemplateName();

        if (is_readable($source_file_name)) {
          $source = file($source_file_name, FILE_IGNORE_NEW_LINES);
          $line = $source[$source_line - 1];

          preg_match('/ladybug\((.+)\)/', $line, $matches);
          if (isset($matches[1])) {
            $parameters = array_map('trim', explode(',', $matches[1]));
          }
        }
      }
    }

    return $parameters;
  }

}
