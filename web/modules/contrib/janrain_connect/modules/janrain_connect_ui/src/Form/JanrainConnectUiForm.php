<?php

namespace Drupal\janrain_connect_ui\Form;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Drupal\janrain_connect\Constants\JanrainConnectWebServiceConstants;
use Drupal\janrain_connect_ui\Service\JanrainConnectUiUsers;
use Drupal\janrain_connect_ui\Event\JanrainConnectUiEvents;
use Drupal\janrain_connect_ui\Event\JanrainConnectUiAlterEvent;
use Drupal\janrain_connect_ui\Event\JanrainConnectUiSubmitEvent;
use Drupal\janrain_connect_ui\Service\JanrainConnectUiFormService;
use Symfony\Component\Yaml\Yaml;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Component\Utility\Html;

/**
 * Form for configure messages.
 */
class JanrainConnectUiForm extends FormBase {

  /**
   * JanrainConnectUsers.
   *
   * @var \Drupal\janrain_connect_ui\Service\JanrainConnectUiUsers
   */
  private $janrainUsers;

  /**
   * Config Factory for Janrain Settings.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  private $config;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Janrain Connect Form Service.
   *
   * @var \Drupal\janrain_connect_ui\Service\JanrainConnectUiFormService
   */
  private $janrainConnectFormService;

  /**
   * Janrain Connect Route Match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * Request object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Symfony session handler.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  private $session;

  /**
   * Form id.
   *
   * @var string
   */
  public $formId = '';

  /**
   * JanrainConnectToken.
   *
   * @var \Drupal\janrain_connect_ui\Service\JanrainConnectUiTokenService
   */
  protected $janrainConnectUiTokenService;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactory $config_factory,
    JanrainConnectUiFormService $janrain_connect_form_service,
    EventDispatcherInterface $event_dispatcher,
    JanrainConnectUiUsers $janrain_users,
    CurrentRouteMatch $route_match,
    RequestStack $request_stack,
    AccountProxy $current_user,
    Session $session
  ) {
    $this->config = $config_factory->get('janrain_connect.settings');
    $this->janrainConnectFormService = $janrain_connect_form_service;
    $this->eventDispatcher = $event_dispatcher;
    $this->janrainUsers = $janrain_users;
    $this->routeMatch = $route_match;
    $this->requestStack = $request_stack;
    $this->currentUser = $current_user;
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('janrain_connect_ui.form'),
      $container->get('event_dispatcher'),
      $container->get('janrain_connect_ui.users'),
      $container->get('current_route_match'),
      $container->get('request_stack'),
      $container->get('current_user'),
      $container->get('session')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    $form_id = !empty($this->routeMatch->getParameter('form_id')) ?
      $this->routeMatch->getParameter('form_id') : $this->formId;

    return 'janrain_connect_form_' . mb_strtolower($form_id);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $form_id = '') {

    // Set Form Id.
    $form['#attributes'] = [
      'id' => 'janrain_connect_form_' . $form_id,
    ];

    // If it is the sign in form and user is logged in, we don't display this
    // form. We also don't display the registration forms if the user is
    // logged in.
    // @Todo: Improve user journey.
    // @codingStandardsIgnoreStart

    if ($this->shouldAccessForm($form_id)) {
    /**
     * $url = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
     * return new RedirectResponse($url);
     * @codingStandardsIgnoreEnd
     */
    }

    // We need check if is test and don't call janrain service.
    // @codingStandardsIgnoreLine
    $path = \Drupal::service('path.current')->getPath();
    $path = explode('/', $path);

    // Default value.
    $data_for_tests = FALSE;

    if (!empty(end($path) && end($path) == 'test')) {
      $data_for_tests = TRUE;
    }

    $form_data = $this->janrainConnectFormService->getForm($form_id, $data_for_tests);

    if (empty($form_data)) {
      throw new NotFoundHttpException();
    }

    // Check form status.
    self::checkFormStatus($form_id);

    $form_state->set('form_id', $form_id);

    $configuration_fields = $this->config->get('configuration_fields');

    $configuration_fields = Yaml::parse($configuration_fields);

    $config_form_fields = FALSE;

    if (!empty($configuration_fields[$form_id])) {
      $config_form_fields = $configuration_fields[$form_id];
    }

    $fields = isset($form_data['fields']) ? $form_data['fields'] : [];
    foreach ($fields as $key => $field) {

      $field_id = $key;
      $config_field = FALSE;

      if (!empty($config_form_fields[$field_id])) {
        $config_field = $config_form_fields[$field_id];
      }

      if (isset($config_field['show']) && $config_field['show'] == FALSE) {
        continue;
      }

      $label = '';
      if (!empty($field['label'])) {
        // All words must be translatable. @codingStandardsIgnoreLine
        $label = $this->t((string) $field['label']);
      }

      if (isset($config_field['label'])) {
        // All messages from Janrain must have translation. @codingStandardsIgnoreLine
        $label = $this->t($config_field['label']);
      }

      $type = 'textfield';
      $required = FALSE;
      $options = FALSE;

      if (!empty($config_field['validations']['messages']['match'])) {
        $match = $config_field['validations']['messages']['match'];
        $field['validations']['messages']['match'] = $match;
      }

      if (!empty($field['validations']['required'])) {
        $required = $field['validations']['required'];
        $form[$field_id]['#attributes']['class'][] = 'janrain-field-required';
        $form[$field_id]['#prefix'] = '<div class="janrain-field-required">';
        $form[$field_id]['#suffix'] = '</div>';
      }

      if (!empty($field['validations']['maxLength'])) {
        $maxlength = $field['validations']['maxLength'];
        $form[$field_id]['#maxlength'] = $maxlength;
      }

      $type = $field['type'];

      if (!empty($config_field['type'])) {
        $type = $config_field['type'];
      }

      $field_mapping = $this->config->get('field_mapping');

      if (!empty($field_mapping[$type])) {
        $type = $field_mapping[$type];
      }

      $options = $field['options'];

      $classes = $field['classes'];

      $form[$field_id]['#type'] = $type;
      $form[$field_id]['#title'] = $label;
      $form[$field_id]['#required'] = $required;

      if (isset($config_field['required-drupal']) && $config_field['required-drupal'] === FALSE) {
        unset($form[$field_id]['#required']);
      }

      if (isset($config_field['required-drupal']) && $config_field['required-drupal'] === TRUE) {
        $form[$field_id]['#required'] = TRUE;
      }

      $form[$field_id]['#attributes']['class'][] = 'janrain-field';
      $form[$field_id]['#attributes']['class'][] = 'janrain-field-' . $field_id;

      if (!empty($config_field['weight'])) {

        $weight = $config_field['weight'];

        $form[$field_id]['#weight'] = $weight;

      }

      if (!empty($options)) {
        foreach ($options as &$option) {
          if (!empty($option)) {
            // All messages from Janrain must have translation. @codingStandardsIgnoreLine
            $option = $this->t($option);
          }
        }
        $form[$field_id]['#options'] = $options;
      }

      if ($classes) {
        $form[$field_id]['#attributes']['class'] = $classes;
      }

      if ($type === 'date') {
        $form[$field_id]['#attributes']['type'] = $type;
      }

      if (isset($field['placeholder'])) {
        // All words must be translatable. @codingStandardsIgnoreLine
        $form[$field_id]['#attributes']['placeholder'] = $this->t((string) $field['placeholder']);
      }

      if (!empty($field['description'])) {
        // All messages from Janrain must have translation. @codingStandardsIgnoreLine
        $form[$field_id]['#description'] = (string) $this->t($field['description']);
        if (!empty($config_field['description'])) {
          // All messages from Janrain must have translation. @codingStandardsIgnoreLine
          $form[$field_id]['#description'] = (string) $this->t($config_field['description']);
        }
      }
    }

    $submit_name = $this->t('Submit');
    try {
      $config_form_data = Yaml::parse(
        $this->config->get('configuration_forms',
        Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE
      ));
    }
    catch (\Exception $e) {
      $config_form_data = $this->config->get('configuration_forms');
    }

    if (!empty($config_form_data[$form_id]['submit_button'])) {
      // Submit button should be translatable @codingStandardsIgnoreLine.
      $submit_name = $this->t($config_form_data[$form_id]['submit_button']);
    }

    // Fill default values.
    $this->fillDefaultValues($form, $form_id);

    // Set Read Only Fields.
    $this->setReadOnlyFields($form, $form_id);

    $ajax_settings = $this->getJanrainConnectUiAjaxSettings($form_id);

    if (!(isset($ajax_settings['use_ajax']) && $ajax_settings['use_ajax'] == FALSE)) {
      // The status messages that will contain any form errors.
      $form['status_messages'] = [
        '#type' => 'status_messages',
      ];
    }

    // Add class to form.
    $form['#attributes']['class'][] = 'janrain-form';
    $form['#attributes']['class'][] = 'janrain_connect_form_' . $form_id;

    // Create ajax submit button.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $submit_name,
      '#id' => 'edit-submit-' . Html::getId($form_id),
    ];

    if (!empty($config_form_data[$form_id]['submit_classes'])) {
      $submit_classes = $config_form_data[$form_id]['submit_classes'];
      $form['submit']['#attributes']['class'][] = $submit_classes;
    }

    $this->addJanrainConnectUiFormAjax($form_id, $form);

    // Allow others to interact with the form.
    $engage_token = $this->requestStack->getCurrentRequest()->request->get('token');
    if ($engage_token) {
      $this->session->set('janrain_connect_social_engage_token', $engage_token);
    }
    else {
      $this->session->get('janrain_connect_social_engage_token');
    }

    $event = new JanrainConnectUiAlterEvent($form_id, ['token' => $engage_token], $form, $form_state);
    $this->eventDispatcher->dispatch(JanrainConnectUiEvents::EVENT_ALTER, $event);

    // Receive possible alterations back from event subscribers.
    $form = $event->getForm();
    $form_state = $event->getFormState();

    // Something happened and we must redirect the user to another page.
    // This is used for merge accounts.
    $redirect = $event->getRedirect();
    if ($redirect) {
      return new RedirectResponse($redirect->toString());
    }

    return $form;
  }

  /**
   * Get Ajax Success Command.
   *
   * This method gets the ajax settings and returns the Redirect or Modal
   * Command.
   *
   * @param string $form_id
   *   Form Id.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form State.
   *
   * @return CommandInterface
   *   Command Interface.
   */
  private function getAjaxSuccessCommand(string $form_id, FormStateInterface $form_state) {
    $ajax_settings = $this->getJanrainConnectUiAjaxSettings($form_id);
    $ajax_success_type = $ajax_settings['success_type'];

    $command = '';

    switch ($ajax_success_type) {

      case 'redirect':
        $success_url = isset($ajax_settings['success_url']) ? $ajax_settings['success_url'] : '/';
        $url = Url::fromUserInput($success_url);
        $command = new RedirectCommand($url->toString());
        break;

      case 'modal':
      default:
        $success_title = isset($ajax_settings['success_title']) ? $ajax_settings['success_title'] : 'Success';

        $messages = drupal_get_messages('status');
        $success_message = implode('</br>', $messages['status']);

        // Set modal options.
        $options['classes'] = [
          'ui-dialog' => 'janrain-connect-' . strtolower($form_id) . '-thank-you',
        ];

        if (isset($ajax_settings['modal_width'])) {
          $options['width'] = $ajax_settings['modal_width'];
        }

        // The result should have translate. @codingStandardsIgnoreLine
        $command = new OpenModalDialogCommand($this->t($success_title), $success_message, $options);
    }

    return $command;

  }

  /**
   * Get Janrain Ui Ajax Settings.
   *
   * @param string $form_id
   *   Form ID.
   *
   * @return string
   *   Return value.
   */
  private function getJanrainConnectUiAjaxSettings(string $form_id) {
    $configuration_forms = $this->config->get('configuration_forms');
    $ajax_settings = Yaml::parse($configuration_forms);

    if (!empty($ajax_settings[$form_id])) {
      return $ajax_settings[$form_id];
    }

    return '';
  }

  /**
   * Add Ajax behavior for Janrain Connect Ui Form .
   *
   * @param string $form_id
   *   Form ID.
   * @param array $form
   *   Form.
   */
  private function addJanrainConnectUiFormAjax(string $form_id, array &$form) {
    $ajax_settings = $this->getJanrainConnectUiAjaxSettings($form_id);

    if (empty($ajax_settings['use_ajax'])) {
      return;
    }

    // Create a wrapper for the form.
    $form['#prefix'] = "<div id='$form_id' class='janrain_connect_ajax_form'>";
    $form['#suffix'] = '</div>';

    // Create ajax submit button.
    $form['submit']['#attributes']['class'][] = 'use-ajax';
    $form['submit']['#ajax'] = [
      'callback' => [$this, 'submitJanrainConnectUiFormAjax'],
      'event' => 'click',
      'progress' => [
        'type' => 'bar',
        'message' => $this->t('Please wait...'),
      ],
    ];

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

  }

  /**
   * AJAX callback handler that displays any errors or a success message.
   */
  public function submitJanrainConnectUiFormAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // If there are any form errors, re-display the form.
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#' . $form_state->get('form_id'), $form));
    }
    else {
      $form_id = $form_state->get('form_id');
      $return_command = $this->getAjaxSuccessCommand($form_id, $form_state);
      $response->addCommand($return_command);
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // If has other errors, this validation avoid submit the request to janrain.
    if ($form_state->hasAnyErrors()) {
      return;
    }

    $this->submitFormToJanrain($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitFormToJanrain(array &$form, FormStateInterface $form_state) {

    $form_id = '';
    $data = [];
    $drupal_internal_form_keys = [
      'submit',
      'form_build_id',
      'form_token',
      'form_id',
      'op',
    ];

    if (!empty($form_state->get('form_id'))) {
      $form_id = $form_state->get('form_id');
    }

    if (!empty($form_state->getValues())) {

      $data = $form_state->getValues();

      // Remove Drupal internal form keys.
      foreach ($drupal_internal_form_keys as $key) {
        unset($data[$key]);
      }
    }

    foreach ($data as $key => $current_data) {

      if (empty($form[$key]['#attributes']['class'])) {
        continue;
      }

      $classes = $form[$key]['#attributes']['class'];

      if (!empty($current_data) && in_array('dateselect', $classes)) {

        $field_date_select = $current_data;

        $field_date_select_array = explode("-", $field_date_select);

        $dateselect_day = $field_date_select_array[2];
        $dateselect_month = $field_date_select_array[1];
        $dateselect_year = $field_date_select_array[0];

        $data[$key] = [
          JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FIELD_DATESELECT_DAY => $dateselect_day,
          JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FIELD_DATESELECT_MONTH => $dateselect_month,
          JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FIELD_DATESELECT_YEAR => $dateselect_year,
        ];
      }
    }

    if (empty($data['birthdate'])) {
      unset($data['birthdate']);
    }

    $event = new JanrainConnectUiSubmitEvent($form_id, $data, $form, $form_state);

    $this->eventDispatcher->dispatch(JanrainConnectUiEvents::EVENT_SUBMIT, $event);
    $this->eventDispatcher->dispatch(JanrainConnectUiEvents::EVENT_SUBMIT . '.' . $event->getFormId(), $event);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Method to fill default values.
   */
  public function fillDefaultValues(&$form, $form_id) {

    switch ($form_id) {

      case JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_EDIT_PROFILE;
        $this->janrainUsers->fillUserValuesUpdateProfileForm($form, $form_id);
        break;

      default:
        break;
    }
  }

  /**
   * Method to set Read Only Fields.
   */
  public function setReadOnlyFields(&$form, $form_id) {

    $readonly_fields = $this->config->get('readonly_fields');

    if (empty($readonly_fields[$form_id])) {
      return FALSE;
    }

    foreach ($readonly_fields[$form_id] as $field_key) {
      $form[$field_key]['#attributes']['disabled'] = 'disabled';
    }
  }

  /**
   * Method to check Form Status.
   */
  public function checkFormStatus($form_id, $show_message = TRUE) {

    if (empty($this->config->get('disabled_forms'))) {
      return TRUE;
    }

    $disabled_forms = $this->config->get('disabled_forms');

    if (in_array($form_id, $disabled_forms)) {

      if ($show_message) {

        // Check if user is admin. @codingStandardsIgnoreLine
        $is_admin = \Drupal::service('router.admin_context')->isAdminRoute();

        if (empty($is_admin)) {
          return FALSE;
        }

        drupal_set_message($this->t('The form: @form may not work correctly because it is not fully implemented.', [
          '@form' => $form_id,
        ]), 'warning', TRUE);

        drupal_set_message($this->t('For more information access our <a href="@url_documentation" target="blank">official Janrain documentation</a>.', [
          '@url_documentation' => 'https://www.drupal.org/docs/8/modules/drupal-connector-for-janrain-identity-cloud/forms-as-block',
        ]), 'warning', TRUE);

      }

      return FALSE;

    }
  }

  /**
   * If user can access this form.
   *
   * @param string $form_id
   *   The Form ID.
   *
   * @return bool
   *   True if user has access. False otherwise.
   */
  private function shouldAccessForm(string $form_id) {
    $anonymous_forms = $form_id == JanrainConnectWebServiceConstants::JANRAIN_CONNECT_SOCIAL_FORM_SIGNIN ||
    $form_id == JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_REGISTRATION ||
    $form_id == JanrainConnectWebServiceConstants::JANRAIN_CONNECT_FORM_SIGNIN;

    return $anonymous_forms && $this->currentUser->isAuthenticated();
  }

}
