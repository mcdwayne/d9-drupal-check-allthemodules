<?php
/**
 * @file
 * Contains \Drupal\am_registration\Form\RegistrationForm.
 */
namespace Drupal\am_registration\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Element\Email;
use Drupal\user\UserStorageInterface;
use Drupal\am_registration\Controller\CreateUserController;
use Drupal\am_registration\Controller\CreateLoginLinkController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;


class RegistrationForm extends FormBase {

	/**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a UserPasswordForm object.
   *
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(UserStorageInterface $user_storage, LanguageManagerInterface $language_manager) {
    $this->userStorage = $user_storage;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('user'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'registration_form';
  }

   /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

	$form['reg_info'] = array(
    //'#type' => 'fieldset',
    '#prefix'=>"<div class='div-border'>",
    '#type' => container,
    //'#title' => t('Organization Information'), 
    '#attributes' => array('class' => array('clearfix','registration-info-wrapper')),    
    );
    $form['reg_info']['candidate_mail'] = array(
      '#type' => 'email',
      '#title' => t('Enter your email address'),
      '#attributes' => array('placeholder' => t('Email address'),'class' => array('form-control')),
      '#size' => 50,
      '#required' => TRUE,
    );   
    $form['reg_info']['actions']['#type'] = 'actions';
    $form['reg_info']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Email me a login link'),
      '#button_type' => 'primary',
    );
    return $form;
  }


  /**
   * {@inheritdoc}
   */
    public function validateForm(array &$form, FormStateInterface $form_state) {
      $name = trim($form_state->getValue('candidate_mail'));
    // Try to load by email.
    $users = $this->userStorage->loadByProperties(array('mail' => $name));
    if (empty($users)) {
      // No success, try to load by name.
      $users = $this->userStorage->loadByProperties(array('name' => $name));
    }

    $account = reset($users);
    if ($account && $account->id()) {
      // Blocked accounts cannot request a new password.
      if (!$account->isActive()) {
        $form_state->setErrorByName('name', $this->t('%name is blocked or has not been activated yet.', array('%name' => $name)));
      }
    }
   }
   


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $name = trim($form_state->getValue('candidate_mail'));
    // Try to load by email.
    $users = $this->userStorage->loadByProperties(array('mail' => $name));
    if (empty($users)) {
      // No success, try to load by name.
      $users = $this->userStorage->loadByProperties(array('name' => $name));
    }

    $account = reset($users);
    if ($account && $account->id()) {     
           // Load user for token replacement.
           //$user = user_load_by_mail($name);
           //$user = $account;
           try{
           $CreateLoginLinkController = new CreateLoginLinkController;
           $value = $CreateLoginLinkController->createLoginLink($account);
           drupal_set_message($this->t('<div class="AMloginMsg"><div class="titleKarlaBold">A login link has been sent to you!</div></div>'));
      $form_state->setRedirect('<front>');
           }catch (Exception $e) {
          drupal_set_message($e."Some error occured","error");
        return new RedirectResponse('/user/login');
         }  
    }
    else {
          try{
              //Create a new user with provided email id.
          $CreateUserController = new CreateUserController;
          $value = $CreateUserController->createUser($name);
          drupal_set_message($this->t('<div class="AMloginMsg"><div class="titleKarlaBold">A login link has been sent to you!</div></div>'));
        $form_state->setRedirect('<front>');
          }catch (Exception $e) {
            drupal_set_message($e."Some error occured","error");
          return new RedirectResponse('/user/login');
        }
      }
  }
}