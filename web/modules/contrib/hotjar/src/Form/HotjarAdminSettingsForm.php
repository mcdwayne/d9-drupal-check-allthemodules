<?php

namespace Drupal\hotjar\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hotjar\HotjarSettingsInterface;
use Drupal\hotjar\SnippetBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Hotjar settings for this site.
 */
class HotjarAdminSettingsForm extends ConfigFormBase {

  /**
   * Hotjar settings.
   *
   * @var \Drupal\hotjar\HotjarSettingsInterface
   */
  protected $hotjarSettings;

  /**
   * Snippet builder.
   *
   * @var \Drupal\hotjar\SnippetBuilderInterface
   */
  protected $snippetBuilder;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    HotjarSettingsInterface $hotjar_settings,
    SnippetBuilderInterface $snippet_builder
  ) {
    parent::__construct($config_factory);
    $this->hotjarSettings = $hotjar_settings;
    $this->snippetBuilder = $snippet_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('hotjar.settings'),
      $container->get('hotjar.snippet')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hotjar_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['hotjar.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General settings'),
      '#open' => TRUE,
    ];

    $form['general']['hotjar_account'] = [
      '#default_value' => $this->hotjarSettings->getSetting('account'),
      '#description' => $this->t('Your Hotjar ID can be found in your tracking code on the line <code>h._hjSettings={hjid:<b>12345</b>,hjsv:5};</code> where <code><b>12345</b></code> is your Hotjar ID'),
      '#maxlength' => 20,
      '#required' => TRUE,
      '#size' => 15,
      '#title' => $this->t('Hotjar ID'),
      '#type' => 'textfield',
    ];

    $form['general']['hotjar_snippet_version'] = [
      '#default_value' => $this->hotjarSettings->getSetting('snippet_version'),
      '#description' => $this->t('Your Hotjar snippet version is near your Hotjar ID<code>h._hjSettings={hjid:12345,hjsv:<b>5</b>};</code> where <code><b>5</b></code> is your Hotjar snippet version'),
      '#maxlength' => 10,
      '#required' => TRUE,
      '#size' => 5,
      '#title' => $this->t('Hotjar snippet version'),
      '#type' => 'textfield',
    ];

    $visibility = $this->hotjarSettings->getSetting('visibility_pages');
    $pages = $this->hotjarSettings->getSetting('pages');

    // Visibility settings.
    $form['tracking']['page_track'] = [
      '#type' => 'details',
      '#title' => $this->t('Pages'),
      '#group' => 'tracking_scope',
      '#open' => TRUE,
    ];

    if ($visibility == 2) {
      $form['tracking']['page_track'] = [];
      $form['tracking']['page_track']['hotjar_visibility_pages'] = [
        '#type' => 'value',
        '#value' => 2,
      ];
      $form['tracking']['page_track']['hotjar_pages'] = [
        '#type' => 'value',
        '#value' => $pages,
      ];
    }
    else {
      $options = [
        $this->t('Every page except the listed pages'),
        $this->t('The listed pages only'),
      ];
      $description_args = [
        '%blog' => 'blog',
        '%blog-wildcard' => 'blog/*',
        '%front' => '<front>',
      ];
      $description = $this->t("Specify pages by using their paths. Enter one path per line. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", $description_args);
      $title = $this->t('Pages');

      $form['tracking']['page_track']['hotjar_visibility_pages'] = [
        '#type' => 'radios',
        '#title' => $this->t('Add tracking to specific pages'),
        '#options' => $options,
        '#default_value' => $visibility,
      ];
      $form['tracking']['page_track']['hotjar_pages'] = [
        '#type' => 'textarea',
        '#title' => $title,
        '#title_display' => 'invisible',
        '#default_value' => $pages,
        '#description' => $description,
        '#rows' => 10,
      ];
    }

    // Render the role overview.
    $visibility_roles = $this->hotjarSettings->getSetting('roles');

    $form['tracking']['role_track'] = [
      '#type' => 'details',
      '#title' => $this->t('Roles'),
      '#group' => 'tracking_scope',
      '#open' => TRUE,
    ];

    $form['tracking']['role_track']['hotjar_visibility_roles'] = [
      '#type' => 'radios',
      '#title' => $this->t('Add tracking for specific roles'),
      '#options' => [
        $this->t('Add to the selected roles only'),
        $this->t('Add to every role except the selected ones'),
      ],
      '#default_value' => $this->hotjarSettings->getSetting('visibility_roles'),
    ];
    $role_options = array_map(['\Drupal\Component\Utility\SafeMarkup', 'checkPlain'], user_role_names());
    $form['tracking']['role_track']['hotjar_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#default_value' => !empty($visibility_roles) ? $visibility_roles : [],
      '#options' => $role_options,
      '#description' => $this->t('If none of the roles are selected, all users will be tracked. If a user has any of the roles checked, that user will be tracked (or excluded, depending on the setting above).'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Trim some text values.
    $form_state->setValue('hotjar_account', trim($form_state->getValue('hotjar_account')));
    $form_state->setValue('hotjar_snippet_version', trim($form_state->getValue('hotjar_snippet_version')));
    $form_state->setValue('hotjar_pages', trim($form_state->getValue('hotjar_pages')));
    $form_state->setValue('hotjar_roles', array_filter($form_state->getValue('hotjar_roles')));

    // Verify that every path is prefixed with a slash.
    if ($form_state->getValue('hotjar_visibility_pages') != 2) {
      $pages = preg_split('/(\r\n?|\n)/', $form_state->getValue('hotjar_pages'));
      foreach ($pages as $page) {
        if (strpos($page, '/') !== 0 && $page !== '<front>') {
          $form_state->setErrorByName(
            'hotjar_pages',
            $this->t('Path "@page" not prefixed with slash.', ['@page' => $page])
          );
          // Drupal forms show one error only.
          break;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('hotjar.settings');
    $config
      ->set('account', $form_state->getValue('hotjar_account'))
      ->set('snippet_version', $form_state->getValue('hotjar_snippet_version'))
      ->set('visibility_pages', $form_state->getValue('hotjar_visibility_pages'))
      ->set('pages', $form_state->getValue('hotjar_pages'))
      ->set('visibility_roles', $form_state->getValue('hotjar_visibility_roles'))
      ->set('roles', $form_state->getValue('hotjar_roles'))
      ->save();

    parent::submitForm($form, $form_state);

    $this->snippetBuilder->createAssets();
  }

}
