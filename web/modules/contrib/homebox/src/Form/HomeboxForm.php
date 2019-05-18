<?php

namespace Drupal\homebox\Form;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutPluginManager;
use Drupal\Core\Plugin\CachedDiscoveryClearerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class HomeboxForm.
 */
class HomeboxForm extends EntityForm {

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $manager;

  /**
   * The layout plugin manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManager
   */
  protected $layoutPluginManager;

  /**
   * Manager block plugin.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * Manager block plugin.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * A plugin cache clear instance.
   *
   * @var \Drupal\Core\Plugin\CachedDiscoveryClearerInterface
   */
  protected $pluginCacheClearer;

  /**
   * A cache backend interface instance.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheRender;

  /**
   * HomeboxForm constructor.
   *
   * @param \Drupal\Core\Layout\LayoutPluginManager $layoutPluginManager
   *   Layout plugin manager.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   Manager block plugin.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   Context repository plugin.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheRender
   *   A cache backend interface instance.
   * @param \Drupal\Core\Plugin\CachedDiscoveryClearerInterface $plugin_cache_clearer
   *   A plugin cache clear instance.
   */
  public function __construct(LayoutPluginManager $layoutPluginManager, BlockManagerInterface $block_manager, ContextRepositoryInterface $context_repository, CacheBackendInterface $cacheRender, CachedDiscoveryClearerInterface $plugin_cache_clearer) {
    $this->layoutPluginManager = $layoutPluginManager;
    $this->blockManager = $block_manager;
    $this->contextRepository = $context_repository;
    $this->cacheRender = $cacheRender;
    $this->pluginCacheClearer = $plugin_cache_clearer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /* @var CachedDiscoveryClearerInterface $plugin_cache_clearer */
    $plugin_cache_clearer = $container->get('plugin.cache_clearer');
    /* @var CacheBackendInterface $cache_render */
    $cache_render = $container->get('cache.render');
    /* @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository */
    $context_repository = $container->get('context.repository');
    /* @var \Drupal\Core\Block\BlockManagerInterface $block_manager */
    $block_manager = $container->get('plugin.manager.block');
    /* @var \Drupal\Core\Layout\LayoutPluginManager $layout_plugin_manager */
    $layout_plugin_manager = $container->get('plugin.manager.core.layout');
    return new static(
      $layout_plugin_manager,
      $block_manager,
      $context_repository,
      $cache_render,
      $plugin_cache_clearer
    );
  }

  /**
   * Provide layout plugin instance.
   *
   * @param string $layout_id
   *   Layout id.
   * @param array $layout_settings
   *   Layout settings.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return mixed|object
   *   Layout plugin.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getLayout($layout_id, array $layout_settings, FormStateInterface $form_state) {
    if (!$layout_plugin = $form_state->get('layout_plugin')) {
      $layout_plugin = $this->layoutPluginManager->createInstance($layout_id, $layout_settings);
      $form_state->set('layout_plugin', $layout_plugin);
    }

    return $layout_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /* @var \Drupal\homebox\Entity\HomeboxInterface $homebox */
    $homebox = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $homebox->label(),
      '#description' => $this->t("Label for the Homebox."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $homebox->id(),
      '#machine_name' => [
        'exists' => '\Drupal\homebox\Entity\Homebox::load',
      ],
      '#disabled' => !$homebox->isNew(),
    ];

    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#maxlength' => 255,
      '#default_value' => $homebox->getPath(),
      '#description' => $this->t("Specify a URL by which this page can be accessed. For example, type \"dashboard\" when creating a Dashboard page. Use a relative path and don't add a trailing slash or the URL alias won't work."),
      '#required' => TRUE,
    ];

    $form['options'] = [
      '#type' => 'checkboxes',
      '#options' => [
        'menu' => t('Visible menu item'),
        'status' => t('Enable the page'),
      ],
    ];

    $roles = user_roles();
    $role_list = [];
    foreach ($roles as $id => $role) {
      if (!$role->isAdmin()) {
        $role_list[$role->id()] = $role->label();
      }
    }
    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allow only certain roles to access the page'),
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', $role_list),
      '#description' => $this->t('Select which roles can view the page.'),
    ];

    $form['columns'] = [
      '#type' => 'select',
      '#title' => t('Number of columns'),
      '#options' => $this->layoutPluginManager->getLayoutOptions(),
      '#description' => t('Set the number of columns you want to activate for this Homebox page.'),
      '#default_value' => $homebox->getRegions(),
    ];

    if (!$homebox->isNew()) {
      $form['options']['#default_value'] = $homebox->getOptions();
      $form['roles']['#default_value'] = $homebox->getRoles();
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\homebox\Entity\HomeboxInterface $homebox */
    $homebox = $this->entity;
    // @todo remove block settings on form. clear block settings in resave?
    $homebox->setBlocks([]);
    $status = $homebox->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Homebox.', [
          '%label' => $homebox->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Homebox.', [
          '%label' => $homebox->label(),
        ]));
    }
    // Clear cache to rebuild tasks links.
    $this->pluginCacheClearer->clearCachedDefinitions();
    $this->cacheRender->invalidateAll();
    $form_state->setRedirectUrl($homebox->toUrl('collection'));
  }

}
