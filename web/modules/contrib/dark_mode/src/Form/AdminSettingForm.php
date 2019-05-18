<?php

namespace Drupal\dark_mode\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Messenger\Messenger;

/**
 * Class AdminSettingForm.
 */
class AdminSettingForm extends ConfigFormBase {

  /**
   * Drupal\Core\Extension\ThemeHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  protected $messenger;

  /**
   * Constructs a new AdminSettingForm object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ThemeHandlerInterface $theme_handler, Messenger $messenger) {
    parent::__construct($config_factory);
    $this->themeHandler  = $theme_handler;
    $this->configFactory = $config_factory;
    $this->messenger     = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('config.factory'), $container->get('theme_handler'), $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dark_mode.adminsetting',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'admin_setting_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $themes         = $this->getThemeList();
    $selected_theme = $this->configFactory->getEditable('dark_mode.adminsetting')->get('active_theme');

    if (!empty($selected_theme) && $selected_theme != '__none') {
      $this->messenger->addMessage($this->t("<b> @theme </b> will activate at time <b>@start_time</b> and deactive at time <b>@end_time</b> every day.", [
        '@theme' => $themes[$selected_theme]['theme_name'],
        '@start_time' => date('h:i A', strtotime($themes[$selected_theme]['start_time'])),
        '@end_time' => date('h:i A', strtotime($themes[$selected_theme]['end_time'])),
      ]));
    }

    $theme_options = [
      '__none' => $this->t("Select Theme"),
    ];

    $form['dark_mode'] = [
      '#type' => 'table',
      '#title' => $this->t('Dark mode processing order'),
      '#header' => [
        $this->t('Theme Activation Time'),
        $this->t('Theme Deactivation Time'),
        $this->t('Theme Name'),
      ],
      '#empty' => $this->t('There are no items yet. Add roles.'),
    ];

    foreach ($themes as $machine_name => $info) {

      $form['dark_mode'][$machine_name]['start_time'] = [
        '#type' => 'time',
        '#default_value' => $info['start_time'],
      ];

      $form['dark_mode'][$machine_name]['end_time'] = [
        '#type' => 'time',
        '#default_value' => $info['end_time'],
      ];

      $form['dark_mode'][$machine_name]['theme'] = [
        '#type' => '#markup',
        '#markup' => $info['theme_name'],
      ];

      $theme_options[$machine_name] = $info['theme_name'];
    }

    $form['active_theme'] = [
      '#type' => 'select',
      '#title' => $this->t("Theme"),
      '#default_value' => !empty($selected_theme) ? $selected_theme : '',
      '#options' => $theme_options,
      '#description' => $this->t("This theme will be activated as per your selected time duration above. If you have not selected any theme then no theme will be activate."),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $themes = $this->getThemeList();
    // Check time for selected can't be empty.
    $selected_theme = $form_state->getValue('active_theme');

    if ($selected_theme != '__none') {
      if (empty($form_state->getValue('dark_mode')[$selected_theme]['start_time'])) {
        $form_state->setErrorByName("dark_mode[$selected_theme][start_time]", $this->t("Start time can't be empty for @theme", ['@theme' => $themes[$selected_theme]['theme_name']]));
      }
      if (empty($form_state->getValue('dark_mode')[$selected_theme]['end_time'])) {
        $form_state->setErrorByName("dark_mode[$selected_theme][end_time]", $this->t("End time can't be empty for @theme", ['@theme' => $themes[$selected_theme]['theme_name']]));
      }
      // Validate Start and End time.
      // If start time is selected than end time can't be empty.
      // If end time is selected than start time can't be empty.
      foreach ($themes as $machine_name => $label) {
        $start_time = $form_state->getValue('dark_mode')[$machine_name]['start_time'];
        $end_time   = $form_state->getValue('dark_mode')[$machine_name]['end_time'];
        if ((!empty($start_time) && empty($end_time))) {
          $form_state->setErrorByName("dark_mode[$machine_name][end_time]", $this->t("End time can't be empty for @theme", ['@theme' => $label['theme_name']]));
        }
        elseif ((!empty($end_time) && empty($start_time))) {
          $form_state->setErrorByName("dark_mode[$machine_name][start_time]", $this->t("Start time can't be empty for @theme", ['@theme' => $label['theme_name']]));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $themes = $this->getThemeList();
    $data   = [];
    foreach ($themes as $machine_name => $label) {
      $data[$machine_name] = $form_state->getValue('dark_mode')[$machine_name];
    }

    $this->config('dark_mode.adminsetting')->set('dark_mode', $data)->save();
    $this->config('dark_mode.adminsetting')->set('active_theme', $form_state->getValue('active_theme'))->save();
  }

  /**
   * Gets a list of active themes without hidden ones.
   *
   * @return array[]
   *   An array with all compatible active themes.
   */
  private function getThemeList() {
    $config = $this->config('dark_mode.adminsetting')->get('dark_mode');

    $themes_list = [];
    $themes      = $this->themeHandler->listInfo();
    foreach ($themes as $theme) {
      $theme_name = $theme->getName();
      if (!empty($theme->info['hidden'])) {
        continue;
      }
      $themes_list[$theme_name] = [
        'theme_name' => $theme->info['name'],
        'start_time' => ($config[$theme_name]['start_time']) ? $config[$theme_name]['start_time'] : "",
        'end_time' => ($config[$theme_name]['end_time']) ? $config[$theme_name]['end_time'] : "",
      ];
    }
    return $themes_list;
  }

}
