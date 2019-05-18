<?php

namespace Drupal\boolean_block_formatter\Plugin\Field\FieldFormatter;

use Drupal\block\Entity\Block;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'boolean_block_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "boolean_block_formatter",
 *   label = @Translation("Boolean block formatter"),
 *   field_types = {
 *     "boolean"
 *   }
 * )
 */
class BooleanBlockFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Renderer variable to be used for rendering content.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs an ImageFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode for the formatter.
   * @param array $third_party_settings
   *   Any other third party settings for the field formatter.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, RendererInterface $renderer) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [
      'block_id' => '',
    ];
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $blockManager = \Drupal::service('plugin.manager.block');
    $contextRepository = \Drupal::service('context.repository');

    // Get the list of blocks definition.
    $definitions = $blockManager->getDefinitionsForContexts($contextRepository->getAvailableContexts());

    // Prepare the list of all blocks options array.
    $blockList = [];
    foreach ($definitions as $id => $block) {
      $blockList[$id] = $block['admin_label'];
    }

    $form['block_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Block'),
      '#default_value' => $this->getSetting('block_id'),
      '#options' => $blockList,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    if ($block_id = $this->getSetting('block_id')) {
      $summary[] = $this->t('Block ID: %block_id', ['%block_id' => $block_id]);
    }
    else {
      $summary[] = $this->t('Block not configured.');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $block_id = $this->getSetting('block_id');
    // Format the block id for machine name lookup (for view based blocks).
    $block_id = strtolower(str_replace([':', '-'], ['__', '_'], $block_id));

    foreach ($items as $delta => $item) {
      // Display the block only when the boolean value is true.
      if ($item->value) {
        // Load the block configuration.
        $block = Block::load($block_id);

        // Return empty content, if the block is not configured.
        if (empty($block)) {
          return $elements;
        }
        // Prepare the block content renderable array.
        $render = \Drupal::entityTypeManager()
          ->getViewBuilder('block')
          ->view($block);
        // Place the rendered block as the content for this field.
        $elements[$delta] = ['#markup' => $this->renderer->render($render)];
      }
    }
    return $elements;
  }

}
