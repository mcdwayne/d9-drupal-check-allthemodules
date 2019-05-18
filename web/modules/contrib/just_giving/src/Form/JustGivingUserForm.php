<?php

namespace Drupal\just_giving\Form;

use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\just_giving\JustGivingAccount;
use Drupal\just_giving\JustGivingCountries;
use Drupal\just_giving\JustGivingRequest;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\AjaxResponse;

/**
 * Class JustGivingUserForm.
 */
class JustGivingUserForm extends FormBase {

  /**
   * Drupal\just_giving\JustGivingClient definition.
   *
   * @var \Drupal\just_giving\JustGivingClient
   */
  protected $justGivingRequest;

  protected $justGivingAccount;

  protected $justGivingCountries;

  /**
   * JustGivingUserForm constructor.
   *
   * @param \Drupal\just_giving\JustGivingRequest $jg_request
   */
  public function __construct(JustGivingRequest $jg_request, justGivingAccount $js_account, JustGivingCountries $js_countries) {
    $this->justGivingRequest = $jg_request;
    $this->justGivingAccount = $js_account;
    $this->justGivingCountries = $js_countries;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('just_giving.request'),
      $container->get('just_giving.account'),
      $container->get('just_giving.countries')
      );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'just_giving_user_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Account check variable for conditional form state.
    $accCheck = $form_state->get('acc_check');

    switch ($accCheck) {
      case NULL:
        $message = 'Please submit your email to register a fundraising page.';
        break;
      case 'existing_user':
        $message = 'Your Just Giving account exists, please submit your Just Giving password to register for the event.';
        break;
      case 'new_user':
        $message = 'A Just Giving account doesn\'t exist for this email, please complete the form to create an account and register for the event.';
        break;
    }

    $form['#prefix'] = '<div id="justgiving-ajax-wrapper">' . $message;
    $form['#suffix'] = '</div>';

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#prefix' => '<div id="user-email-result"></div>',
      '#required' => TRUE,
    ];

    if ($accCheck === NULL) {
      $form['check_email'] = [
        '#type' => 'submit',
        '#value' => t('Submit Email'),
        '#id' => 'submit_email',
        '#submit' => ['::checkUserEmailValidation'],
        '#ajax' => [
          'callback' => '::submitEmail',
          'effect' => 'fade',
          'wrapper' => 'justgiving-ajax-wrapper',
          'progress' => [
            'type' => 'throbber',
            'message' => t('Checking Email..'),
          ],
        ],
      ];
    }

    // Account exists, display password and submit button.
    if ($accCheck == 'existing_user') {
      $form['password'] = [
        '#type' => 'password',
        '#title' => $this->t('Password'),
      ];
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Register Page'),
        '#id' => 'register_page',
        '#ajax' => [
          'callback' => '::ajaxSubmit',
          'wrapper' => 'justgiving-ajax-wrapper',
          'progress' => [
            'type' => 'throbber',
            'message' => t('Checking details...'),
          ],
        ],
      ];

      $form['password_submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Password Reminder'),
        '#id' => 'password_submit',
        '#ajax' => [
          'callback' => '::ajaxReminder',
          'wrapper' => 'justgiving-ajax-wrapper',
          'progress' => [
            'type' => 'throbber',
            'message' => t('Checking details...'),
          ],
        ],
      ];
    }

    // Account doesn't exist, display signup form.
    if ($accCheck == 'new_user') {
      $form['title'] = [
        '#type' => 'select',
        '#title' => $this->t('Title'),
        '#options' => [
          '0' => $this->t('Please Select a title'),
          'Mrs' => $this->t('Mrs'),
          'Mr' => $this->t('Mr'),
          'Miss' => $this->t('Miss'),
          'Ms' => $this->t('Ms'),
          'Mx' => $this->t('Mx'),
          'Dr' => $this->t('Dr'),
          'Rev' => $this->t('Rev'),
          'Other' => $this->t('Other'),
        ],
        '#size' => 1,
      ];
      $form['first_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('First Name'),
        '#maxlength' => 64,
        '#size' => 64,
        '#required' => TRUE,
      ];
      $form['last_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Last Name'),
        '#maxlength' => 64,
        '#size' => 64,
        '#required' => TRUE,
      ];

      $form['password'] = [
        '#type' => 'password',
        '#required' => TRUE,
      ];
      $form['password'] = [
        '#type' => 'password_confirm',
      ];

      $form['first_line_of_address'] = [
        '#type' => 'textfield',
        '#title' => $this->t('First Line of Address'),
        '#maxlength' => 64,
        '#size' => 64,
        '#required' => TRUE,
      ];
      $form['second_line_of_address'] = [
        '#type' => 'hidden',
        '#title' => $this->t('Second Line of Address'),
        '#maxlength' => 64,
        '#size' => 64,
      ];
      $form['town_or_city'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Town or City'),
        '#maxlength' => 64,
        '#size' => 64,
        '#required' => TRUE,
      ];
      $form['county_or_state'] = [
        '#type' => 'hidden',
        '#title' => $this->t('County or State'),
        '#maxlength' => 64,
        '#size' => 64,
      ];

      // Prevents page errors when module is not configured.
      if ($this->justGivingCountries->getCountriesFormList()) {
        $countries = $this->justGivingCountries->getCountriesFormList();
      }
      else {
        $countries = ['0' => $this->t('Please Select a Country')];
      }

      $form['country'] = [
        '#type' => 'select',
        '#title' => $this->t('Country'),
        '#options' => $countries,
      ];
      $form['postcode'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Postcode'),
        '#maxlength' => 64,
        '#size' => 64,
        '#required' => TRUE,
      ];
      $form['accept_terms_and_conditions'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Accept Just Giving Terms and Conditions'),
        '#field_prefix' => '<a href="https://www.justgiving.com/info/terms-of-service-versions/terms-of-service-v10" target="_blank">Just Giving Terms Conditions</a><br />',
        '#maxlength' => 64,
        '#size' => 64,
        '#required' => TRUE,
      ];
      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Register'),
        '#ajax' => [
          'callback' => '::ajaxSubmit',
          'wrapper' => 'justgiving-ajax-wrapper',
          'progress' => [
            'type' => 'throbber',
            'message' => t('Verifying entry...'),
          ],
        ],
      ];
    }

    $form_state->setCached(FALSE);
    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function submitEmail(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function checkUserEmailValidation(array &$form, FormStateInterface $form_state) {
    $emailCheck = $this->justGivingAccount->checkAccountExists($form_state->getValue('email'));
    if ($emailCheck == TRUE) {
      $form_state->set('acc_check', 'existing_user');
    }
    elseif ($emailCheck == FALSE) {
      $form_state->set('acc_check', 'new_user');
    }
    $form_state->setRebuild();
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // TODO setup validation to provide inline error messages.

    $triggerdElement = $form_state->getTriggeringElement();
    if ($form_state->get('acc_check') == 'existing_user'
      && $triggerdElement['#id'] == "register_page") {
      $validateAcc = $this->justGivingAccount->validateAccount(
        $form_state->getValue('email'),
        $form_state->getValue('password')
      );
      if ($validateAcc->isValid == FALSE) {
        $form_state->setErrorByName('password', $this->t('Login details are wrong please try again.'));
      }
    }

    if ($form_state->get('acc_check') == 'new_user') {
      // Choose a country.
      if ($form_state->getValue('country') == '0') {
        $form_state->setErrorByName('country', $this->t('Please choose a country.'));
      }
      // Choose a title.
      if ($form_state->getValue('title') == '0') {
        $form_state->setErrorByName('title', $this->t('Please choose a title.'));
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
    if ($form_state::hasAnyErrors() == TRUE) {
      return $form;
    }
    else {
      $justGivingResponse = $this->justGivingRequest->createFundraisingPage($form_state);
      $response = new AjaxResponse();
      $response->addCommand(new HtmlCommand('#justgiving-ajax-wrapper', $justGivingResponse));
      return $response;
    }
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array|\Drupal\Core\Ajax\AjaxResponse
   */
  public function ajaxReminder(array &$form, FormStateInterface $form_state) {
    $emailCheck = $this->justGivingAccount->checkAccountExists($form_state->getValue('email'));
    if ($emailCheck == TRUE) {
      $emailReminder = $this->justGivingAccount->passwordReminder($form_state->getValue('email'));
      if ($emailReminder) {
        drupal_set_message($this->t('Password reminder sent.'));
      }
    }
    elseif ($emailCheck == FALSE) {
      drupal_set_message($this->t('This email does not have a registered account, please submit it again.'));
    }
    $response = new AjaxResponse();
    $currentURL = Url::fromRoute('<current>');
    $response->addCommand(new RedirectCommand($currentURL->toString()));
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    return $form;
  }

}


