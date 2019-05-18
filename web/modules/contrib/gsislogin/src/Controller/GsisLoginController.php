<?php

namespace Drupal\gsislogin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

use Drupal\Component\Serialization\Json;
use Drupal\user\Entity\User;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

use Drupal\Core\Language\LanguageManager;

/**
 * Returns responses for gis login route.
 */
class GsisLoginController extends ControllerBase {

  private $tag = "_gsis_";
  private $appId = "";
  private $secret = "";
  private $redirectUri = "";
  private $tokenUrl = "";
  private $userinfoUrl = "";
  private $authorizeUrl = "";

  private $config = NULL;
  private $language = NULL;
  private $httpClient = NULL;

  /**
   * Constructor for GsisLoginController.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack object.
   * @param \GuzzleHttp\Client $http_client
   *   The http client.
   * @param \Drupal\Core\Language\LanguageManager $language_manager
   *   Language support.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RequestStack $request_stack, Client $http_client, LanguageManager $language_manager) {
    $request = $request_stack->getCurrentRequest();

    $this->language = $language_manager->getCurrentLanguage()->getId();
    $this->httpClient = $http_client;;

    $this->config = $config_factory->get('config.gsislogin');
    $this->tag = "_gsis_" . uniqid(mt_rand());
    $this->appId = $this->config->get('GSISID');
    $this->secret = $this->config->get('GSISSECRET');
    $this->redirectUri = $request->getScheme() . "://" . $request->getHost() . "/gsis";

    $ontest = $this->config->get('GSISTEST');
    if ($ontest) {
      $this->tokenUrl = 'https://test.gsis.gr/oauth2server/oauth/token';
      $this->userinfoUrl = 'https://test.gsis.gr/oauth2server/userinfo';
      $this->authorizeUrl = 'https://test.gsis.gr/oauth2server/oauth/authorize';
    }
    else {
      $this->tokenUrl = 'https://www1.gsis.gr/oauth2server/oauth/token';
      $this->userinfoUrl = 'https://www1.gsis.gr/oauth2server/userinfo';
      $this->authorizeUrl = 'https://www1.gsis.gr/oauth2server/oauth/authorize';
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
    $container->get('config.factory'),
    $container->get('request_stack'),
    $container->get('http_client'),
    $container->get('language_manager')
    );
  }

  /**
   * Accept requests and provide oauth 2 steps.
   */
  public function start(Request $request) {

    // In case of gsis error.
    if (NULL !== $request->get('error')) {
      if (NULL !== $request->get('error_description')) {
        if ($request->get('error_description') == "User denied access") {
          return ['#markup' => $this->t("You chose not to proceed to login from the systems of the Greek General Secretariat for Information Systems.")];
        }
        else {
          return ['#markup' => $this->t("ERR: 001 There was a problem connecting to the systems of the General Secretariat for Information Systems.")];
        }
      }
      else {
        return $this->redirect('user.login');
      }
    }

    // step2 success get token with code.
    if (NULL !== $request->get('code')) {

      $code = $request->get('code');
      $state = $request->get('state');

      if ($state != $_SESSION['gsis_state']) {
        return ['#markup' => $this->t("ERR: 002 Problem in response to the systems of the General Secretariat for Information Systems.")];
      }

      try {
        $gettokenurl = $this->tokenUrl;
        $data = [
          'code' => $code,
          'redirect_uri' => $this->redirectUri,
          'client_id' => $this->appId,
          'client_secret' => $this->secret,
          'scope' => '',
          'grant_type' => 'authorization_code',
        ];

        $body = http_build_query($data);

        $tokenjson = $this->httpClient->post(
        $gettokenurl, [
          'body' => $body,
          'headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/x-www-form-urlencoded',
          ],
        ]
        )->getBody(TRUE)->getContents();

        $this->tokenarray = Json::decode($tokenjson);

      }
      catch (RequestException $e) {
        watchdog_exception('gsislogin', $e, $e->getMessage());
        return ['#markup' => $this->t("ERR: 003 Problem in connection with the General Secretariat of Information Systems.")];
      }
      catch (ClientException $e) {
        watchdog_exception('gsislogin', $e, $e->getMessage());
        return ['#markup' => $this->t("ERR: 004 Problem in connection with the General Secretariat of Information Systems.")];
      }

      // step3.
      try {
        // TODO check fields.'&fields=id,name,emails';.
        $getuserurl = $this->userinfoUrl . "?format=xml&access_token=" . $this->tokenarray['access_token'];
        $userxml = $this->httpClient->get(
        $getuserurl, [
          'headers' => [
            'Accept' => 'application/xml',
            'Content-Type' => 'application/x-www-form-urlencoded;',
          ],
        ]
        )->getBody(TRUE)->getContents();

        // In case of error gsis sends JSON !!!
        $checkerror = Json::decode($userxml);
        if ($checkerror !== NULL) {
          return ['#markup' => $this->t("ERR: 005 Data collection problem from the General Secretariat of Information Systems.")];
        }

        $xml = simplexml_load_string($userxml);

        $userid = $xml->userinfo['userid'][0]->__toString();
        $taxid = $xml->userinfo['taxid'][0]->__toString();
        $lastname = $xml->userinfo['lastname'][0]->__toString();
        $firstname = $xml->userinfo['firstname'][0]->__toString();
        $fathername = $xml->userinfo['fathername'][0]->__toString();
        $mothername = $xml->userinfo['mothername'][0]->__toString();
        $birthyear = $xml->userinfo['birthyear'][0]->__toString();

        $userDetails = [
          "userid" => $userid,
          "taxid" => $taxid,
          "lastname" => $lastname,
          "firstname" => $firstname,
          "fathername" => $fathername,
          "mothername" => $mothername,
          "birthyear" => $birthyear,
        ];

        $this->createUser($userDetails);

        return $this->redirect('<front>');

      }
      catch (RequestException $e) {
        watchdog_exception('gsislogin', $e, $e->getMessage());
        return ['#markup' => $this->t("ERR: 006 Problem of entering data from the General Secretariat of Information Systems.")];
      }
      catch (ClientException $e) {
        watchdog_exception('gsislogin', $e, $e->getMessage());
        return ['#markup' => $this->t("ERR: 007 Data entry problem by the General Secretariat of Information Systems.")];
      }

    }

    // Step 1.
    $_SESSION['gsis_state'] = $this->tag;
    return new TrustedRedirectResponse($this->authorizeUrl . '?client_id=' . $this->appId . '&redirect_uri=' . $this->redirectUri . '&response_type=code&scope=read&state=' . $this->tag);
  }

  /**
   * Login or/add Create and register user.
   */
  private function createUser($userDetails) {

    $username = $userDetails['userid'];

    if (!($user = user_load_by_name($username))) {
      // Create user.
      $user = User::create();

      // Mandatory settings
      // $user->setPassword('test');.
      $user->enforceIsNew();
      // $user->setEmail("");.
      $user->setUsername($username);

      // Optional settings
      // $user->set("init", 'email');.
      $user->set("langcode", $this->language);
      $user->set("preferred_langcode", $this->language);
      $user->set("preferred_admin_langcode", $this->language);

      // Activate user
      // _user_mail_notify('register_no_approval_required', $user);.
      $user->activate();
    }

    // GSIS Custom fields. Update the fields just in case of changes.
    $user->set("field_gsis_userid", $userDetails['userid']);
    $user->set("field_gsis_taxid", $userDetails['taxid']);
    $user->set("field_gsis_lastname", $userDetails['lastname']);
    $user->set("field_gsis_firstname", $userDetails['firstname']);
    $user->set("field_gsis_mothername", $userDetails['mothername']);
    $user->set("field_gsis_fathername", $userDetails['fathername']);
    $user->set("field_gsis_birthyear", $userDetails['birthyear']);

    // Save user changes.
    $user->save();

    // Login the user.
    user_login_finalize($user);
  }

}
