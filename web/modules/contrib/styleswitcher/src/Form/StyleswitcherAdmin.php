<?php

namespace Drupal\styleswitcher\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configure Styleswitcher settings.
 */
class StyleswitcherAdmin extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'styleswitcher_admin';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['styleswitcher.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $styles = styleswitcher_custom_styles();
    ksort($styles);

    $header = [
      $this->t('Style'),
      $this->t('Operations'),
    ];
    $rows = [];

    foreach ($styles as $name => $style) {
      $name_hyphenated = strtr($name, '_', '-');
      list(, $name_value) = explode('/', $name_hyphenated);

      $operations = [
        'edit' => [
          'title' => $this->t('Edit'),
          'url' => Url::fromRoute('styleswitcher.style_edit', ['style' => $name_value]),
        ],
      ];
      // Do not allow to delete the blank style.
      if (isset($style['path'])) {
        $operations['delete'] = [
          'title' => $this->t('Delete'),
          'url' => Url::fromRoute('styleswitcher.style_delete', ['style' => $name_value]),
        ];
      }

      // Build the table row.
      $rows[] = [
        [
          'data' => [
            '#theme' => 'styleswitcher_admin_style_overview',
            '#style' => $style,
          ],
        ],
        ['data' => ['#type' => 'operations', '#links' => $operations]],
      ];
    }

    $form['styleswitcher_custom_styles'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    ];

    $form['enable_overlay'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable overlay'),
      '#description' => $this->t('Enable the overlay and fade when switching stylesheets.'),
      '#default_value' => $this->config('styleswitcher.settings')->get('enable_overlay'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('styleswitcher.settings');
    $config->set('enable_overlay', $form_state->getValue('enable_overlay'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
