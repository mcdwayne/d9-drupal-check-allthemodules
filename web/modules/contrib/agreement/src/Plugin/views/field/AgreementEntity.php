<?php

namespace Drupal\agreement\Plugin\views\field;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a field handler for agreement configuration entities.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("agreement_entity")
 */
class AgreementEntity extends FieldPluginBase {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Initialize method.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition array.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['display'] = [
      'default' => ['label'],
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $display_options = [
      'id' => $this->t('ID'),
      'label' => $this->t('Label'),
      'path' => $this->t('Path'),
      'roles' => $this->t('Roles'),
      'title' => $this->t('Page Title'),
    ];
    $default_options = [];
    foreach ($display_options as $name => $value) {
      $default_options[$name] = in_array($name, $this->options['display']) ? $name : 0;
    }

    $form['display'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Display'),
      '#description' => $this->t('Choose the agreement options to display.'),
      '#options' => $display_options,
      '#default_value' => $default_options,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) {
    parent::submitOptionsForm($form, $form_state);

    $options = &$form_state->getValue('options');
    $settable_options = [];
    if (!empty($options['display'])) {
      foreach ($options['display'] as $key => $value) {
        if ($value) {
          $settable_options[] = $key;
        }
      }
    }
    $options['display'] = $settable_options;
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values) {
    parent::preRender($values);

    // Get all the configuration entities.
    $agreements = $this->entityTypeManager
      ->getStorage('agreement')
      ->loadMultiple();

    foreach ($values as $index => $result) {
      $value = $this->getValue($result);
      if (isset($agreements[$value])) {
        $values[$index]->_agreement = $agreements[$value];
      }
      else {
        $values[$index]->_agreement = NULL;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    if (empty($this->options['display'])) {
      return parent::render($values);
    }

    $build = [];
    $props = ['id', 'label', 'path'];
    $settings = $values->_agreement !== NULL ? $values->_agreement->getSettings() : [];

    // Create rendered markup for each key enabled in display options.
    foreach ($this->options['display'] as $key) {
      $build[$key] = [
        '#markup' => '',
      ];

      if ($values->_agreement !== NULL) {
        if (in_array($key, $props)) {
          $build[$key]['#markup'] = $this->sanitizeValue($values->_agreement->get($key));
        }
        elseif ($key === 'roles') {
          $build[$key] = [
            '#type' => 'item_list',
            '#items' => $settings['roles'],
          ];
        }
        else {
          $build[$key]['#markup'] = $this->sanitizeValue($settings[$key]);
        }
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getAgreement(ResultRow $values) {
    return $values->_agreement;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

}
