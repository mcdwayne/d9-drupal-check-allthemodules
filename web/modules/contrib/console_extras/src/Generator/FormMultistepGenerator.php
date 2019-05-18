<?php

namespace Drupal\console_extras\Generator;

use Drupal\Console\Core\Generator\Generator;
use Drupal\Console\Core\Utils\StringConverter;

/**
 * Class FormMultistepGenerator.
 *
 * @package Drupal\Console\Generator.
 */
class FormMultistepGenerator extends Generator {

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
      'form_class' => $form_class,
      'form_class_underscore' => $form_class_underscore,
      'form_class_human' => $form_class_human,
    ];

    // Adds extra skeleton dir so the console can find the templates files.
    $this->addSkeletonDir($src_module_path . '/console/templates');

    // Generates form file.
    $this->renderFile(
      'form/multistep/src/Form/form.php.twig',
      $dest_module_path . '/src/Form/' . $parameters['form_class'] . '.php',
      $parameters
    );

    // Generates block file.
    $this->renderFile(
      'form/multistep/src/Plugin/Block/block.php.twig',
      $dest_module_path . '/src/Plugin/Block/' . $parameters['form_class'] . 'Block.php',
      $parameters
    );
  }

}
