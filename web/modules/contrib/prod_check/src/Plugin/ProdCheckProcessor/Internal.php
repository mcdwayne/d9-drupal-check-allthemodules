<?php

namespace Drupal\prod_check\Plugin\ProdCheckProcessor;

use Drupal\prod_check\Plugin\ProdCheckInterface;

/**
 * Internal processor that handles processing of all checks.
 *
 * @ProdCheckProcessor(
 *   id = "internal",
 *   title = @Translation("Internal prod check processor"),
 * )
 */
class Internal extends ProdCheckProcessorBase {

  /**
   * Fetches all the requirements for the prod check status report page.
   *
   * @return array
   *   An array of requirements keyed by plugin id.
   */
  public function requirements() {
    $definitions = $this->checkManager->getDefinitions();

    $requirements = [];
    foreach ($definitions as $plugin_id => $definition) {
      $plugin = $this->checkManager->createInstance($plugin_id, $definition);
      $requirement = $this->process($plugin);
      if (!empty($requirement)) {
        $requirements[$plugin_id] = $requirement;
      }
    }

    return $requirements;
  }

  /**
   * Processes a single prod check plugin
   *
   * @param \Drupal\prod_check\Plugin\ProdCheckInterface $plugin
   * @return array
   *   An array containing the check result. Contains the status, severity,
   *   title and success or failure messages depending on the result.
   *
   */
  public function process(ProdCheckInterface $plugin) {
    if (!$plugin) {
      return [];
    }

    $plugin->setProcessor($this);

    $status = $plugin->state();
    $requirement = array(
      'status' => $status,
      'severity' => $status ? $this->ok() : $plugin->severity(),
      'title' => $plugin->title(),
      'category' => $plugin->category(),
    );

    if ($status) {
      $requirement += $plugin->successMessages();
    }
    else {
      $requirement += $plugin->failMessages();
    }

    return $requirement;
  }

}
