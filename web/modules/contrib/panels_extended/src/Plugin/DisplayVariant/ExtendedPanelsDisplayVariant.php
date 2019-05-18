<?php

namespace Drupal\panels_extended\Plugin\DisplayVariant;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Block\BlockManager;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Drupal\panels\Plugin\DisplayBuilder\DisplayBuilderManagerInterface;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;
use Drupal\panels_extended\Event\JsonDisplayVariantBuildEvent;
use Drupal\panels_extended\Form\AdminSettingsForm;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides an extended panels display variant.
 *
 * @DisplayVariant(
 *   id = "extended_panels_variant",
 *   admin_label = @Translation("Extended Panels")
 * )
 */
class ExtendedPanelsDisplayVariant extends PanelsDisplayVariant {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $dispatcher;

  /**
   * The name of the default display builder.
   *
   * @var string
   */
  protected $defaultBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ContextHandlerInterface $context_handler,
    AccountInterface $account,
    UuidInterface $uuid_generator,
    Token $token,
    BlockManager $block_manager,
    ConditionManager $condition_manager,
    ModuleHandlerInterface $module_handler,
    DisplayBuilderManagerInterface $builder_manager,
    LayoutPluginManagerInterface $layout_manager,
    EventDispatcherInterface $dispatcher,
    ConfigFactoryInterface $config_factory
  ) {
    $this->dispatcher = $dispatcher;

    // Set this before calling the parent constructor since this is related to the default config.
    $this->defaultBuilder = $config_factory->get(AdminSettingsForm::CFG_NAME)->get(AdminSettingsForm::CFG_DEFAULT_DISPLAY_BUILDER) ?: 'panels_extended';

    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $context_handler,
      $account,
      $uuid_generator,
      $token,
      $block_manager,
      $condition_manager,
      $module_handler,
      $builder_manager,
      $layout_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('context.handler'),
      $container->get('current_user'),
      $container->get('uuid'),
      $container->get('token'),
      $container->get('plugin.manager.block'),
      $container->get('plugin.manager.condition'),
      $container->get('module_handler'),
      $container->get('panels_extended.plugin.manager.display_builder'),
      $container->get('plugin.manager.core.layout'),
      $container->get('event_dispatcher'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['builder'] = $this->defaultBuilder;
    $config['pattern'] = 'extended_pattern';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (!_panels_extended_is_json_requested()) {
      return parent::build();
    }

    $build = $this->getBuilder()->build($this);
    $build['#title'] = $this->renderPageTitle($this->configuration['page_title']);

    $event = new JsonDisplayVariantBuildEvent($build, $this);
    $this->dispatcher->dispatch(JsonDisplayVariantBuildEvent::ALTER_BUILD, $event);

    return $build;
  }

}
