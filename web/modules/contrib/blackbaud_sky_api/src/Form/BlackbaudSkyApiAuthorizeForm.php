<?php

namespace Drupal\blackbaud_sky_api\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\blackbaud_sky_api\BlackbaudOauth;
use Drupal\blackbaud_sky_api\BlackbaudAPI;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\State\StateInterface;

/**
 * Class BlackbaudSkyApiAuthorizeForm.
 *
 * @package Drupal\blackbaud_sky_api
 */
class BlackbaudSkyApiAuthorizeForm extends FormBase {

  /**
   * The Drupal state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new BlackbaudSkyApiAuthorizeForm object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state')
    );
  }
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'blackbaud_sky_api_authorize_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Check both methods to make sure we have auth.
    $token = $this->state->get('blackbaud_sky_api_access_token', '');

    // If we have a token.
    if (!empty($token)) {
      $bb = new BlackbaudAPI();
      $api = $bb->checkToken();
    }

    // Show the submit or not.
    if (empty($token) || !$api) {
      // We are not Authorized, instruct the people.
      $form['title'] = [
        '#type' => 'item',
        '#markup' => '<center><h3>Blackbaud is not authorized on this site.  Click Authorize below and follow the prompts.<h3></center>',
      ];

      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Authorize'),
        '#prefix' => '<center>',
        '#suffix' => '</center>',
      ];
    }
    else {
      // We are good!
      $form['title'] = [
        '#type' => 'item',
        '#markup' => '<center><h3>Blackbaud is authorized, you may carry on.<h3></center>',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Instantiate the BlackBaud request and grab the code.
    $bb = new BlackbaudOauth();
    $bb->getCode();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
