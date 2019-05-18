<?php

namespace Drupal\console_extras\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Core\Utils\StringConverter;

/**
 * Class BatchGenerator.
 *
 * @package Drupal\console_extras\Generator.
 */
class BatchGenerator extends Generator {

  /**
   * {@inheritdoc}
   */
  public function generate(array $parameters) {
    $module = $parameters['module'];
    $form_class = $parameters['form_class'];

    $stringConverter = new StringConverter();

    $src_module = 'console_extras';
    $src_module_path = drupal_get_path('module', $src_module);

    $dest_module_name = $module;
    $dest_module_path = drupal_get_path('module', $dest_module_name);

    $form_class_human = $stringConverter->camelCaseToHuman($form_class);
    $form_class_underscore = $stringConverter->camelCaseToMachineName($form_class);

    $parameters = [
      'module_name' => $dest_module_name,
      'route_title' => $form_class_human,
      'route_name' => $dest_module_name . '.batch.' . $form_class_underscore,
      'route_path' => '/admin/content/batch/' . $form_class_underscore,
      'form_class' => $form_class,
      'form_class_underscore' => $form_class_underscore,
    ];

    // Adds extra skeleton dir so the console can find the templates files.
    $this->addSkeletonDir($src_module_path . '/console/templates');

    // Generates routing.yml file.
    $this->renderFile(
      'batch/routing.yml.twig',
      $dest_module_path . '/' . $dest_module_name . '.routing.yml',
      $parameters,
      FILE_APPEND
    );

    // Generates batch form file.
    $this->renderFile(
      'batch/src/Form/form.php.twig',
      $dest_module_path . '/src/Form/' . $parameters['form_class'] . '.php',
      $parameters
    );
  }

}
