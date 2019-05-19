<?php

namespace Drupal\sir_trevor\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sir_trevor\Plugin\SirTrevorPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class GlobalConfigForm extends ConfigFormBase {
  /** @var \Drupal\sir_trevor\Plugin\SirTrevorPluginManagerInterface */
  private $sirTrevorPluginManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, SirTrevorPluginManagerInterface $sirTrevorPluginManager) {
    parent::__construct($config_factory);
    $this->sirTrevorPluginManager = $sirTrevorPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.sir_trevor')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sir_trevor.global',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sir_trevor_global_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sir_trevor.global');
    $enabledBlocks = $config->get('enabled_blocks');

    $form['enabled_blocks'] = [
      '#type' => 'details',
      '#title' => $this->t('Enabled blocks'),
      '#description' => $this->t('Select the blocks that should be enabled or leave all unchecked to allow all of them.'),
      '#tree' => TRUE,
    ];

    foreach ($this->sirTrevorPluginManager->getBlocks() as $block) {
      if ($block->getMachineName() === 'text') {
        // The Sir trevor library depends on the text block. We therefore won't
        // show it and ensure it's always enabled.
        continue;
      }
      $form['enabled_blocks'][$block->getMachineName()] = [
        '#type' => 'checkbox',
        '#title' => $block->getMachineName(),
        '#default_value' => in_array($block->getMachineName(), $enabledBlocks),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('sir_trevor.global');

    $enabledBlocks = array_keys(array_filter($form_state->getValue('enabled_blocks')));
    // Ensure the text block is always enabled because the javascript library
    // expects it to be.
    if (!empty($enabledBlocks)) {
      $enabledBlocks[] = 'text';
    }

    $config->set('enabled_blocks', $enabledBlocks);
    $config->save();
    parent::submitForm($form, $form_state);
  }
}
