<?php

namespace Drupal\atm\Form;

use Drupal\atm\Ajax\RedirectInNewTabCommand;
use Drupal\atm\AtmHttpClient;
use Drupal\atm\Helper\AtmApiHelper;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Extension\ThemeHandler;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AtmRevenueModelForm.
 */
class AtmRegisterForm extends AtmAbstractForm {

  /**
   * AtmAbstractForm constructor.
   *
   * @param \Drupal\atm\Helper\AtmApiHelper $atmApiHelper
   *   Provides helper for ATM.
   * @param \Drupal\atm\AtmHttpClient $atmHttpClient
   *   Client for API.
   * @param \Drupal\Core\Extension\ThemeHandler $themeHandler
   *   Default theme handler.
   */
  public function __construct(AtmApiHelper $atmApiHelper, AtmHttpClient $atmHttpClient, ThemeHandler $themeHandler) {
    $this->atmApiHelper = $atmApiHelper;
    $this->atmHttpClient = $atmHttpClient;
    $this->themeHandler = $themeHandler;
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'atm-register';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $description = [];
    $description[] = $this->t('<strong>IMPORTANT:</strong>');
    $description[] = $this->t('Registration step is not required to be able to use this plugin.');
    $description[] = $this->t('Once you generate some revenue and want to transfer it into your bank account, then we encourage you to register here (using "Email address").');
    $description[] = $this->t('Follow the steps to setup your account on AdTechMedia.io platform and enjoy the influx of revenue into your bank account.');

    $form['info'] = [
      '#type' => 'markup',
      '#markup' => implode(" ", $description),
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => t('Email'),
      '#default_value' => \Drupal::config('system.site')->get('mail'),
      '#description' => $this->t('Provide your email address that will be used to register, connect and interact with AdTechMedia.io platform'),
      '#required' => TRUE,
    ];

    $form['terms'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<a id="atm-terms">I agree to Terms of Use</a>'),
      '#required' => TRUE,
    ];

    $form['register'] = [
      '#type' => 'button',
      '#value' => t('Register'),
      '#ajax' => [
        'event' => 'click',
        'callback' => [$this, 'saveParams'],
      ],
      '#states' => [
        'enabled' => [
          ':input[name="terms"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public function saveParams(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $response = new AjaxResponse();

    if ($form_state->getErrors()) {
      $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
      $response->setAttachments($form['#attached']);

      $_errors = [];
      foreach ($form_state->getErrors() as $error) {
        $_errors[] = $this->getErrorMessage($error);
      }

      $response->addCommand(
        new OpenModalDialogCommand('Form errors', $_errors)
      );

      $form_state->clearErrors();

      return $response;
    }

    $this->getHelper()->setApiEmail($values['email']);

    $response->addCommand(
      new RedirectInNewTabCommand(
        $this->getHelper()->get('register_url')
      )
    );

    return $response;
  }

}
