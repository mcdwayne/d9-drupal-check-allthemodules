<?php

namespace Drupal\ajax_login\Plugin\Block;

use Drupal\Component\Serialization\Json;

use Drupal\Core\Block\BlockBase;

use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\Url;

use Drupal\Core\Config\ConfigFactoryInterface;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\Core\Session\AccountProxy;

/**
 * Provides a 'LoginRegisterBlock' block.
 *
 * @Block(
 *  id = "login_register_block",
 *  admin_label = @Translation("Login and register block"),
 * )
 */
class LoginRegisterBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Info about current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Config of the module.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configModule;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, AccountProxy $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->configModule = $config_factory;
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
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config       = $this->configModule->get('ajax_login.settings');
    $config_block = $this->getConfiguration();
    $output       = [];
    foreach ($config_block['ajax_login_links'] as $type) {
      if ($type == "0") {
        continue;
      }
      $output['links'][$type] = [
        '#type'     => 'link',
        '#title'    => $this->getLinks()[$type],
        '#url'      => Url::fromRoute('user.' . $type),
        '#options'  => [
          'attributes' => [
            'class'               => ['use-ajax', 'ajax-' . $type],
            'data-dialog-type'    => 'modal',
            'data-dialog-options' => Json::encode([
              'width' => $config->get('ajax_modal_width'),
            ]),
          ],
        ],
        '#attached' => ['library' => ['core/drupal.dialog.ajax']],
      ];
    }
    $output['output_type'] = $config_block['ajax_login_output'];
    // Block will not be shown if user is not anonymous or choose no links.
    if (!$this->currentUser->isAnonymous() || empty($output['links'])) {
      return;
    }
    $block = [
      '#theme'       => 'ajax_login',
      '#links'       => $output['links'],
      '#output_type' => $output['output_type'],
    ];
    return $block;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form                      = parent::blockForm($form, $form_state);
    $config                    = $this->getConfiguration();
    $form['ajax_login_links']  = [
      '#type'          => 'checkboxes',
      '#title'         => $this->t('Please enable necessary links.'),
      '#options'       => $this->getLinks(),
      '#default_value' => isset($config['ajax_login_links']) ? $config['ajax_login_links'] : $this->getDefaultValue('ajax_login_links'),
    ];
    $form['ajax_login_output'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Please choose output would you like:'),
      '#options'       => $this->getOutputType(),
      '#default_value' => isset($config['ajax_login_output']) ? $config['ajax_login_output'] : $this->getDefaultValue('ajax_login_output'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['login', 'register', 'pass', 'default'];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['ajax_login_links'] = $form_state->getValue('ajax_login_links');
    $this->configuration['ajax_login_output'] = $form_state->getValue('ajax_login_output');
  }

  /**
   * Return array of links.
   */
  protected function getLinks() {
    return [
      'login'    => $this->t('Login'),
      'register' => $this->t('Create new account'),
      'pass'     => $this->t('Reset your password'),
    ];
  }

  /**
   * Return settings for output of links.
   */
  protected function getOutputType() {
    return [
      'default' => $this->t('Default'),
      'inline'  => $this->t('Inline'),
    ];
  }

  /**
   * Define default value for block's settings.
   */
  protected function getDefaultValue($field) {
    switch ($field) {
      case 'ajax_login_links':
        return [
          'login',
          'register',
          'pass',
        ];

      case 'ajax_login_output':
        return 'default';
    }
  }

}
