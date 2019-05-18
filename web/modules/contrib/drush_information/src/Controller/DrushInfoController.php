<?php

namespace Drupal\drush_info\Controller;

use Symfony\Component\Yaml\Yaml;
use Drupal\Core\Render\Markup;

/**
 * Class DrushInfoController.
 */
class DrushInfoController {

  /**
   * Page callback for /admin/config/development/drush-info.
   */
  public function view() {
    $build = [];

    $moduleHandler = \Drupal::moduleHandler();
    $modules = $moduleHandler->getModuleList();
    $counter = FALSE;

    foreach ($modules as $module => $data) {
      $module_path = drupal_get_path('module', $module);
      $info = Yaml::parse(file_get_contents($module_path . '/' . $module . '.info.yml'));

      if (module_load_include('inc', $module, $module . '.drush')
        || module_load_include('inc', $module, 'drush/' . $module . '.drush')
      ) {
        $counter = TRUE;
        $rows = [];

        $commands = [];
        $function = "{$module}_drush_command";
        if (function_exists($function)) {
          $commands = call_user_func($function);
        }

        foreach ($commands as $command => $value) {
          $row = [
            $command,
            isset($value['aliases']) ? implode(', ', $value['aliases']) : '',
            isset($value['callback']) ? $value['callback'] : 'drush_' . str_replace('-', '_', $command),
            isset($value['description']) ? $value['description'] : '',
          ];

          if (isset($value['arguments'])) {
            $argument_text = '';

            foreach ($value['arguments'] as $key => $val) {
              $argument_text .= '<strong>' . $key . '</strong>: ' . $val . '<br />';
            }
            $row[] = t('@argument_text', ['@argument_text' => Markup::create($argument_text)]);
          }
          else {
            $row[] = '';
          }

          if (isset($value['options'])) {
            $options_text = '';

            foreach ($value['options'] as $key => $val) {
              // Sometimes the value is an array. If it's an array, show
              // each value as a comma-delimited list.
              if (is_array($val)) {
                $options_text .= '<strong>' . $key . '</strong>: ' . implode(', ', $val) . '<br />';
              }
              elseif (is_string($val)) {
                $options_text .= '<strong>' . $key . '</strong>: ' . $val . '<br />';
              }
            }
            $row[] = t('@options_text', ['@options_text' => Markup::create($options_text)]);
          }
          else {
            $row[] = '';
          }

          if (isset($value['examples'])) {
            $examples_text = '';

            foreach ($value['examples'] as $key => $val) {
              $examples_text .= '<h3><code>' . $key . '</code></h3>' . $val . '<br /><br />';
            }
            $row[] = t('@examples_text', ['@examples_text' => Markup::create($examples_text)]);
          }
          else {
            $row[] = '';
          }

          $rows[] = $row;
        }

        $build[$module] = [
          '#type' => 'fieldset',
          '#title' => $info['name'],
        ];
        $build[$module]['output_table'] = [
          '#theme' => 'table',
          '#header' => [
            t('Command'),
            t('Aliases'),
            t('Callback'),
            t('Description'),
            t('Arguments'),
            t('Options'),
            t('Examples'),
          ],
          '#rows' => $rows,
        ];
      }
    }
    // Display error message in case of no module found with drush command.
    if (!$counter) {
      drupal_set_message(t('None of the modules that are installed contain drush commands.'), 'error');
    }

    return $build;
  }

}
