<?php

namespace Drupal\consent_iframe\Form;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract class ConsentIframeForm.
 */
abstract class ConsentIframeFormBase extends ConfigFormBase {

  /**
   * The configuration Id.
   *
   * @var string
   */
  static protected $configId = 'consent_iframe.settings';

  /**
   * The block storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $blockStorage;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->setBlockStorage($container->get('entity_type.manager')->getStorage('block'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'consent_iframe_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [static::$configId];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::$configId);
    $form = parent::buildForm($form, $form_state);

    $form['#tree'] = TRUE;

    $form['cors_allowed'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Allowed external sources for cross-origin resource sharing (CORS)'),
      '#description' => $this->t("Paste any trusted origin here. One origin per field. Use wildcard <b>*</b> to allow all subdomains like <em>*.example.com</em> or just paste <em>*</em> to allow all domains (<b>not recommended</b>). Save the form to get another text field for inserting. You might want to include official sources for your Accelerated Mobile Pages too. Examples: <em>https://example-com.cdn.ampproject.org, https://example.com.amp.cloudflare.com</em>"),
    ];
    $cors_allowed = $config->get('cors_allowed');
    if (!is_array($cors_allowed)) {
      $cors_allowed = [];
    }
    $cors_allowed[] = '';
    foreach ($cors_allowed as $i => $allowed) {
      $form['cors_allowed'][$i] = [
        '#type' => 'textfield',
        '#default_value' => $allowed,
      ];
    }

    $consent_blocks = $this->getConsentBlocks();
    if (!empty($consent_blocks)) {
      $block_options = ['___none' => '___none'];
      foreach ($consent_blocks as $block) {
        $block_options[$block->id()] = $block->id();
      }
      $form['block'] = [
        '#type' => 'select',
        '#title' => $this->t('Adapt settings from block configuration'),
        '#options' => $block_options,
        '#empty_value' => '___none',
        '#default_value' => $config->get('block') ? $config->get('block') : '___none',
      ];
    }

    $form['trigger'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Trigger for user consents'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#states' => [
        'visible' => [
          'select[name="block"]' => ['value' => '___none'],
        ],
      ],
    ];
    $form['trigger']['storage'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Submit user consents to the backend storage.'),
      '#default_value' => $config->get('trigger.storage'),
    ];
    $form['trigger']['parent_response'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pass consent-response to iFrame parent (required for AMP)'),
      '#default_value' => $config->get('trigger.parent_response'),
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $block_id = $form_state->getValue('block');
    if (!(empty($block_id) || $block_id === '___none')) {
      $consent_blocks = $this->getConsentBlocks();
      $exists = FALSE;
      foreach ($consent_blocks as $block) {
        if ($block->id() == $block_id) {
          $exists = TRUE;
          break;
        }
      }
      if (!$exists) {
        $form_state->setError($form['block'], $this->t('The chosen block is not a consent block.'));
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config(static::$configId);
    $block_id = $form_state->getValue('block');
    if (empty($block_id) || $block_id === '___none') {
      $config->set('trigger.storage', (bool) $form_state->getValue(['trigger', 'storage']));
      $config->set('trigger.parent_response', (bool) $form_state->getValue(['trigger', 'parent_response']));
      $config->set('block', NULL);
    }
    else {
      $config->set('block', $block_id);
    }

    $user_cors_allowed = $form_state->getValue('cors_allowed');
    $cors_allowed = [];
    if (empty($user_cors_allowed) || !is_array($user_cors_allowed)) {
      $user_cors_allowed = [];
    }
    foreach ($user_cors_allowed as $user_allowed) {
      $user_allowed = trim($user_allowed);
      if (!empty($user_allowed)) {
        $cors_allowed[] = $user_allowed;
      }
    }
    $config->set('cors_allowed', array_values($cors_allowed));

    $config->save();
  }

  /**
   * Set the block storage.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The block storage.
   */
  public function setBlockStorage(EntityStorageInterface $storage) {
    $this->blockStorage = $storage;
  }

  /**
   * Get consent block configurations.
   *
   * @return \Drupal\block\BlockInterface[]
   *   The consent blocks.
   */
  abstract protected function getConsentBlocks();

}
