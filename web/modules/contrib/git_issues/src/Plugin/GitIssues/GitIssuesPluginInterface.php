<?php

namespace Drupal\git_issues\Plugin\GitIssues;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the interface for git issues.
 */
interface GitIssuesPluginInterface extends PluginInspectionInterface {

  /**
   * Get project issues list.
   *
   * {@inheritdoc}
   *
   * @return array
   *   Returns Issues list.
   */
  public function getProjectIssues();

  /**
   * Get single issue.
   *
   * {@inheritdoc}
   *
   * @param int $issueId
   *   Issue id.
   *
   * @return array
   *   Returns view od single issue.
   */
  public function getIssue($issueId);

  /**
   * Retrieves the settings form of plugin.
   *
   * {@inheritdoc}
   *
   * @return array
   *   Returns form array.
   */
  public function getSettingsForm();

  /**
   * Submit the settings form of plugin.
   *
   * {@inheritdoc}
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormStateInterface object.
   */
  public function submitSettingsForm(array $form, FormStateInterface $form_state);

  /**
   * Retrieves the add/edit form of single issue.
   *
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormStateInterface object.
   * @param array $vars
   *   Form variables.
   *
   * @return array
   *   Returns form array.
   */
  public function getIssueForm(FormStateInterface $form_state, array $vars);

  /**
   * Retrieves the single issue add/edit form.
   *
   * {@inheritdoc}
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   FormStateInterface object.
   */
  public function submitIssueForm(FormStateInterface $form_state);

}
