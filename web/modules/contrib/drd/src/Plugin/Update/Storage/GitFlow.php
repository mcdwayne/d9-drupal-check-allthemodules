<?php

namespace Drupal\drd\Plugin\Update\Storage;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a git flow update storage plugin.
 *
 * @Update(
 *  id = "gitflow",
 *  admin_label = @Translation("GitFlow"),
 * )
 */
class GitFlow extends Git {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'branch' => 'develop',
      'pattern' => 'Y-m-d-H-i',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $element = parent::buildConfigurationForm($form, $form_state);

    $element['pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Pattern for release branch name'),
      '#default_value' => $this->configuration['pattern'],
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
    $this->configuration['pattern'] = $this->getFormValue($form_state, 'pattern');
  }

  /**
   * {@inheritdoc}
   */
  public function prepareWorkingDirectory() {
    parent::prepareWorkingDirectory();

    // Get the release name.
    $name = \Drupal::service('date.formatter')->format(\Drupal::time()->getRequestTime(), 'custom', $this->configuration['pattern']);

    // Call GitFlow after update has been completed in the working directory.
    $this->repository->getCaller()->execute('flow init -d');
    $this->repository->getCaller()->execute('flow release start ' . $name);
    $this->logGitCommand('flow release start');

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function saveWorkingDirectory() {
    parent::saveWorkingDirectory();

    $this->repository->getCaller()->execute('flow release finish --push --message "Release for DRD Update"');
    $this->logGitCommand('flow release finish');

    return $this;
  }

}
