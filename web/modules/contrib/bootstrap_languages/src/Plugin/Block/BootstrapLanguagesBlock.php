<?php

namespace Drupal\bootstrap_languages\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\language\Plugin\Block\LanguageBlock;

/**
 * Provides a 'Bootstrap Languages' block.
 *
 * @Block(
 *  id = "bootstrap_languages",
 *  admin_label = @Translation("Bootstrap Language switcher"),
 *  deriver = "Drupal\bootstrap_languages\Plugin\Derivative\BootstrapLanguagesBlock"
 * )
 */
class BootstrapLanguagesBlock extends LanguageBlock {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();
    $settings = $config['bootstrap_language'];

    $form['bootstrap_language'] = [
      '#type' => 'details',
      '#title' => $this->t('Bootstrap settings'),
      '#open' => TRUE,
    ];

    $form['bootstrap_language']['dropdown_style'] = [
      '#type' => 'select',
      '#title' => $this->t('Dropdown display style'),
      '#options' => [
        'all' => $this->t('Icons and text'),
        'icons' => $this->t('Only icons'),
      ],
      '#default_value' => !empty($settings['dropdown_style']) ? $settings['dropdown_style'] : 'all',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();
    $this->configuration['bootstrap_language'] = $values['bootstrap_language'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $settings = $this->configuration['bootstrap_language'];

    $route_name = $this->pathMatcher->isFrontPage() ? '<front>' : '<current>';
    $type = $this->getDerivativeId();
    $links = $this->languageManager->getLanguageSwitchLinks($type, Url::fromRoute($route_name));

    if (isset($links->links)) {
      $build = [
        '#theme' => 'links__bootstrap_language_block',
        '#links' => $links->links,
        '#attributes' => [
          'class' => [
            "language-switcher-{$links->method_id}",
            !empty($settings['dropdown_style']) ? "{$settings['dropdown_style']}-dropdown-style" : 'all-dropdown-style',
          ],
        ],
        '#set_active_class' => TRUE,
        '#attached' => [
          'library' => [
            'bootstrap_languages/default',
          ],
        ],
      ];
    }
    return $build;
  }

}
