<?php

namespace Drupal\media_private_access\Form;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\media_private_access\MediaPrivateAccessControlHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to configure Media Private Access settings.
 */
class MediaPrivateAccessSettingsForm extends ConfigFormBase {

  /**
   * The Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The router builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routerBuilder;

  /**
   * The Cache Render.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheRender;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, RouteBuilderInterface $router_builder, CacheBackendInterface $cache_render, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->routerBuilder = $router_builder;
    $this->cacheRender = $cache_render;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('router.builder'),
      $container->get('cache.render'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'media_private_access_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['media_private_access.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $help_text = $this->t('<h3>Choose the access restriction to be used on each Media Type</h3>');
    $help_text .= $this->t("<p><strong>No specific restriction:</strong> Drupal core default behavior, only check access to the media entity itself.</p>");
    $help_text .= $this->t("<p><strong>Permission-based:</strong> Access will be granted to all users containing either one of the permissions: <em>'administer media'</em> or <em>'view [type name] media'</em>, regardless of the context. Owners (authors of the media entities) always have access to their entities.</p>");
    $help_text .= $this->t("<p><strong>Inherited from top level route:</strong> When being accessed from another entity's canonical route (for example a node page), access will be granted to users that have access to the route's entity. Access to the media item in other contexts (media page, views, etc) will be only granted to users having the permission <em>'administer media'</em>, or the owner.</p>");
    $help_text .= $this->t("<p><strong>Inherited from immediate parent:</strong> Replicates Drupal's default file access handling, where access is inherited from the entity that hosts the asset. Once media items can be used in multiple places, access is granted (regardless of the context) if the user has access to at least one 'parent' that references the media item on its default revision. If no parents exist or the user hasn't access to any of them, access will be granted only to users with the <em>'administer media'</em> permission, or the owner. Note that this mode is only available if the contributed module <a href='@entity_usage_url'>Entity Usage</a> is enabled.</p>", [
      '@entity_usage_url' => 'https://drupal.org/project/entity_usage',
    ]);
    $form['access_mode'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Media access mode'),
      '#description' => $help_text,
      '#tree' => TRUE,
    ];

    $modes = media_private_access_get_modes();
    $options = [
      MediaPrivateAccessControlHandler::MEDIA_PRIVATE_ACCESS_DEFAULT => $this->t('No specific restriction'),
      MediaPrivateAccessControlHandler::MEDIA_PRIVATE_ACCESS_PERMISSION => $this->t('Permission-based'),
      MediaPrivateAccessControlHandler::MEDIA_PRIVATE_ACCESS_INHERITED_FROM_ROUTE => $this->t('Inherited from top level route'),
    ];
    if ($this->moduleHandler->moduleExists('entity_usage')) {
      $options[MediaPrivateAccessControlHandler::MEDIA_PRIVATE_ACCESS_INHERITED_FROM_PARENT] = $this->t('Inherited from immediate parent');
    }
    /** @var \Drupal\media\MediaTypeInterface[] $media_types */
    $media_types = $this->entityTypeManager->getStorage('media_type')->loadMultiple();
    foreach ($media_types as $type) {
      $form['access_mode'][$type->id()] = [
        '#type' => 'select',
        '#title' => $type->label(),
        '#options' => $options,
        '#default_value' => isset($modes[$type->id()]) ? $modes[$type->id()] : MediaPrivateAccessControlHandler::MEDIA_PRIVATE_ACCESS_DEFAULT,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $config = $this->config('media_private_access.settings');

    $modes = $form_state->getValue(['access_mode']);
    if (!empty($modes)) {
      $selected_modes = [];
      foreach ($modes as $type => $mode) {
        $selected_modes[] = $type . ":" . $mode;
      }
    }

    $config->set('media_private_access_modes', $selected_modes)
      ->save();

    // @todo Should we invalidate something else here?
    $this->cacheRender->invalidateAll();

    parent::submitForm($form, $form_state);
  }

}
