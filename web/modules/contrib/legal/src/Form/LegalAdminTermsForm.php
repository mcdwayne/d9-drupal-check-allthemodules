<?php

namespace Drupal\legal\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\legal\Entity\Conditions;
use Drupal\Component\Render\PlainTextOutput;
use Drupal\Component\Utility\Html;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for administering content of Terms & Conditions.
 */
class LegalAdminTermsForm extends ConfigFormBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * LegalAdminTermsForm constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
                              ModuleHandlerInterface $module_handler,
                              LanguageManagerInterface $language_manager,
                              DateFormatterInterface $date_formatter) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('language_manager'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'legal_admin_terms';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'legal.settings',
    ];
  }

  /**
   * Module settings form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config       = $this->config('legal.settings');
    $conditions   = legal_get_conditions();
    $multilingual = $this->moduleHandler->moduleExists('language');

    if ($multilingual) {
      $langcode   = $this->languageManager->getCurrentLanguage()->getId();
      $conditions = legal_get_conditions($langcode);

      foreach ($this->languageManager->getLanguages() as $key => $object) {
        $languages[$key] = $object->getName();
      }
      $language         = $langcode;
      $version_options  = array(
        'version'  => $this->t('All users (new version)'),
        'revision' => $this->t('Language specific users (a revision)'),
      );
      $version_handling = 'version';
    }
    else {
      $languages        = array('en' => $this->t('English'));
      $language         = 'en';
      $version_handling = 'version';
    }

    $form['current_tc'] = array(
      '#type'  => 'fieldset',
      '#title' => $this->t('Current T&C'),
    );

    if (empty($conditions['version'])) {
      $form['current_tc']['no_tc_message'] = array(
        '#type'  => 'html_tag',
        '#tag'   => 'strong',
        '#value' => $this->t('Terms & Conditions are not being displayed to users, as no T&C have been saved.'),
      );
    }
    else {

      $form['current_tc']['#theme'] = 'legal_current_metadata';

      $form['current_tc']['current_version'] = array(
        '#type'   => 'item',
        '#title'  => $this->t('Version'),
        '#markup' => $conditions['version'],
      );

      $form['current_tc']['current_revision'] = array(
        '#type'   => 'item',
        '#title'  => $this->t('Version'),
        '#markup' => $conditions['revision'],
      );

      $form['current_tc']['current_language'] = array(
        '#type'   => 'item',
        '#title'  => $this->t('Language'),
        '#markup' => $conditions['language'],
      );

      $form['current_tc']['current_date'] = array(
        '#type'   => 'item',
        '#title'  => $this->t('Created'),
        '#markup' => $this->dateFormatter->format($conditions['date'], 'short'),
      );

      $form['current_tc']['multilingual'] = array(
        '#type'   => 'item',
        '#markup' => $multilingual,
      );
    }

    $form['legal_tab'] = array(
      '#type' => 'vertical_tabs',
    );

    $form['terms_of_use'] = array(
      '#type'  => 'details',
      '#title' => $this->t('Terms of use'),
      '#group' => 'legal_tab',
    );

    $form['terms_of_use']['conditions'] = array(
      '#type'          => 'text_format',
      '#title'         => $this->t('Terms & Conditions'),
      '#default_value' => $conditions['conditions'],
      '#description'   => $this->t('Your Terms & Conditions'),
      '#format'        => isset($conditions['format']) ? $conditions['format'] : filter_default_format(),
      '#required'      => TRUE,
    );

    $form['registration'] = array(
      '#type'  => 'details',
      '#title' => $this->t('Display Style Registration'),
      '#group' => 'legal_tab',
    );

    // Override display setting.
    $form['registration']['registration_terms_style'] = array(
      '#type'          => 'radios',
      '#title'         => $this->t('Display Style'),
      '#default_value' => $config->get('registration_terms_style'),
      '#options'       => array(
        $this->t('Scroll Box'),
        $this->t('Scroll Box (CSS)'),
        $this->t('HTML Text'),
        $this->t('Page Link'),
      ),
      '#description'   => $this->t('How terms & conditions should be displayed to users on the registration form.'),
      '#required'      => TRUE,
    );

    $form['registration']['registration_modal_terms'] = array(
      '#type'          => 'radios',
      '#title'         => $this->t('Link target'),
      '#default_value' => $config->get('registration_modal_terms') === TRUE ? 1 : 0,
      '#options'       => array(0 => $this->t('New window'), 1 => $this->t('Modal overlay')),
      '#description'   => $this->t('How to display the T&Cs when a user clicks on the link.'),
      '#required'      => TRUE,
      '#states'        => array(
        'visible' => array(
          ':input[name="registration_terms_style"]' => array('value' => 3),
        ),
      ),
    );

    $form['registration']['registration_container'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Display wrapped with details container'),
      '#default_value' => $config->get('registration_container'),
      '#description'   => $this->t('How terms & conditions should be displayed to users after the login form.'),
    );

    $form['login'] = array(
      '#type'  => 'details',
      '#title' => $this->t('Display Style Login'),
      '#group' => 'legal_tab',
    );

    $form['login']['login_terms_style'] = array(
      '#type'          => 'radios',
      '#title'         => $this->t('Display Style'),
      '#default_value' => $config->get('login_terms_style'),
      '#options'       => array(
        $this->t('Scroll Box'),
        $this->t('Scroll Box (CSS)'),
        $this->t('HTML Text'),
        $this->t('Page Link'),
      ),
      '#description'   => $this->t('How terms & conditions should be displayed to users after the login form.'),
      '#required'      => TRUE,
    );

    $form['login']['login_modal_terms'] = array(
      '#type'          => 'radios',
      '#title'         => $this->t('Link target'),
      '#default_value' => $config->get('login_modal_terms') === TRUE ? 1 : 0,
      '#options'       => array(0 => $this->t('New window'), 1 => $this->t('Modal overlay')),
      '#description'   => $this->t('How to display the T&Cs when a user clicks on the link.'),
      '#required'      => TRUE,
      '#states'        => array(
        'visible' => array(
          ':input[name="login_terms_style"]' => array('value' => 3),
        ),
      ),
    );

    $form['login']['login_container'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Display wrapped with details container'),
      '#default_value' => $config->get('login_container'),
      '#description'   => $this->t('How terms & conditions should be displayed to users after the login form.'),
    );

    // Only display options if there's more than one language available.
    if (count($languages) > 1) {
      // Language and version handling options.
      $form['language'] = array(
        '#type'  => 'details',
        '#title' => $this->t('Language'),
        '#group' => 'legal_tab',
      );

      $form['language']['language'] = array(
        '#type'          => 'select',
        '#title'         => $this->t('Language'),
        '#options'       => $languages,
        '#default_value' => $language,
      );

      $form['language']['version_handling'] = array(
        '#type'          => 'select',
        '#title'         => $this->t('Ask To Re-accept'),
        '#description'   => $this->t('<strong>All users</strong>: all users will be asked to accept the new version of the T&C, including users who accepted a previous version.<br />
                           <strong>Language specific</strong>: only new users, and users who accepted the T&C in the same language as this new revision will be asked to re-accept.'),
        '#options'       => $version_options,
        '#default_value' => $version_handling,
      );
    }
    else {
      $form['language']['language']         = array(
        '#type'  => 'value',
        '#value' => $language,
      );
      $form['language']['version_handling'] = array(
        '#type'  => 'value',
        '#value' => $version_handling,
      );

    }

    // Additional checkboxes.
    $form['extras'] = array(
      '#type'        => 'details',
      '#title'       => $this->t('Additional Checkboxes'),
      '#description' => $this->t('Each field will be shown as a checkbox which the user must tick to register.'),
      '#open'        => FALSE,
      '#tree'        => TRUE,
      '#group'       => 'legal_tab',
    );

    $extras_count = ((count($conditions['extras']) < 10) ? 10 : count($conditions['extras']));

    for ($counter = 1; $counter <= $extras_count; $counter++) {
      $extra = isset($conditions['extras']['extras-' . $counter]) ? $conditions['extras']['extras-' . $counter] : '';

      $form['extras']['extras-' . $counter] = array(
        '#type'          => 'textarea',
        '#title'         => $this->t('Label'),
        '#default_value' => $extra,
      );
    }

    // Notes about changes to T&C.
    $form['changes'] = array(
      '#type'        => 'details',
      '#title'       => $this->t('Explain Changes'),
      '#description' => $this->t('Explain what changes were made to the T&C since the last version. This will only be shown to users who accepted a previous version. Each line will automatically be shown as a bullet point.'),
      '#group'       => 'legal_tab',
    );

    $form['changes']['changes'] = array(
      '#type'          => 'textarea',
      '#title'         => $this->t('Changes'),
      '#default_value' => !empty($conditions['changes']) ? $conditions['changes'] : '',
    );

    $form['preview_section'] = [
      '#type'  => 'details',
      '#title' => $this->t('Preview'),
      '#open'  => FALSE,
    ];

    $form['preview_section']['preview'] = [
      '#type'       => 'container',
      '#tree'       => TRUE,
      '#attributes' => [
        'id' => ['legal-preview'],
      ],
    ];

    $form['preview_section']['trigger'] = array(
      '#type' => 'html_tag',
      '#tag'  => 'p',
    );

    $form['preview_section']['trigger']['preview_button'] = [
      '#type'  => 'button',
      '#value' => $this->t('Preview'),
      '#ajax'  => [
        'callback' => 'Drupal\legal\Form\LegalAdminTermsForm::preview',
        'event'    => 'click',
        'wrapper'  => 'legal-preview',
        'progress' => [
          'type'    => 'throbber',
          'message' => $this->t('Verifying entry...'),
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    // Preview request, don't save anything.
    if ($form_state->getTriggeringElement()['#value'] == $this->t('Preview')) {
      return;
    }

    $this->configFactory->getEditable('legal.settings')
      ->set('registration_terms_style', $values['registration_terms_style'])
      ->set('registration_container', $values['registration_container'])
      ->set('registration_modal_terms', $values['registration_modal_terms'])
      ->set('login_terms_style', $values['login_terms_style'])
      ->set('login_container', $values['login_container'])
      ->set('login_modal_terms', $values['login_modal_terms'])
      ->save();

    // If new conditions are different from current, enter in database.
    if ($this->legalConditionsUpdated($values)) {
      $version = legal_version($values['version_handling'], $values['language']);

      Conditions::create(array(
        'version'    => $version['version'],
        'revision'   => $version['revision'],
        'language'   => $values['language'],
        'conditions' => $values['conditions']['value'],
        'format'     => $values['conditions']['format'],
        'date'       => time(),
        'extras'     => serialize($values['extras']),
        'changes'    => $values['changes'],
      ))->save();

      drupal_set_message(t('Terms & Conditions have been saved.'));
    }

    parent::submitForm($form, $form_state);

    // @todo flush only the cache elements that need to be flushed.
    drupal_flush_all_caches();

  }

  /**
   * Check if T&Cs have been updated.
   *
   * @param array $new
   *   Newly created T&Cs.
   *
   * @return bool
   *   TRUE if the newly created T&Cs are different from the current T&Cs.
   */
  protected function legalConditionsUpdated(array $new) {

    $previous_same_language = legal_get_conditions($new['language']);
    $previous               = legal_get_conditions();

    if (($previous_same_language['conditions'] != $new['conditions']['value']) && ($previous['conditions'] != $new['conditions']['value'])) {
      return TRUE;
    }

    $count = count($new['extras']);

    for ($counter = 1; $counter <= $count; $counter++) {
      $previous_same_language_extra = isset($previous_same_language['extras']['extras-' . $counter]) ? $previous_same_language['extras']['extras-' . $counter] : '';
      $previous_extra               = isset($previous['extras']['extras-' . $counter]) ? $previous['extras']['extras-' . $counter] : '';

      if (($previous_same_language_extra != $new['extras']['extras-' . $counter]) && ($previous_extra != $new['extras']['extras-' . $counter])) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Preview section wrapper.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Returns the preview section form element.
   */
  public static function preview(array &$form, FormStateInterface $form_state) {

    $conditions       = $form_state->getValue('conditions');
    $extra_checkboxes = $form_state->getValue('extras');

    $element['preview_section']['preview'] = [
      '#type'       => 'container',
      '#tree'       => TRUE,
      '#attributes' => [
        'id' => ['legal-preview'],
      ],
    ];

    // Preview the registration form.
    $element['preview_section']['preview']['registration']['title'] = [
      '#type'  => 'html_tag',
      '#tag'   => 'h3',
      '#value' => t('Registration'),
    ];

    $style                                                         = $form_state->getValue('registration_terms_style');
    $modal                                                         = $form_state->getValue('registration_modal_terms');
    $element['preview_section']['preview']['registration']['form'] = LegalAdminTermsForm::previewForm($style, $conditions, $extra_checkboxes, $modal);

    // Override accept checkbox requirement on preview.
    $element['preview_section']['preview']['registration']['form']['legal_accept']['#required'] = FALSE;

    // Preview the login form.
    $element['preview_section']['preview']['login']['title'] = [
      '#type'  => 'html_tag',
      '#tag'   => 'h3',
      '#value' => t('Login'),
    ];

    $style                                                  = $form_state->getValue('login_terms_style');
    $modal                                                  = $form_state->getValue('login_modal_terms');
    $element['preview_section']['preview']['login']['form'] = LegalAdminTermsForm::previewForm($style, $conditions, $extra_checkboxes, $modal);

    // Override accept checkbox requirement on preview.
    $element['preview_section']['preview']['login']['form']['legal_accept']['#required'] = FALSE;

    return $element;
  }

  /**
   * Form elements to be displayed as a preview of the T&C form.
   *
   * @param int $style
   *   Style that T&Cs should be displayed as.
   * @param array $conditions
   *   'value' = T&C conditions content.
   *   'format' = Format to render content with.
   * @param array $extras
   *   Each item of array to be displayed as label of a checkbox.
   * @param bool $modal
   *   Display target of Page Link option as new window or a modal overlay.
   *
   * @return array
   *   Returns the contents of the preview form element.
   */
  public static function previewForm($style, array $conditions, array $extras, $modal) {

    switch ($style) {
      // Scroll box (CSS).
      case 1:
        $form['#attached']['library'][] = 'legal/css-scroll';

        $form['conditions'] = [
          '#type'       => 'html_tag',
          '#tag'        => 'div',
          '#attributes' => ['class' => ['legal-terms', 'legal-terms-scroll']],
        ];

        $form['conditions']['content'] = array(
          '#type'   => 'processed_text',
          '#text'   => $conditions['value'],
          '#format' => isset($conditions['format']) ? $conditions['format'] : filter_default_format(),
        );

        $accept_label = legal_accept_label();
        break;

      // HTML.
      case 2:
        $form['legal_accept']['#title'] = t('<strong>Accept</strong> Terms & Conditions of Use');

        $form['conditions'] = [
          '#type'       => 'html_tag',
          '#tag'        => 'div',
          '#attributes' => ['class' => ['legal-terms']],
        ];

        $form['conditions']['content'] = array(
          '#type'   => 'processed_text',
          '#text'   => $conditions['value'],
          '#format' => isset($conditions['format']) ? $conditions['format'] : filter_default_format(),
        );

        $accept_label = legal_accept_label();

        break;

      // Page Link.
      case 3:
        $form['#attached']['library'][] = 'legal/modal';
        $form['conditions']             = array('#markup' => '');
        $accept_label                   = legal_accept_label(TRUE, $modal);
        break;

      // Scroll box (HTML).
      default:
        $form['conditions'] = array(
          '#id'         => 'preview',
          '#name'       => 'preview',
          '#type'       => 'textarea',
          '#title'      => t('Terms & Conditions'),
          '#value'      => PlainTextOutput::renderFromHtml($conditions['value']),
          '#parents'    => array('legal'),
          '#rows'       => 10,
          '#attributes' => array('readonly' => 'readonly'),
        );

        $accept_label = legal_accept_label();
    }

    // Override additional checkboxes in preview.
    if (!empty($extras)) {

      foreach ($extras as $key => $label) {
        if (!empty($label)) {
          $form[$key] = array(
            '#type'  => 'checkbox',
            '#title' => Html::escape($label),
          );
        }
      }
    }

    $form['legal_accept'] = array(
      '#type'          => 'checkbox',
      '#title'         => $accept_label,
      '#default_value' => 0,
      '#weight'        => 50,
      '#required'      => TRUE,
    );

    return $form;
  }

}
