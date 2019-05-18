<?php

namespace Drupal\search_api_saved_searches\Plugin\Block;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\search_api\Utility\QueryHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Displays the "Save search" form in a block.
 *
 * @Block(
 *   id = "search_api_saved_searches",
 *   admin_label = @Translation("Save search"),
 *   category = @Translation("Forms"),
 * )
 */
class SaveSearch extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|null
   */
  protected $entityTypeManager;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface|null
   */
  protected $formBuilder;

  /**
   * The query helper.
   *
   * @var \Drupal\search_api\Utility\QueryHelperInterface|null
   */
  protected $queryHelper;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|null
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $block = new static($configuration, $plugin_id, $plugin_definition);

    $block->setStringTranslation($container->get('string_translation'));
    $block->setEntityTypeManager($container->get('entity_type.manager'));
    $block->setFormBuilder($container->get('form_builder'));
    $block->setQueryHelper($container->get('search_api.query_helper'));
    $block->setRequestStack($container->get('request_stack'));

    return $block;
  }

  /**
   * Retrieves the entity type manager.
   *
   * @return \Drupal\Core\Entity\EntityTypeManagerInterface
   *   The entity type manager.
   */
  public function getEntityTypeManager() {
    return $this->entityTypeManager ?: \Drupal::entityTypeManager();
  }

  /**
   * Sets the entity type manager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The new entity type manager.
   *
   * @return $this
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    return $this;
  }

  /**
   * Retrieves the form builder.
   *
   * @return \Drupal\Core\Form\FormBuilderInterface
   *   The form builder.
   */
  public function getFormBuilder() {
    return $this->formBuilder ?: \Drupal::formBuilder();
  }

  /**
   * Sets the form builder.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The new form builder.
   *
   * @return $this
   */
  public function setFormBuilder(FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
    return $this;
  }

  /**
   * Retrieves the query helper.
   *
   * @return \Drupal\search_api\Utility\QueryHelperInterface
   *   The query helper.
   */
  public function getQueryHelper() {
    return $this->queryHelper ?: \Drupal::service('search_api.query_helper');
  }

  /**
   * Sets the query helper.
   *
   * @param \Drupal\search_api\Utility\QueryHelperInterface $query_helper
   *   The new query helper.
   *
   * @return $this
   */
  public function setQueryHelper(QueryHelperInterface $query_helper) {
    $this->queryHelper = $query_helper;
    return $this;
  }

  /**
   * Retrieves the request stack.
   *
   * @return \Symfony\Component\HttpFoundation\RequestStack
   *   The request stack.
   */
  public function getRequestStack() {
    return $this->requestStack ?: \Drupal::service('request_stack');
  }

  /**
   * Sets the request stack.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The new request stack.
   *
   * @return $this
   */
  public function setRequestStack(RequestStack $request_stack) {
    $this->requestStack = $request_stack;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'type' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $types = $this->getEntityTypeManager()
      ->getStorage('search_api_saved_search_type')
      ->loadMultiple();
    $type_options = [];
    foreach ($types as $type_id => $type) {
      $type_options[$type_id] = $type->label();
    }
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Saved search type'),
      '#description' => $this->t('The type/bundle of saved searches that should be created by this block.'),
      '#options' => $type_options,
      '#default_value' => $this->configuration['type'] ?: key($type_options),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['type'] = $form_state->getValue('type');
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    $access = parent::access($account, TRUE);

    $create_access = $this->getEntityTypeManager()
      ->getAccessControlHandler('search_api_saved_search')
      ->createAccess($this->configuration['type'], $account, [], TRUE);
    $access = $access->andIf($create_access);

    return $return_as_object ? $access : $access->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $cacheability = new CacheableMetadata();

    // @todo Move those checks to access()? Would mean access results can't be
    //   cached, though.
    $type = $this->getSavedSearchType();
    if (!$type) {
      $tags = $this->getEntityTypeManager()
        ->getDefinition('search_api_saved_search_type')
        ->getListCacheTags();
      $cacheability->addCacheTags($tags);
      $cacheability->applyTo($build);
      return $build;
    }
    $cacheability->addCacheableDependency($type);
    if (!$type->status()) {
      $cacheability->applyTo($build);
      return $build;
    }

    // Since there is no cache context for "search query on this page", we can't
    // cache this block (unless building it didn't get this far).
    $cacheability->setCacheMaxAge(0);
    $cacheability->applyTo($build);
    $query = $type->getActiveQuery($this->getQueryHelper());
    if (!$query) {
      return $build;
    }

    $description = $type->getOption('description');
    if ($description) {
      $build['description']['#markup'] = Xss::filterAdmin($description);
    }

    $values = [
      'type' => $type->id(),
      'index_id' => $query->getIndex()->id(),
      'query' => $query,
      // Remember the page on which the search was created.
      'path' => $this->getCurrentPath(),
    ];
    $saved_search = $this->getEntityTypeManager()
      ->getStorage('search_api_saved_search')
      ->create($values);

    $form_object = $this->getEntityTypeManager()
      ->getFormObject('search_api_saved_search', 'create');
    $form_object->setEntity($saved_search);
    $build['form'] = $this->getFormBuilder()->getForm($form_object);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();

    $type = $this->getSavedSearchType();
    if ($type) {
      $dependencies['config'][] = $type->getConfigDependencyName();
    }

    return $dependencies;
  }

  /**
   * Loads the saved search type used for this block.
   *
   * @return \Drupal\search_api_saved_searches\SavedSearchTypeInterface|null
   *   The saved search type, or NULL if it couldn't be loaded.
   */
  protected function getSavedSearchType() {
    if (!$this->configuration['type']) {
      return NULL;
    }
    /** @var \Drupal\search_api_saved_searches\SavedSearchTypeInterface $type */
    $type = $this->getEntityTypeManager()
      ->getStorage('search_api_saved_search_type')
      ->load($this->configuration['type']);
    return $type;
  }

  /**
   * Retrieves a sanitized version of the current path.
   *
   * @return string
   *   The current path, relative to the Drupal installation.
   */
  protected function getCurrentPath() {
    // Get the current path.
    $path = $this->getRequestStack()->getCurrentRequest()->getRequestUri();

    // Remove the Drupal base path, if any.
    $base_path = rtrim(base_path(), '/');
    $base_path_length = strlen($base_path);
    if ($base_path && substr($path, 0, $base_path_length) === $base_path) {
      $path = substr($path, $base_path_length);
    }

    // Remove AJAX parameters.
    $path = preg_replace('/([?&])(ajax_form|_wrapper_format)=[^&#]+/', '$1', $path);
    // Sanitize empty GET parameter arrays.
    $path = preg_replace('/\?(#|$)/', '$1', $path);

    return $path;
  }

}
