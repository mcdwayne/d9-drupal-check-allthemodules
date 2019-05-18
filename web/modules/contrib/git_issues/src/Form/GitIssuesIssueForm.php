<?php

namespace Drupal\git_issues\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\git_issues\GitIssuesManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a creation form for the issue instance create form.
 *
 * @package Drupal/git_issues/Service
 */
class GitIssuesIssueForm extends FormBase implements ContainerInjectionInterface {
  /**
   * Plugin instance.
   *
   * @var string
   */
  private $instance;

  /**
   * Plugin manager instance.
   *
   * @var string
   */
  private $pluginManager;

  /**
   * Provides a form id.
   */
  public function getFormId() {
    // Unique ID of the form.
    return 'issueAdd';
  }

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
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $issueId = NULL) {
    if (empty($this->instance) || is_null($this->instance)) {
      $this->instance = $this->pluginManager->createInstance($this->activePlugin);
    }

    if (!is_null($issueId)) {
      $vars = [
        'action' => 'edit',
        'issueId' => $issueId,
      ];
    }
    else {
      $vars = [
        'action' => 'add',
      ];
    }

    $form = [];
    $form += $this->instance->getIssueForm($form_state, $vars);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate submitted form data.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    if (empty($this->instance) || is_null($this->instance)) {
      $this->instance = $this->pluginManager->createInstance($this->activePlugin);
    }

    $this->instance->submitIssueForm($form_state);

    if ($form_state->get('action') == 'add') {
      $url = Url::fromRoute('git_issues.issues');
      $form_state->setRedirectUrl($url);
    }

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

}
