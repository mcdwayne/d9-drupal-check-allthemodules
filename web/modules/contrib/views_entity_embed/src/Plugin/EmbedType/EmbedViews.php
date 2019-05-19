<?php

namespace Drupal\views_entity_embed\Plugin\EmbedType;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\embed\EmbedType\EmbedTypeBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
// Use Drupal\views\Element\View;.
use Drupal\views\Views;

/**
 * Viws embed type.
 *
 * @EmbedType(
 *   id = "embed_views",
 *   label = @Translation("Views")
 * )
 */
class EmbedViews extends EmbedTypeBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
        $configuration, $plugin_id, $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'filter_views' => FALSE,
      'views_options' => [],
      'filter_displays' => FALSE,
      'dipslays_options' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultIconUrl() {
    return file_create_url(drupal_get_path('module', 'views_entity_embed') . '/js/plugins/drupalviews/views_entity_embed.png');
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['filter_views'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Filter which Views to be allowed as options:'),
      '#default_value' => $this->getConfigurationValue('filter_views'),
    ];
    $form['views_options'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed Views'),
      '#default_value' => $this->getConfigurationValue('views_options'),
      '#options' => $this->getAllViews(),
      '#states' => [
        'visible' => [':input[name="type_settings[filter_views]"]' => ['checked' => TRUE]],
      ],
    ];
    $form['filter_displays'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Filter which Display to be allowed as options:'),
      '#default_value' => $this->getConfigurationValue('filter_displays'),
    ];
    $form['display_options'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Allowed displays'),
      '#default_value' => $this->getConfigurationValue('display_options'),
      '#options' => $this->getAllDisplays(),
      '#states' => [
        'visible' => [':input[name="type_settings[filter_displays]"]' => ['checked' => TRUE]],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (!$form_state->hasAnyErrors()) {
      $this->setConfigurationValue('filter_views', $form_state->getValue('filter_views'));
      $this->setConfigurationValue('filter_displays', $form_state->getValue('filter_displays'));

      // Set views options.
      $views_options = $form_state->getValue('filter_views') ? array_filter($form_state->getValue('views_options')) : [];
      $this->setConfigurationValue('views_options', $views_options);

      // Display options.
      $displays_options = $form_state->getValue('filter_displays') ? array_filter($form_state->getValue('display_options')) : [];
      $this->setConfigurationValue('display_options', $displays_options);

    }
  }

  /**
   * Methods get all views as options list.
   */
  protected function getAllViews() {
    $views = [];
    foreach (Views::getAllViews() as $view) {
      if ($view->enable()) {
        $views[$view->id()] = $view->label();
      }
    }
    return $views;
  }

  /**
   * Method gets all displays as options list.
   */
  protected function getAllDisplays() {
    $displays = [];
    // Get all display plugins which provides the type.
    $display_plugins = Views::pluginManager('display')->getDefinitions();
    $plugin_ids = [];
    foreach ($display_plugins as $id => $definition) {
      $displays[$definition['class']] = $definition['title'];
    }
    return $displays;
  }

}
