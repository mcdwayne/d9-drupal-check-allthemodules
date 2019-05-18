<?php

namespace Drupal\dut_views\Plugin\views\display_extender;

use Drupal\views\Views;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display_extender\DisplayExtenderPluginBase;

/**
 * Theme suggestions display extender plugin.
 *
 * @ingroup views_display_extender_plugins
 *
 * @ViewsDisplayExtender(
 *   id = "theme_suggestions",
 *   title = @Translation("Theme suggestions"),
 *   help = @Translation("Get information on how to theme this display."),
 *   no_ui = FALSE
 * )
 */
class ThemeSuggestionsDisplayExtender extends DisplayExtenderPluginBase {

  /**
   * {@inheritdoc}
   */
  public function optionsSummary(&$categories, &$options) {
    $options['theme_suggestions'] = array(
      'category' => 'other',
      'title' => $this->t('Theme'),
      'value' => $this->t('Information'),
      'desc' => $this->t('Get information on how to theme this display'),
    );
  }

  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $section = $form_state->get('section');
    switch ($section) {
      case 'theme_suggestions':
        // Get a list of available themes
        $theme_handler = \Drupal::service('theme_handler');
        $themes = $theme_handler->listInfo();
        $form['#title'] .= $this->t('Theming information');

        // @todo: via DI.
        if (isset($_POST['ajax_page_state']['theme'])) {
          $this->theme = $_POST['ajax_page_state']['theme'];
        }
        elseif (empty($this->theme)) {
          // @todo: via DI.
          $this->theme = $config = \Drupal::config('system.theme')->get('default');
        }

        /** @var \Drupal\Core\Theme\ActiveTheme $active_theme */
        $active_theme = \Drupal::theme()->getActiveTheme();
        if (isset($active_theme) && $active_theme->getName() == $this->theme) {
          $this->theme_registry = theme_get_registry();
          $theme_engine = $active_theme->getEngine();
        }
        // @todo: add 'else' condition.

        // If there's a theme engine involved, we also need to know its extension
        // so we can give the proper filename.
        $this->theme_extension = '.html.twig';
        if (isset($theme_engine)) {
          $extension_function = $theme_engine . '_extension';
          if (function_exists($extension_function)) {
            $this->theme_extension = $extension_function();
          }
        }

        $suggestions_list = [];
        // @todo: add fields.
        $plugin_types = [
          'display' => ['Display output', 'Alternative display output'],
          'style'  => ['Style output', 'Alternative style'],
          'row' => ['Row style output', 'Alternative row style'],
        ];
        $display_plugin_id = $this->displayHandler->getPluginId();
        foreach (array_keys($plugin_types) as $plugin_type) {
          $definitions = Views::pluginManager($plugin_type)->getDefinitions();

          $definition = !empty($definitions[$display_plugin_id])
            ? $definitions[$display_plugin_id]
            : [];
          $display_theme = !empty($definition['theme'])
            ? $definition['theme']
            : NULL;
          // Get theme functions for the display. Note that some displays may
          // not have themes. The 'feed' display, for example, completely
          // delegates to the style.
          if ($plugin_type === 'display' && empty($display_theme)) {
            continue;
          }

          // $plugin_type !== display.
          if (empty($definition)) {
            $display_options = $this->displayHandler->options;
            $display_plugin_id = !empty($display_options[$plugin_type]['type'])
              ? $display_options[$plugin_type]['type']
              : NULL;
            $definition = !empty($display_plugin_id)
              ? $definitions[$display_plugin_id]
              : [];
            $display_theme = !empty($definition) ? $definition['theme'] : NULL;
          }

          if (empty($display_theme)) {
            continue;
          }

          $group = $plugin_types[$plugin_type][0];
          $suggestions = $this->view->buildThemeFunctions($display_theme);
          $suggestions_list[$group][] = $this->format_themes($suggestions);

          $additional_suggestions = !empty($definition['additional themes']) ? $definition['additional themes'] : [];
          foreach ($additional_suggestions as $theme => $type) {
            $group = $plugin_types[$plugin_type][1];
            $suggestions = $this->view->buildThemeFunctions($theme);
            $suggestions_list[$group][] = $this->format_themes($suggestions);
          }
        }

        $form['important'] = [
          '#theme' => 'views_view_theme_suggestions_important',
        ];

        if (isset($this->displayHandler->display['new_id'])) {
          $form['important-new_id'] = [
            '#theme' => 'views_view_theme_suggestions_important_new_id',
          ];
        }

        $form['suggestions'] = [
          '#theme' => 'views_view_theme_suggestions',
          '#suggestions' => $suggestions_list,
        ];
        break;
    }
  }

  /**
   * Format a list of theme templates for output by the theme info helper.
   */
  function format_themes($themes) {
    $registry = $this->theme_registry;
    $extension = $this->theme_extension;

    $picked = FALSE;
    foreach ($themes as $theme) {
      $template_name = strtr($theme, '_', '-') . $extension;
      $template = ['template' => $template_name];
      if (!$picked && !empty($registry[$theme])) {
        $template['path'] = isset($registry[$theme]['path']) ? $registry[$theme]['path'] . '/' : './';
        $template['exists'] = file_exists($template['path'] . $template_name);
        $picked = TRUE;
      }
      $fixed[] = $template;
    }

    return array_reverse($fixed);
  }

}
