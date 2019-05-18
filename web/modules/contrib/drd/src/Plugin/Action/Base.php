<?php

namespace Drupal\drd\Plugin\Action;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\taxonomy\Entity\Term;

/**
 * Base class for DRD Action plugins.
 */
abstract class Base extends ActionBase implements BaseInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Action arguments.
   *
   * @var array
   */
  protected $arguments = [];

  /**
   * Action output.
   *
   * @var string|string[]
   */
  private $output;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setDefaultArguments();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $terms = [];
    foreach ($this->configuration['terms'] as $id) {
      $terms[] = Term::load($id);
    }
    $form['terms'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'taxonomy_term',
      '#title' => 'Terms',
      '#default_value' => $terms,
      '#tags' => TRUE,
      '#autocreate' => [
        'bundle' => 'tags',
        'uid' => \Drupal::currentUser()->id(),
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Nothing to do so far.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['terms'] = [];
    foreach ($form_state->getValue('terms') as $item) {
      $item = reset($item);
      if ($item instanceof EntityInterface) {
        if ($item->isNew()) {
          $item->save();
        }
        $this->configuration['terms'][] = $item->id();
      }
      else {
        $this->configuration['terms'][] = $item;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep(
      $this->defaultConfiguration(),
      $configuration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'terms' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function execute() {
    // This is deliberatly empty as we have implemented executeAction with
    // varying signatures.
    // TODO: before we can use DRD actions with rules, we need to change that.
  }

  /**
   * Allows an action to set default arguments.
   */
  protected function setDefaultArguments() {}

  /**
   * {@inheritdoc}
   */
  public function restrictAccess() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function canBeQueued() {
    return TRUE;
  }

  /**
   * Get a list of optional follow-up actions.
   *
   * @return array|string|bool
   *   Return the action key, or a list of action keys, that should follow this
   *   action or FALSE, if no follow-up action required.
   */
  protected function getFollowUpAction() {
    return FALSE;
  }

  /**
   * Create an action instance for the given action ID.
   *
   * @param string $id
   *   The action id.
   *
   * @return BaseInterface|bool
   *   Returns the action instance or FALSE if no action with the given id was
   *   found or if it had the wrong type.
   */
  final public static function instance($id) {
    $type = \Drupal::service('plugin.manager.action');
    /** @var BaseEntityRemote $action */
    $action = $type->createInstance($id);
    if (empty($action) || !($action instanceof BaseInterface)) {
      return FALSE;
    }
    return $action;
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if (PHP_SAPI == 'cli' || @ignore_user_abort()) {
      // We are either running via CLI (Drush or Console) or it's a cron run.
      return $return_as_object ? AccessResult::allowed() : TRUE;
    }
    if (!$account) {
      $account = \Drupal::currentUser();
    }
    if ($account->hasPermission($this->getPluginId())) {
      return $return_as_object ? AccessResult::allowed() : TRUE;
    }
    return $return_as_object ? AccessResult::forbidden() : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  final public function setActionArgument($key, $value) {
    $this->arguments[$key] = $value;
  }

  /**
   * {@inheritdoc}
   */
  final public function setArguments(array $arguments) {
    foreach ($arguments as $key => $value) {
      $this->arguments[$key] = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
  final public function getArguments() {
    return $this->arguments;
  }

  /**
   * Reset action such that another request can be executed.
   */
  protected function reset() {
    $this->output = NULL;
  }

  /**
   * Add another part to the action output.
   *
   * @param string $output
   *   The new part for the output.
   */
  final protected function setOutput($output) {
    $this->output[] = $output;
  }

  /**
   * {@inheritdoc}
   */
  final public function getOutput() {
    return empty($this->output) ? FALSE : $this->output;
  }

  /**
   * Logging fort actions, forwarding to the DRD logging service.
   *
   * @param string $severity
   *   The message severity.
   * @param string $message
   *   The log message.
   * @param array $args
   *   The log message arguments.
   */
  protected function log($severity, $message, array $args = []) {
    $args['@plugin_id'] = $this->pluginId;
    \Drupal::service('drd.logging')->log($severity, $message, $args);
  }

  /**
   * {@inheritdoc}
   */
  public function hasTerm(Term $term) {
    return in_array($term->id(), $this->configuration['terms']);
  }

}
