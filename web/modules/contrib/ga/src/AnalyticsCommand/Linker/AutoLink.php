<?php

namespace Drupal\ga\AnalyticsCommand\Linker;

use Drupal\ga\AnalyticsCommand\Generic;

/**
 * Class AutoLink.
 */
class AutoLink extends Generic {

  /**
   * A list of domains.
   *
   * @var array
   */
  protected $domains;

  /**
   * Add linker parameter to anchor rather than query.
   *
   * @var bool|null
   */
  protected $useAnchor;

  /**
   * Add linker parameters to form submissions.
   *
   * @var bool|null
   */
  protected $decorateForms;

  /**
   * Create constructor.
   *
   * @param array $domains
   *   A list of domains.
   * @param bool|null $useAnchor
   *   Add linker parameter to anchor rather than query (optional).
   * @param bool|null $decorateForms
   *   Add linker parameters to form submissions (optional).
   * @param string $tracker_name
   *   The tracker name (optional).
   * @param int $priority
   *   The command priority.
   */
  public function __construct(array $domains, $useAnchor = NULL, $decorateForms = NULL, $tracker_name = NULL, $priority = parent::DEFAULT_PRIORITY) {
    parent::__construct('linker:autoLink', [], $tracker_name, $priority);

    $this->domains = $domains;
    $this->useAnchor = $useAnchor;
    $this->decorateForms = $decorateForms;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingCommands() {
    $command = [
      ($this->trackerName ? $this->trackerName . '.' : '') . $this->command,
      $this->domains,
    ];

    if (!is_null($this->useAnchor)) {
      $command[] = $this->useAnchor;
    }
    // Add default value if later option is specified.
    elseif (!is_null($this->decorateForms)) {
      $command[] = FALSE;
    }

    if (!is_null($this->decorateForms)) {
      $command[] = $this->decorateForms;
    }

    return [$command];
  }

}
