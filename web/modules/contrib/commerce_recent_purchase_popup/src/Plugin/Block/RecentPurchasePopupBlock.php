<?php

namespace Drupal\commerce_recent_purchase_popup\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\token\TokenEntityMapperInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Recent Purchase Popup' Block.
 *
 * @Block(
 *   id = "recent_purchase_popup_block",
 *   admin_label = @Translation("Recent Purchase Popup block"),
 *   category = @Translation("Commerce"),
 * )
 */
class RecentPurchasePopupBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The token entity mapper service.
   *
   * @var \Drupal\token\TokenEntityMapperInterface
   */
  protected $tokenEntityMapper;

  /**
   * Creates a Recent Purchase Popup block instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\token\TokenEntityMapperInterface $token_entity_mapper
   *   Token mapper.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    TokenEntityMapperInterface $token_entity_mapper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->tokenEntityMapper = $token_entity_mapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('token.entity_mapper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'delay' => 8000,
      'interval' => 10000,
      'time_to_show' => 5000,
      'user_info' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['delay'] = [
      '#type' => 'number',
      '#title' => $this->t('Delay before popup appears, ms'),
      '#default_value' => $this->configuration['delay'],
      '#min' => 0,
      '#step' => 100,
    ];
    $form['interval'] = [
      '#type' => 'number',
      '#title' => $this->t('Interval between popups appearing, ms'),
      '#default_value' => $this->configuration['interval'],
      '#min' => 0,
      '#step' => 100,
    ];
    $form['time_to_show'] = [
      '#type' => 'number',
      '#title' => $this->t('Time to show popup to user, ms'),
      '#default_value' => $this->configuration['time_to_show'],
      '#min' => 0,
      '#step' => 100,
    ];
    $form['user_info'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Short user information'),
      '#default_value' => $this->configuration['user_info'],
    ];
    $form['user_info_tokens'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => [$this->tokenEntityMapper->getTokenTypeForEntityType('profile')],
      '#show_restricted' => TRUE,
      '#global_types' => TRUE,
      '#show_nested' => FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    foreach (array_keys($this->defaultConfiguration()) as $key) {
      $this->configuration[$key] = $form_state->getValue($key);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block['content'] = [
      '#create_placeholder' => TRUE,
      '#lazy_builder' => [
        'commerce_recent_purchase_popup.lazy_renderer:renderPopup',
        [
          $this->configuration['user_info'],
          $this->configuration['delay'],
          $this->configuration['interval'],
          $this->configuration['time_to_show'],
        ],
      ],
    ];
    return $block;
  }

}
