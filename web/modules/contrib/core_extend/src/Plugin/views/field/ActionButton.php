<?php

namespace Drupal\core_extend\Plugin\views\field;

use Drupal\Core\Action\ActionManager;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\views\Entity\Render\EntityTranslationRenderTrait;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\field\UncacheableFieldHandlerTrait;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Run an Action plugin from a button.
 *
 * @ViewsField("action_plugin_button")
 */
class ActionButton extends FieldPluginBase implements CacheableDependencyInterface {

  use RedirectDestinationTrait;
  use UncacheableFieldHandlerTrait;
  use EntityTranslationRenderTrait;

  /**
   * The action plugin manager.
   *
   * @var \Drupal\Core\Action\ActionManager
   */
  protected $actionManager;

  /**
   * The loaded Action plugin instance.
   *
   * @var \Drupal\Core\Action\ActionInterface
   */
  protected $actionPlugin;

  /**
   * The Action plugin configuration.
   *
   * @var array
   */
  protected $actionPluginConfiguration;

  /**
   * The Action plugin definition array.
   *
   * @var array
   */
  protected $actionPluginDefinition;

  /**
   * The Action plugin ID.
   *
   * @var string
   */
  protected $actionPluginId;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The current entity-type definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new ActionButton object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Action\ActionManager $action_manager
   *   The action plugin manager.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ActionManager $action_manager, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->actionManager = $action_manager;
    $this->entityManager = $entity_manager;
    $this->languageManager = $language_manager;

    $this->actionPluginConfiguration = [];
    if (array_key_exists('action_plugin_configuration', $configuration) && is_array($configuration['action_plugin_configuration'])) {
      $this->actionPluginConfiguration = $configuration['action_plugin_configuration'];
    }
    if (array_key_exists('action_plugin_id', $configuration) && is_string($configuration['action_plugin_id'])) {
      $this->actionPluginId = $configuration['action_plugin_id'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.action'),
      $container->get('entity.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * Get the Action plugin definition.
   *
   * @return array
   *   The Action plugin definition array.
   */
  protected function getActionDefinition() {
    return $this->actionManager->getDefinition($this->actionPluginId);
  }

  /**
   * Gets the loaded Action plugin.
   *
   * @return \Drupal\Core\Action\ActionInterface
   *   The Action plugin instance.
   */
  protected function getActionPlugin() {
    if (is_null($this->actionPlugin)) {
      $this->actionPlugin = $this->actionManager->createInstance($this->actionPluginId, $this->actionPluginConfiguration);
    }
    return $this->actionPlugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $cache_contexts = $this->getEntityTypeDefinition()->getListCacheContexts();
    if ($this->languageManager->isMultilingual()) {
      $cache_contexts = Cache::mergeContexts($cache_contexts, $this->getEntityTranslationRenderer()->getCacheContexts());
    }
    return $cache_contexts;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $cache_tags = $this->getEntityTypeDefinition()->getListCacheTags();
    if ($this->languageManager->isMultilingual()) {
      $cache_tags = Cache::mergeTags($cache_tags, $this->getEntityTranslationRenderer()->getCacheTags());
    }
    return $cache_tags;
  }

  /**
   * Returns the current entity-type definition.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface|null
   *   The entity-type definition.
   */
  public function getEntityTypeDefinition() {
    if (is_null($this->entityType)) {
      $this->entityType = $this->entityManager->getDefinition($this->getEntityType());
    }
    return $this->entityType;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->getEntityType();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityManager() {
    return $this->entityManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getLanguageManager() {
    return $this->languageManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function getView() {
    return $this->view;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['button_title'] = [
      'default' => $this->getActionDefinition()['label'],
    ];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['button_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button title'),
      '#default_value' => $this->options['button_title'],
      '#description' => $this->t('The title shown on the button dropdown.'),
    ];
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $row, $field = NULL) {
    return '<!--form-item-' . $this->options['id'] . '--' . $row->index . '-->';
  }

  /**
   * Form constructor for the bulk form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function viewsForm(array &$form, FormStateInterface $form_state) {
    // Make sure we do not accidentally cache this form.
    $form['#cache']['max-age'] = 0;
    // Add the tableselect javascript.
    $form['#attached']['library'][] = 'core/drupal.tableselect';
    $use_revision = array_key_exists('revision', $this->view->getQuery()->getEntityTableInfo());
    $hide_actions = TRUE;
    // Only add the buttons if there are results.
    if (!empty($this->view->result)) {
      // Render buttons for all rows.
      $form[$this->options['id']]['#tree'] = TRUE;
      $form[$this->options['id']]['#action_button_column'] = TRUE;
      foreach ($this->view->result as $row_index => $row) {
        $entity = $this->getEntityTranslation($this->getEntity($row), $row);
        $id = $this->calculateEntityBulkFormKey($entity, $use_revision);
        $form[$this->options['id']][$row_index] = [
          '#type' => 'submit',
          '#value' => $this->options['button_title'],
          '#name' => $id,
          '#submit' => [[$this, 'action']],
          '#limit_validation_errors' => [],
          '#row_index' => $row_index,
          '#action_id' => $this->actionPluginId,
        ];
      }
      // Remove form actions if no other field has a views form.
      foreach ($this->getView()->field as $field_id => $field) {
        if ($field->getPluginId() !== $this->getPluginId() && method_exists($field, 'viewsForm')) {
          $hide_actions = FALSE;
          break;
        }
      }
    }
    if ($hide_actions) {
      // Remove the default actions build array.
      $form['actions']['#access'] = FALSE;
    }
  }

  /**
   * Form callback to run the action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function action(array &$form, FormStateInterface $form_state) {
    // Clear errors from other fields that were validated without using a
    // getTriggeringElement() check on the submitted form element.
    $form_state->clearErrors();
    $entity = $this->loadEntityFromBulkFormKey($form_state->getTriggeringElement()['#name']);
    if ($this->validateAction($entity, $form, $form_state)) {
      $this->doAction($entity, $form, $form_state);
    }
  }

  /**
   * Validate the action.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The current entity.
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   Whether the action is valid.
   */
  protected function validateAction(EntityInterface $entity, array &$form, FormStateInterface $form_state) {
    // Check if user has access to preform action.
    if (!$this->getActionPlugin()->access($entity, $this->view->getUser())) {
      $this->drupalSetMessage($this->t('Invalid access to perform this action.'), 'error');
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Execute the action.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The current entity.
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function doAction(EntityInterface $entity, array &$form, FormStateInterface $form_state) {
    // Execute plugin for this entity.
    $this->getActionPlugin()->execute($entity);
    // Redirect if redirect needed.
    if (!empty($this->getActionPlugin()->getPluginDefinition()['confirm_form_route_name'])) {
      $options = ['query' => $this->getDestinationArray()];
      $parameters = \Drupal::routeMatch()->getRawParameters()->all();
      $form_state->setRedirect($this->getActionPlugin()->getPluginDefinition()['confirm_form_route_name'], $parameters, $options);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function viewsFormSubmit(array &$form, FormStateInterface $form_state) {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function viewsFormValidate(array &$form, FormStateInterface $form_state) {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if ($this->languageManager->isMultilingual()) {
      $this->getEntityTranslationRenderer()->query($this->query, $this->relationship);
    }
  }

  /**
   * Wraps drupal_set_message().
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $message
   *   (optional) The translated message to be displayed to the user. For
   *   consistency with other messages, it should begin with a capital letter
   *   and end with a period.
   * @param string $type
   *   (optional) The message's type. Defaults to 'status'. These values are
   *   supported:
   *   - 'status'
   *   - 'warning'
   *   - 'error'
   * @param bool $repeat
   *   (optional) If this is FALSE and the message is already set, then the
   *   message won't be repeated. Defaults to FALSE.
   */
  protected function drupalSetMessage($message = NULL, $type = 'status', $repeat = FALSE) {
    drupal_set_message($message, $type, $repeat);
  }

  /**
   * Calculates a bulk form key.
   *
   * This generates a key that is used as the checkbox return value when
   * submitting a bulk form. This key allows the entity for the row to be loaded
   * totally independently of the executed view row.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to calculate a bulk form key for.
   * @param bool $use_revision
   *   Whether the revision id should be added to the bulk form key. This should
   *   be set to TRUE only if the view is listing entity revisions.
   *
   * @return string
   *   The bulk form key representing the entity's id, language and revision (if
   *   applicable) as one string.
   *
   * @see self::loadEntityFromBulkFormKey()
   */
  protected function calculateEntityBulkFormKey(EntityInterface $entity, $use_revision) {
    $key_parts = [$this->field, $entity->language()->getId(), $entity->id()];

    if ($entity instanceof RevisionableInterface && $use_revision) {
      $key_parts[] = $entity->getRevisionId();
    }

    // An entity ID could be an arbitrary string (although they are typically
    // numeric). JSON then Base64 encoding ensures the bulk_form_key is
    // safe to use in HTML, and that the key parts can be retrieved.
    $key = json_encode($key_parts);
    return base64_encode($key);
  }

  /**
   * Loads an entity based on a bulk form key.
   *
   * @param string $bulk_form_key
   *   The bulk form key representing the entity's id, language and revision (if
   *   applicable) as one string.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity loaded in the state (language, optionally revision) specified
   *   as part of the bulk form key.
   */
  protected function loadEntityFromBulkFormKey($bulk_form_key) {
    $key = base64_decode($bulk_form_key);
    $key_parts = json_decode($key);
    $revision_id = NULL;

    // If there are 4 parts, vid will be last.
    if (count($key_parts) === 4) {
      $revision_id = array_pop($key_parts);
    }

    // The first two parts will always be entity-type ID, langcode and ID.
    $id = array_pop($key_parts);
    $langcode = array_pop($key_parts);

    // Load the entity or a specific revision depending on the given key.
    $storage = $this->entityManager->getStorage($this->getEntityType());
    $entity = $revision_id ? $storage->loadRevision($revision_id) : $storage->load($id);

    if ($entity instanceof TranslatableInterface) {
      $entity = $entity->getTranslation($langcode);
    }

    return $entity;
  }

}
