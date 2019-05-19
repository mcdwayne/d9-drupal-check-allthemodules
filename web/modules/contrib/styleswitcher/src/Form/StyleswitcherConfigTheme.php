<?php

namespace Drupal\styleswitcher\Form;

use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Configure theme-specific styles settings.
 */
class StyleswitcherConfigTheme extends FormBase {

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs the StyleswitcherConfigTheme.
   *
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(ThemeHandlerInterface $theme_handler) {
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static ($container->get('theme_handler'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'styleswitcher_config_theme';
  }

  /**
   * {@inheritdoc}
   *
   * @param string $theme
   *   Name of the theme to configure styles for.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $theme = '') {
    if (!$this->themeHandler->hasUi($theme)) {
      throw new NotFoundHttpException();
    }

    $styles = styleswitcher_style_load_multiple($theme);
    uasort($styles, 'styleswitcher_sort');
    $options = array_fill_keys(array_keys($styles), '');

    $form['theme_name'] = ['#type' => 'value', '#value' => $theme];

    $form['settings'] = [
      '#theme' => 'styleswitcher_admin_styles_table',
      '#tree' => TRUE,
    ];
    foreach ($styles as $name => $style) {
      $form['settings']['weight'][$name] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @label', ['@label' => $style['label']]),
        '#title_display' => 'invisible',
        '#delta' => $this->weightDelta($theme),
        '#default_value' => $style['weight'],
        '#weight' => $style['weight'],
        // Set special class for drag and drop updating.
        '#attributes' => ['class' => ['styleswitcher-style-weight']],
      ];
      $form['settings']['name'][$name] = [
        '#theme' => 'styleswitcher_admin_style_overview',
        '#style' => $style,
      ];
      $form['settings']['label'][$name] = [
        '#markup' => $style['label'],
      ];
    }
    $form['settings']['enabled'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled'),
      '#title_display' => 'invisible',
      '#options' => $options,
      '#default_value' => array_keys(styleswitcher_style_load_multiple($theme, ['status' => TRUE])),
    ];
    $form['settings']['default'] = [
      '#type' => 'radios',
      '#title' => $this->t('Default'),
      '#title_display' => 'invisible',
      '#options' => $options,
      '#default_value' => styleswitcher_default_style_key($theme),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $theme = $form_state->getValue('theme_name');
    $values = $form_state->getValue('settings');

    // Automatically enable the default style and the style which was default
    // previously because we will not get the value from that disabled checkbox.
    $values['enabled'][$values['default']] = 1;
    $values['enabled'][styleswitcher_default_style_key($theme)] = 1;

    $form_state->setValueForElement($form['settings'], $values);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $theme = $form_state->getValue('theme_name');
    $values = $form_state->getValue('settings');
    $theme_settings = [];

    foreach (array_keys(styleswitcher_style_load_multiple($theme)) as $name) {
      $theme_settings[$name] = [
        'weight' => $values['weight'][$name],
        'status' => !empty($values['enabled'][$name]),
        'is_default' => ($values['default'] == $name),
      ];
    }

    // Get all settings (for all themes).
    $config = $this->configFactory()
      ->getEditable('styleswitcher.styles_settings');
    $settings = $config->get();
    $settings[$theme] = $theme_settings;
    $config->setData($settings)->save();

    drupal_set_message($this->t('The configuration options have been saved.'));
  }

  /**
   * Calculates #delta for style's weight element.
   *
   * @param string $theme
   *   Name of the theme for which styles the delta is calculated.
   *
   * @return int
   *   Optimal #delta value.
   */
  protected function weightDelta($theme) {
    foreach (styleswitcher_style_load_multiple($theme) as $style) {
      $weights[] = $style['weight'];
    }

    return max(abs(min($weights)), max($weights), floor(count($weights) / 2));
  }

}
