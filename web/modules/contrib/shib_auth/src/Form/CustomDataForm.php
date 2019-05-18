<?php

namespace Drupal\shib_auth\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\Core\Url;
use Drupal\shib_auth\Login\ShibSessionVars;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

// Use Symfony\Component\ClassLoader\ApcClassLoader;
// use Symfony\Component\HttpFoundation\RedirectResponse;.
/**
 * Class CustomEmailForm.
 *
 * @package Drupal\shib_auth\Form
 */
class CustomDataForm extends FormBase {

  /**
   * Symfony\Component\ClassLoader\ApcClassLoader definition.
   *
   * @var \Symfony\Component\ClassLoader\ApcClassLoader
   */
  protected $shib_session;
  protected $temp_store_factory;
  protected $session_manager;
  protected $current_user;
  protected $custom_data_store;

  /**
   * CustomEmailForm constructor.
   *
   * @param \Drupal\shib_auth\Login\ShibSessionVars $shib_session
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   * @param \Drupal\Core\Session\AccountInterface $current_user
   */
  public function __construct(ShibSessionVars $shib_session, PrivateTempStoreFactory $temp_store_factory, SessionManagerInterface $session_manager, AccountInterface $current_user) {
    $this->shib_session = $shib_session;
    $this->temp_store_factory = $temp_store_factory;
    $this->session_manager = $session_manager;
    $this->current_user = $current_user;

    $this->custom_data_store = $this->temp_store_factory->get('shib_auth');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('shib_auth.shib_session_vars'),
      $container->get('user.private_tempstore'),
      $container->get('session_manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_data_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#cache'] = ['max-age' => 0];
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#default_value' => (!empty($this->shib_session->getEmail()) ? $this->shib_session->getEmail() : ''),
      '#description' => 'Please enter a valid email address.',
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Start Session if it does not exist yet.
    if ($this->current_user->isAnonymous() && !isset($_SESSION['session_started'])) {
      $_SESSION['session_started'] = TRUE;
      $this->session_manager->start();
    }

    // Add custom Email to the session.
    $this->custom_data_store->set('custom_email', $form_state->getValue('email'));

    // Redirect.
    $form_state->setRedirectUrl(Url::fromUri(\Drupal::request()
      ->getSchemeAndHttpHost() . $this->custom_data_store->get('return_url')));
  }

}
