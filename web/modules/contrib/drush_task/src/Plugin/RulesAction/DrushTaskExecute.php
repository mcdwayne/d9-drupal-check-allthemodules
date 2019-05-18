<?php

namespace Drupal\drush_task\Plugin\RulesAction;

use Drupal\drush_task\DrushTask;
use Drupal\rules\Core\RulesActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Execute drush command' action.
 *
 * @RulesAction(
 *   id = "drush_task_execute",
 *   label = @Translation("Execute drush command"),
 *   category = @Translation("Service"),
 *   context = {
 *     "command" = @ContextDefinition("string",
 *       label = @Translation("Drush command"),
 *       description = @Translation("From the commands listed in 'drush help'."),
 *       assignment_restriction = "input",
 *     ),
 *     "site_alias" = @ContextDefinition("string",
 *       label = @Translation("Site alias"),
 *       description = @Translation("An available site alias, such as '@live.host'. Other site-alias syntaxes such as '/var/www/htdocs#site.name' should also work."),
 *       required = FALSE,
 *     ),
 *     "arguments" = @ContextDefinition("string",
 *       label = @Translation("Arguments"),
 *       description = @Translation("Additional arguments to the drush command. They will be enquoted."),
 *       required = FALSE,
 *     )
 *   },
 *   provides = {
 *     "drush_response" = @ContextDefinition("entity",
 *       label = @Translation("Drush response")
 *     )
 *   }
 * )
 */
class DrushTaskExecute extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\drush_task\DrushTask
   */
  protected $task;

  /**
   * Constructs a DrushTaskExecute object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\drush_task\DrushTask $task
   *   The entity type manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DrushTask $task) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->task = $task;
  }

  /**
   * Create tells the system how to create me .
   *
   * It describes what arguments __construct should be given.
   *
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('drush_task.drush_task')
    );
  }

  /**
   * {@inheritdoc}
   *
   * Not sure if I'll need this - probably should drop it.
   */
  public function refineContextDefinitions(array $selected_data) {
    if ($command = $this->getContextValue('command')) {
      if ($command == 'version') {
        $this->pluginDefinition['provides']['drush_response']->setDataType("string");
      }
    }
  }

  /**
   * Executes the action with the given context.
   *
   * @param string $command
   *   Drush command.
   * @param string $site_alias
   *   Site alias.
   * @param string $arguments
   *   Arguments.
   */
  protected function doExecute($command, $site_alias, $arguments) {
    // $task has already been given to me.
    /** @var \Drupal\drush_task\DrushTask $task */
    $this->task->command = $command;
    $this->task->siteAlias = $site_alias;
    $this->task->arguments = $arguments;
    $this->task->run();
    $result = $this->task->resultRaw;
dpm($result, gettype($result));
    $this->setProvidedValue('drush_response', $result);
  }

}
