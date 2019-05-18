<?php

/**
 * @file
 * Contains \Drupal\login_shadowbox\Controller\LoginShadowboxController.
 */

namespace Drupal\login_shadowbox\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityFormBuilder;

/**
 * Controller routines for login_shadowbox routes.
 */
class LoginShadowboxController extends ControllerBase {

  /**
   * Stores the Entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Built entity form.
   *
   * @var \Drupal\Core\Entity\EntityFormBuilder
   */
  protected $entityFormBuilders;

  /**
   * Constructs a \Drupal\login_shadowbox\Controller\LoginShadowboxController object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The Entity manager.
   * @param \Drupal\user\TempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityFormBuilder $entity_form_builder) {
    $this->entityManager = $entity_manager;
    $this->entityFormBuilders = $entity_form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity.form_builder')
    );
  }

  /**
   * Displays user profile if user is logged in, or login shadowbox form
   * for anonymous users.
   */
  public function login() {
    $user = $this->currentUser();

    if ($user->id()) {
      $response = $this->redirect('user.view', array('user' => $user->id()));
    }
    else {
      $path = drupal_get_path('module', 'login_shadowbox');
      $css_path = $this->config('login_shadowbox.settings')->get('login_shadowbox_css');

      // Login form begin.
      $login_form = '<div id="shadowbox_login" class="shadowbox_login_wrapper">';
      $login_form .= '<button class="shadowbox_login_close_button">x</button>';

      // Add login form.
      $form_builder = $this->formBuilder();
      $form = $form_builder->getForm('Drupal\user\Form\UserLoginForm');

      $form['#attached'] = array(
        'css' => array($css_path, array('group' => 'CSS_DEFAULT')),
        'js' => array($path . '/scripts/login_shadowbox_messages.js'),
      );

      $login_form .= drupal_render($form);

      if ($this->config('user.settings')->get('register')) {
        $items[] = l(t('Create new account'), 'user/register', array('attributes' => array('title' => t('Create a new user account.'))));
      }

      $items[] = l(t('Request new password'), 'user/password', array('attributes' => array('title' => t('Request new password via e-mail.'))));
      $links = array('#theme' => 'item_list', '#items' => $items);
      $login_form .= drupal_render($links);

      $login_form .= '</div>';

      $login =  array(
        '#theme' => 'login_shadowbox_page',
        '#title' => t('Login'),
        '#content' => $login_form,
        '#css' => drupal_get_css(),
        '#js' => drupal_get_js(),
      );

      exit(drupal_render($login));
    }

    return NULL;
  }

  /**
   * Displays user profile if user is logged in, or register shadowbox form
   * for anonymous users.
   */
  public function register() {
    $user = $this->currentUser();

    if ($user->id()) {
      $response = $this->redirect('user.view', array('user' => $user->id()));
    }
    else {
      $path = drupal_get_path('module', 'login_shadowbox');
      $css_path = $this->config('login_shadowbox.settings')->get('login_shadowbox_css');

      // Refistration form begin.
      $register_form = '<div id="shadowbox_register" class="shadowbox_login_wrapper">';
      $register_form .= '<button class="shadowbox_login_close_button">x</button>';

      if ($this->config('user.settings')->get('register') <> USER_REGISTER_ADMINISTRATORS_ONLY) {
        $entity = $this->entityManager->getStorage('user')->create();
        $form = $this->entityFormBuilders->getForm($entity, 'register');

        $form['#attached'] = array(
          'css' => array($css_path, array('group' => 'CSS_DEFAULT')),
          'js' => array($path . '/scripts/login_shadowbox_messages.js'),
        );

        $register_form .= drupal_render($form);
        $register_form .= '</div>';

        $register =  array(
          '#theme' => 'login_shadowbox_page',
          '#title' => t('Create new account'),
          '#content' => $register_form,
          '#css' => drupal_get_css(),
          '#js' => drupal_get_js(),
        );

        exit (drupal_render($register));
      }
    }

    return NULL;
  }

  /**
   * Displays user profile if user is logged in, or reset password shadowbox
   * form for anonymous users.
   */
  public function password() {
    $user = $this->currentUser();

    if ($user->id()) {
      $response = $this->redirect('user.view', array('user' => $user->id()));
    }
    else {
      $path = drupal_get_path('module', 'login_shadowbox');
      $css_path = $this->config('login_shadowbox.settings')->get('login_shadowbox_css');

      // Reset password form begin.
      $password_form = '<div id="shadowbox_password" class="shadowbox_login_wrapper">';
      $password_form .= '<button class="shadowbox_login_close_button">x</button>';

      $form_builder = $this->formBuilder();
      $form = $form_builder->getForm('Drupal\user\Form\UserPasswordForm');

      $form['#attached'] = array(
        'css' => array($css_path, array('group' => 'CSS_DEFAULT')),
        'js' => array($path . '/scripts/login_shadowbox_messages.js'),
      );

      $password_form .= drupal_render($form);
      $password_form .= '</div>';

      $password_form =  array(
        '#theme' => 'login_shadowbox_page',
        '#title' => t('Request new password'),
        '#content' => $password_form,
        '#css' => drupal_get_css(),
        '#js' => drupal_get_js(),
      );

      exit (drupal_render($password_form));
    }

    return NULL;
  }
}