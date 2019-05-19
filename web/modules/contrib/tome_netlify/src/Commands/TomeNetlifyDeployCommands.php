<?php

namespace Drupal\tome_netlify\Commands;

use Drupal\tome_netlify\TomeNetlifyDeployBatch;
use Drush\Commands\DrushCommands;

/**
 * Contains the tome-netlify:deploy command.
 */
class TomeNetlifyDeployCommands extends DrushCommands {

  /**
   * The batch service.
   *
   * @var \Drupal\tome_netlify\TomeNetlifyDeployBatch
   */
  protected $batch;

  /**
   * TomeNetlifyDeployCommand constructor.
   *
   * @param \Drupal\tome_netlify\TomeNetlifyDeployBatch $batch
   *   The batch service.
   */
  public function __construct(TomeNetlifyDeployBatch $batch) {
    $this->batch = $batch;
  }

  /**
   * Deploys a static build to Netlify.
   *
   * @command tome-netlify:deploy
   * @option title A title to identify this build.
   */
  public function deploy(array $options = ['title' => 'Sent from Tome Netlify']) {
    if (!$this->batch->checkConfiguration()) {
      $this->io()->error('Tome Netlify has not been configured.');
      return 1;
    }
    if (!$this->batch->checkStaticBuild()) {
      $this->io()->error('No static build available for deploy.');
      return 1;
    }
    $batch_builder = $this->batch->getBatch($options['title'])
      ->setFinishCallback([$this, 'finishCallback']);
    batch_set($batch_builder->toArray());
    $result = drush_backend_batch_process();
    if (!is_array($result) || !array_key_exists('object', $result) || !array_key_exists('deploy_ssl_url', $result['object'][0])) {
      $this->io()->error('Deploy failed - consult the error log for more details.');
      return 1;
    }
    if (!empty($result['object'][0]['errors'])) {
      foreach ($result['object'][0]['errors'] as $error) {
        $this->io()->error($error);
      }
      $this->io()->error('Deploy failed - consult the error log for more details.');
      return 1;
    }
    $this->io()->success("Deploy complete!\nView deploy: {$result['object'][0]['deploy_ssl_url']}\nPublish deploy: {$result['object'][0]['admin_url']}/deploys");
  }

  /**
   * Batch finished callback after the static site has been deployed.
   *
   * @param bool $success
   *   Whether or not the batch was successful.
   * @param mixed $results
   *   Batch results set with context.
   */
  public function finishCallback($success, $results) {}

}
