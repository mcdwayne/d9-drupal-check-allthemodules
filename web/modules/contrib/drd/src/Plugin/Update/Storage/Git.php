<?php

namespace Drupal\drd\Plugin\Update\Storage;

use Drupal\Core\Form\FormStateInterface;
use GitElephant\Repository;

/**
 * Provides a git based update storage plugin.
 *
 * @Update(
 *  id = "git",
 *  admin_label = @Translation("Git"),
 * )
 */
class Git extends Base {

  /**
   * The git repository object.
   *
   * @var \GitElephant\Repository
   */
  protected $repository;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'repository' => '',
      'branch' => '',
      'gituser' => 'DRD AutoUpdate',
      'gitmail' => \Drupal::config('system.site')->get('mail'),
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $element = parent::buildConfigurationForm($form, $form_state);

    $element['repository'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Git repository'),
      '#default_value' => $this->configuration['repository'],
      '#states' => [
        'required' => $this->condition,
      ],
    ];
    $element['branch'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Branch'),
      '#default_value' => $this->configuration['branch'],
      '#states' => [
        'required' => $this->condition,
      ],
    ];
    $element['gituser'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Git user name'),
      '#default_value' => $this->configuration['gituser'],
      '#states' => [
        'required' => $this->condition,
      ],
    ];
    $element['gitmail'] = [
      '#type' => 'email',
      '#title' => $this->t('Git mail address'),
      '#default_value' => $this->configuration['gitmail'],
      '#states' => [
        'required' => $this->condition,
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['repository'] = $this->getFormValue($form_state, 'repository');
    $this->configuration['branch'] = $this->getFormValue($form_state, 'branch');
    $this->configuration['gituser'] = $this->getFormValue($form_state, 'gituser');
    $this->configuration['gitmail'] = $this->getFormValue($form_state, 'gitmail');
  }

  /**
   * Callback to log the git command which gets executed.
   *
   * @param string $cmd
   *   The git command that should be logged.
   *
   * @return $this
   */
  protected function logGitCommand($cmd) {
    return $this->log('[GIT ' . $cmd . ']: ' . $this->repository->getCaller()->getOutput());
  }

  /**
   * {@inheritdoc}
   */
  public function prepareWorkingDirectory() {
    parent::prepareWorkingDirectory();

    // Git Clone the repository into the working directory.
    $this->repository = Repository::open($this->workingDirectory);
    $this->repository->cloneFrom($this->configuration['repository'], '.');
    $this->logGitCommand('clone');
    $this->repository->checkout($this->configuration['branch']);
    $this->logGitCommand('checkout');

    $this->repository->getCaller()->execute('config user.name "' . $this->configuration['gituser'] . '"');
    $this->repository->getCaller()->execute('config user.email "' . $this->configuration['gitmail'] . '"');

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function saveWorkingDirectory() {
    parent::saveWorkingDirectory();

    // Commit changes and push upstream.
    if ($this->repository->getStatus()->all()->count()) {
      $this->repository->commit('DRD Update', TRUE);
      $this->logGitCommand('commit');
      $this->repository->push();
      $this->logGitCommand('push');
    }

    return $this;
  }

}
