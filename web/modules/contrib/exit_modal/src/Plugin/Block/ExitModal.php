<?php

namespace Drupal\exit_modal\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Plugin\Context\LazyContextRepository;
use Drupal\Core\Block\BlockManager;

/**
 * Provides a 'ExitModal' block.
 *
 * @Block(
 *  id = "exit_modal",
 *  admin_label = @Translation("Exit Modal"),
 * )
 */
class ExitModal extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The context repository.
   *
   * @var \Drupal\Core\Plugin\Context\LazyContextRepository
   */
  protected $contextRepository;

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManager
   */
  protected $blockManager;

  /**
   * Constructs a new ExitModal object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Plugin\Context\LazyContextRepository $context_repository
   *   The context repository.
   * @param \Drupal\Core\Block\BlockManager $block_manager
   *   The block manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    AccountProxyInterface $current_user,
    LazyContextRepository $context_repository,
    BlockManager $block_manager
  ) {
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->contextRepository = $context_repository;
    $this->blockManager = $block_manager;
    parent::__construct($configuration, $plugin_id, $plugin_definition);

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('context.repository'),
      $container->get('plugin.manager.block')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Get blocks definition.
    $blocks = $this->blockManager->getDefinitionsForContexts($this->contextRepository->getAvailableContexts());

    // Set options for select form element.
    $options = ['' => t('Select a block')];
    foreach ($blocks as $block_id => $block) {
      if ($block_id == 'exit_modal') {
        continue;
      }
      $options[$block_id] = $block['admin_label'];
    }

    // Select form element.
    $form['block'] = [
      '#type' => 'select',
      '#title' => $this->t('Select a block to display in modal'),
      '#default_value' => (isset($this->configuration['block'])) ? $this->configuration['block'] : '',
      '#options' => $options,
      '#weight' => 10,
      '#description' => $this->t('Selected block will be displayed in modal.'),
    ];
    // In certain cases it may be useful to actually display the block content
    // on the page. E.g. if you have a newsletter signup, mobile users won't
    // see the exit popup as their behaviour won't trigger it, but we can
    // still show the newsletter signup on the page as a fallback.
    $form['exit_modal_display'] = [
      '#type' => 'checkbox',
      '#title' => t('Also show on page'),
      '#weight' => 11,
      '#description' => t("Tick this box to display this block on the page as well as in the modal. This is useful if you want to visitor to see the block contents even if the user doesn't trigger the modal popup."),
      '#default_value' => isset($this->configuration['exit_modal_display']) ? $this->configuration['exit_modal_display'] : 0,
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    // Set default values for selected block and show on page checkbox.
    $this->configuration['block'] = '';
    $this->configuration['exit_modal_display'] = FALSE;

    return parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    // If user didn't set block display error.
    if ($form_state->getValue('block') == '') {
      $form_state->setErrorByName('block', 'You have to select block');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    // Save block and checkbox values to our configuration.
    $this->configuration['block'] = $form_state->getValue('block');
    $this->configuration['exit_modal_display'] = $form_state->getValue('exit_modal_display');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get active configuration and data from it.
    $config = $this->configFactory->get('block.block.exitmodal');
    $data = $config->getRawData();
    // Get block name and display_on_page block option.
    $block_id = $data['settings']['block'];
    $display_on_page = $data['settings']['exit_modal_display'];
    // Create block instance.
    $block_manager = \Drupal::service('plugin.manager.block');
    $config = [];
    $block_instance = $block_manager->createInstance($block_id, $config);

    // Some blocks might implement access check.
    $access_result = $block_instance->access($this->currentUser);
    // Return empty render array if user doesn't have access.
    // $access_result can be boolean or an AccessResult class.
    if (is_object($access_result) && $access_result->isForbidden() || is_bool($access_result) && !$access_result) {
      // You might need to add some cache tags/contexts.
      return [];
    }

    // Build block instance.
    $block = $block_instance->build();
    // Attach our library and send block label to js, add class
    // and hide block if display_on_page is not checked.
    $block['#attached']['library'][] = 'exit_modal/exit_modal';
    $block['#attached']['drupalSettings']['exit_modal_block']['label'] = $this->configuration['label'];
    $block['#attributes']['class'][] = "block-modal-exit-modal";
    $block['#attributes']['class'][] = ($display_on_page == TRUE) ?: 'visually-hidden';
    return $block;
  }

}
