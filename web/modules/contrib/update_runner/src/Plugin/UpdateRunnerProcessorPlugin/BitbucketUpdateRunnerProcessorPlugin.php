<?php

namespace Drupal\update_runner\Plugin\UpdateRunnerProcessorPlugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use GuzzleHttp\Exception\ClientException;

/**
 * Defines the bitbucket processor plugin.
 *
 * @UpdateRunnerProcessorPlugin(
 *  id = "bitbucket_update_runner_processor_plugin",
 *  label = @Translation("Bitbucket Processor"),
 * )
 */
class BitbucketUpdateRunnerProcessorPlugin extends UpdateRunnerProcessorPlugin implements ContainerFactoryPluginInterface, PluginInspectionInterface {

  /**
   * {@inheritdoc}
   */
  public function run($job) {

    $access_token = $this->getAccessToken($this->configuration['api_key'], $this->configuration['api_secret']);
    if (!$access_token) {
      return UPDATE_RUNNER_JOB_FAILED;
    }

    $auth = 'Bearer ' . $access_token;

    // Check previous sha1.
    try {
      $query = $this->httpClient->get($this->configuration['api_endpoint'] . '/repositories/' . $this->configuration['api_repository'] . '/refs/branches/' . $this->configuration['api_branch'], [
        'headers' => [
          'Authorization' => $auth,
        ],
      ]);

      $contents = json_decode($query->getBody()->getContents());
    }
    catch (ClientException $e) {
      // Might be first commit.
    }

    $object = [
      'author' => $this->configuration['api_commiter_info'],
      'branch' => $this->configuration['api_branch'],
      'update_runner.json' => json_encode(unserialize($job->data->value)),
      'message' => 'Update Runner Commit - ' . date('Y-m-d H:i:s'),
    ];

    // Make sure previous commit is parent.
    if (!empty($contents)) {
      $object['parents'] = $contents->target->hash;
    }

    // Does the push.
    try {
      $query = $this->httpClient->post(trim($this->configuration['api_endpoint']) . '/repositories/' . $this->configuration['api_repository'] . '/src', [
        'form_params' => (array) ($object),
        'headers' => [
          'Authorization' => $auth,
          'Content-Type' => 'application/x-www-form-urlencoded',
        ],
      ]);

    }
    catch (RequestException $e) {
      $this->logger->error("Update runner process for bitbucket plugin failed: %msg", ['%msg' => $e->getMessage()]);
      return UPDATE_RUNNER_JOB_FAILED;
    }

    return parent::run($job);
  }

  /**
   * Gets the access token to use in following calls.
   */
  private function getAccessToken($api_key, $api_secret) {
    $auth = 'Basic ' . base64_encode(trim($api_key) . ':' . trim($api_secret));

    try {
      $query = $this->httpClient->post('https://bitbucket.org/site/oauth2/access_token', [
        'form_params' => ['grant_type' => 'client_credentials'],
        'headers' => [
          'Authorization' => $auth,
        ],
      ]);

      $contents = json_decode($query->getBody()->getContents());
      return $contents->access_token;
    }
    catch (ClientException $e) {
      $this->logger->error("Update runner process for bitbucket plugin failed: %msg", ['%msg' => $e->getMessage()]);
      return UPDATE_RUNNER_JOB_FAILED;;
    }
  }

  /**
   * Returns keys used for configuration.
   */
  public function optionsKeys() {
    return array_merge(parent::optionsKeys(), [
      'api_endpoint',
      'api_repository',
      'api_key',
      'api_secret',
      'api_branch',
      'api_commiter_info',
    ]);
  }

  /**
   * Returns form options for processor configuration.
   *
   * @param \Drupal\update_runner\Plugin\UpdateRunnerProcessorPlugin\EntityInterface|null $entity
   *   Processor to configure.
   *
   * @return array
   *   Form for configuration.
   */
  public function formOptions(EntityInterface $entity = NULL) {

    $formOptions = parent::formOptions($entity);

    $formOptions['bitbucket'] = [
      '#type' => 'fieldset',
      '#title' => t('Bitbucket configuration'),
    ];

    $formOptions['bitbucket']['api_endpoint'] = [
      '#type' => 'textfield',
      '#title' => t('API Endpoint'),
      '#description' => t('In case of bitbucket.com, should be https://api.bitbucket.org/2.0 . Do not include trailing slash.'),
      '#default_value' => !empty($this->defaultValues['api_endpoint']) ? $this->defaultValues['api_endpoint'] : 'https://api.bitbucket.org/2.0',
      '#required' => TRUE,
    ];

    $formOptions['bitbucket']['api_repository'] = [
      '#type' => 'textfield',
      '#title' => t('Repository'),
      '#description' => t('Repository to use'),
      '#required' => TRUE,
      '#default_value' => !empty($this->defaultValues['api_repository']) ? $this->defaultValues['api_repository'] : '',
    ];

    $formOptions['bitbucket']['api_key'] = [
      '#type' => 'textfield',
      '#title' => t('Key'),
      '#description' => t('Key to use'),
      '#required' => TRUE,
      '#default_value' => !empty($this->defaultValues['api_key']) ? $this->defaultValues['api_key'] : '',
    ];

    $formOptions['bitbucket']['api_secret'] = [
      '#type' => 'textfield',
      '#title' => t('Secret'),
      '#description' => t('Secret to use'),
      '#required' => TRUE,
      '#default_value' => !empty($this->defaultValues['api_secret']) ? $this->defaultValues['api_secret'] : '',
    ];

    $formOptions['bitbucket']['api_branch'] = [
      '#type' => 'textfield',
      '#title' => t('Branch'),
      '#required' => TRUE,
      '#description' => t('The branch to use'),
      '#default_value' => !empty($this->defaultValues['api_branch']) ? $this->defaultValues['api_branch'] : '',
    ];

    $formOptions['api_commiter'] = [
      '#type' => 'fieldset',
      '#title' => t('Committer information'),
    ];

    $formOptions['api_commiter']['api_commiter_info'] = [
      '#type' => 'textfield',
      '#title' => t('Committer info'),
      '#required' => TRUE,
      '#description' => Html::escape(t("Name <email>")),
      '#default_value' => !empty($this->defaultValues['api_commiter_info']) ? $this->defaultValues['api_commiter_info'] : '',
    ];

    return $formOptions;
  }

  /**
   * Validates introduced settings.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\update_runner\Plugin\UpdateRunnerProcessorPlugin\FormStateInterface $form_state
   *   Form state.
   */
  public function validate(array &$form, FormStateInterface $form_state) {

    $access_token = $this->getAccessToken($form_state->getValue('api_key'), $form_state->getValue('api_secret'));

    if (!$access_token) {
      return UPDATE_RUNNER_JOB_FAILED;
    }

    $auth = 'Bearer ' . $access_token;
    $repo = $form_state->getValue('api_endpoint') . '/repositories/' . $form_state->getValue('api_repository');

    // Get defined repo.
    try {
      $query = $this->httpClient->get($repo . '/refs/branches/' . $form_state->getValue('api_branch'), [
        'headers' => [
          'Authorization' => $auth,
        ],
      ]);
      // $contents = json_decode($query->getBody()->getContents());
    }
    catch (\Exception $e) {
      $form_state->setErrorByName('api_repository', t('Impossible to query repository %repo, please verify your settings. Error %error', [
        '%repo' => $repo,
        '%error' => $e->getMessage()
      ]));
    }
  }

}
