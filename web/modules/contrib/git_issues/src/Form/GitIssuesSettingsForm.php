<?php

namespace Drupal\git_issues\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\git_issues\GitIssuesManager;

/**
 * Provides a settings form.
 */
class GitIssuesSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * Plugin instance.
   *
   * @var string
   */
  private $instance;

  /**
   * Active plugin name.
   *
   * @var string
   */
  protected $activePlugin;

  /**
   * Config instance.
   *
   * @var string
   */
  protected $gitSettings;

  /**
   * Plugin manager instance.
   *
   * @var string
   */
  protected $pluginManager;

  /**
   * Constructs a GitIssuesIssueForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Dependency injection for 'config.factory'.
   * @param \Drupal\git_issues\GitIssuesManager $manager
   *   Dependency injection for GitIssuesManager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, GitIssuesManager $manager) {
    $this->pluginManager = $manager;
    $this->gitSettings = $config_factory->getEditable('git_issues.settings');
    $this->activePlugin = $this->gitSettings->get('plugins.active');
    $this->activePlugin = (empty($this->activePlugin) && is_null($this->activePlugin)) ? 'gitlab' : $this->activePlugin;
    $this->instance = $this->pluginManager->createInstance($this->activePlugin);

    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.git_issues')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'git_issues_admin_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $pluginDefinitions = $this->pluginManager->getDefinitions();

    $form = [];
    $options = [];

    foreach ($pluginDefinitions as $plugin) {
      $options[$plugin['id']] = $plugin['gitLabel'];
    }

    // Fixed part of form that enables to chose plugin.
    $form['plugins'] = [
      '#type' => 'radios',
      '#title' => $this->t('Available plugins'),
      '#options' => $options,
      '#attributes' => ['name' => 'plugins'],
      '#default_value' => $this->activePlugin,
    ];

    $form += $this->instance->getSettingsForm();

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values['plugins'] = [
      'plugins' => $form_state->getValue('plugins'),
    ];

    $this->gitSettings->set('plugins.active', $values['plugins']['plugins'])->save();

    $this->instance->submitSettingsForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'git_issues.settings',
    ];
  }

}
