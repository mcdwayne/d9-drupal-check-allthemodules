<?php /** @noinspection PhpUnusedParameterInspection, PhpDocSignatureInspection, PhpDocMissingReturnTagInspection */

namespace Drupal\wodby\Commands;

use Consolidation\SiteAlias\SiteAliasManagerAwareInterface;
use Consolidation\SiteAlias\SiteAliasManagerAwareTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\wodby\WodbyClientServiceInterface;
use Drush\Commands\DrushCommands;
use Wodby\Api\ApiException;
use Wodby\Api\Configuration;
use Wodby\Api\Model\InstanceType;
use Wodby\Api\Model\Task;

/**
 * A Drush commandfile.
 */
class WodbyDrushCommands extends DrushCommands implements SiteAliasManagerAwareInterface {

  use SiteAliasManagerAwareTrait;

  const DEFAULT_OPTS = [
    'api-key' => self::REQ,
  ];

  /**
   * @var \Drupal\wodby\WodbyClientServiceInterface
   */
  protected $wodbyClientService;

  public function __construct(WodbyClientServiceInterface $wodby_client_service) {
    $this->wodbyClientService = $wodby_client_service;
  }

  /**
   * Downloads the alias file to <code>DRUPAL_ROOT/..drush/sites/self.site.yml</code>
   *
   * @command wodby:alias-download
   * @aliases w:dl
   *
   * @param string $app_uuid
   *   Optional App UUID from Wodby. If not provided will be looked up in the
   *   config, if not available will be prompted.
   *
   * @option api-key
   *   Wodby API Key, if not specified will be fetched from the environment.
   *
   * @usage wodby:alias-download
   *   Downloads the alias file, the App UUID must be set in config or via the environment variable WODBY_APP_UUID
   * @usage wodby:alias-download 'a234-abd1-f34a5-7597-foo'
   *   Downloads the alias for the specified app, disregarding the current site configuration.
   *
   * @throws \Wodby\Api\ApiException
   */
  public function downloadDrushAlias(
    string $app_uuid = NULL,
    $opts = self::DEFAULT_OPTS
  ) {
    $wodby_conf = $this->getWodbyConfig($opts);
    $app_uuid = $app_uuid ?: $this->getCurrentAppUuid();

    $api = $this->wodbyClientService->getAppApi($wodby_conf);

    $alias_file_data = $api->getAppDrushAliases($app_uuid);
    $alias_file_path = realpath(DRUPAL_ROOT . '/../drush/sites/self.site.yml');
    if (!file_unmanaged_save_data($alias_file_data, $alias_file_path, FILE_EXISTS_REPLACE)) {
      $this->logger()->error("Could not write to drush alias file.");
      return FALSE;
    }

    $this->logger()->notice("Alias file updated.");
  }

  /**
   * Sets the App UUID for the current site.
   *
   * @command wodby:set-app-uuid
   * @aliases w:suid
   *
   * @param string $app_uuid
   *   App UUID from Wodby
   *
   * @option skip-api-check
   *   If specified will not check if the UUID is valid against the API.
   * @option api-key
   *   Wodby API Key, if not specified will be fetched from the environment.
   *
   * @usage wodby:set-uuid 'a234-abd1-f34a5-7597-foo'
   *   Sets the App UUID in the configuration of the current site.
   *
   * @throws \Wodby\Api\ApiException
   */
  public function setAppUuid(
    string $app_uuid,
    $opts = [
      'skip-api-check' => FALSE
    ] + self::DEFAULT_OPTS
  ) {
    if (!$opts['skip-api-check']) {
      $wodby_conf = $this->getWodbyConfig($opts);
      $api = $this->wodbyClientService->getAppApi($wodby_conf);
      try {
        /*
         * Try to load the App details via the API, if the app does not exist
         * it wil throw a ApiException.
         */
        $selected_app = $api->getApp($app_uuid);
      }
      catch (ApiException $exception) {
        if ($exception->getCode() === 404) {
          $this->logger()->error(
            new TranslatableMarkup("App does not exist on Wodby or current Api Key does not allow you access. If you want to add this anyway use the --skip-api-check option.")
          );
          // Exit
          return FALSE;
        }

        // Unhandled exception should be propagated.
        throw $exception;
      }
    }

    $this->wodbyClientService->setSiteAppUuid($app_uuid);
    /*
     * Print a nice message if we have the info from the API.
     * If not simply fallback to print the app UUID
     */
    if (isset($selected_app)) {
      $message = new TranslatableMarkup(
        "Configuration updated. Selected App: \"@label\" (Name: @name / UUID: @id)",
        [
          '@label' => $selected_app->getTitle(),
          '@name'  => $selected_app->getTitle(),
          '@id'    => $selected_app->getId(),
        ]
      );

    }
    else {
      $message = new TranslatableMarkup(
        "Configuration updated with new UUID: @id", ['@id' => $app_uuid]
      );
    }
    $this->logger()->notice($message);
  }

  /**
   * Deploys a instance of a specific type.
   *
   * @command wodby:deploy
   * @aliases w:d
   *
   * @param string $instance_type
   *   Should be one of: "prod", "stage", "dev". (default: dev)
   *
   * @option ignore-task-status
   *   If specified it will not wait for the task completion.
   * @option api-key
   *   Wodby API Key, if not specified will be fetched from the environment.
   *
   * @usage wodby:deploy
   *   Deploys the
   * @usage wodby:deploy prod
   *   Deploys the Prod
   *
   * @throws \Wodby\Api\ApiException
   * @throws \Drush\Exceptions\UserAbortException
   */
  public function deployInstance(
    string $instance_type = 'dev',
    $opts = [
      'ignore-task-status' => FALSE,
    ] + self::DEFAULT_OPTS
  ) {
    $allowed_types = InstanceType::getAllowableEnumValues();
    if (!in_array($instance_type, $allowed_types)) {
      $this->logger()->error(
        new TranslatableMarkup("Instance type must be one of: @types", ['@types'=>implode(', ', $allowed_types)])
      );
      // Exit.
      return FALSE;
    }
    $api = $this->wodbyClientService->getInstanceApi($this->getWodbyConfig($opts));

    $app_uuid = $this->getCurrentAppUuid();
    $instances = $api->getInstances(NULL, NULL, $app_uuid, $instance_type);

    if (empty($instances)) {
      throw new \RuntimeException(
        strtr(
          "No instances of type  found for app: @app",
          ['@type' => $instance_type, '@app' => $app_uuid]
        )
      );
    }

    $instance = NULL;
    // If we only have 1, select the first.
    if (count($instances) === 1) {
      $instance = reset($instances);
    }

    if (!$instance) {
      $instances_choice = $this->getChoicesFromInstanceArray($instances);
      $choice = $this->io()->choice("Multiple instances found, select one: ", $instances_choice);
      $instance = $instances[$choice] ?? NULL;
    }

    try {
      $response = $api->deployInstanceCodebase($instance->getId());
    }
    catch (ApiException $exception) {
      throw new \RuntimeException(
        "Unable to deploy codebase.",
        $exception->getCode(),
        $exception
      );
    }

    $this->logger()->notice(
      new TranslatableMarkup(
        "Deploy task started (ID: @id).",
        ['@id' => $response->getTask()->getId()]
      )
    );

    if (!$opts['ignore-task-status']) {
      $task = $response->getTask();
      $task_api = $this->wodbyClientService->getTaskApi($this->getWodbyConfig($opts));
      // Start wait bar.
      $this->io()->progressStart();
      // Keep track of elapsed time.
      $elapsed_time = 0;
      while ($task->getEnd() === 0) {
        // Refresh task info.
        $task = $task_api->getTask($task->getId());

        $status = $task->getStatus();
        if ($status === Task::STATUS_DONE) {
          break;
        }
        elseif ($status === Task::STATUS_CANCELED) {
          $this->logger()->warning(
            new TranslatableMarkup("Deploy task canceled.")
          );
          break;
        }
        elseif ($status === Task::STATUS_FAILED) {
          $this->logger()->error(
            new TranslatableMarkup("Deploy task failed, please check Wodby UI for more info.")
          );
          break;
        }
        elseif ($elapsed_time >= 300) {
          $this->logger()->warning(
            new TranslatableMarkup("Deploy operation is taking to long, giving up.")
          );
          break;
        }
        else {
          sleep(10);
          // Step forward.
          $this->io()->progressAdvance();
          // Add the elapsed time.
          $elapsed_time += 10;
        }
      }
      // Mark completed.
      $this->io()->progressFinish();

      if ($task->getStatus() === Task::STATUS_DONE) {
        /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
        $date_formatter = \Drupal::service('date.formatter');
        // Log the end time.
        $this->logger()->notice(
          new TranslatableMarkup(
            "Deploy task completed at: @end",
            ['@end' => $date_formatter->format($task->getEnd())]
          )
        );
      }
    }
  }

  /**
   * Quick deploy pre-defined instances.
   *
   * @command wodby:quick-deploy
   * @aliases w:ddd
   *
   * @option api-key
   *   Wodby API Key, if not specified will be fetched from the environment.
   *
   * @usage w:qdd
   */
  public function quickDeploy(
    $opts = self::DEFAULT_OPTS
  ) {
    $quick_deploy = $this->wodbyClientService->getQuickDeployList();
    if (empty($quick_deploy)) {
      $this->logger()->warning(
        new TranslatableMarkup("Quick-deploy list is empty.")
      );
      // Exit nicely.
      return;
    }

    $instanceApi = $this->wodbyClientService->getInstanceApi($this->getWodbyConfig($opts));

    $tasks = [];
    // Start wait bar.
    $this->io()->progressStart(count($quick_deploy));
    foreach ($quick_deploy as $instance_id) {
      try {
        $tasks[$instance_id] = $instanceApi->deployInstanceCodebase($instance_id);
      }
      catch (ApiException $e) {
        $this->logger()->error(
          new TranslatableMarkup(
            "Could not deploy instance: @id (Error: @code)",
            ['@id' => $instance_id, '@code' => $e->getCode()]
          )
        );
      }
      // Advance step
      $this->io()->progressAdvance();
    }
    // Loop done.
    $this->io()->progressFinish();

    // Optionally check the status of each item.
    if ($opts['follow-task-status']) {
      // TODO Implement status check on $tasks
      $this->logger()->warning(
        new TranslatableMarkup("Options not implemented: --follow-task-status.")
      );
    }

    $this->logger()->notice(
      new TranslatableMarkup("All deployment requests are done.")
    );
  }


  /**
   * Returns
   * @param $opts
   *
   * @return \Wodby\Api\Configuration
   */
  protected function getWodbyConfig(array $opts): ?Configuration {
    if (!empty($opts['api-key'])) {
      return $this->wodbyClientService->getWodbyConfig($opts['api-key']);
    }

    // Return null if there's
    return NULL;
  }

  /**
   * Gets the current app UUID from environment ot from config.
   *
   * @return string
   */
  protected function getCurrentAppUuid(): string {
    // First try to get from the env.
    $app_uuid = getenv('WODBY_APP_UUID');
    if ($app_uuid) {
      return $app_uuid;
    }

    $app_uuid = $this->wodbyClientService->getSiteAppUuid();
    if ($app_uuid) {
      return $app_uuid;
    }

    throw new \RuntimeException("Missing App UUID from env and config.");
  }

  /**
   * Given an array of instances builds a choice array to be used as IO::choice()
   *
   * @param \Wodby\Api\Model\Instance[] $instances
   *
   * @return array
   */
  protected function getChoicesFromInstanceArray(array $instances): array {
    $choices = [];
    foreach ($instances as $key => $instance) {
      $choices[$key] = strtr(
        "@name (@uuid)",
        ['@name' => $instance->getName(), '@uuid' => $instance->getId()]
      );
    }
    return $choices;
  }
}
