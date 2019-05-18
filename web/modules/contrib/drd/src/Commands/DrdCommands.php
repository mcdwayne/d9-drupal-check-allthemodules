<?php

namespace Drupal\drd\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\drd\Plugin\Action\Base;
use Drupal\drd\Plugin\Action\BaseGlobalInterface;
use Drupal\drd\Plugin\Action\BaseInterface;
use Drupal\user\Entity\User;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Class Base.
 *
 * @package Drupal\drd
 */
class DrdCommands extends DrushCommands {

  /**
   * DRD action which will be executed.
   *
   * @var \Drupal\drd\Plugin\Action\BaseInterface
   */
  protected $action;

  /**
   * ID of the action to be executed.
   *
   * @var string
   */
  protected $actionKey;

  /**
   * Options from the command line.
   *
   * @var array
   */
  protected $options = [];

  /**
   * List of entities for which the action will be executed.
   *
   * @var \Drupal\Core\Entity\EntityInterface[]
   */
  protected $entities;

  /**
   * Constructor for Drush commands.
   */
  public function __construct() {
    parent::__construct();
    if (drush_get_context('DRUSH_DEBUG')) {
      \Drupal::service('drd.logging')->enforceDebug();
    }
  }

  /**
   * Callback to validate arguments from the command line.
   *
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *   Source of the command data.
   * @param array $arguments
   *   List of argument ids that are expected from the command line.
   *
   * @throws \Exception
   */
  protected function validateArguments(CommandData $commandData, array $arguments) {
    $commandArguments = $commandData->arguments();
    foreach ($arguments as $argument) {
      if (empty($commandArguments[$argument])) {
        throw new \Exception('Missing argument ' . $argument);
      }
    }
  }

  /**
   * Load and configure service to select entities.
   *
   * @return \Drupal\drd\Entities
   *   DRD service for entity selction.
   */
  protected function service() {
    return \Drupal::service('drd.entities')
      ->setTag($this->options['tag'])
      ->setHost($this->options['host'])
      ->setHostId($this->options['host-id'])
      ->setCore($this->options['core'])
      ->setCoreId($this->options['core-id'])
      ->setDomain($this->options['domain'])
      ->setDomainId($this->options['domain-id']);
  }

  /**
   * Prepare services, the action and their arguments.
   *
   * @param array $arguments
   *   Associated array with arguments.
   * @param array $options
   *   List of option keys.
   *
   * @return $this
   */
  protected function prepare(array $arguments = [], array $options = []) {
    \Drupal::service('drd.logging')->setIO($this->io());
    $this->action = Base::instance($this->actionKey);
    if (!$this->action || !($this->action instanceof BaseInterface)) {
      return $this;
    }
    \Drupal::currentUser()->setAccount(User::load(1));

    foreach ($arguments as $key => $value) {
      $this->action->setActionArgument($key, $value);
    }

    foreach ($options as $key) {
      if (isset($this->options[$key])) {
        $this->action->setActionArgument($key, $this->options[$key]);
      }
    }

    return $this;
  }

  /**
   * Callback to execute the prepared action.
   */
  protected function execute() {
    if (empty($this->action)) {
      return FALSE;
    }
    if ($this->action instanceof BaseGlobalInterface) {
      $this->entities[] = FALSE;
    }
    elseif (empty($this->entities)) {
      return FALSE;
    }

    $result = FALSE;
    $ok = $failure = 0;
    $this->io()->title('Executing ' . $this->action->getPluginDefinition()['label']);
    foreach ($this->entities as $entity) {
      if ($entity) {
        /* @var \Drupal\drd\Entity\BaseInterface $entity */
        /* @var \Drupal\drd\Plugin\Action\BaseEntityInterface $action */
        $action = $this->action;
        $this->io()->section('- on id ' . $entity->id() . ': ' . $entity->getName());
        $result = $action->executeAction($entity);
      }
      else {
        $result = $this->action->executeAction();
      }
      if ($result !== FALSE) {
        $this->io()->success('  ok!');
        $ok++;
      }
      else {
        $this->io()->error('  failure!');
        $failure++;
      }

      $output = $this->action->getOutput();
      if ($output) {
        foreach ($output as $value) {
          $this->io()->write('  ' . $value, TRUE);
        }
      }
    }

    /* @var \Drupal\drd\QueueManager $q */
    $q = \Drupal::service('queue.drd');
    $q->processAll();

    if (empty($failure)) {
      $this->io()->success('Completed!');
    }
    elseif (empty($ok)) {
      $this->io()->error('Completed!');
    }
    else {
      $this->io()->warning('Completed!');
    }
    return $result;
  }

  /**
   * Retrieve blocks from remote domain(s).
   *
   * @param array $options
   *   CLI options.
   *
   * @command drd:blocks
   * @option tag Tag name
   * @option host Host name
   * @option host-id Host ID
   * @option core Core name
   * @option core-id Core ID
   * @option domain Domain name
   * @option domain-id Domain ID
   * @option module The remote module from which to receive blocks
   * @option delta The identifier of a specific block within a module
   * @aliases drd-blocks
   */
  public function blocks(array $options = [
    'tag' => NULL,
    'host' => NULL,
    'host-id' => NULL,
    'core' => NULL,
    'core-id' => NULL,
    'domain' => NULL,
    'domain-id' => NULL,
    'module' => NULL,
    'delta' => NULL,
  ]) {
    $this->actionKey = 'drd_action_blocks';
    $this->options = $options;
    $this->entities = $this->service()->domains();
    $this
      ->prepare([], ['module', 'delta'])
      ->execute();
  }

  /**
   * Run cron on remote domain(s).
   *
   * @param array $options
   *   CLI options.
   *
   * @command drd:cron
   * @option tag Tag name
   * @option host Host name
   * @option host-id Host ID
   * @option core Core name
   * @option core-id Core ID
   * @option domain Domain name
   * @option domain-id Domain ID
   * @aliases drd-cron
   */
  public function cron(array $options = [
    'tag' => NULL,
    'host' => NULL,
    'host-id' => NULL,
    'core' => NULL,
    'core-id' => NULL,
    'domain' => NULL,
    'domain-id' => NULL,
  ]) {
    $this->actionKey = 'drd_action_cron';
    $this->options = $options;
    $this->entities = $this->service()->domains();
    $this
      ->prepare()
      ->execute();
  }

  /**
   * Download database from remote domain(s).
   *
   * @param array $options
   *   CLI options.
   *
   * @command drd:database
   * @option tag Tag name
   * @option host Host name
   * @option host-id Host ID
   * @option core Core name
   * @option core-id Core ID
   * @option domain Domain name
   * @option domain-id Domain ID
   * @aliases drd-database
   */
  public function database(array $options = [
    'tag' => NULL,
    'host' => NULL,
    'host-id' => NULL,
    'core' => NULL,
    'core-id' => NULL,
    'domain' => NULL,
    'domain-id' => NULL,
  ]) {
    $this->actionKey = 'drd_action_database';
    $this->options = $options;
    $this->entities = $this->service()->domains();
    $this
      ->prepare()
      ->execute();
  }

  /**
   * Command to determine all IP addresses of remote host(s).
   *
   * @param array $options
   *   CLI options.
   *
   * @command drd:dnslookup
   * @option tag Tag name
   * @option host Host name
   * @option host-id Host ID
   * @option core Core name
   * @option core-id Core ID
   * @option domain Domain name
   * @option domain-id Domain ID
   * @aliases drd-dnslookup
   */
  public function dnslookup(array $options = [
    'tag' => NULL,
    'host' => NULL,
    'host-id' => NULL,
    'core' => NULL,
    'core-id' => NULL,
    'domain' => NULL,
    'domain-id' => NULL,
  ]) {
    $this->actionKey = 'drd_action_dnslookup';
    $this->options = $options;
    $this->entities = $this->service()->hosts();
    $this
      ->prepare()
      ->execute();
  }

  /**
   * Change the settings of a local domain entity.
   *
   * @param array $options
   *   CLI options.
   *
   * @command drd:domainchange
   * @option tag Tag name
   * @option host Host name
   * @option host-id Host ID
   * @option core Core name
   * @option core-id Core ID
   * @option domain Domain name
   * @option domain-id Domain ID
   * @option newdomain
   * @option secure
   * @option port
   * @option force
   * @aliases drd-domainchange
   */
  public function domainchange(array $options = [
    'tag' => NULL,
    'host' => NULL,
    'host-id' => NULL,
    'core' => NULL,
    'core-id' => NULL,
    'domain' => NULL,
    'domain-id' => NULL,
    'newdomain' => NULL,
    'secure' => NULL,
    'port' => NULL,
    'force' => FALSE,
  ]) {
    $this->actionKey = 'drd_action_domainchange';
    $this->options = $options;
    $this->entities = $this->service()->domains();
    $this
      ->prepare([], ['newdomain', 'secure', 'port', 'force'])
      ->execute();
  }

  /**
   * Move a local domain record to a different core.
   *
   * @param int $dest_core_id
   *   Destination Core ID.
   * @param array $options
   *   CLI options.
   *
   * @command drd:domainmove
   * @option tag Tag name
   * @option host Host name
   * @option host-id Host ID
   * @option core Core name
   * @option core-id Core ID
   * @option domain Domain name
   * @option domain-id Domain ID
   * @aliases drd-domainmove
   */
  public function domainmove($dest_core_id, array $options = [
    'tag' => NULL,
    'host' => NULL,
    'host-id' => NULL,
    'core' => NULL,
    'core-id' => NULL,
    'domain' => NULL,
    'domain-id' => NULL,
  ]) {
    $this->actionKey = 'drd_action_domainchange';
    $this->options = $options;
    $this->entities = $this->service()->domains();
    $this
      ->prepare(['dest-core-id' => $dest_core_id], [
        'domain',
        'secure',
        'port',
        'force',
      ])
      ->execute();
  }

  /**
   * Validation callback for the DomainMove command.
   *
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *   The command data to validate.
   *
   * @hook validate drd:domainmove
   *
   * @throws \Exception
   */
  public function validateDomainMove(CommandData $commandData) {
    $this->validateArguments($commandData, ['dest_core_id']);
  }

  /**
   * Enable all domains of remote core(s).
   *
   * @param array $options
   *   CLI options.
   *
   * @command drd:domains:enableall
   * @option tag Tag name
   * @option host Host name
   * @option host-id Host ID
   * @option core Core name
   * @option core-id Core ID
   * @option domain Domain name
   * @option domain-id Domain ID
   * @aliases drd-domains-enableall
   */
  public function domainsEnableall(array $options = [
    'tag' => NULL,
    'host' => NULL,
    'host-id' => NULL,
    'core' => NULL,
    'core-id' => NULL,
    'domain' => NULL,
    'domain-id' => NULL,
  ]) {
    $this->actionKey = 'drd_action_domains_enableall';
    $this->options = $options;
    $this->entities = $this->service()->cores();
    $this
      ->prepare()
      ->execute();
  }

  /**
   * Receive all domains from remote core(s).
   *
   * @param array $options
   *   CLI options.
   *
   * @command drd:domains:receive
   * @option tag Tag name
   * @option host Host name
   * @option host-id Host ID
   * @option core Core name
   * @option core-id Core ID
   * @option domain Domain name
   * @option domain-id Domain ID
   * @aliases drd-domains-receive
   */
  public function domainsReceive(array $options = [
    'tag' => NULL,
    'host' => NULL,
    'host-id' => NULL,
    'core' => NULL,
    'core-id' => NULL,
    'domain' => NULL,
    'domain-id' => NULL,
  ]) {
    $this->actionKey = 'drd_action_domains_receive';
    $this->options = $options;
    $this->entities = $this->service()->cores();
    $this
      ->prepare()
      ->execute();
  }

  /**
   * Download a file from remote domain(s).
   *
   * @param string $source
   *   Full remote path and filename which should be downloaded file.
   * @param string $destination
   *   Full local path and filename where to store the download file.
   * @param array $options
   *   CLI options.
   *
   * @command drd:download
   * @option tag Tag name
   * @option host Host name
   * @option host-id Host ID
   * @option core Core name
   * @option core-id Core ID
   * @option domain Domain name
   * @option domain-id Domain ID
   * @aliases drd-download
   */
  public function download($source, $destination, array $options = [
    'tag' => NULL,
    'host' => NULL,
    'host-id' => NULL,
    'core' => NULL,
    'core-id' => NULL,
    'domain' => NULL,
    'domain-id' => NULL,
  ]) {
    $this->actionKey = 'drd_action_download';
    $this->options = $options;
    $this->entities = $this->service()->domains();
    $this
      ->prepare(['source' => $source, 'destination' => $destination])
      ->execute();
  }

  /**
   * Validation callback for the Download command.
   *
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *   The command data to validate.
   *
   * @hook validate drd:download
   *
   * @throws \Exception
   */
  public function validateDownload(CommandData $commandData) {
    $this->validateArguments($commandData, ['source', 'destination']);
  }

  /**
   * Download error logs from remote domain(s).
   *
   * @param array $options
   *   CLI options.
   *
   * @command drd:errorlogs
   * @option tag Tag name
   * @option host Host name
   * @option host-id Host ID
   * @option core Core name
   * @option core-id Core ID
   * @option domain Domain name
   * @option domain-id Domain ID
   * @aliases drd-errorlogs
   */
  public function errorlogs(array $options = [
    'tag' => NULL,
    'host' => NULL,
    'host-id' => NULL,
    'core' => NULL,
    'core-id' => NULL,
    'domain' => NULL,
    'domain-id' => NULL,
  ]) {
    $this->actionKey = 'drd_action_error_logs';
    $this->options = $options;
    $this->entities = $this->service()->domains();
    $this
      ->prepare()
      ->execute();
  }

  /**
   * Flush all caches on remote domain(s).
   *
   * @param array $options
   *   CLI options.
   *
   * @command drd:flushcache
   * @option tag Tag name
   * @option host Host name
   * @option host-id Host ID
   * @option core Core name
   * @option core-id Core ID
   * @option domain Domain name
   * @option domain-id Domain ID
   * @aliases drd-flushcache
   */
  public function flushcache(array $options = [
    'tag' => NULL,
    'host' => NULL,
    'host-id' => NULL,
    'core' => NULL,
    'core-id' => NULL,
    'domain' => NULL,
    'domain-id' => NULL,
  ]) {
    $this->actionKey = 'drd_action_flush_cache';
    $this->options = $options;
    $this->entities = $this->service()->domains();
    $this
      ->prepare()
      ->execute();
  }

  /**
   * Receive information from remote domain(s).
   *
   * @param array $options
   *   CLI options.
   *
   * @command drd:info
   * @option tag Tag name
   * @option host Host name
   * @option host-id Host ID
   * @option core Core name
   * @option core-id Core ID
   * @option domain Domain name
   * @option domain-id Domain ID
   * @aliases drd-info
   */
  public function info(array $options = [
    'tag' => NULL,
    'host' => NULL,
    'host-id' => NULL,
    'core' => NULL,
    'core-id' => NULL,
    'domain' => NULL,
    'domain-id' => NULL,
  ]) {
    $this->actionKey = 'drd_action_info';
    $this->options = $options;
    $this->entities = $this->service()->domains();
    $this
      ->prepare()
      ->execute();
  }

  /**
   * Run the job scheduler on remote domain(s).
   *
   * @param array $options
   *   CLI options.
   *
   * @command drd:jobscheduler
   * @option tag Tag name
   * @option host Host name
   * @option host-id Host ID
   * @option core Core name
   * @option core-id Core ID
   * @option domain Domain name
   * @option domain-id Domain ID
   * @aliases drd-jobscheduler
   */
  public function jobscheduler(array $options = [
    'tag' => NULL,
    'host' => NULL,
    'host-id' => NULL,
    'core' => NULL,
    'core-id' => NULL,
    'domain' => NULL,
    'domain-id' => NULL,
  ]) {
    $this->actionKey = 'drd_action_job_scheduler';
    $this->options = $options;
    $this->entities = $this->service()->domains();
    $this
      ->prepare()
      ->execute();
  }

  /**
   * Domanload and update translations on remote domain(s).
   *
   * @param array $options
   *   CLI options.
   *
   * @command drd:l10n:update
   * @option tag Tag name
   * @option host Host name
   * @option host-id Host ID
   * @option core Core name
   * @option core-id Core ID
   * @option domain Domain name
   * @option domain-id Domain ID
   * @aliases drd-l10n-update
   */
  public function l10nUpdate(array $options = [
    'tag' => NULL,
    'host' => NULL,
    'host-id' => NULL,
    'core' => NULL,
    'core-id' => NULL,
    'domain' => NULL,
    'domain-id' => NULL,
  ]) {
    $this->actionKey = 'drd_action_update_translation';
    $this->options = $options;
    $this->entities = $this->service()->domains();
    $this
      ->prepare()
      ->execute();
  }

  /**
   * Push the latest library to remote domain(s).
   *
   * @param string $source
   *   The source from where to pull the library.
   * @param array $options
   *   CLI options.
   *
   * @command drd:library:push
   * @option tag Tag name
   * @option host Host name
   * @option host-id Host ID
   * @option core Core name
   * @option core-id Core ID
   * @option domain Domain name
   * @option domain-id Domain ID
   * @aliases drd-library-push
   */
  public function libraryPush($source, array $options = [
    'tag' => NULL,
    'host' => NULL,
    'host-id' => NULL,
    'core' => NULL,
    'core-id' => NULL,
    'domain' => NULL,
    'domain-id' => NULL,
  ]) {
    $this->actionKey = 'drd_action_library';
    $this->options = $options;
    $this->entities = $this->service()->domains();
    $this
      ->prepare(['source' => $source])
      ->execute();
  }

  /**
   * Validation callback for the Library command.
   *
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *   The command data to validate.
   *
   * @hook validate drd:library:push
   *
   * @throws \Exception
   */
  public function validateLibraryPush(CommandData $commandData) {
    $this->validateArguments($commandData, ['source']);
  }

  /**
   * Re-build the local library.
   *
   * @command drd:library:build
   * @aliases drd-library-build
   */
  public function libraryBuild() {
    $this->actionKey = 'drd_action_library_build';
    $this
      ->prepare()
      ->execute();
  }

  /**
   * Output a list of cores with certain details.
   *
   * @param string $tag
   *   The tag for which to list actions.
   * @param array $options
   *   CLI options.
   *
   * @command drd:list:actions
   * @table-style default
   * @field-labels
   *   id: ID
   *   type: Type
   *   label: Label
   * @default-fields id,type,label
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   The formatted rows with fields.
   */
  public function listActions($tag, array $options = [
    'format' => 'table',
  ]) {
    $this->actionKey = 'drd_action_list_action';
    $this->setOutput(new NullOutput());

    /** @var \Drupal\drd\RemoteActions $service */
    $service = \Drupal::service('drd.remote.actions');
    $service->setTerm($tag);

    $rows = [];
    /** @var \Drupal\drd\Plugin\Action\BaseInterface $action */
    foreach ($service->getActionPlugins() as $action) {
      $rows[] = [
        'id' => $action->getPluginId(),
        'type' => $action->getPluginDefinition()['type'],
        'label' => $action->getPluginDefinition()['label'],
      ];
    }
    return new RowsOfFields($rows);
  }

  /**
   * Output a list of cores with certain details.
   *
   * @param array $options
   *   CLI options.
   *
   * @command drd:list:cores
   * @option tag Tag name
   * @option host Host name
   * @option host-id Host ID
   * @option core Core name
   * @option core-id Core ID
   * @option domain Domain name
   * @option domain-id Domain ID
   * @table-style default
   * @field-labels
   *   core-id: CID
   *   core-label: Core
   *   host-id: HID
   *   host-label: Host
   * @default-fields core-id,core-label,host-label
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   The formatted rows with fields.
   */
  public function listCores(array $options = [
    'tag' => NULL,
    'host' => NULL,
    'host-id' => NULL,
    'core' => NULL,
    'core-id' => NULL,
    'domain' => NULL,
    'domain-id' => NULL,
    'format' => 'table',
  ]) {
    $this->actionKey = 'drd_action_list_cores';
    $this->options = $options;
    $this->setOutput(new NullOutput());
    $rows = $this
      ->prepare([], array_keys($options))
      ->execute();
    return new RowsOfFields($rows);
  }

  /**
   * Output a list of domains with certain details.
   *
   * @param array $options
   *   CLI options.
   *
   * @command drd:list:domains
   * @option tag Tag name
   * @option host Host name
   * @option host-id Host ID
   * @option core Core name
   * @option core-id Core ID
   * @option domain Domain name
   * @option domain-id Domain ID
   * @table-style default
   * @field-labels
   *   domain-id: DID
   *   domain-label: Name
   *   domain: Domain
   *   core-id: CID
   *   core-label: Core
   *   host-id: HID
   *   host-label: Host
   * @default-fields domain-id,domain-label,domain,core-label,host-label
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   The formatted rows with fields.
   */
  public function listDomains(array $options = [
    'tag' => NULL,
    'host' => NULL,
    'host-id' => NULL,
    'core' => NULL,
    'core-id' => NULL,
    'domain' => NULL,
    'domain-id' => NULL,
    'format' => 'table',
  ]) {
    $this->actionKey = 'drd_action_list_domains';
    $this->options = $options;
    $this->setOutput(new NullOutput());
    $rows = $this
      ->prepare([], array_keys($options))
      ->execute();
    return new RowsOfFields($rows);
  }

  /**
   * Output a list of hosts with certain details.
   *
   * @param array $options
   *   CLI options.
   *
   * @command drd:list:hosts
   * @option tag Tag name
   * @option host Host name
   * @option host-id Host ID
   * @option core Core name
   * @option core-id Core ID
   * @option domain Domain name
   * @option domain-id Domain ID
   * @table-style default
   * @field-labels
   *   host-id: HID
   *   host-label: Host
   * @default-fields host-id,host-label
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   The formatted rows with fields.
   */
  public function listHosts(array $options = [
    'tag' => NULL,
    'host' => NULL,
    'host-id' => NULL,
    'core' => NULL,
    'core-id' => NULL,
    'domain' => NULL,
    'domain-id' => NULL,
    'format' => 'table',
  ]) {
    $this->actionKey = 'drd_action_list_hosts';
    $this->options = $options;
    $this->setOutput(new NullOutput());
    $rows = $this
      ->prepare([], array_keys($options))
      ->execute();
    return new RowsOfFields($rows);
  }

  /**
   * Get or set the maintenance mode on/from remote domain(s).
   *
   * @param string $mode
   *   The mode for this command, you can turn on or off maintenance mode and
   *   you can get the current status.
   * @param array $options
   *   CLI options.
   *
   * @command drd:maintenancemode
   * @option tag Tag name
   * @option host Host name
   * @option host-id Host ID
   * @option core Core name
   * @option core-id Core ID
   * @option domain Domain name
   * @option domain-id Domain ID
   * @aliases drd-maintenancemode
   */
  public function maintenancemode($mode, array $options = [
    'tag' => NULL,
    'host' => NULL,
    'host-id' => NULL,
    'core' => NULL,
    'core-id' => NULL,
    'domain' => NULL,
    'domain-id' => NULL,
  ]) {
    $this->actionKey = 'drd_action_maintenance_mode';
    $this->options = $options;
    $this->entities = $this->service()->domains();
    $this
      ->prepare(['mode' => $mode])
      ->execute();
  }

  /**
   * Validation callback for the MaintenanceMode command.
   *
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *   The command data to validate.
   *
   * @hook validate drd:maintenancemode
   *
   * @throws \Exception
   */
  public function validateMaintenanceMode(CommandData $commandData) {
    $this->validateArguments($commandData, ['mode']);
  }

  /**
   * Execute arbitrary PHP code on remote domain(s).
   *
   * @param string $php
   *   Arbitrary PHP code to be executed.
   * @param array $options
   *   CLI options.
   *
   * @command drd:php
   * @option tag Tag name
   * @option host Host name
   * @option host-id Host ID
   * @option core Core name
   * @option core-id Core ID
   * @option domain Domain name
   * @option domain-id Domain ID
   * @aliases drd-php
   */
  public function php($php, array $options = [
    'tag' => NULL,
    'host' => NULL,
    'host-id' => NULL,
    'core' => NULL,
    'core-id' => NULL,
    'domain' => NULL,
    'domain-id' => NULL,
  ]) {
    $this->actionKey = 'drd_action_php';
    $this->options = $options;
    $this->entities = $this->service()->domains();
    $this
      ->prepare(['php' => $php])
      ->execute();
  }

  /**
   * Validation callback for the Php command.
   *
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *   The command data to validate.
   *
   * @hook validate drd:php
   *
   * @throws \Exception
   */
  public function validatePhp(CommandData $commandData) {
    $this->validateArguments($commandData, ['php']);
  }

  /**
   * Ping remote domain(s).
   *
   * @param array $options
   *   CLI options.
   *
   * @command drd:ping
   * @option tag Tag name
   * @option host Host name
   * @option host-id Host ID
   * @option core Core name
   * @option core-id Core ID
   * @option domain Domain name
   * @option domain-id Domain ID
   * @option save
   * @aliases drd-ping
   */
  public function ping(array $options = [
    'tag' => NULL,
    'host' => NULL,
    'host-id' => NULL,
    'core' => NULL,
    'core-id' => NULL,
    'domain' => NULL,
    'domain-id' => NULL,
    'save' => FALSE,
  ]) {
    $this->actionKey = 'drd_action_ping';
    $this->options = $options;
    $this->entities = $this->service()->domains();
    $this
      ->prepare([], ['save'])
      ->execute();
  }

  /**
   * Receive a list of projects used by remote domain(s).
   *
   * @param array $options
   *   CLI options.
   *
   * @command drd:projects:usage
   * @option tag Tag name
   * @option host Host name
   * @option host-id Host ID
   * @option core Core name
   * @option core-id Core ID
   * @option domain Domain name
   * @option domain-id Domain ID
   * @aliases drd-projects-usage
   */
  public function projectsUsage(array $options = [
    'tag' => NULL,
    'host' => NULL,
    'host-id' => NULL,
    'core' => NULL,
    'core-id' => NULL,
    'domain' => NULL,
    'domain-id' => NULL,
  ]) {
    $this->actionKey = 'drd_action_projects';
    $this->options = $options;
    $this->entities = $this->service()->domains();
    $this
      ->prepare()
      ->execute();
  }

  /**
   * Check update status for all projects being used by all remote domains.
   *
   * @command drd:projects:status
   * @aliases drd-projects-status
   */
  public function projectsStatus() {
    $this->actionKey = 'drd_action_projects_status';
    $this
      ->prepare()
      ->execute();
  }

  /**
   * Update a complete Drupal installation of remote core(s).
   *
   * @param array $options
   *   CLI options.
   *
   * @command drd:projects:update
   * @option tag Tag name
   * @option host Host name
   * @option host-id Host ID
   * @option core Core name
   * @option core-id Core ID
   * @option domain Domain name
   * @option domain-id Domain ID
   * @option dry-run Perform the update in the working directory but do not
   *   commit, push or deploy
   * @option show-log Show the log output
   * @option list Output a list of available updates
   * @option include-locked Also include locked releases
   * @option security-only Only include security updates
   * @option force-locked-security Always include security updates, even if
   *   locked
   * @aliases drd-projects-update
   */
  public function projectsUpdate(array $options = [
    'tag' => NULL,
    'host' => NULL,
    'host-id' => NULL,
    'core' => NULL,
    'core-id' => NULL,
    'domain' => NULL,
    'domain-id' => NULL,
    'dry-run' => FALSE,
    'show-log' => FALSE,
    'list' => FALSE,
    'include-locked' => FALSE,
    'security-only' => FALSE,
    'force-locked-security' => FALSE,
  ]) {
    $this->actionKey = 'drd_action_projects_update';
    $this->options = $options;
    $this->entities = $this->service()->cores();
    $this
      ->prepare([], [
        'dry-run',
        'show-log',
        'list',
        'include-locked',
        'security-only',
        'force-locked-security',
      ])
      ->execute();
  }

  /**
   * Display logs of core updates.
   *
   * @param array $options
   *   CLI options.
   *
   * @command drd:projects:update:log
   * @option tag Tag name
   * @option host Host name
   * @option host-id Host ID
   * @option core Core name
   * @option core-id Core ID
   * @option domain Domain name
   * @option domain-id Domain ID
   * @option id Shows the latest by default, you can get any of the other from
   *   the list with this option
   * @option list List die available logs
   * @aliases drd-projects-update-log
   */
  public function projectsUpdateLog(array $options = [
    'tag' => NULL,
    'host' => NULL,
    'host-id' => NULL,
    'core' => NULL,
    'core-id' => NULL,
    'domain' => NULL,
    'domain-id' => NULL,
    'id' => NULL,
    'list' => FALSE,
  ]) {
    $this->actionKey = 'drd_action_projects_update_log';
    $this->options = $options;
    $this->entities = $this->service()->cores();
    $this
      ->prepare([], ['id', 'list'])
      ->execute();
  }

  /**
   * Lock a project release globally or for specific cores.
   *
   * @param string $projectName
   *   Name of the project to be locked.
   * @param string $version
   *   Version of the project release to be locked.
   * @param array $options
   *   CLI options.
   *
   * @command drd:release:lock
   * @option tag Tag name
   * @option host Host name
   * @option host-id Host ID
   * @option core Core name
   * @option core-id Core ID
   * @option domain Domain name
   * @option domain-id Domain ID
   * @aliases drd-release-lock
   */
  public function projectsReleaseLock($projectName, $version, array $options = [
    'tag' => NULL,
    'host' => NULL,
    'host-id' => NULL,
    'core' => NULL,
    'core-id' => NULL,
    'domain' => NULL,
    'domain-id' => NULL,
  ]) {
    $this->actionKey = 'drd_action_release_lock';
    $this->options = $options;
    $service = $this->service();
    $criteria = $service->getSelectionCriteria();
    $this
      ->prepare([
        'projectName' => $projectName,
        'version' => $version,
        'cores' => empty($criteria) ? NULL : $service->cores(),
      ])
      ->execute();
  }

  /**
   * Unlock a project release globally or for specific cores.
   *
   * @param string $projectName
   *   Name of the project to be unlocked.
   * @param string $version
   *   Version of the project release to be unlocked.
   * @param array $options
   *   CLI options.
   *
   * @command drd:release:unlock
   * @option tag Tag name
   * @option host Host name
   * @option host-id Host ID
   * @option core Core name
   * @option core-id Core ID
   * @option domain Domain name
   * @option domain-id Domain ID
   * @aliases drd-release-unlock
   */
  public function projectsReleaseUnlock($projectName, $version, array $options = [
    'tag' => NULL,
    'host' => NULL,
    'host-id' => NULL,
    'core' => NULL,
    'core-id' => NULL,
    'domain' => NULL,
    'domain-id' => NULL,
  ]) {
    $this->actionKey = 'drd_action_release_unlock';
    $this->options = $options;
    $service = $this->service();
    $criteria = $service->getSelectionCriteria();
    $this
      ->prepare([
        'projectName' => $projectName,
        'version' => $version,
        'cores' => empty($criteria) ? NULL : $service->cores(),
      ])
      ->execute();
  }

  /**
   * Receive a URL to start a session on remote domain(s).
   *
   * @param array $options
   *   CLI options.
   *
   * @command drd:session
   * @option tag Tag name
   * @option host Host name
   * @option host-id Host ID
   * @option core Core name
   * @option core-id Core ID
   * @option domain Domain name
   * @option domain-id Domain ID
   * @aliases drd-session
   */
  public function session(array $options = [
    'tag' => NULL,
    'host' => NULL,
    'host-id' => NULL,
    'core' => NULL,
    'core-id' => NULL,
    'domain' => NULL,
    'domain-id' => NULL,
  ]) {
    $this->actionKey = 'drd_action_session';
    $this->options = $options;
    $this->entities = $this->service()->domains();
    $this
      ->prepare()
      ->execute();
  }

  /**
   * Run update.php on remote domain(s).
   *
   * @param array $options
   *   CLI options.
   *
   * @command drd:update
   * @option tag Tag name
   * @option host Host name
   * @option host-id Host ID
   * @option core Core name
   * @option core-id Core ID
   * @option domain Domain name
   * @option domain-id Domain ID
   * @aliases drd-update
   */
  public function update(array $options = [
    'tag' => NULL,
    'host' => NULL,
    'host-id' => NULL,
    'core' => NULL,
    'core-id' => NULL,
    'domain' => NULL,
    'domain-id' => NULL,
  ]) {
    $this->actionKey = 'drd_action_update';
    $this->options = $options;
    $this->entities = $this->service()->domains();
    $this
      ->prepare()
      ->execute();
  }

  /**
   * Change credentials of a user on remote domain(s).
   *
   * @param int $uid
   *   User id of the remote account which should be changed.
   * @param array $options
   *   CLI options.
   *
   * @command drd:user:credentials
   * @option tag Tag name
   * @option host Host name
   * @option host-id Host ID
   * @option core Core name
   * @option core-id Core ID
   * @option domain Domain name
   * @option domain-id Domain ID
   * @option username The user name to be set
   * @option password The password to be set
   * @option status The status to be set
   * @aliases drd-user-credentials
   */
  public function userCredentials($uid, array $options = [
    'tag' => NULL,
    'host' => NULL,
    'host-id' => NULL,
    'core' => NULL,
    'core-id' => NULL,
    'domain' => NULL,
    'domain-id' => NULL,
    'username' => NULL,
    'password' => NULL,
    'status' => NULL,
  ]) {
    $this->actionKey = 'drd_action_user_credentials';
    $this->options = $options;
    $this->entities = $this->service()->domains();
    $this
      ->prepare(['uid' => $uid, ['username', 'password', 'status']])
      ->execute();
  }

  /**
   * Validation callback for the UserCredential command.
   *
   * @param \Consolidation\AnnotatedCommand\CommandData $commandData
   *   The command data to validate.
   *
   * @hook validate drd:user:credentials
   *
   * @throws \Exception
   */
  public function validateUserCredentials(CommandData $commandData) {
    $this->validateArguments($commandData, ['uid']);
  }

  /**
   * Remove unused releases from the local database.
   *
   * @command drd:cleanup:unused:releases
   * @aliases drd-cleanup-unused-releases
   */
  public function cleanupUnusedReleases() {
    \Drupal::service('drd.cleanup')->cleanupReleases();
  }

  /**
   * Remove unused major versions from the local database.
   *
   * @command drd:cleanup:unused:majors
   * @aliases drd-cleanup-unused-majors
   */
  public function cleanupUnusedMajors() {
    \Drupal::service('drd.cleanup')->cleanupMajors();
  }

  /**
   * Remove unused projects from the local database.
   *
   * @command drd:cleanup:unused:projects
   * @aliases drd-cleanup-unused-projects
   */
  public function cleanupUnusedProjects() {
    \Drupal::service('drd.cleanup')->cleanupProjects();
  }

  /**
   * Remove all projects, major versions and releases from the local database.
   *
   * @command drd:reset:all:projects:data
   * @aliases drd-reset-all-projects-data
   */
  public function resetAllProjectsData() {
    \Drupal::service('drd.cleanup')->resetAll();
  }

}
