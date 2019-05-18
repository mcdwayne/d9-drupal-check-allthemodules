<?php

namespace Drupal\onelogin_integration;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * AuthenticationService Class.
 *
 * This class takes care of logging the user in and/or creating one when not
 * present yet. The difference with the SAMLAuthenticatorFactory, is that that
 * class instantiates the Auth library with certain settings,
 * while this class uses that instance to log the user in.
 *
 * @package Drupal\onelogin_integration
 */
class AuthenticationService implements AuthenticationServiceInterface {

  /**
   * The variable that holds an instance of ConfigFactoryInterface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * The variable that holds an instance of the SAMLAuthenticatorFactoryInterface.
   *
   * @var \Drupal\onelogin_integration\SAMLAuthenticatorFactoryInterface
   */
  private $oneLoginAuthFactory;

  /**
   * The variable that holds an instance of the EntityTypeManagerInterface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The variable that holds an instance of the UserServiceInterface.
   *
   * @var \Drupal\onelogin_integration\UserServiceInterface
   */
  private $user;

  /**
   * AuthenticationService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Reference to ConfigFactoryInterface.
   * @param \Drupal\onelogin_integration\SAMLAuthenticatorFactoryInterface $one_login_authenticator_factory
   *   Instance to SAMLAuthenticatorFactoryInterface.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Reference to EntityTypeManagerInterface.
   * @param \Drupal\onelogin_integration\UserServiceInterface $user
   *   Reference to UserServiceInterface.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    SAMLAuthenticatorFactoryInterface $one_login_authenticator_factory,
    EntityTypeManagerInterface $entity_type_manager,
    UserServiceInterface $user
  ) {
    $this->configFactory = $config_factory;
    $this->oneLoginAuthFactory = $one_login_authenticator_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->user = $user;
  }

  /**
   * The processLoginRequest function.
   *
   * This function takes the attributes sent with the login request from
   * OneLogin and tries to authenticate the user through other methods.
   *
   * It tries to find a name or e-mail address and process the request.
   * If checked in the admin settings of this module, it will also try to
   * synchronise the roles and create a user if the data from the given
   * OneLogin request is not present in the system yet.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects back to the homepage with a statusmessage accordingly.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function processLoginRequest() {
    // If there is no nameId found, logging in with SAML has no use. So redirect
    // the user back to the homepage with a message accordingly.
    if (empty($this->oneLoginAuthFactory->createFromSettings()->getNameId())) {
      drupal_set_message("A NameId could not be found. Please supply a NameId in your SAML Response.", 'error', FALSE);
      return new RedirectResponse('/');
    }

    // Get the SAML attributes.
    $saml_attributes = $this->oneLoginAuthFactory->createFromSettings()->getAttributes();

    if (!empty($saml_attributes)) {
      $usernameMapping = $this->configFactory->get('onelogin_integration.settings')->get('username');
      $mailMapping     = $this->configFactory->get('onelogin_integration.settings')->get('email');

      // Try to get the email and username from the attributes of the SAML
      // response and set them accordingly.
      if (!empty($usernameMapping) && isset($saml_attributes[$usernameMapping]) && !empty($saml_attributes[$usernameMapping][0])) {
        $username = $saml_attributes[$usernameMapping][0];
      }
      if (!empty($mailMapping) && isset($saml_attributes[$mailMapping]) && !empty($saml_attributes[$mailMapping][0])) {
        $email = $saml_attributes[$mailMapping][0];
      }
    }

    // If there are attributes found in the SAML response, but the email is in
    // the NameID, try to obtain it.
    if (empty($email) && strpos($this->oneLoginAuthFactory->createFromSettings()->getNameId(), '@')) {
      $email = $this->oneLoginAuthFactory->createFromSettings()->getNameId();
    }

    if (empty($username) && $this->configFactory->get('onelogin_integration.settings')->get('username_from_email')) {
      $username = str_replace('@', '.', $email);
    }

    // Get the configuration for the matcher.
    $matcher = $this->configFactory->get('onelogin_integration.settings')->get('account_matcher');

    // Build up the query.
    $query = $this->entityTypeManager->getStorage('user')->getQuery();

    if ($matcher == 'username') {
      if (empty($username)) {
        drupal_set_message("Username value not found on the SAML Response. Username was selected as the account matcher field. Review at the settings the username mapping and be sure that the IdP provides this value", 'error', FALSE);
        return new RedirectResponse('/');
      }
      // Query for active users given a username.
      $query->condition('name', $username);
    }
    else {
      if (empty($email)) {
        drupal_set_message("Email value not found on the SAML Response. Email was selected as the account matcher field. Review at the settings the username mapping and be sure that the IdP provides this value", 'error', FALSE);
        return new RedirectResponse('/');
      }
      // Query for active users given an e-mail address.
      $query->condition('mail', $email);
    }

    // If the user exists, try to sync the roles.
    // If the user does not exist yet, create a new user and try to sync the
    // roles.
    $result = $query->execute();
    if (!empty($result) && $user = User::load(reset($result))) {
      $user_synced = $this->syncRoles($user, $saml_attributes);

      // The two possible outcomes are a RedirectResponse object or a User
      // object. So we have to check the type of outcome. A RedirectResponse
      // is triggered immediately, if it's a User object, we will need to make
      // some alterations before triggering that one.
      if ($user_synced instanceof RedirectResponse) {
        return $user_synced;
      }

      user_login_finalize($user_synced);
      user_cookie_save(['onelogin_integration_login' => '1']);
      return new RedirectResponse('user/' . $user->id() . '/edit');
    }
    else {
      $this->autocreateUser($username, $email, $saml_attributes);
    }
  }

  /**
   * The syncRoles function.
   *
   * The function takes care of syncing the roles of the user that wants to log
   * in (if enabled in the settings of this module). If so, it will compare the
   * current roles of the user with the ones that come from OneLogin. Depending
   * on some statements, it will assign the new roles or returns you to the
   * homepage with a certain errormessage.
   *
   * @param object $user
   *   The user object.
   * @param array $saml_attributes
   *   The attributes coming from OneLogin.
   *
   * @return object
   *   Depending on the case, it will return a user object or a
   *   RedirectResponse to the homepage with an error message accordingly.
   */
  public function syncRoles($user, array $saml_attributes) {
    $site_mail = $this->configFactory->get('system.site')->get('mail');
    $role_mapping = $this->configFactory->get('onelogin_integration.settings')->get('role');
    $role_delimiter = $this->configFactory->get('onelogin_integration.settings')->get('onelogin_role_delimiter');
    $roles = [];

    // Get the configured mapping of each role in the system, except for
    // anonymous and authenticated.
    $available_roles = user_role_names();
    unset($available_roles['anonymous']);
    unset($available_roles['authenticated']);

    $drupal_role_mappings = [];
    foreach ($available_roles as $role_machine_name => $role_nice_name) {
      $drupal_role_mappings[$role_machine_name] = explode(',', $this->configFactory->get('onelogin_integration.settings')->get('role_' . $role_machine_name));
    }

    // If the delimiter is set, split the string with the delimiter.
    // Otherwise, split is by the fallback, a ;.
    if (isset($role_delimiter)) {
      $saml_roles = explode($role_delimiter, $saml_attributes[$role_mapping][0]);
    }
    else {
      $saml_roles = explode(';', $saml_attributes[$role_mapping][0]);
    }

    // Look at the mapped roles and assign those to the user.
    foreach ($saml_roles as $saml_role) {
      foreach ($drupal_role_mappings as $drupal_role => $mapping) {
        if (in_array($saml_role, $mapping)) {
          $roles[] = $drupal_role;
        }
      }
    }

    $user->set('roles', $roles);

    if (empty($roles)) {
      $user->set('status', 0);

      drupal_set_message('You are blocked. Probably because there are no roles assigned to your account in OneLogin. If you think this is incorrect, please contact the administrator of this website via ' . $site_mail, 'error', FALSE);
      return new RedirectResponse('/');
    }
    else {
      $user->set('status', 1);
    }

    $user->save();

    return $user;
  }

  /**
   * The autocreateUser method.
   *
   * This method takes care of creating a user if the user from the OneLogin
   * request is not found in the system.
   *
   * @param string $username
   *   The username of the user that has to be created.
   * @param string $email
   *   The email of the user that has to be created.
   * @param array $saml_attributes
   *   The attributes from the OneLogin request, so roles can be properly
   *   assigned.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   If the user is correctly created, a RedirectResponse will take them to
   *   their own page.
   */
  public function autocreateUser($username, $email, array $saml_attributes) {
    /*
     * If auto-provisioning is enabled but the attributes that are required for
     * it are not there, redirect them to the homepage with an error message.
     */
    if (empty($email) || empty($username)) {
      drupal_set_message("Auto-provisioning accounts requires a username and email address. Please supply both in your SAML response.", 'error', FALSE);
      return new RedirectResponse('/');
    }

    /* Create the user with the given roles. The syncRoles function takes care
     * when data from the OneLogin request is incorrect.
     */
    $user = $this->user->createUser($username, $email);
    $user_synced = $this->syncRoles($user, $saml_attributes);

    /*
     * The two possible outcomes are a RedirectResponse object or a User
     * object. So we have to check the type of outcome.
     */
    if ($user_synced instanceof RedirectResponse) {
      return $user_synced;
    }

    user_login_finalize($user_synced);
    user_cookie_save(['onelogin_integration_login' => '1']);
    return new RedirectResponse('user/' . $user->id() . '/edit');
  }

}
