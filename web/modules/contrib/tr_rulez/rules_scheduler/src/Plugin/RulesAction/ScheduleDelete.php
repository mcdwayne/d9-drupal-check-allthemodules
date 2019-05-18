<?php

namespace Drupal\rules_scheduler\Plugin\RulesAction;

use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesActionBase;
use Drupal\rules\Exception\IntegrityException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Delete scheduled tasks' action.
 *
 * @RulesAction(
 *   id = "schedule_delete",
 *   label = @Translation("Delete scheduled tasks"),
 *   category = @Translation("Rules scheduler"),
 *   context = {
 *     "component" = @ContextDefinition("string",
 *       label = @Translation("Component"),
 *       list_options_callback = "getOptionsList",
 *       description = @Translation("The component for which scheduled tasks will be deleted."),
 *       optional = TRUE
 *     ),
 *     "task" = @ContextDefinition("string",
 *       label = @Translation("Task identifier"),
 *       description = @Translation("All tasks that are annotated with the given identifier will be deleted."),
 *       optional = TRUE
 *     )
 *   }
 * )
 */
class ScheduleDelete extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a ScheduleDelete object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
    );
  }

  /**
   * Action: Delete scheduled tasks.
   *
   * @param string $component_name
   *   The component name.
   * @param int $task_identifier
   *   The task identifier.
   */
  protected function doExecute($component_name = NULL, $task_identifier = NULL) {
    $query = $this->database->delete('rules_scheduler');
    if (!empty($component_name)) {
      $query->condition('config', $component_name);
    }
    if (!empty($task_identifier)) {
      $query->condition('identifier', $task_identifier);
    }
    $query->execute();
  }

  /**
   * Cancels scheduled task action validation callback.
   */
  public function validate($element) {
    if (empty($element->settings['task']) && empty($element->settings['task:select']) &&
        empty($element->settings['component']) && empty($element->settings['component:select'])) {

      throw new IntegrityException($this->t('You have to specify at least either a component or a task identifier.'), $element);
    }
  }

  /**
   * Help for the cancel action.
   */
  public function help() {
    return $this->t('This action allows you to delete scheduled tasks that are waiting for future execution. They can be addressed by an identifier or by the component name, whereas if both are specified only tasks fulfilling both requirements will be deleted.');
  }

}
