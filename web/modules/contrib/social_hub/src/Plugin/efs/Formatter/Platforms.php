<?php

namespace Drupal\social_hub\Plugin\efs\Formatter;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\EntityDisplayBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Url;
use Drupal\efs\Entity\ExtraFieldInterface;
use Drupal\efs\ExtraFieldFormatterPluginBase;
use Drupal\field_ui\Form\EntityDisplayFormBase;
use Drupal\social_hub\PlatformIntegrationPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation to display platforms in an entity.
 *
 * @ExtraFieldFormatter(
 *   id = "social_hub_platforms",
 *   label = @Translation("Platforms"),
 *   description = @Translation("Display selected platforms using a given integration plugin."),
 *   supported_contexts = {
 *     "display"
 *   }
 * )
 *
 * @phpcs:disable Drupal.Commenting.InlineComment.InvalidEndChar
 * @phpcs:disable Drupal.Commenting.PostStatementComment.Found
 */
class Platforms extends ExtraFieldFormatterPluginBase {

  /**
   * The platform integration plugin manager.
   *
   * @var \Drupal\social_hub\PlatformIntegrationPluginManager
   */
  private $pluginManager;

  /**
   * The platform entity storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  private $storage;

  /**
   * The platform entities.
   *
   * @var \Drupal\social_hub\PlatformInterface[]
   */
  private $entities;

  /**
   * Constructs PlatformsBlock instance.
   *
   * @param array $configuration
   *   An array of block configuration.
   * @param string $plugin_id
   *   The block plugin id.
   * @param mixed $plugin_definition
   *   The block plugin definition.
   * @param \Drupal\social_hub\PlatformIntegrationPluginManager $plugin_manager
   *   The platform integrations manager.
   * @param \Drupal\Core\Config\Entity\ConfigEntityStorageInterface $storage
   *   The platform entities storage.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    PlatformIntegrationPluginManager $plugin_manager,
    ConfigEntityStorageInterface $storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pluginManager = $plugin_manager;
    $this->storage = $storage;
    $this->entities = [];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.social_hub.platform'),
      $container->get('entity_type.manager')->getStorage('platform')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function view(
    array $build,
    EntityInterface $entity,
    EntityDisplayBase $display,
    string $view_mode,
    ExtraFieldInterface $extra_field) {
    $this->entities = $this->getEntityStorage()->loadMultiple($this->getSetting('entities'));
    $element = [
      '#theme' => 'item_list',
      '#items' => [],
    ];

    if (!empty($this->getSetting('label'))) {
      $element['#title'] = $this->getSetting('label');
    }

    $metadata = BubbleableMetadata::createFromRenderArray($build);

    foreach ($this->entities as $platform) {
      $item = $platform->build(array_values($this->getSetting('plugins')));
      $metadata->merge(BubbleableMetadata::createFromRenderArray($item));
      $element['#items'][] = $item;
    }

    $metadata->applyTo($build);

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(
    EntityDisplayFormBase $view_display,
    array $form,
    FormStateInterface $form_state,
    ExtraFieldInterface $extra_field,
    string $field) {
    $form = parent::settingsForm($view_display, $form, $form_state, $extra_field, $field);
    $plugins = $this->getPluginManager()->getPluginsAsOptions();

    if (empty($plugins)) {
      $form['#markup'] = $this->t('There are no plugins implemented. Please, follow the instructions provided in @link', [
        '@link' => (string) Url::fromRoute('help.page.social_hub')->toString(),
      ]);

      return $form;
    }

    $wrapper_id = Html::getUniqueId($field . '-' . $this->getPluginId() . '-display-settings');
    $form['#tree'] = TRUE;
    $form['#prefix'] = '<div id="' . $wrapper_id . '">';
    $form['#suffix'] = '</div>';

    $triggering_element = $form_state->getTriggeringElement();

    if (strpos($triggering_element['#id'], 'plugins') !== FALSE) {
      $parents = array_slice($triggering_element['#parents'], 0, count($triggering_element['#parents']) - 2);
      $values = NestedArray::getValue($form_state->getValues(), $parents);
      $selected_plugins = array_values(array_filter($values['plugins'] ?? $this->getSetting('plugins')));
      $selected_entities = array_values(array_filter($values['entities'] ?? $this->getSetting('entities')));
    }
    else {
      $selected_plugins = $this->getSetting('plugins');
      $selected_entities = $this->getSetting('entities');
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('The label for the field, by default is set to the field name. Leave it blank to choose not show a label for this field.'), // NOSONAR
      '#default_value' => $this->getSetting('label') ?? (string) $extra_field->label(),
    ];

    $form['plugins'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Available plugins'),
      '#descrption' => $this->t('Use those platforms using the selected plugin.'), // NOSONAR
      '#options' => $plugins,
      '#default_value' => $selected_plugins,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [static::class, 'ajaxRefresh'],
        'wrapper' => $wrapper_id,
      ],
    ];

    // Early return form if no plugin is selected.
    if (empty($selected_plugins)) {
      return $form;
    }

    $this->fetchEntities($selected_plugins);

    if (empty($this->entities)) {
      $form['#markup'] = $this->t('There are no platforms created yet. Please, go to @link and create some platforms.', [
        '@link' => (string) Url::fromRoute('entity.platform.collection')->toString(),
      ]);

      return $form;
    }

    $entities = [];
    foreach ($this->entities as $platform) {
      $entities[$platform->id()] = $platform->label();
    }

    $form['entities'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Available platforms'),
      '#descrption' => $this->t('Select which platforms should appear when rendering this field. If none is selected all will be rendered.'), // NOSONAR
      '#options' => $entities,
      '#default_value' => $selected_entities,
      '#empty_value' => '',
      '#empty_option' => NULL,
    ];

    return $form;
  }

  /**
   * Ajax callback.
   *
   * @param array $form
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The AJAX form result.
   */
  public static function ajaxRefresh(array $form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $parents = array_slice($triggering_element['#parents'], 0, count($triggering_element['#parents']) - 4);
    array_push($parents, ...['format', 'format_settings', 'settings']);
    return NestedArray::getValue($form, $parents);
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings) {
    $settings['plugins'] = array_filter($settings['plugins'] ?? []);
    $settings['entities'] = array_filter($settings['entities'] ?? []);

    return parent::setSettings($settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $defaults = [
      'label' => NULL,
      'plugins' => [],
      'entities' => [],
    ];
    return $defaults + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(string $context) {
    $summary = parent::settingsSummary($context);
    $plugins = $this->getSetting('plugins');

    if (empty($plugins)) {
      $summary[] = $this->t('Not configured.');

      return $summary;
    }

    $definitions = $this->getPluginManager()->getDefinitions();
    $plugins_labels = [];

    foreach ($plugins as $plugin) {
      if (!array_key_exists($plugin, $definitions)) {
        $plugins_labels[] = $this->t('%plugin (not found)', [
          '%plugin' => $plugin,
        ]);
        continue;
      }

      $plugins_labels[] = $definitions[$plugin]['label'];
    }

    $summary[] = $this->t('Label: %label', [
      '%label' => $this->getSetting('label') ?? (string) $this->t('None'),
    ]);
    $summary[] = $this->t('Platform integrations: %suffix', [
      '%suffix' => implode(', ', $plugins_labels),
    ]);

    $entities = $this->getSetting('entities');
    if (!empty($entities)) {
      $entities_labels = [];

      foreach ($this->getEntityStorage()->loadMultiple($entities) as $entity) {
        $entities_labels[] = $entity->label();
      }

      $summary[] = $this->t('Platforms: %suffix', [
        '%suffix' => implode(', ', $entities_labels),
      ]);
    }
    else {
      $summary[] = $this->t('Platforms: %suffix', [
        '%suffix' => $this->t('All'),
      ]);
    }

    return $summary;
  }

  /**
   * Get the plugin manager service.
   *
   * @return \Drupal\social_hub\PlatformIntegrationPluginManager
   *   The plugin manager service.
   *
   * @phpcs:disable DrupalPractice.Objects.GlobalDrupal.GlobalDrupal
   */
  private function getPluginManager() {
    if ($this->pluginManager === NULL) {
      $this->pluginManager = \Drupal::service('plugin.manager.social_hub.platform');
    }

    return $this->pluginManager;
  }

  /**
   * Fetch platform entities.
   *
   * @param array $selected_plugins
   *   An array of plugin ids.
   */
  private function fetchEntities(array $selected_plugins) {
    $results = $this->getEntityStorage()->getQuery()
      ->condition('plugins.*', $selected_plugins, 'IN')
      ->condition('status', 1)
      ->execute();

    if (!empty($results)) {
      $this->entities = $this->getEntityStorage()->loadMultiple($results);
    }
  }

  /**
   * Get the entity storage instance.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   *   The entity storage instance.
   */
  private function getEntityStorage() {
    if ($this->storage === NULL) {
      $this->storage = \Drupal::entityTypeManager()->getStorage('platform');
    }

    return $this->storage;
  }

}
