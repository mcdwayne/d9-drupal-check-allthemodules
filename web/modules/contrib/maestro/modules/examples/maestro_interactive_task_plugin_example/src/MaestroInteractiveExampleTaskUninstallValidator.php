<?php

namespace Drupal\maestro_interactive_task_plugin_example;

use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\maestro\Engine\MaestroEngine;

/**
 * Prevents example task module from being uninstalled when the task is bound in a template
 */
class MaestroInteractiveExampleTaskUninstallValidator implements ModuleUninstallValidatorInterface {

  use StringTranslationTrait;

  /**
   * Constructs a new MaestroInteractiveExampleTaskUninstallValidator.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(TranslationInterface $string_translation) {
    $this->stringTranslation = $string_translation;  //we only use string translation in this validator, the rest is up to the Maestro Engine
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module) {
    $reasons = [];
    if ($module == 'maestro_interactive_task_plugin_example') {
      //cycle through all of the Maestro templates and determine if any of the tasks are of type MaestroIntExample
      
      $templates = MaestroEngine::getTemplates();
      foreach($templates as $template) {
        foreach($template->tasks as $task) {
          if($task['tasktype'] == 'MaestroIntExample') {
            $reasons[] = $this->t('To uninstall the Interactive Plugin Task Example module, remove the Interactive Example task from the <em>:template</em> template.', array(':template' => $template->label));
          }
        }
      }
    }
    return $reasons;
  }
}
