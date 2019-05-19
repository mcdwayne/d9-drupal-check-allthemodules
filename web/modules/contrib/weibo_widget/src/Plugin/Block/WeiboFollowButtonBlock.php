<?php

namespace Drupal\weibo_widget\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides a 'Weibo Follow Button' block.
 *
 * @Block(
 *   id = "weibo_follow_button_block",
 *   admin_label = @Translation("Weibo Follow Button"),
 *   category = @Translation("Social")
 * )
 */
class WeiboFollowButtonBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected $config;

  /**
   * Class constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactory $config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Getting module configurations.
    $config = $this->config->get('weibo_widget.settings');
    $uid = $config->get('weibo_widget_uid');

    // Setting Weibo tag attributes.
    $type = $this->configuration['color'] . '_' . $this->configuration['type'];
    $attributes = new Attribute();
    $attributes['uid'] = $uid;
    $attributes['type'] = $type;

    // Simplified Chinese is the default.
    if ($this->configuration['language'] == 'traditional') {
      $attributes['language'] = 'zh_tw';
    }

    // Settting WBML tag.
    $wbml = $this->t('No user UID configured.');
    if ($uid) {
      $wbml = "<wb:follow-button {$attributes}></wb:follow-button>";
    }

    return [
      '#theme' => 'weibo_follow',
      '#wbml' => $wbml,
      '#attached' => [
        'library' => [
          'weibo_widget/weibo_api',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default = [
      'color' => 'red',
      'type' => 1,
      'language' => 'simple',
    ];
    return $default;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Widget Type'),
      '#description' => $this->t('Choose how this widget will be displayed.'),
      '#default_value' => $this->configuration['type'],
      '#options' => [
        1 => $this->t('Simple Button'),
        2 => $this->t('Button and number of fans.'),
        3 => $this->t('Button, number of fans and display name.'),
        4 => $this->t('Full with fans pictures.'),
      ],
    ];

    $form['color'] = [
      '#type' => 'radios',
      '#title' => $this->t('Widget color'),
      '#description' => $this->t('Choose the button color.'),
      '#default_value' => $this->configuration['color'],
      '#options' => [
        'red' => $this->t('Red'),
        'gray' => $this->t('Gray'),
      ],
    ];

    $form['language'] = [
      '#type' => 'radios',
      '#title' => $this->t('Widget language'),
      '#description' => $this->t('Choose between simplified or traditional chinese.'),
      '#default_value' => $this->configuration['language'],
      '#options' => [
        'simple' => $this->t('Simplified Chinese'),
        'traditional' => $this->t('Traditional Chinese'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['type'] = $form_state->getValue('type');
    $this->configuration['color'] = $form_state->getValue('color');
    $this->configuration['language'] = $form_state->getValue('language');
  }

}
