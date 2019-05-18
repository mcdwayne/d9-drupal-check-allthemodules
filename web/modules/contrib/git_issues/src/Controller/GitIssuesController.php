<?php

namespace Drupal\git_issues\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\git_issues\GitIssuesManager;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller that is used for Git Issues actions.
 *
 * @package Drupal/git_issues/Controller
 */
class GitIssuesController extends ControllerBase implements ContainerInjectionInterface {
  /**
   * Plugin instance.
   *
   * @var string
   */
  protected $plugin;

  /**
   * Config instance.
   *
   * @var string
   */
  protected $gitSettings;

  /**
   * Form builder instance.
   *
   * @var string
   */
  protected $formBuilder;

  /**
   * Constructs a new GitIssuesController object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Dependency injection for 'config.factory'.
   * @param \Drupal\git_issues\GitIssuesManager $manager
   *   Dependency injection for GitIssuesManager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   Dependency injection for FormBuilderInterface.
   */
  public function __construct(ConfigFactoryInterface $config_factory, GitIssuesManager $manager, FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
    $gitSettings = $config_factory->getEditable('git_issues.settings');
    $activePlugin = $gitSettings->get('plugins.active');
    $activePlugin = (empty($activePlugin) && is_null($activePlugin)) ? 'gitlab' : $activePlugin;

    $this->plugin = $manager->createInstance($activePlugin);
  }

  /**
   * Returns the list of issues for single project.
   *
   * @return array
   *   Returns array of issues list.
   */
  public function project() {
    $result = $this->plugin->getProjectIssues();

    if (!$result) {
      return $this->redirect('git_issues.admin_form');
    }
    else {
      return $result;
    }
  }

  /**
   * Returns the single issue view.
   *
   * @return string
   *   Returns html of single issue view.
   */
  public function issueView($issueId) {
    return $this->plugin->getIssue($issueId);
  }

  /**
   * Change issue state.
   */
  public function issueState($issueId, $state) {
    if (!is_null($state) && !empty($state)) {
      $this->plugin->issueChangeState($issueId, $state);
    }

    return $this->redirect('git_issues.issue.view', ['issueId' => $issueId]);
  }

  /**
   * Post issue comment.
   */
  public function issueComment($issueId) {
    $this->plugin->postIssueComment($issueId);

    return $this->redirect('git_issues.issue.view', ['issueId' => $issueId]);
  }

  /**
   * Returns edit issue form.
   */
  public function issueEdit($issueId) {
    $form = $this->formBuilder->getForm('Drupal\git_issues\Form\GitIssuesIssueForm', $issueId);

    return $form;
  }

  /**
   * Returns add issue form.
   */
  public function addIssue() {
    $form = $this->formBuilder->getForm('Drupal\git_issues\Form\GitIssuesIssueForm');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.git_issues'),
      $container->get('form_builder')
    );
  }

}
