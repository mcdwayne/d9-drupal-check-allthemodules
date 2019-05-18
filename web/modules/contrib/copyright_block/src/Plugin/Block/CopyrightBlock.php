<?php

namespace Drupal\copyright_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\Token;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds a copyright block.
 *
 * @Block(
 *   id = "copyright_block",
 *   admin_label = @Translation("Copyright block"),
 * )
 */
class CopyrightBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    Token $token
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('token')
    );
  }

  // TODO: Does this do anything?
  public function settings() {
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['start_year'] = [
      '#title' => $this->t('Start year'),
      '#type' => 'number',
      '#min' => '1900',
      '#max' => date('Y'),
      '#required' => TRUE,
      '#default_value' => $config['start_year'],
    ];

    $form['separator'] = [
      '#title' => $this->t('Separator'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $config['separator'],
    ];

    $form['text'] = [
      '#title' => $this->t('Copyright statement text'),
      '#type' => 'text_format',
      '#required' => TRUE,
      '#default_value' => $config['text']['value'],
      '#format' => $config['text']['format'],
    ];

    $form['token_tree'] = [
      '#theme' => 'token_tree_link',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('start_year', $form_state->getValue('start_year'));
    $this->setConfigurationValue('separator', $form_state->getValue('separator'));
    $this->setConfigurationValue('text', $form_state->getValue('text'));
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    return [
      '#type' => 'processed_text',
      '#text' => $this->token->replace($config['text']['value'], [], compact('config')),
      '#format' => $config['text']['format'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_config = \Drupal::config('copyright_block.settings');

    return [
      'start_year' => date('Y'),
      'separator' => $default_config->get('separator'),
      'text' => [
        'value' => $default_config->get('text.value'),
        'format' => $default_config->get('text.format'),
      ],
    ];
  }

}
