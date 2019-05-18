<?php

namespace Drupal\config_pr\Form;

use Drupal\config_pr\RepoControllerManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigPrSettingsForm extends ConfigFormBase {

  /**
   * @var \Drupal\config_pr\RepoControllerManagerInterface
   */
  protected $repoController;

  /**
   * Constructs a ConfigPrSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\config_pr\RepoControllerManagerInterface $repo_controller
   *   The repo controller.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RepoControllerManagerInterface $repo_controller) {
    parent::__construct($config_factory);

    $this->repoController = $repo_controller;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config_pr.repo_controller_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_pr_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['config_pr.settings'];
  }

  /**
   * Configuration form.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array The form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['repo'] = [
      '#title' => $this->t('Repository'),
      '#type' => 'fieldset',
    ];
    $form['repo']['repo_controller'] = [
      '#type' => 'select',
      '#title' => $this->t('Repo provider'),
      '#description' => $this->t('Select controller.'),
      '#options' => $this->repoController->getControllers(),
      '#default_value' => $this->config('config_pr.settings')
          ->get('repo.controller') ?? 'config_pr.repo_controller.github',
      '#required' => TRUE,
    ];

    // Try to get the information from the local repo. This only works with Git.
    $repo_info = $this->repoController->getLocalRepoInfo();

    $form['repo']['repo_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enterprise repository URL'),
      '#description' => $this->t('Enter the Github Enterprise URL.'),
      '#default_value' => $this->config('config_pr.settings')
          ->get('repo.repo_url') ?? $repo_info['repo_url'],
      '#states' => [
        'visible' => [
          ':input[name="repo_controller"]' => [
            'value' => 'config_pr.repo_controller.github_enterprise',
          ],
        ],
        'required' => [
          ':input[name="repo_controller"]' => [
            'value' => 'config_pr.repo_controller.github_enterprise',
          ],
        ],
      ],
    ];

    $form['repo']['repo_user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Repo user name'),
      '#description' => $this->t('Enter the repo user name.'),
      //'#default_value' => $this->config('config_pr.settings')->get('repo.repo_user'),
      '#default_value' => $this->config('config_pr.settings')->get('repo.repo_user') ?? $repo_info['repo_user'],
      '#required' => TRUE,
    ];
    $form['repo']['repo_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Repo name'),
      '#description' => $this->t('Enter the repo name.'),
      '#default_value' => $this->config('config_pr.settings')->get('repo.repo_name') ?? $repo_info['repo_name'],
      '#required' => TRUE,
    ];
    $form['commit_messages'] = [
      '#title' => $this->t('Commit messages'),
      '#type' => 'fieldset',
      '#description' => $this->t('Available tokens: @config_name'),
    ];
    $form['commit_messages']['message_create'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Creating files'),
      '#description' => $this->t('Enter the commit message.'),
      '#default_value' => $this->config('config_pr.settings')
          ->get('commit_messages.create') ?? $this->t('Created config @config_name.yml'),
      '#required' => TRUE,
    ];
    $form['commit_messages']['message_delete'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Deleting files'),
      '#description' => $this->t('Enter the commit message.'),
      '#default_value' => $this->config('config_pr.settings')
          ->get('commit_messages.delete') ?? $this->t('Deleted config @config_name.yml'),
      '#required' => TRUE,
    ];
    $form['commit_messages']['message_update'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Updating files'),
      '#description' => $this->t('Enter the commit message.'),
      '#default_value' => $this->config('config_pr.settings')
          ->get('commit_messages.update') ?? $this->t('Updated config @config_name.yml'),
      '#required' => TRUE,
    ];
    $form['commit_messages']['message_rename'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Renaming files'),
      '#description' => $this->t('Enter the commit message.'),
      '#default_value' => $this->config('config_pr.settings')
          ->get('commit_messages.rename') ?? $this->t('Renamed config from @config_name.yml'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Form validator.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('config_pr.settings');
    $config->set('repo.controller', $form_state->getValue('repo_controller'));
    $config->set('repo.repo_url', $form_state->getValue('repo_url'));
    $config->set('repo.repo_user', $form_state->getValue('repo_user'));
    $config->set('repo.repo_name', $form_state->getValue('repo_name'));
    $config->set('commit_messages.update', $form_state->getValue('message_update'));
    $config->set('commit_messages.create', $form_state->getValue('message_create'));
    $config->set('commit_messages.delete', $form_state->getValue('message_delete'));
    $config->set('commit_messages.rename', $form_state->getValue('message_rename'));
    $config->save();

    parent::submitForm($form, $form_state);
  }
}
