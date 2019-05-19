<?php

declare(strict_types = 1);

namespace Drupal\views_parity_row\Plugin\views\row;

use Drupal\views\Plugin\views\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\row\EntityRow as ViewsEntityRow;

/**
 * Generic entity row plugin to provide a common base for all entity types.
 *
 * @ViewsRow(
 *   id = "views_parity_row_entity",
 *   deriver = "Drupal\views_parity_row\Plugin\Derivative\ViewsParityRowEntityRow"
 * )
 */
class EntityRow extends ViewsEntityRow {

  /**
   * The renderer to be used to render the entity row.
   *
   * @var \Drupal\views_parity_row\Plugin\views\Entity\Render\RendererBase
   */
  protected $entityLanguageRenderer;

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $options = $this->options['views_parity_row'];
    $default = $this->defineOptions();

    $form['views_parity_row_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Alternate with a different view mode every X rows ?'),
      '#default_value' => isset($this->options['views_parity_row_enable']) ?
      $this->options['views_parity_row_enable'] : $default['views_parity_row_enable'],
    ];

    $form['views_parity_row'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Configuration of Views Parity Row'),
      '#states' => [
        'visible' => [
          ':input[name="row_options[views_parity_row_enable]"]' => ['checked' => TRUE],
        ],
      ],
      'frequency' => [
        '#type' => 'number',
        '#title' => $this->t('Frequency of view mode change.'),
        '#description' => $this->t('Enter a positive integer number. This number will be the frequency of change of the content view mode. Example, if you chose <em>3</em>, it means that every 3 rows, the content will use the other View mode.'),
        '#size' => 6,
        '#maxlength' => 6,
        '#default_value' => isset($options['frequency']) ? (int) $options['frequency'] : $default['views_parity_row']['frequency']['default'],
      ],
      'start' => [
        '#type' => 'number',
        '#title' => $this->t('Start'),
        '#description' => $this->t('Start at which row ?'),
        '#min' => 0,
        '#size' => 6,
        '#maxlength' => 6,
        '#default_value' => isset($options['start']) ? (int) $options['start'] : $default['views_parity_row']['start']['default'],
      ],
      'end' => [
        '#type' => 'number',
        '#title' => $this->t('End'),
        '#description' => $this->t('End at which row ? Set <em>0</em> to disable.'),
        '#min' => 0,
        '#size' => 6,
        '#maxlength' => 6,
        '#default_value' => isset($options['end']) ? (int) $options['end'] : $default['views_parity_row']['end']['default'],
      ],
      'view_mode' => [
        '#type' => 'select',
        '#options' => $this->getEntityManager()->getViewModeOptions($this->entityTypeId),
        '#title' => $this->t('Alternate view mode'),
        '#default_value' => isset($options['view_mode']) ? $options['view_mode'] : $default['views_parity_row']['view_mode']['default'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    $options = $this->getEntityManager()->getViewModeOptions($this->entityTypeId);

    if (isset($this->options['views_parity_row_enable']) && $this->options['views_parity_row_enable'] === TRUE) {
      $string = $options[$this->options['view_mode']] . ' | ' . $this->options['views_parity_row']['frequency'] . ' | ' . $options[$this->options['views_parity_row']['view_mode']];
    }
    else {
      $string = $options[$this->options['view_mode']];
    }

    return $string;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['views_parity_row_enable'] = ['default' => FALSE];
    $options['views_parity_row']['contains']['frequency'] = ['default' => 2];
    $options['views_parity_row']['contains']['start'] = ['default' => 0];
    $options['views_parity_row']['contains']['end'] = ['default' => 0];
    $options['views_parity_row']['contains']['view_mode'] = ['default' => 'default'];

    return $options;
  }

  /**
   * Returns the current renderer.
   *
   * @return \Drupal\views_parity_row\Plugin\views\Entity\Render\RendererBase
   *   The configured renderer.
   */
  protected function getEntityTranslationRenderer() {
    if (!isset($this->entityLanguageRenderer)) {
      $view = $this->getView();
      $rendering_language = $view->display_handler->getOption('rendering_language');
      $langcode = NULL;
      $dynamic_renderers = [
        '***LANGUAGE_entity_translation***' => 'TranslationLanguageRenderer',
        '***LANGUAGE_entity_default***' => 'DefaultLanguageRenderer',
        '***LANGUAGE_language_interface***' => 'CurrentLanguageRenderer',
      ];
      if (isset($dynamic_renderers[$rendering_language])) {
        // Dynamic language set based on result rows or instance defaults.
        $renderer = $dynamic_renderers[$rendering_language];
      }
      else {
        if (strpos($rendering_language, '***LANGUAGE_') !== FALSE) {
          $langcode = PluginBase::queryLanguageSubstitutions()[$rendering_language];
        }
        else {
          // Specific langcode set.
          $langcode = $rendering_language;
        }
        $renderer = 'ConfigurableLanguageRenderer';
      }
      $class = '\Drupal\views_parity_row\Plugin\views\Entity\Render\\' . $renderer;
      $entity_type = $this->getEntityManager()->getDefinition($this->getEntityTypeId());
      $this->entityLanguageRenderer = new $class($view, $this->getLanguageManager(), $entity_type, $langcode);
    }

    return $this->entityLanguageRenderer;
  }

}
