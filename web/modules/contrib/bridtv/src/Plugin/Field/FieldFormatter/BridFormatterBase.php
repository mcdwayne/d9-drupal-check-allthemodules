<?php

namespace Drupal\bridtv\Plugin\Field\FieldFormatter;

use Drupal\bridtv\BridInfoNegotiator;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base formatter class for viewing Bridtv videos.
 */
abstract class BridFormatterBase extends FormatterBase implements ContainerFactoryPluginInterface {

  static protected $theme = 'bridtv_js';

  /**
   * The Brid.TV negotiator service.
   *
   * @var \Drupal\bridtv\BridInfoNegotiator
   */
  protected $bridNegotiator;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings']
    );
    $instance->setBridNegotiator($container->get('bridtv.negotiator'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'show_desc' => TRUE,
      'format' => 'plain_text',
    ];
  }

  /**
   * Set the Brid.TV negotiator service.
   *
   * @param \Drupal\bridtv\BridInfoNegotiator $negotiator
   */
  public function setBridNegotiator(BridInfoNegotiator $negotiator) {
    $this->bridNegotiator = $negotiator;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $show_description = !empty($this->settings['show_desc']);
    $summary[] = $this->t('Show description: @enabled', ['@enabled' => $show_description ? $this->t('Yes') : $this->t('No')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $filter_format_options = ['__none' => $this->t('None')];
    if (function_exists('filter_formats')) {
      foreach (filter_formats() as $format_id => $format) {
        $filter_format_options[$format_id] = $format->label();
      }
    }
    $form['show_desc'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display description'),
      '#default_value' => !empty($this->settings['show_desc']),
    ];
    if (!empty($filter_format_options)) {
      $form['format'] = [
        '#type' => 'select',
        '#title' => $this->t('Filter format'),
        '#default_value' => $this->settings['format'],
        '#options' => $filter_format_options,
        '#empty_value' => '__none',
        '#states' => [
          'invisible' => [
            ':input[name="fields[' . $this->fieldDefinition->getName() . '][settings_edit_form][settings][show_desc]"]' => ['checked' => FALSE],
          ],
        ],
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    /**
     * @var \Drupal\bridtv\Field\BridtvVideoItemInterface $item
     */
    foreach ($items as $delta => $item) {
      if ($embedding = $item->getBridEmbeddingInstance()) {
        $player_sizes = $this->bridNegotiator->getPlayerSizes($embedding->getPlayerId());
        $format = empty($this->settings['format']) || $this->settings['format'] == '__none' ? FALSE : $this->settings['format'];
        $embedding->setSettings(['format' => $format] + $this->settings + $player_sizes);
        $elements[$delta] = [
          '#theme' => static::$theme,
          '#embedding' => $embedding,
          '#cache' => [
            'tags' => $embedding->getEntity()->getCacheTags(),
          ],
        ];
      }
    }
    return $elements;
  }

}
