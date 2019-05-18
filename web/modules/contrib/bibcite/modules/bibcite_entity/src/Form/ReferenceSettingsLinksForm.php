<?php

namespace Drupal\bibcite_entity\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Common Reference settings.
 */
class ReferenceSettingsLinksForm extends ConfigFormBase {

  /**
   * Link plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $bibciteLinkManager;

  /**
   * Constructs a new ReferenceSettingsLinksForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $bibcite_link_manager
   *   Link plugin manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PluginManagerInterface $bibcite_link_manager) {
    parent::__construct($config_factory);

    $this->bibciteLinkManager = $bibcite_link_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.bibcite_link')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['bibcite_entity.reference.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bibcite_entity_reference_settings_links';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bibcite_entity.reference.settings');
    $links = $config->get('links');

    $form['links'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Label'),
        $this->t('Enabled'),
        $this->t('Weight'),
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'bibcite-links-order-weight',
        ],
      ],
    ];

    foreach ($this->bibciteLinkManager->getDefinitions() as $plugin_id => $definition) {
      $weight = !empty($links[$plugin_id]['weight']) ? (int) $links[$plugin_id]['weight'] : NULL;

      $form['links'][$plugin_id]['#attributes']['class'][] = 'draggable';
      $form['links'][$plugin_id]['#weight'] = $weight;

      $form['links'][$plugin_id]['label'] = [
        '#plain_text' => $definition['label'],
      ];
      $form['links'][$plugin_id]['enabled'] = [
        '#type' => 'checkbox',
        '#default_value' => isset($links[$plugin_id]['enabled']) ? $links[$plugin_id]['enabled'] : TRUE,
      ];
      $form['links'][$plugin_id]['weight'] = [
        '#type' => 'weight',
        '#title' => t('Weight for @title', ['@title' => $definition['label']]),
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        '#attributes' => [
          'class' => ['bibcite-links-order-weight'],
        ],
      ];
    }

    uasort($form['links'], 'Drupal\Component\Utility\SortArray::sortByWeightProperty');

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('bibcite_entity.reference.settings');

    $links = $form_state->getValue('links');
    array_walk($links, function (&$link) {
      $link['enabled'] = (bool) $link['enabled'];
      $link['weight'] = (int) $link['weight'];
    });

    $config->set('links', $links);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
