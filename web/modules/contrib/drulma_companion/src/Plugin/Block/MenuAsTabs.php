<?php

namespace Drupal\drulma_companion\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Plugin\Block\SystemMenuBlock;

/**
 * Provides a drupal menu as bulma tabs.
 *
 * @Block(
 *   id = "drulma_companion_menu_tabs",
 *   admin_label = @Translation("Menu as bulma tabs"),
 *   deriver = "Drupal\system\Plugin\Derivative\SystemMenuBlock",
 *   category = @Translation("Bulma tabs")
 * )
 */
class MenuAsTabs extends SystemMenuBlock {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaultConfiguration = parent::defaultConfiguration() + [
      'horizontally_centered' => TRUE,
      'boxed' => FALSE,
      'toggle' => FALSE,
      'toggle_rounded' => FALSE,
      'fullwidth' => FALSE,
      'size' => '',
      'alignment' => '',
      'label_display' => FALSE,
    ];
    // Default to depth 1 because multilevel tabs look weird.
    $defaultConfiguration['depth'] = 1;
    return $defaultConfiguration;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    $form['horizontally_centered'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Center the tabs content horizontally'),
      '#default_value' => $this->configuration['horizontally_centered'],
      '#description' => $this->t('Use a .container for the tabs content. <a href="@url">See Bulma documentation</a>', [
        '@url' => 'https://bulma.io/documentation/layout/container/',
      ]),
    ];

    $form['boxed'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use boxed style'),
      '#default_value' => $this->configuration['boxed'],
      '#description' => $this->t('Use a classic style with borders. <a href="@url">See Bulma documentation</a>', [
        '@url' => 'https://bulma.io/documentation/components/tabs/#styles',
      ]),
    ];

    $form['toggle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use toggle style'),
      '#default_value' => $this->configuration['toggle'],
      '#description' => $this->t('Use mutually exclusive tabs. <a href="@url">See Bulma documentation</a>', [
        '@url' => 'https://bulma.io/documentation/components/tabs/#styles',
      ]),
    ];

    $form['toggle_rounded'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use toggle rounded style'),
      '#default_value' => $this->configuration['toggle_rounded'],
      '#description' => $this->t('When using mutually exclusive tabs show rounded edges. <a href="@url">See Bulma documentation</a>', [
        '@url' => 'https://bulma.io/documentation/components/tabs/#styles',
      ]),
    ];

    $form['fullwidth'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Take up the whole width available'),
      '#default_value' => $this->configuration['fullwidth'],
      '#description' => $this->t('Use fullwidth tabs. <a href="@url">See Bulma documentation</a>', [
        '@url' => 'https://bulma.io/documentation/components/tabs/#styles',
      ]),
    ];

    $form['size'] = [
      '#type' => 'select',
      '#title' => $this->t('Size of the tabs'),
      '#default_value' => $this->configuration['size'],
      '#description' => $this->t('Use fullwidth tabs. <a href="@url">See Bulma documentation</a>', [
        '@url' => 'https://bulma.io/documentation/components/tabs/#styles',
      ]),
      '#options' => [
        'small' => $this->t('Small'),
        '' => $this->t('Normal'),
        'medium' => $this->t('Medium'),
        'large' => $this->t('Large'),
      ],
    ];

    $form['alignment'] = [
      '#type' => 'select',
      '#title' => $this->t('Alignment of the tabs'),
      '#default_value' => $this->configuration['alignment'],
      '#description' => $this->t('Position the tabs horizonatally. <a href="@url">See Bulma documentation</a>', [
        '@url' => 'https://bulma.io/documentation/components/tabs/#alignment',
      ]),
      '#options' => [
        'centered' => $this->t('Centered'),
        '' => $this->t('Left'),
        'right' => $this->t('Right'),
      ],
    ];

    $form += parent::blockForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['horizontally_centered'] = $form_state->getValue('horizontally_centered');
    $this->configuration['boxed'] = $form_state->getValue('boxed');
    $this->configuration['toggle'] = $form_state->getValue('toggle');
    $this->configuration['toggle_rounded'] = $form_state->getValue('toggle_rounded');
    $this->configuration['fullwidth'] = $form_state->getValue('fullwidth');
    $this->configuration['size'] = $form_state->getValue('size');
    $this->configuration['alignment'] = $form_state->getValue('alignment');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = parent::build();
    $build['#theme'] = $this->addSuggestion($build['#theme']);
    return $build;
  }

  /**
   * Add a suggestion to be able to overwrite menu links markup.
   */
  protected function addSuggestion($themeHook) {
    return str_replace('menu__', 'menu__bulma_tabs__', $themeHook);
  }

}
