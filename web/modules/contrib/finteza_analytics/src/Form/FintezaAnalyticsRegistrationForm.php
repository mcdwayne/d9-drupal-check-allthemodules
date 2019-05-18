<?php

namespace Drupal\finteza_analytics\Form;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatter;
use Egulias\EmailValidator\EmailValidator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure finteza_analytics settings for this site.
 */
class FintezaAnalyticsRegistrationForm extends ConfigFormBase {

  protected $fintezaSettings;
  protected $dateFormatter;
  protected $emailValidator;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, DateFormatter $date_formatter, EmailValidator $email_validator) {
    $this->fintezaSettings = $config_factory;
    $this->dateFormatter = $date_formatter;
    $this->emailValidator = $email_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('date.formatter'),
      $container->get('email.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getWebsiteId() {
    return $this->fintezaSettings->get('finteza_analytics.settings')->get('tracking_settings.finteza_analytics_website_id');
  }

  /**
   * {@inheritdoc}
   */
  public function saveWebsiteId($website) {
    return $this->fintezaSettings->getEditable('finteza_analytics.settings')->set('tracking_settings.finteza_analytics_website_id', $website)->save();
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormId().
   */
  public function getFormId() {
    return 'finteza_analytics_registration';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'finteza_analytics.settings',
    ];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state, $action = NULL) {

    $website_id = $this->getWebsiteId();

    if (empty($website_id)) {

      $form['registration'] = [
        '#type' => 'details',
        '#title' => $this->t('Registration'),
      ];

      $utc_offset = $this->dateFormatter->format(time(), 'custom', 'Z') / 60 * -1;

      $form['registration']['finteza_analytics_offset'] = [
        '#value' => $utc_offset,
        '#type' => 'hidden',
      ];

      $form['registration']['finteza_analytics_name'] = [
        '#required' => TRUE,
        '#title' => $this->t('Your full name'),
        '#type' => 'textfield',
      ];

      $form['registration']['finteza_analytics_company'] = [
        '#required' => FALSE,
        '#title' => $this->t('Company'),
        '#type' => 'textfield',
      ];

      $form['registration']['finteza_analytics_email'] = [
        '#required' => TRUE,
        '#title' => $this->t('Email'),
        '#type' => 'textfield',
      ];

      $form['registration']['finteza_analytics_password'] = [
        '#required' => TRUE,
        '#title' => $this->t('Password'),
        '#type' => 'password',
      ];

      $form['registration']['finteza_analytics_policy'] = [
        '#type' => 'checkbox',
        "#title" => $this->t(
          "I have read and understood <a href='@privacy_url' target='_blank'>privacy and data protection policy</a>",
          finteza_analytics_urls()
        ),
        '#required' => TRUE,
      ];

      $form['registration']['finteza_analytics_agreement'] = [
        '#type' => 'checkbox',
        "#title" => $this->t(
          "I agree to <a href='@agreement_url' target='_blank'>subscription service agreement</a>",
          finteza_analytics_urls()
        ),
        '#required' => TRUE,
      ];

      $form['registration']['save'] = [
        '#type' => 'submit',
        '#value' => $this->t('Register'),
      ];

      $form['registration']['save']['#attributes']['class'][] = 'button--primary';

    }

    $form['getting_started'] = [
      '#type' => 'details',
      '#title' => $this->t('Getting Started'),
    ];

    $output = '<p>';
    $output .= $this->t(
      "How to use the plugin:<br /><br />&nbsp;1. <a href='@registration_url' target='_blank'>Register</a> an account in Finteza<br />&nbsp;2. Save the generated website ID in the settings<br />&nbsp;3. Configure tracking of link click events<br />&nbsp;4. View your website visit statistics in the <a href='@dashboard_url' target='_blank'>Finteza dashboard</a>",
      finteza_analytics_urls()
    );
    $output .= '</p>';

    $form['getting_started']['tracking_title'] = [
      '#markup' => $output,
    ];

    $form['how_to'] = [
      '#type' => 'details',
      '#title' => $this->t('How to track clicks'),
    ];

    $output = '<p>';
    $output .= $this->t(
      "To enable tracking of link click events in your website:<br />&nbsp;<br />&nbsp;1. Open a website page or message for editing<br />&nbsp;2. In the text editor, select the link element and click on the Finteza button<br />&nbsp;3. Enter the click event name to be used in statistics<br />&nbsp;4. View event statistics in the <a href='@dashboard_url' target='_blank'>Finteza dashboard</a><br /><p>To send events correctly, disable the 'Limit allowed HTML tags and fix incorrect HTML' option in the editor profile.</p>",
      finteza_analytics_urls()
    );
    $output .= '</p>';

    $form['how_to']['tracking_title'] = [
      '#markup' => $output,
    ];

    $form['stats'] = [
      '#type' => 'details',
      '#title' => $this->t('Where to view statistics'),
    ];

    $output = '<p>';
    $output .= $this->t(
      "Statistics on your website visits is collected in the <a href='@dashboard_url' target='_blank'>Finteza dashboard</a>. Log in using the email and password specified during registration. If you forgot the password, use the <a href='@password_recovery_url' target='_blank'>password recovery</a> page",
      finteza_analytics_urls()
    );
    $output .= '</p>';

    $form['stats']['tracking_title'] = [
      '#markup' => $output,
    ];

    unset($output);

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if (!$this->emailValidator->isValid($form_state->getValue('finteza_analytics_email'))) {
      $form_state->setErrorByName('finteza_analytics_email', $this->t('Please enter a valid email address.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $message = "";
    $registration = finteza_analytics_register_website(
      [
        'website'    => $this->getRequest()->getSchemeAndHttpHost(),
        'email'      => $form_state->getValue('finteza_analytics_email'),
        'password'   => $form_state->getValue('finteza_analytics_password'),
        'fullname'   => $form_state->getValue('finteza_analytics_name'),
        'company'    => $form_state->getValue('finteza_analytics_company'),
        'utc_offset' => $form_state->getValue('finteza_analytics_offset'),
      ]
    );

    if ($registration && $registration['status'] === 1) {

      $this->saveWebsiteId($registration['website']);

      $message = $this->t("<b>Registration completed successfully.</b> Please activate your account using the link sent to your email.");

      Cache::invalidateTags(['FINTEZA_ANALYTICS_HELP']);

      drupal_set_message($message, 'status');
      parent::submitForm($form, $form_state);
    }
    else {
      $error = $registration && isset($registration['error']) ? $registration['error'] : NULL;

      $form_state->setRebuild();

      switch ($error) {
        case 1:
          $message = $this->t('An account with this email address already exists');
          break;

        case 2:
          $message = $this->t('Invalid password');
          break;

        case 3:
          $message = $this->t('Invalid email address');
          break;

        case 4:
          $message = $this->t('A company with this name already exists');
          break;

        case 5:
          $message = $this->t('Invalid website address');
          break;

        case 6:
          $message = $this->t('Registration limit exceeded');
          break;

        default:
          $message = $this->t('Registration error');
      }

      drupal_set_message($message, 'error');
    }
  }

}
