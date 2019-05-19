<?php

/**
 * @file
 * Contains \Drupal\youtrack\Plugin\RulesAction\CreateIssue.
 */

namespace Drupal\youtrack\Plugin\RulesAction;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\rules\Core\RulesActionBase;
use Drupal\youtrack\API\IssueManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the 'Create an Issue' action.
 *
 * @RulesAction(
 *   id = "youtrack_create_issue",
 *   label = @Translation("Create an Issue"),
 *   category = @Translation("YouTrack"),
 *   context = {
 *     "summary" = @ContextDefinition("string",
 *       label = @Translation("Summary"),
 *       description = @Translation("Specify the issue summary."),
 *     ),
 *     "description" = @ContextDefinition("string",
 *       label = @Translation("Description"),
 *       description = @Translation("Specify the issue description."),
 *     ),
 *     "project" = @ContextDefinition("string",
 *       label = @Translation("Project ID"),
 *       description = @Translation("YouTrack project ID to create the issue in."),
 *     ),
 *     "commands" = @ContextDefinition("string",
 *       label = @Translation("Commands"),
 *       description = @Translation("Execute additional commands on the created issue. Use it for setting priority, assignees, tags and so on. See https://confluence.jetbrains.com/display/YTD65/Quick+Start+Guide.+Using+Command+Window for the documentation"),
 *     )
 *   }
 * )
 */
class CreateIssue extends RulesActionBase implements ContainerFactoryPluginInterface {

  /**
   * The issue manager used to create the issue.
   *
   * @var \Drupal\youtrack\API\IssueManager $youTrackIssuesManager
   */
  protected $youTrackIssuesManager;

  /**
   * The corresponding request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('youtrack.issue')
    );
  }

  /**
   * Constructs the CreateIssue object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, IssueManager $issues_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->youTrackIssuesManager = $issues_manager;
  }

  /**
   * Executes the action with the given context.
   */
  protected function doExecute($summary, $description, $project, $commands) {
    $this->youTrackIssuesManager->createIssue($project, $summary, $description, $commands);
  }

}
