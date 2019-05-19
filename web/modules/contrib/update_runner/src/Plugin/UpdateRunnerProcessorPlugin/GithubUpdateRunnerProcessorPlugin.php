<?php

namespace Drupal\update_runner\Plugin\UpdateRunnerProcessorPlugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

/**
 * Defines the github processor plugin.
 *
 * @UpdateRunnerProcessorPlugin(
 *  id = "github_update_runner_processor_plugin",
 *  label = @Translation("Github Processor"),
 * )
 */
class GithubUpdateRunnerProcessorPlugin extends UpdateRunnerProcessorPlugin implements ContainerFactoryPluginInterface, PluginInspectionInterface {

  /**
   * {@inheritdoc}
   */
  public function run($job) {

    $auth = 'Basic ' . base64_encode($this->configuration['api_username'] . ':' . $this->configuration['api_token']);

    // Check if file already exists.
    try {
      $query = $this->httpClient->get(trim($this->configuration['api_endpoint']) . '/repos/' . $this->configuration['api_repository'] . '/contents/update_runner.json', [
        'headers' => [
          'Authorization' => $auth,
        ],
      ]);

      $contents = json_decode($query->getBody()->getContents());
    } catch (ConnectException $e) {

    } catch (RequestException $e) {
      // File might not only exists.
    }

    $object = [
      'committer' => [
        'name' => $this->configuration['api_commiter_name'],
        'email' => $this->configuration['api_commiter_email'],
      ],
      'message' => 'Automatic Updates Commit',
      'content' => base64_encode(json_encode(unserialize($job->data->value))),
    ];

    // File already exists, just updates.
    if (!empty($contents)) {
      $object['sha'] = $contents->sha;
    }

    try {
      $query = $this->httpClient->put($this->configuration['api_endpoint'] . 'repos/' . $this->configuration['api_repository'] . '/contents/update_runner.json', [
        'body' => json_encode($object),
        'headers' => [
          'Accept' => 'application/json',
          'Content-Type' => 'application/json',
          'Authorization' => $auth,
        ],
      ]);
    } catch (RequestException $e) {
      $this->logger->error("Update runner process for github plugin failed:  %msg", ['%msg' => $e->getMessage()]);
      return UPDATE_RUNNER_JOB_FAILED;
    }

    return parent::run($job);
  }

  /**
   * Define keys used in the configuration.
   */
  public function optionsKeys() {
    return array_merge(parent::optionsKeys(), [
      'api_endpoint',
      'api_repository',
      'api_username',
      'api_token',
      'api_commiter_name',
      'api_commiter_email',
    ]);
  }

  /**
   * Function to generate form options for the plugin.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   Processor used.
   *
   * @return array
   *   Return form array used for settings.
   */
  public function formOptions(EntityInterface $entity = NULL) {

    $formOptions = parent::formOptions($entity);

    $formOptions['github'] = [
      '#type' => 'fieldset',
      '#title' => t('Github configuration'),
    ];

    $formOptions['github']['api_endpoint'] = [
      '#type' => 'textfield',
      '#title' => t('API Endpoint'),
      '#required' => TRUE,
      '#description' => t('In case of github.com, should be https://api.github.com . Do not include trailing slash'),
      '#default_value' => !empty($this->defaultValues['api_endpoint']) ? $this->defaultValues['api_endpoint'] : 'https://api.github.com',
    ];

    $formOptions['github']['api_repository'] = [
      '#type' => 'textfield',
      '#title' => t('Repository'),
      '#required' => TRUE,
      '#description' => t('Repository that should be used (format organization/repository)'),
      '#default_value' => !empty($this->defaultValues['api_repository']) ? $this->defaultValues['api_repository'] : '',
    ];

    $formOptions['github']['api_username'] = [
      '#type' => 'textfield',
      '#title' => t('Username'),
      '#required' => TRUE,
      '#description' => t('The username with access to the repository'),
      '#default_value' => !empty($this->defaultValues['api_username']) ? $this->defaultValues['api_username'] : '',
    ];

    $formOptions['github']['api_token'] = [
      '#type' => 'textfield',
      '#title' => t('Token'),
      '#required' => TRUE,
      '#description' => t('Token field to use'),
      '#default_value' => !empty($this->defaultValues['api_token']) ? $this->defaultValues['api_token'] : '',
    ];

    $formOptions['api_commiter'] = [
      '#type' => 'fieldset',
      '#title' => t('Committer information'),
    ];

    $formOptions['api_commiter']['api_commiter_name'] = [
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#required' => TRUE,
      '#default_value' => !empty($this->defaultValues['api_commiter_name']) ? $this->defaultValues['api_commiter_name'] : '',
    ];

    $formOptions['api_commiter']['api_commiter_email'] = [
      '#type' => 'textfield',
      '#title' => t('Email'),
      '#required' => TRUE,
      '#default_value' => !empty($this->defaultValues['api_commiter_email']) ? $this->defaultValues['api_commiter_email'] : '',
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

    $auth = 'Basic ' . base64_encode($form_state->getValue('api_username') . ':' . $form_state->getValue('api_token'));
    $repo = $form_state->getValue('api_endpoint') . 'repos/' . $form_state->getValue('api_repository');

    try {
      $query = $this->httpClient->get($repo, [
        'headers' => [
          'Accept' => 'application/json',
          'Content-Type' => 'application/json',
          'Authorization' => $auth,
        ],
      ]);

      $repoDetails = json_decode($query->getBody()->getContents());
      if (!$repoDetails->permissions->push) {
        $form_state->setErrorByName('api_repository', t('Repository correcly recognized, but the used user/token pair does not have permissions to push'));
      }

    } catch (\Exception $e) {
      $form_state->setErrorByName('api_repository', t('Impossible to query repository %repo, please verify your settings. Error %error', [
        '%repo' => $repo,
        '%error' => $e->getMessage()
      ]));
    }
  }

}
