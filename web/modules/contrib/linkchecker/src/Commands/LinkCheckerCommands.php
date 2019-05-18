<?php

namespace Drupal\linkchecker\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drush\Commands\DrushCommands;
use Psr\Log\LoggerInterface;

/**
 * Drush 9 commands for Linkchecker module.
 */
class LinkCheckerCommands extends DrushCommands {

  use StringTranslationTrait;

  /**
   * A config factory for retrieving required config settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The logger.channel.linkchecker service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * LinkCheckerCommands constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   A config factory object for retrieving configuration settings.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   */
  public function __construct(
    ConfigFactoryInterface $config,
    LoggerInterface $logger
  ) {
    parent::__construct();
    $this->config = $config;
    $this->logger = $logger;
  }

  /**
   * Reanalyzes content for links. Recommended after module has been upgraded.
   *
   * @command linkchecker:analyze
   *
   * @aliases lca
   */
  public function analyze() {
    // @fixme
    global $base_url;
    if ($base_url == 'http://default') {
      $this->logger()->error('You MUST configure the site $base_url or provide --uri parameter.');
    }

    // @fixme
    module_load_include('admin.inc', 'linkchecker');

    // Fake $form_state to leverage _submit function.
    $form_state = [
      'values' => ['op' => $this->t('Analyze content for links')],
      'buttons' => [],
    ];

    $node_types = linkchecker_scan_node_types();
    if (!empty($node_types) || \Drupal::config('linkchecker.settings')->get('scan_blocks')) {
      linkchecker_analyze_links_submit(NULL, $form_state);
      drush_backend_batch_process();
    }
    else {
      $this->logger()->warning('No content configured for link analysis.');
    }
  }

  /**
   * Clears all link data and analyze content for links.
   *
   * WARNING: Custom link check settings are deleted.
   *
   * @command linkchecker:clear
   *
   * @aliases lccl
   */
  public function clear() {
    // @fixme
    global $base_url;
    if ($base_url == 'http://default') {
      $this->logger()->error('You MUST configure the site $base_url or provide --uri parameter.');
      return;
    }

    // @fixme
    module_load_include('admin.inc', 'linkchecker');

    // Fake $form_state to leverage _submit function.
    $form_state = [
      'values' => ['op' => $this->t('Clear link data and analyze content for links')],
      'buttons' => [],
    ];

    $node_types = linkchecker_scan_node_types();
    if (!empty($node_types) || \Drupal::config('linkchecker.settings')
      ->get('scan_blocks')) {
      linkchecker_clear_analyze_links_submit(NULL, $form_state);
      drush_backend_batch_process();
    }
    else {
      $this->logger()->warning('No content configured for link analysis.');
    }
  }

  /**
   * Check link status.
   *
   * @command linkchecker:check
   *
   * @aliases lcch
   */
  public function check() {
    $this->logger()->info('Starting link checking...');
    $run = _linkchecker_check_links();
    if (!$run) {
      $this->logger()->warning('Attempted to re-run link checks while they are already running.');
    }
    else {
      $this->logger()->info('Finished checking links.');
    }
  }

}
