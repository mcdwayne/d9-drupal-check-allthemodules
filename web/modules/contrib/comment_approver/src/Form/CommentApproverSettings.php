<?php

namespace Drupal\comment_approver\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\comment_approver\Plugin\CommentApproverManager;
use Drupal\comment_approver\CommentTesterInterface;

/**
 * Class CommentApproverSettings.
 */
class CommentApproverSettings extends ConfigFormBase {

  /**
   * Drupal\comment_approver\Plugin\CommentApproverManager definition.
   *
   * @var \Drupal\comment_approver\Plugin\CommentApproverManager
   */
  protected $pluginManagerCommentApprover;

  /**
   * Constructs a new CommentApproverSettings object.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
      CommentApproverManager $plugin_manager_comment_approver
    ) {
    parent::__construct($config_factory);
    $this->pluginManagerCommentApprover = $plugin_manager_comment_approver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.comment_approver')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'comment_approver.commentapproversettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'comment_approver_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('comment_approver.commentapproversettings');
    $plugins = $this->pluginManagerCommentApprover->getDefinitions();
    $options = [];
    $options_description = [];

    foreach ($plugins as $plugin) {
      $plugin_id = $plugin['id'];

      // Get the already saved plugin configuration.
      $plugin_config = $config->get($plugin_id) ? $config->get($plugin_id) : [];

      // Create a plugin instance.
      $plugin_instance = $this->pluginManagerCommentApprover->createInstance($plugin_id, $plugin_config);

      // Create options array.
      $options[$plugin_id] = $plugin_instance->getLabel();
      $options_description[$plugin_id]['#description'] = $plugin_instance->getDescription();

      // Gets a plugin settings form.
      $settings_form = $plugin_instance->settingsForm();

      if ($settings_form) {
        // If settings form is available embed it in the form.
        $form[$plugin_id] = [
          '#type' => 'details',
          '#title' => $plugin['label'],
          '#tree' => TRUE,
          '#parents' => [$plugin_id],
          '#group' => 'test_settings',
        ];
        $form[$plugin_id] += $settings_form;
      }
    }

    $form['select_tests_to_perform'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Select tests to perform'),
      '#description' => $this->t('Select the tests which will be performed on a comment to publish/unpublish them automatically'),
      '#options' => $options,
      '#default_value' => $config->get('select_tests_to_perform') ? $config->get('select_tests_to_perform') : array_keys($options),
    ];
    $form['select_tests_to_perform'] += $options_description;

    $options_mode = [
      CommentTesterInterface::DEFAULT => $this->t('Bypass the comment approver'),
      CommentTesterInterface::APPROVER => $this->t('Work as comment approver'),
      CommentTesterInterface::BLOCKER => $this->t('Work as comment blocker'),
    ];
    $options_mode_description = [
      CommentTesterInterface::DEFAULT => ['#description' => $this->t('Default drupal flow will be followed')],
      CommentTesterInterface::APPROVER => ['#description' => $this->t('If all tests passes then the comment is approved')],
      CommentTesterInterface::BLOCKER => ['#description' => $this->t('If any test fails then comment is blocked')],
    ];
    $form['mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Mode of operation'),
      '#description' => $this->t('Select the mode in which this module works'),
      '#options' => $options_mode,
      '#default_value' => is_numeric($config->get('mode')) ? $config->get('mode') : CommentTesterInterface::APPROVER,
    ];
    $form['mode'] += $options_mode_description;

    $form['test_settings'] = [
      '#type' => 'vertical_tabs',
      '#title' => t('Settings'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('comment_approver.commentapproversettings');
    $select_tests_to_perform = $form_state->getValue('select_tests_to_perform');

    // Save the configuration of each selected plugin.
    foreach ($select_tests_to_perform as $testname => $value) {
      if ($value) {
        $config->set($testname, $form_state->getValue($testname));
      }
    }

    $config->set('select_tests_to_perform', $select_tests_to_perform)
      ->set('mode', $form_state->getValue('mode'))
      ->save();
  }

}
