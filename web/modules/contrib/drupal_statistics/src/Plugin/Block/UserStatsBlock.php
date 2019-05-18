<?php

namespace Drupal\drupal_statistics\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\drupal_statistics\DrupalStatisticsHelper;
use Drupal\node\Entity\Node;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a block for user statistics.
 *
 * @Block(
 *   id = "user_stats_block",
 *   admin_label = @Translation("User Statistics block")
 * )
 */
class UserStatsBlock extends BlockBase {

  private $instance;

  /**
   * Constructor for UserStatsBlock Class.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->instance = DrupalStatisticsHelper::instance();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $stats_data = [
      'history' => $this->t('Read history'),
      'nodes_visited' => $this->t('Number of nodes visited by the user'),
      'join_date' => $this->t('Join date'),
      'days_registered' => $this->t('Days registered'),
      'last_login_time' => $this->t('Last login time'),
      'days_last_login' => $this->t('Days since last login'),
    ];
    $form['user_stats_data'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select User Stats Data'),
      '#description' => $this->t('Check to view statistics in this block.'),
      '#options' => $stats_data,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['user_stats'] = $form_state->getValue('user_stats_data');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $render = '';
    if ($this->configuration['user_stats']) {
      if ($this->configuration['user_stats']['history']) {
        $render = $this->getUserHistory();
      }
      if ($this->configuration['user_stats']['nodes_visited']) {
        $render = $render . $this->getUserVisits();
      }
      if ($this->configuration['user_stats']['join_date']) {
        $render = $render . $this->getUserJoinDate();
      }
      if ($this->configuration['user_stats']['days_registered']) {
        $render = $render . $this->getUserDaysRegistered();
      }
      if ($this->configuration['user_stats']['last_login_time']) {
        $render = $render . $this->getUserLastLoginTime();
      }
      if ($this->configuration['user_stats']['days_last_login']) {
        $render = $render . $this->getUserLastLogin();
      }
    }
    else {
      $render = 'No Data Selected';
    }
    return [
      '#type' => 'markup',
      '#markup' => $render,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }

  /**
   * Function to get data for user history.
   */
  public function getUserHistory() {
    $data = $this->instance->getStatistics();
    $render = "<table><tr><td><b>node</b></td><td><b>read / not-read</b></td></tr>";
    foreach ($data as $nid => $value) {
      $node_title = Node::load($nid)->getTitle();
      $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $nid);
      $render = $render . "<tr><td><a href='" . $alias . "'>" . $node_title . "</a></td><td>" . $value . "</td></tr>";
    }
    $render = $render . "</table>";
    return $render;
  }

  /**
   * Function to get data for user history.
   */
  public function getUserVisits() {
    $data = $this->instance->getStatisticsCount();
    $render = "<p>Nodes Visited: " . $data . "</p>";
    return $render;
  }

  /**
   * Function to get data for user join date.
   */
  public function getUserJoinDate() {
    $data = $this->instance->getJoinDate();
    $render = "<p>Join Date: " . date('d/m/Y', $data) . "</p>";
    return $render;
  }

  /**
   * Function to get number of days registered for the current user.
   */
  public function getUserDaysRegistered() {
    $data = $this->instance->getJoinDate();
    $render = "<p>Days Registered: " . intval(abs(time() - $data) / 86400) . " Days </p>";
    return $render;
  }

  /**
   * Function to get last Login date of current user.
   */
  public function getUserLastLoginTime() {
    $data = $this->instance->getUserLastLoginTime();
    $render = "<p>Last Login: " . date('d/m/Y H:i:s', $data) . "</p>";
    return $render;
  }

  /**
   * Function to get days since last Login of current user.
   */
  public function getUserLastLogin() {
    $data = $this->instance->getUserLastLoginTime();
    $render = "<p>Days Since Last Login: " . intval(abs(time() - $data) / 86400) . " Days </p>";
    return $render;
  }

}
