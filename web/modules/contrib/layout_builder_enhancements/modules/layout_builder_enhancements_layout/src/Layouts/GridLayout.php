<?php

namespace Drupal\layout_builder_enhancements_layout\Layouts;

use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Form\FormStateInterface;

/**
 * Add layout settings.
 */
class GridLayout extends LayoutDefault implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'color' => '',
      'allow_color' => '',
      'title' => '',
      'level' => '',
      'regions' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $configuration = $this->getConfiguration();

    $form['headline'] = [
      '#type' => 'details',
      '#title' => $this->t('Headline'),
      '#open' => TRUE,
    ];

    $form['headline']['title'] = [
      '#type' => 'textfield',
      '#title' => t('Headline'),
      '#default_value' => $configuration['title'],
    ];

    $form['headline']['level'] = [
      '#type' => 'select',
      '#title' => t('Headline level'),
      '#default_value' => (isset($configuration['level'])) ? $configuration['level'] : 2,
      '#options' => [
        1 => 1,
        2 => 2,
        3 => 3,
      ],
    ];

    $definition = $this->getPluginDefinition();

    foreach ($definition->getRegions() as $id => $region) {
      $form['regions'][$id] = [
        '#type' => 'details',
        '#title' => $this->t('Region: @name', ['@name' => $region['label']->render()]),
        '#open' => FALSE,
      ];

      $form['regions'][$id]['allow_color'] = [
        '#title' => $this->t('Set a background color'),
        '#type' => 'checkbox',
        '#default_value' => isset($configuration['regions'][$id]['allow_color']) ? $configuration['regions'][$id]['allow_color'] : FALSE,
      ];

      $form['regions'][$id]['color'] = [
        '#title' => $this->t('Background color'),
        '#tree' => TRUE,
        '#default_value' => ($configuration['regions'][$id]['color']) ? $configuration['regions'][$id]['color'] : '',
        '#type' => 'color',
        '#states' => [
          'visible' => [
            ':input[name="layout_settings[regions][' . $id . '][allow_color]"]' => ['checked' => TRUE],
          ],
          'invisible' => [
            ':input[name="layout_settings[regions][' . $id . '][allow_color]"]' => ['checked' => FALSE],
          ],
        ],
      ];

      $form['regions'][$id]['image'] = [
        '#title' => $this->t('Background image'),
        '#tree' => TRUE,
        '#default_value' => ($configuration['regions'][$id]['image']) ? $configuration['regions'][$id]['image'] : 0,
        '#type' => 'managed_file',
        '#upload_location' => 'public://layout-backgrounds',
        '#upload_validators'    => [
          'file_validate_is_image'      => [],
          'file_validate_extensions'    => ['gif png jpg jpeg'],
        ],
      ];
    }

    $form['settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#open' => FALSE,
    ];

    $form['settings']['image'] = [
      '#title' => $this->t('Background image'),
      '#default_value' => ($configuration['image']) ? $configuration['image'] : '',
      '#type' => 'managed_file',
      '#upload_location' => 'public://layout-backgrounds',
      '#upload_validators'    => [
        'file_validate_is_image'      => [],
        'file_validate_extensions'    => ['gif png jpg jpeg'],
      ],
    ];

    $form['settings']['allow_color'] = [
      '#title' => $this->t('Set a background color'),
      '#type' => 'checkbox',
      '#default_value' => isset($configuration['settings']['allow_color']) ? $configuration['settings']['allow_color'] : FALSE,
    ];

    $form['settings']['color'] = [
      '#type' => 'color',
      '#default_value' => (isset($configuration['color'])) ? $configuration['color'] : '',
      '#states' => [
        'visible' => [
          ':input[name="layout_settings[settings][allow_color]"]' => ['checked' => TRUE],
        ],
        'invisible' => [
          ':input[name="layout_settings[settings][allow_color]"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['settings']['link'] = [
      '#type' => 'url',
      '#title' => $this->t('Link'),
    ];

    $form['settings']['link_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link title'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['color'] = $form_state->getValue('settings')['color'];
    $this->configuration['allow_color'] = $form_state->getValue('settings')['allow_color'];
    $this->configuration['title'] = $form_state->getValue('headline')['title'];
    $this->configuration['level'] = $form_state->getValue('headline')['level'];
    $this->configuration['regions'] = $form_state->getValue('regions');
    $this->configuration['link'] = $form_state->getValue('settings')['link'];
    $this->configuration['image'] = $form_state->getValue('settings')['image'];
    $this->configuration['link_title'] = $form_state->getValue('settings')['link_title'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $configuration = $this->getConfiguration();
    $build = parent::build($regions);
    $build['#settings']['grid']['class'] = 'layout-grid';

    if (!isset($configuration['regions'])) {
      $configuration['regions'] = [];
    }

    $build['#settings']['regions'] = $configuration['regions'];
    if ($configuration['allow_color'] == FALSE) {
      unset($build['#settings']['color']);
    }

    foreach ($configuration['regions'] as $id => $region) {
      if (!isset($region['allow_color']) || $region['allow_color'] != TRUE) {
        unset($build['#settings']['regions'][$id]['color']);
      }
    }

    return $build;
  }

}
