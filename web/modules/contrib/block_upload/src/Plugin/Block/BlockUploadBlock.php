<?php

namespace Drupal\block_upload\Plugin\Block;

use Drupal\block_upload\BlockUploadManager;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Custom block.
 *
 * @Block(
 *   id = "block_upload",
 *   admin_label = @Translation("Block Upload"),
 *   category = @Translation("Block Upload"),
 *   deriver = "Drupal\block_upload\Plugin\Derivative\BlockUploadBlock"
 * )
 */
class BlockUploadBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected $account;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccountProxyInterface $account
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->account = $account;
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
      $container->get('current_user')
    );
  }

  /**
   * Build the content for mymodule block.
   */
  public function build() {
    if (!$this->account->hasPermission('block upload')) {
      return [];
    }

    $block_id = $this->getDerivativeId();
    return [
      BlockUploadManager::blockUploadBuildBlockContent($block_id),
      '#cache' => ['max-age' => 0],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $block_id = $this->getDerivativeId();
    $form = parent::blockForm($form, $form_state);
    // Retrieve existing configuration for this block.
    $config = $this->getConfiguration();
    // Add a form field to the existing block configuration form.
    $fields = BlockUploadManager::blockUploadGetFieldList();
    $form['block_upload_' . $block_id . '_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Field'),
      '#description' => $this->t('Select field you wish to upload file.'),
      '#options' => $fields,
      '#default_value' => \Drupal::state()->get('block_upload_' . $block_id . '_field') ?: '',
      '#ajax' => [
        'callback' => [$this, 'blockUploadAjaxCallback'],
        'wrapper' => 'config',
        'effect' => 'fade',
      ],
    ];
    $form['block_upload_id'] = [
      '#type' => 'textfield',
      '#default_value' => $block_id,
      '#access' => FALSE,
    ];
    // Add field additional display options.
    $field_name = \Drupal::state()->get('block_upload_' . $block_id . '_field') ?: '';
    $field = FieldStorageConfig::loadByName(explode('.', $field_name)[0], explode('.', $field_name)[1]);
    if (!empty($field)) {
      BlockUploadManager::blockUploadFieldOptionsFormElements($form, $block_id, $field->getType());
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $block_id = 'block_upload_' . $form_state->getValue('block_upload_id') . '_';
    \Drupal::state()->set($block_id . 'field', $form_state->getValue($block_id . 'field'));
    $settings = [];
    $settings['alt'] = $form_state->getValue(['config', $block_id . 'alt']);
    $settings['title'] = $form_state->getValue(['config', $block_id . 'title']);
    $settings['desc'] = $form_state->getValue(['config', $block_id . 'desc']);
    $settings['plupload'] = $form_state->getValue($block_id . 'plupload_status');
    \Drupal::state()->set($block_id . 'settings', $settings);
  }

  /**
   * Display fields checkboxes depends on selected field.
   */
  public function blockUploadAjaxCallback(array &$form, FormStateInterface $form_state) {
    $block_upload_id = $form_state->getValue(['settings', 'block_upload_id']);
    if (empty($form_state->getValue(['settings', 'block_upload_' . $block_upload_id . '_field']))) {
      return;
    }
    $field_name = $form_state->getValue(['settings', 'block_upload_' . $block_upload_id . '_field']);
    $field = field_info_field($field_name);
    BlockUploadManager::blockUploadFieldOptionsFormElements($form, $block_upload_id, $field['type']);
    return $form['config'];
  }

}
