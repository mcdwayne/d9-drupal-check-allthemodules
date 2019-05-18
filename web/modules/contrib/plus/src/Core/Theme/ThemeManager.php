<?php

namespace Drupal\plus\Core\Theme;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\plus\Events\ThemeEvents;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Theme\ActiveTheme;
use Drupal\Core\Theme\ThemeInitializationInterface;
use Drupal\Core\Theme\ThemeManager as CoreThemeManager;
use Drupal\Core\Theme\ThemeNegotiatorInterface;
use Drupal\plus\AlterPluginManager;
use Drupal\plus\Events\ThemeEvent;
use Drupal\plus\ThemePluginManager;
use Drupal\plus\Utility\Variables;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Modifies core's "theme.manager" service.
 */
class ThemeManager extends CoreThemeManager {

  /**
   * The Alter Plugin Manager.
   *
   * @var \Drupal\plus\AlterPluginManager
   */
  protected $alterPluginManager;

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

  /**
   * The Event Dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The renderer.
   *
   * @var \Drupal\plus\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The Theme Plugin Manager service.
   *
   * @var \Drupal\plus\ThemePluginManager
   */
  protected $themePluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($root, ThemeNegotiatorInterface $theme_negotiator, ThemeInitializationInterface $theme_initialization, ModuleHandlerInterface $module_handler, EventDispatcherInterface $event_dispatcher, ControllerResolverInterface $controller_resolver, RendererInterface $renderer, ThemePluginManager $theme_plugin_manager, AlterPluginManager $alter_plugin_manager) {
    parent::__construct($root, $theme_negotiator, $theme_initialization, $module_handler);
    $this->alterPluginManager = $alter_plugin_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->themePluginManager = $theme_plugin_manager;
    $this->controllerResolver = $controller_resolver;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function alter($type, &$data, &$context1 = NULL, &$context2 = NULL) {
    parent::alter($type, $data, $context1, $context2);
    $this->alterPluginManager->alter($type, $data, $context1, $context2);
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveTheme(ActiveTheme $active_theme) {
    // Activate (before).
    $event = new ThemeEvent([$active_theme->getName()]);
    $this->eventDispatcher->dispatch(ThemeEvents::ACTIVATE, $event);
    if ($event->isPropagationStopped()) {
      return $this;
    }

    // Invoke original core method.
    parent::setActiveTheme($active_theme);

    // Activated (after).
    $this->eventDispatcher->dispatch(ThemeEvents::ACTIVATED, $event);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function render($hook, array $variables) {
    static $default_attributes;

    $active_theme = $this->getActiveTheme();

    // BEGIN ALTERATION.
    $theme = $this->themePluginManager->getActiveTheme();
    // END ALTERATION.
    // If called before all modules are loaded, we do not necessarily have a
    // full theme registry to work with, and therefore cannot process the theme
    // request properly. See also \Drupal\Core\Theme\Registry::get().
    if (!$this->moduleHandler->isLoaded() && !defined('MAINTENANCE_MODE')) {
      throw new \Exception('The theme implementations may not be rendered until all modules are loaded.');
    }

    $theme_registry = $this->themeRegistry->getRuntime();

    // If an array of hook candidates were passed, use the first one that has an
    // implementation.
    if (is_array($hook)) {
      foreach ($hook as $candidate) {
        if ($theme_registry->has($candidate)) {
          break;
        }
      }
      $hook = $candidate;
    }
    // Save the original theme hook, so it can be supplied to theme variable
    // preprocess callbacks.
    $original_hook = $hook;

    // If there's no implementation, check for more generic fallbacks.
    // If there's still no implementation, log an error and return an empty
    // string.
    if (!$theme_registry->has($hook)) {
      // Iteratively strip everything after the last '__' delimiter, until an
      // implementation is found.
      while ($pos = strrpos($hook, '__')) {
        $hook = substr($hook, 0, $pos);
        if ($theme_registry->has($hook)) {
          break;
        }
      }
      if (!$theme_registry->has($hook)) {
        // Only log a message when not trying theme suggestions ($hook being an
        // array).
        if (!isset($candidate)) {
          \Drupal::logger('theme')->warning('Theme hook %hook not found.', ['%hook' => $hook]);
        }
        // There is no theme implementation for the hook passed. Return FALSE so
        // the function calling
        // \Drupal\Core\Theme\ThemeManagerInterface::render() can differentiate
        // between a hook that exists and renders an empty string, and a hook
        // that is not implemented.
        return FALSE;
      }
    }

    $info = $theme_registry->get($hook);

    // If a renderable array is passed as $variables, then set $variables to
    // the arguments expected by the theme function.
    if (isset($variables['#theme']) || isset($variables['#theme_wrappers'])) {
      $element = $variables;
      $variables = [];
      if (isset($info['variables'])) {
        foreach (array_keys($info['variables']) as $name) {
          if (isset($element["#$name"]) || array_key_exists("#$name", $element)) {
            $variables[$name] = $element["#$name"];
          }
        }
      }
      else {
        $variables[$info['render element']] = $element;
        // Give a hint to render engines to prevent infinite recursion.
        $variables[$info['render element']]['#render_children'] = TRUE;
      }
    }

    // Merge in argument defaults.
    if (!empty($info['variables'])) {
      $variables += $info['variables'];
    }
    elseif (!empty($info['render element'])) {
      $variables += [$info['render element'] => []];
    }
    // Supply original caller info.
    $variables += [
      'theme_hook_original' => $original_hook,
    ];

    // BEGIN ALTERATION.
    $variables += $theme->preprocessVariables();
    // END ALTERATION.
    // Set base hook for later use. For example if '#theme' => 'node__article'
    // is called, we run hook_theme_suggestions_node_alter() rather than
    // hook_theme_suggestions_node__article_alter(), and also pass in the base
    // hook as the last parameter to the suggestions alter hooks.
    if (isset($info['base hook'])) {
      $base_theme_hook = $info['base hook'];
    }
    else {
      $base_theme_hook = $hook;
    }

    // Invoke hook_theme_suggestions_HOOK().
    $suggestions = $this->moduleHandler->invokeAll('theme_suggestions_' . $base_theme_hook, [$variables]);
    // If the theme implementation was invoked with a direct theme suggestion
    // like '#theme' => 'node__article', add it to the suggestions array before
    // invoking suggestion alter hooks.
    if (isset($info['base hook'])) {
      $suggestions[] = $hook;
    }

    // Invoke hook_theme_suggestions_alter() and
    // hook_theme_suggestions_HOOK_alter().
    $hooks = [
      'theme_suggestions',
      'theme_suggestions_' . $base_theme_hook,
    ];
    $this->moduleHandler->alter($hooks, $suggestions, $variables, $base_theme_hook);
    $this->alter($hooks, $suggestions, $variables, $base_theme_hook);

    // Check if each suggestion exists in the theme registry, and if so,
    // use it instead of the base hook. For example, a function may use
    // '#theme' => 'node', but a module can add 'node__article' as a suggestion
    // via hook_theme_suggestions_HOOK_alter(), enabling a theme to have
    // an alternate template file for article nodes.
    foreach (array_reverse($suggestions) as $suggestion) {
      if ($theme_registry->has($suggestion)) {
        $info = $theme_registry->get($suggestion);
        break;
      }
    }

    // Include a file if the theme function or variable preprocessor is held
    // elsewhere.
    if (!empty($info['includes'])) {
      foreach ($info['includes'] as $include_file) {
        include_once $this->root . '/' . $include_file;
      }
    }

    // Invoke the variable preprocessors, if any.
    if (isset($info['base hook'])) {
      $base_hook = $info['base hook'];
      $base_hook_info = $theme_registry->get($base_hook);
      // Include files required by the base hook, since its variable
      // preprocessors might reside there.
      if (!empty($base_hook_info['includes'])) {
        foreach ($base_hook_info['includes'] as $include_file) {
          include_once $this->root . '/' . $include_file;
        }
      }
      if (isset($base_hook_info['preprocess functions'])) {
        // Set a variable for the 'theme_hook_suggestion'. This is used to
        // maintain backwards compatibility with template engines.
        $theme_hook_suggestion = $hook;
      }
      // BEGIN ALTERATION
      // Ensure base theme hook variables exist.
      if (isset($base_hook_info['variables'])) {
        $variables = NestedArray::mergeDeepArray([$base_hook_info['variables'], $variables], TRUE);
      }
      // END ALTERATION.
    }
    if (isset($info['preprocess functions'])) {
      foreach ($info['preprocess functions'] as $preprocessor_function) {
        // BEGIN ALTERATION.
        $vars = Variables::create($variables, $theme);

        // Normal procedural preprocess functions.
        if (is_string($preprocessor_function) && function_exists($preprocessor_function)) {
          $callable = $preprocessor_function;
        }
        // Otherwise, allow preprocess "functions" to be method calls on
        // objects or services.
        else {
          $callable = $this->controllerResolver->getControllerFromDefinition($preprocessor_function);
        }

        call_user_func_array($callable, [&$vars, $hook, $info, $vars->element]);
        // END ALTERATION.
      }
      // Allow theme preprocess functions to set $variables['#attached'] and
      // $variables['#cache'] and use them like the corresponding element
      // properties on render arrays. In Drupal 8, this is the (only) officially
      // supported method of attaching bubbleable metadata from preprocess
      // functions. Assets attached here should be associated with the template
      // that we are preprocessing variables for.
      $preprocess_bubbleable = [];
      foreach (['#attached', '#cache'] as $key) {
        if (isset($variables[$key])) {
          $preprocess_bubbleable[$key] = $variables[$key];
        }
      }
      // We do not allow preprocess functions to define cacheable elements.
      unset($preprocess_bubbleable['#cache']['keys']);
      if ($preprocess_bubbleable) {
        // BEGIN ALTERATION.
        $this->renderer->render($preprocess_bubbleable);
        // END ALTERATION.
      }
    }

    // Generate the output using either a function or a template.
    $output = '';
    if (isset($info['function'])) {
      // BEGIN ALTERATION.
      // Normal procedural theme hook function.
      if (is_string($info['function']) && function_exists($info['function'])) {
        $callable = $info['function'];
      }
      // Otherwise, allow theme hook function to be a method call on an
      // object or service.
      else {
        $callable = $this->controllerResolver->getControllerFromDefinition($info['function']);
      }
      // Theme functions do not render via the theme engine, so the output is
      // not autoescaped. However, we can only presume that the theme function
      // has been written correctly and that the markup is safe.
      $output = Markup::create(call_user_func_array($callable, [$variables]));
      // END ALTERATION.
    }
    else {
      $render_function = 'twig_render_template';
      $extension = '.html.twig';

      // The theme engine may use a different extension and a different
      // renderer.
      $theme_engine = $active_theme->getEngine();
      if (isset($theme_engine)) {
        if ($info['type'] != 'module') {
          if (function_exists($theme_engine . '_render_template')) {
            $render_function = $theme_engine . '_render_template';
          }
          $extension_function = $theme_engine . '_extension';
          if (function_exists($extension_function)) {
            $extension = $extension_function();
          }
        }
      }

      // In some cases, a template implementation may not have had
      // template_preprocess() run (for example, if the default implementation
      // is a function, but a template overrides that default implementation).
      // In these cases, a template should still be able to expect to have
      // access to the variables provided by template_preprocess(), so we add
      // them here if they don't already exist. We don't want the overhead of
      // running template_preprocess() twice, so we use the 'directory' variable
      // to determine if it has already run, which while not completely
      // intuitive, is reasonably safe, and allows us to save on the overhead of
      // adding some new variable to track that.
      if (!isset($variables['directory'])) {
        $default_template_variables = [];
        template_preprocess($default_template_variables, $hook, $info);
        $variables += $default_template_variables;
      }
      if (!isset($default_attributes)) {
        $default_attributes = new Attribute();
      }
      foreach (['attributes', 'title_attributes', 'content_attributes'] as $key) {
        if (isset($variables[$key]) && !($variables[$key] instanceof Attribute)) {
          if ($variables[$key]) {
            $variables[$key] = new Attribute($variables[$key]);
          }
          else {
            // Create empty attributes.
            $variables[$key] = clone $default_attributes;
          }
        }
      }

      // Render the output using the template file.
      $template_file = $info['template'] . $extension;
      if (isset($info['path'])) {
        $template_file = $info['path'] . '/' . $template_file;
      }
      // Add the theme suggestions to the variables array just before rendering
      // the template for backwards compatibility with template engines.
      $variables['theme_hook_suggestions'] = $suggestions;
      // For backwards compatibility, pass 'theme_hook_suggestion' on to the
      // template engine. This is only set when calling a direct suggestion like
      // '#theme' => 'menu__shortcut_default' when the template exists in the
      // current theme.
      if (isset($theme_hook_suggestion)) {
        $variables['theme_hook_suggestion'] = $theme_hook_suggestion;
      }
      $output = $render_function($template_file, $variables);
    }

    return ($output instanceof MarkupInterface) ? $output : (string) $output;
  }

}
