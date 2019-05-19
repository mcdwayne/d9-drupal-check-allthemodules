<?php

namespace Drupal\styleswitcher\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form to add/edit a style.
 */
class StyleswitcherStyleForm extends FormBase {

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * Constructs the StyleswitcherStyleForm.
   *
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   */
  public function __construct(ThemeManagerInterface $theme_manager) {
    $this->themeManager = $theme_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('theme.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'styleswitcher_style_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param array|null $style
   *   (optional) Style to edit. The structure of an array is the same as
   *   returned from styleswitcher_style_load().
   *
   * @see styleswitcher_style_load()
   */
  public function buildForm(array $form, FormStateInterface $form_state, $style = NULL) {
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t('Human-readable name for this style.'),
      '#default_value' => $style ? $style['label'] : '',
      '#required' => TRUE,
    ];

    if ($style) {
      list(, $name_value) = explode('/', $style['name']);
    }
    $form['name'] = [
      '#type' => 'machine_name',
      '#description' => $this->t('A unique machine-readable name. Can only contain lowercase letters, numbers, and underscores.'),
      '#default_value' => $style ? $name_value : '',
      '#field_prefix' => 'custom/',
      '#machine_name' => [
        'source' => ['label'],
        'exists' => [$this, 'exists'],
      ],
    ];
    if ($style) {
      // Show the warning about renaming styles.
      $form['name']['#description'] .= '<br />' . $this->t('<strong>WARNING:</strong> if you change style machine name, users who have chosen this style will see the default one instead until they switch again.');
    }

    $form['old_name'] = [
      '#type' => 'value',
      '#value' => $style ? $style['name'] : '',
    ];

    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#description' => $this->t('The path to the stylesheet file relative to the site root or an external CSS file.'),
      '#default_value' => $style ? $style['path'] : '',
      '#required' => TRUE,
      '#access' => !$style || isset($style['path']),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    if ($style) {
      $form['actions']['delete'] = [
        '#type' => 'link',
        '#title' => $this->t('Delete'),
        '#url' => Url::fromRoute('styleswitcher.style_delete', ['style' => $name_value]),
        '#attributes' => ['class' => ['button', 'button--danger']],
        // Do not allow to delete the blank style.
        '#access' => isset($style['path']),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Trim text values now, so submission handlers get them fully validated.
    // No need to trim name, because it's validated by a machine-name pattern.
    $form_state->setValueForElement($form['label'], trim($form_state->getValue('label')));

    $path = $form_state->getValue('path');

    if ($path === '') {
      // Set the path back to NULL.
      $form_state->setValueForElement($form['path'], NULL);
    }
    else {
      $path = trim($path);
      $form_state->setValueForElement($form['path'], $path);

      if (!is_file($path) && !UrlHelper::isExternal($path)) {
        $form_state->setErrorByName('path', $this->t('Stylesheet file %path does not exist.', ['%path' => $path]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $old_name = $form_state->getValue('old_name');

    $styles = styleswitcher_custom_styles();
    $style = [
      'label' => $form_state->getValue('label'),
      'name' => 'custom/' . $form_state->getValue('name'),
      'path' => $form_state->getValue('path'),
    ];

    if ($old_name !== '') {
      unset($styles[$old_name]);

      // Update style keys in settings variable.
      if ($style['name'] != $old_name) {
        $config = $this->configFactory()
          ->getEditable('styleswitcher.styles_settings');
        $settings = $config->get();

        foreach (array_keys($settings) as $theme) {
          if (isset($settings[$theme][$old_name])) {
            $settings[$theme][$style['name']] = $settings[$theme][$old_name];
            unset($settings[$theme][$old_name]);
          }
        }

        $config->setData($settings)->save();
      }
    }

    $styles[$style['name']] = $style;
    $this->configFactory()
      ->getEditable('styleswitcher.custom_styles')
      ->setData($styles)
      ->save();

    drupal_set_message($this->t('The style %title has been saved.', ['%title' => $style['label']]));

    $form_state->setRedirect('styleswitcher.admin');
  }

  /**
   * The _title_callback for the style edit form.
   *
   * @param array $style
   *   Style array as returned from styleswitcher_style_load().
   *
   * @return string
   *   Label of the style.
   *
   * @see styleswitcher_style_load()
   */
  public function title(array $style) {
    return $style['label'];
  }

  /**
   * Checks whether a submitted machine name value already exists.
   *
   * @param string $input
   *   User-submitted value.
   *
   * @return array|null
   *   Style array on success or NULL otherwise. Style is an associative array
   *   as returned from styleswitcher_style_load().
   *
   * @see styleswitcher_style_load()
   */
  public function exists($input) {
    // It does not matter what theme to set in this load call, because all
    // custom styles exist in all themes. Let's set one from the current page
    // just to decrease calculations.
    $active_theme_name = $this->themeManager->getActiveTheme()->getName();
    return styleswitcher_style_load($input, $active_theme_name, 'custom');
  }

}
