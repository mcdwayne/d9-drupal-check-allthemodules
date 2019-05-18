<?php

/**
 * @file
 * Contains \Drupal\salesforce_auth\Controller\CallbackController.
 */

namespace Drupal\salesforce_auth\Controller;

use Drupal\user\Entity\User;
use Drupal\Core\Render\Element;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AnonymousUserSession;
use Drupal\Core\Render\MetadataBubblingUrlGenerator;
use Drupal\salesforce_auth\Rest\RestClientInterface;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;


/**
 * Provides route responses for the Example module.
 */
class CallbackController extends ControllerBase {
    protected $client;
    protected $http_client;
    
    /**
    * {@inheritdoc}
    */
    public function __construct(RestClientInterface $rest, Client $http_client, MetadataBubblingUrlGenerator $url_generator) {
        $this->client = $rest;
        $this->http_client = $http_client;
        $this->url_generator = $url_generator;
    }

    /**
    * {@inheritdoc}
    */
    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('salesforce_auth.client'),
            $container->get('http_client'),
            $container->get('url_generator')
        );
    }

    protected function request() {
        return \Drupal::request();
    }

    protected function dbQuery() {
        return \Drupal::database();
    }

    protected function successMessage() {
        drupal_set_message(t('Successfully connected to %endpoint', ['%endpoint' => $this->client->getInstanceUrl()]));
    }

    /**
     * Returns a simple page.
     *
     * @return array
     *   A simple renderable array.
     */
    public function callback() {
        global $user;

        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $user = \Drupal\user\Entity\User::create();

        // If no code is provided, return access denied.
        if (empty($this->request()->query->get('code'))) {
            throw new AccessDeniedHttpException();
        }
        
        $pass = '';
        $code = $this->request()->query->get('code');
        $response = $this->requestToken($code);
        $validateClient = $this->client->handleAuthResponse($response);
        $token = $validateClient['access_token'];
        $tokenId = $validateClient['id'];
        
        if ($token) {
            $custInfo = $this->client->initializeIdentity($validateClient['id']);
            $verified = $custInfo['email_verified'];
            $active = $custInfo['active'];
            // if ($verified == 1 && $active == 1)

            if ($active == 1) {
                $user_id = $custInfo['user_id'];
                $uname = $custInfo['username'];
                $email = $custInfo['email'];
                $first_name = $custInfo['first_name'];
                $last_name = $custInfo['last_name'];
                $user_type = $custInfo['user_type'];
                $nicknm = $custInfo['nick_name'];
                $displaynm = $custInfo['display_name'];

                $uid = $this->_get_uid_from_username($uname);
                $externalid = $this->_get_external_uid_from_username($uname);
                $user_by_email = user_load_by_mail($uname);
                
                if (($externalid || !$uid) && ((!$uid && !$user_by_email) || (isset($user_by_email->get('uid')->value) && $uid == $user_by_email->get('uid')->value && $externalid))) {
                    $existing_uid = $this->_get_uid_from_sfuserid($user_id);
                    $existing_user = NULL;
                    
                    if ($existing_uid) {
                        // load the existing user
                        $existing_user = user_load($existing_uid);
                        $existing_user->get('name')->value = $uname;
                        $existing_user->get('mail')->value = $email;

                        // update/save the existing user
                        $existing_user->setUsername($uname);
                        $existing_user->setPassword($pass);
                        $existing_user->setEmail($email);
                        $existing_user->addRole('rid');
                        $existing_user->save();

                        // update the fs_authmap fields
                        $this->_update_salesforce_authmap($uname, $existing_uid);

                        //update the fs_salesforce_auth fields
                        $this->_update_salesforce_auth($user_id,$nicknm,$displaynm,$existing_uid);

                        // assign user_id to the varible
                        $uid = $existing_user->get('uid')->value;
                    } 
                    else {
                        // register the user.
                        $user->setUsername($uname);
                        $user->setPassword($pass);
                        $user->setEmail($email);
                        $user->enforceIsNew();
                        $user->set("init", $uname);
                        $user->set("langcode", $language);
                        $user->set("preferred_langcode", $language);
                        $user->set("preferred_admin_langcode", $language);
                        $user->addRole('rid');
                        $user->activate();
                        $user->save();

                        //insert into the fs_authmap table
                        $this->_set_salesforce_authmap($user->id(),$uname,'salesforce_auth');

                        //insert into the salesforce_auth table
                        $this->_set_salesforce_auth($user->id(),$user_id,$user_type,$nicknm,$displaynm);
                        
                        // save user email and load user object
                        $newUser = user_load($user->id());
                        $newUser->get('name')->value = $uname;
                        $newUser->get('pass')->value = $pass;

                        // assign user_id to the varible
                        $uid = $newUser->get('uid')->value;
                    }
                }
                // load the user and let login user
                $values['uid'] = $uid;
                $user_load = user_load($uid);
                user_login_finalize($user_load);

                // set sso token in session.
                $tempstore = \Drupal::service('user.private_tempstore')->get('salesforce_auth');
                $tempstore->set('sf_token', $token);
                $tempstore->set('sf_userid', $user_id);
            }
        }
        return new \Symfony\Component\HttpFoundation\RedirectResponse(\Drupal::url('<front>'));
    }

    /**
     * Get consumer key for salesforce API.
     */
    public function getConsumerKey() {
        $config = $this->config('salesforce_auth.settings');
        $custInfo = $config->get('salesforce_consumer_key');
        return $consumerKey = $custInfo['salesforce_consumer_key'];
    }

    /**
     * Get consumer secret key for salesforce API.
     */
    public function getConsumerSecret() {
        $config = $this->config('salesforce_auth.settings');
        $custInfo = $config->get('salesforce_consumer_secret');
        return $consumerSecret = $custInfo['salesforce_consumer_secret'];
    }

    /**
     * Get callback uri for salesforce API.
     */
    public function getCallbackUrl() {
        $config = $this->config('salesforce_auth.settings');
        $custInfo = $config->get('salesforce_callback_uri');
        return $callbackUri = $custInfo['salesforce_callback_uri'];
    }

    /**
     * Get login uri for salesforce API.
     */
    public function getLoginUrl() {
        $config = $this->config('salesforce_auth.settings');
        $custInfo = $config->get('salesforce_login_uri');
        return $callbackUri = $custInfo['salesforce_login_uri'];
    }

    /**
     * Generate auth token uri from login uri for salesforce API.
     */
    public function getAuthTokenUrl() {
        return $this->getLoginUrl() . '/services/oauth2/token';
    }

    /**
     * Returns a simple page.
     *
     * @return array
     *   A simple renderable array.
     */
    public function requestToken($code) {
        $url = $this->getAuthTokenUrl();
        $headers = array(
            'Content-Type' => 'application/x-www-form-urlencoded',
        );

        $data = urldecode(UrlHelper::buildQuery([
            'code' => $code,
            'grant_type' => 'authorization_code',
            'client_id' => $this->getConsumerKey(),
            'client_secret' => $this->getConsumerSecret(),
            'redirect_uri' => $this->getCallbackUrl(),
        ]));
        return $response = $this->http_client->post($url, ['headers' => $headers, 'body' => $data]);
    }

    /**
     * Getting (fetched) field values from different tables with provided 
     * id/name/mail/authname
     * @param $uid
     * @param $uname
     * @param $email
     * @param $userid
     * @param $authname
     */
    public function _get_uid_from_username($uname) {
        $query = $this->dbQuery()->select('users_field_data', 'ufd');
        $query->addField('ufd', 'uid');
        $query->condition('ufd.name', $uname);
        return $query->execute()->fetchField();
    }
    public function _get_external_uid_from_username($uname) {
        $query = $this->dbQuery()->select('fs_authmap', 'fsam');
        $query->addField('fsam', 'uid');
        $query->condition('fsam.authname', $uname);
        $query->condition('fsam.module', 'salesforce_auth');
        return $query->execute()->fetchField();
    }
    public function _get_uid_from_sfuserid($userid) {
        $query = $this->dbQuery()->select('fs_salesforce_auth', 'fssa');
        $query->addField('fssa', 'uid');
        $query->condition('fssa.user_id', $userid);
        return $query->execute()->fetchField();
    }
    public function _set_salesforce_authmap($uid,$name,$module) {
        $query = $this->dbQuery()->insert('fs_authmap');
        $query->fields(['uid','authname','module']);
        $query->values([$uid,$name,$module]);
        $query->execute();
    }
    public function _set_salesforce_auth($uid,$userid,$utype,$nicknm,$displaynm) {
        $query = $this->dbQuery()->insert('fs_salesforce_auth');
        $query->fields(['uid','user_id','user_type','user_nickname','user_displayname']);
        $query->values([$uid,$userid,$utype,$nicknm,$displaynm]);
        $query->execute();
    }
    public function _update_salesforce_authmap($name,$uid) {
        $query = $this->dbQuery()->update('fs_authmap');
        $query->fields(['authname' => $name]);
        $query->condition('uid', $uid);
        $query->execute();
    }
    public function _update_salesforce_auth($userid,$nicknm,$displaynm,$uid) {
        $query = $this->dbQuery()->update('fs_salesforce_auth');
        $query->fields(['user_id' => $userid, 'user_nickname' => $nicknm, 'user_displayname' => $displaynm]);
        $query->condition('uid', $uid);
        $query->execute();
    }
}
