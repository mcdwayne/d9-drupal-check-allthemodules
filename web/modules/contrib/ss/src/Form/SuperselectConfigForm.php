<?php

namespace Drupal\superselect\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ThemeHandler;

/**
 * Implements a SuperselectConfig form.
 */
class SuperselectConfigForm extends ConfigFormBase {

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandler
   *   Theme handler.
   */
  protected $themeHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, ThemeHandler $themeHandler, MessengerInterface $messenger) {
    parent::__construct($config_factory);
    $this->themeHandler = $themeHandler;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('theme_handler'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'superselect_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['superselect.settings'];
  }

  /**
   * Super select configuration form.
   *
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $superselect_path = _superselect_lib_get_path();
    // var_dump($superselect_path);die;
    if (!$superselect_path) {
      $url = Url::fromUri(SUPERSELECT_WEBSITE_URL);
      $link = Link::fromTextAndUrl($this->t('Superselect JavaScript file'), $url)->toString();

      $this->messenger->addError($this->t('The library could not be detected. You need to download the @superselect and extract the entire contents of the archive into the %path directory on your server.',
        ['@superselect' => $link, '%path' => 'libraries']
      ));
      return $form;
    }

    // Super select settings:
    $superselect_conf = $this->configFactory->get('superselect.settings');

    $form['tokensMaxItems'] = [
      '#type' => 'select',
      '#attributes' => array('class' => array('superselect-disable')),
      '#title' => $this->t('Max selection allowed'),
      '#options' => range(1, 25),
      '#default_value' => $superselect_conf->get('tokensMaxItems'),
      '#description' => $this->t('This option allow you to restrict the maximum number of selection items.Example : choosing 10 will only apply Super select if the number of options is lessthan or equal to 10'),
    ];

    $form['dropdownMaxItems'] = [
      '#type' => 'select',
      '#title' => $this->t('Dropdown maximum serarch list'),
      '#attributes' => array('class' => array('superselect-disable')),
      '#options' => range(1, 25),
      '#default_value' => $superselect_conf->get('dropdownMaxItems'),
      '#description' => $this->t('This options allow you to change the maximum number of search result displayed in the dropdown. The default value is 10'),
    ];

    $form['searchMinLength'] = [
      '#type' => 'select',
      '#title' => $this->t('Search Minimum length'),
      '#attributes' => array('class' => array('superselect-disable')),
      '#options' => range(1, 3),
      '#default_value' => $superselect_conf->get('searchMinLength'),
      '#description' => $this->t('The searchMinLength option allow you to specify the minimum of characters required to start searching.'),
    ];

    $form['searchFromStart'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Search from start'),
      '#default_value' => $superselect_conf->get('searchFromStart'),
      '#description' => $this->t('This options allow you to specify if Tokenize2 will search from the begining of a string, but default this option is set to true.'),
      '#size' => 12,
    ];
    $form['searchHighlight'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Search highlight'),
      '#default_value' => $superselect_conf->get('searchHighlight'),
      '#description' => $this->t('The searchHighlight options allow you to choose if you want your search highlighted in the result dropdown. By default this option is set to true.'),
    ];

    $form['jquery_selector'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Apply Super select to the following elements'),
      '#description' => $this->t('A comma-separated list of jQuery selectors to apply Super select to, such as <code>select#edit-operation, select#edit-type</code> or <code>.superselect-select</code>. Defaults to <code>select</code> to apply Super select to all <code>&lt;select&gt;</code> elements.'),
      '#default_value' => $superselect_conf->get('jquery_selector'),
    ];

    $form['theme_options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Superselect per theme options'),
    ];

    $default_disabled_themes = $superselect_conf->get('disabled_themes');
    $default_disabled_themes = is_array($default_disabled_themes) ? $default_disabled_themes : [];
    $form['theme_options']['disabled_themes'] = [
      '#type' => 'checkboxes',
      '#title' => t('Disable the default Superselect theme for the following themes'),
      '#options' => $this->superselect_enabled_themes_options(),
      '#default_value' => $default_disabled_themes,
      '#description' => $this->t('Enable or disable the default Superselect CSS file. Select a theme if it contains custom styles for Superselect replacements.'),
    ];

    $form['options'] = [
      '#type' => 'fieldset',
      '#title' => t('Superselect general options'),
    ];

    $form['options']['superselect_include'] = [
      '#type' => 'radios',
      '#title' => $this->t('Use Superselect for admin pages and/or front end pages'),
      '#options' => [
        SUPERSELECT_INCLUDE_EVERYWHERE => $this->t('Include Superselect on every page'),
        SUPERSELECT_INCLUDE_ADMIN => $this->t('Include Superselect only on admin pages'),
        SUPERSELECT_INCLUDE_NO_ADMIN => $this->t('Include Superselect only on front end pages'),
      ],
      '#default_value' => $superselect_conf->get('superselect_include'),
    ];

    $form['strings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Super Select strings'),
    ];

    $form['strings']['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $superselect_conf->get('placeholder'),
      '#description' => $this->t('This placeholder option add a placeholder to the super select control. By default there is no placeholder.'),
    ];
    $form['strings']['displayNoResultsMessage'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable no results message'),
      '#default_value' => $superselect_conf->get('displayNoResultsMessage'),
    ];
    $form['strings']['noResultsMessageText'] = [
      '#type' => 'textfield',
      '#title' => $this->t('No results text'),
      '#default_value' => $superselect_conf->get('noResultsMessageText'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * Superselect configuration form submit handler.
   *
   * Validates submission by checking for duplicate entries, invalid
   * characters, and that there is an abbreviation and phrase pair.
   *
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('superselect.settings');

    $config
      ->set('tokensMaxItems', $form_state->getValue('tokensMaxItems'))
      ->set('dropdownMaxItems', $form_state->getValue('dropdownMaxItems'))
      ->set('searchMinLength', $form_state->getValue('searchMinLength'))
      ->set('searchFromStart', $form_state->getValue('searchFromStart'))
      ->set('searchHighlight', $form_state->getValue('searchHighlight'))
      ->set('jquery_selector', $form_state->getValue('jquery_selector'))
      ->set('disabled_themes', $form_state->getValue('disabled_themes'))
      ->set('allow_single_deselect', $form_state->getValue('allow_single_deselect'))
      ->set('disabled_themes', $form_state->getValue('disabled_themes'))
      ->set('superselect_include', $form_state->getValue('superselect_include'))
      ->set('placeholder', $form_state->getValue('placeholder'))
      ->set('displayNoResultsMessage', $form_state->getValue('displayNoResultsMessage'))
      ->set('noResultsMessageText', $form_state->getValue('noResultsMessageText'));

    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Helper function to get options for enabled themes.
   */
  private function superselect_enabled_themes_options() {
    $options = [];

    // Get a list of available themes.
    $themes = $this->themeHandler->listInfo();

    foreach ($themes as $theme_name => $theme) {
      // Only create options for enabled themes.
      if ($theme->status) {
        if (!(isset($theme->info['hidden']) && $theme->info['hidden'])) {
          $options[$theme_name] = $theme->info['name'];
        }
      }
    }

    return $options;
  }

}
