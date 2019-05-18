<?php

namespace Drupal\dropshark\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\dropshark\Collector\CollectorManager;
use Drupal\dropshark\Request\RequestInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends FormBase {

  /**
   * DropShark collector manager.
   *
   * @var \Drupal\dropshark\Collector\CollectorManager
   */
  protected $collectorManager;

  /**
   * Request handler.
   *
   * @var \Drupal\dropshark\Request\RequestInterface
   */
  protected $request;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs the settings form.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \Drupal\dropshark\Request\RequestInterface $request
   *   Request handler.
   * @param \Drupal\dropshark\Collector\CollectorManager $collectorManager
   *   Collector manager.
   */
  public function __construct(StateInterface $state, RequestInterface $request, CollectorManager $collectorManager) {
    $this->state = $state;
    $this->request = $request;
    $this->collectorManager = $collectorManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state'),
      $container->get('dropshark.request'),
      $container->get('plugin.manager.dropshark_collector')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if ($this->state->get('dropshark.site_token')) {
      $form = $this->statusForm($form);
    }
    else {
      $form = $this->registrationForm($form);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dropshark_admin_config';
  }

  /**
   * Build the registration form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   *
   * @return array
   *   The form structure.
   */
  protected function registrationForm(array $form) {
    $form['instructions']['#markup'] = $this->t("In order to register your site with the DropShark service, you'll need to enter your credentials and site identifier.");
    $form['instructions']['#prefix'] = '<p>';
    $form['instructions']['#suffix'] = '</p>';

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#description' => $this->t("Enter the email address which you've registered for DropShark."),
      '#required' => TRUE,
    ];

    $form['password'] = [
      '#type' => 'password',
      '#title' => $this->t('DropShark password'),
      '#description' => $this->t('Enter your DropShark password.'),
      '#required' => TRUE,
    ];

    $form['site_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site ID'),
      '#description' => $this->t('Find your site identifier on your DropShark dashboard.'),
      '#default_value' => $this->state->get('drop_shark.site_id'),
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#value' => $this->t('Register site'),
      '#type' => 'submit',
      '#element_validate' => [
        [$this, 'registrationFormValidate'],
      ],
      '#submit' => [
        [$this, 'registrationFormSubmit'],
      ],
    ];

    return $form;
  }

  /**
   * Validation for registration form.
   */
  public function registrationFormValidate(&$element, FormStateInterface $form_state) {
    $result = $this->request->getToken(
      $form_state->getValue('email'),
      $form_state->getValue('password'),
      $form_state->getValue('site_id')
    );

    if (!empty($result->data->token)) {
      drupal_set_message($this->t('Your site has been registered with DropShark.'));
      $form_state->set('site_token', $result->data->token);
      $form_state->set('site_id', $form_state->getValue('site_id'));
    }
    else {
      $message = $this->t('Unable to register your site, please check your information and try again. If you continue to experience issues, please contact DropShark support.');
      $form_state->setErrorByName('', $message);
    }
  }

  /**
   * Submit handler for registration form.
   */
  public function registrationFormSubmit(array &$form, FormStateInterface $form_state) {
    $this->state->setMultiple([
      'dropshark.site_id' => $form_state->get('site_id'),
      'dropshark.site_token' => $form_state->get('site_token'),
    ]);
    $this->collectorManager->collect(['all'], [], TRUE);
  }

  /**
   * Build the status form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   *
   * @return array
   *   The form structure.
   */
  protected function statusForm(array $form) {
    $params['@link'] = Link::fromTextAndUrl('DropShark', Url::fromUri('http://app.dropshark.io'))->toString();
    $form['instructions']['#markup'] = $this->t('Your site is registered and will send data to DropShark. Log in to @link to analyze your data.', $params);
    $form['instructions']['#prefix'] = '<p>';
    $form['instructions']['#suffix'] = '</p>';

    $form['status'] = [
      '#type' => 'item',
      '#title' => $this->t('Site ID'),
      '#plain_text' => $this->state->get('dropshark.site_id'),
    ];

    $form['check'] = [
      '#title' => $this->t('Check connection'),
      '#type' => 'fieldset',
    ];
    $form['check']['instructions']['#markup'] = $this->t('Verify that your site is able to submit data to DropShark.');
    $form['check']['instructions']['#prefix'] = '<p>';
    $form['check']['instructions']['#suffix'] = '</p>';
    $form['check']['submit'] = [
      '#value' => $this->t('Check'),
      '#type' => 'submit',
      '#submit' => [
        [$this, 'statusFormCheckSubmit'],
      ],
    ];

    $form['reset'] = [
      '#title' => $this->t('Reset token'),
      '#type' => 'fieldset',
    ];
    $form['reset']['instructions']['#markup'] = $this->t("If you're having trouble connecting to DropShark, try resetting the token then re-registering the site.");
    $form['reset']['instructions']['#prefix'] = '<p>';
    $form['reset']['instructions']['#suffix'] = '</p>';
    $form['reset']['submit'] = [
      '#value' => $this->t('Reset'),
      '#type' => 'submit',
      '#submit' => [
        [$this, 'statusFormResetSubmit'],
      ],
    ];

    $form['collect'] = [
      '#title' => $this->t('Collect'),
      '#type' => 'fieldset',
    ];
    $form['collect']['instructions'][0]['#markup'] = $this->t("DropShark will attempt to submit your data during Drupal's cron. You may also want to set up a dedicated cron task dedicated to collecting DropShark data.");
    $form['collect']['instructions'][0]['#prefix'] = '<p>';
    $form['collect']['instructions'][0]['#suffix'] = '</p>';
    $form['collect']['instructions'][1]['#markup'] = $this->t('Use this function to perform a real time collection of data from your site.');
    $form['collect']['instructions'][1]['#prefix'] = '<p>';
    $form['collect']['instructions'][1]['#suffix'] = '</p>';
    $form['collect']['submit'] = [
      '#value' => $this->t('Collect'),
      '#type' => 'submit',
      '#submit' => [
        [$this, 'statusFormCollectSubmit'],
      ],
    ];

    return $form;
  }

  /**
   * Checks connectivity to DropShark backend.
   */
  public function statusFormCheckSubmit() {
    $result = $this->request->checkToken();

    if (empty($result->data->site_id)) {
      drupal_set_message($this->t('Unable to verify the site connection.'), 'error');
    }
    else {
      drupal_set_message($this->t('Connection successfully verified.'));
    }
  }

  /**
   * Resets the site token.
   */
  public function statusFormResetSubmit() {
    $this->state->delete('dropshark.site_token');
  }

  /**
   * Performs an on-demand collection of site data.
   */
  public function statusFormCollectSubmit() {
    $this->collectorManager->collect(['all'], [], TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // No op.
  }

}
