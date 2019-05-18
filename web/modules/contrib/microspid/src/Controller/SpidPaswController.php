<?php

namespace Drupal\microspid\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\microspid\Service\SpidPaswManager;
use Drupal\microspid\Service\CertsManager;
use Drupal\microspid\Service\MicrospidDrupalAuth;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;


/**
 * Controller routines for microspid routes.
 */
class SpidPaswController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The SPiD Authentication helper service.
   *
   * @var \Drupal\microspid\Service\SpidPaswManager
   */
  public $spid;
  
  /**
   *
   * The Certification Manager service.
   * @var \Drupal\microspid\Service\CertsManager
   */
  protected $certsManager;

  /**
   * The SimpleSAML Drupal Authentication service.
   *
   * @var \Drupal\microspid\Service\MicrospidDrupalAuth
   */
  public $microspidDrupalauth;

  /**
   * The url generator service.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  public $requestStack;

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * A configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * {@inheritdoc}
   *
   * @param SpidPaswManager $spid
   *   The SPID Authentication helper service.
   * @param CertsManager $certsManager
   *   The Microspid Certification Manager service.
   * @param MicrospidDrupalAuth $microspid_drupalauth
   *   The Microspid Drupal Authentication service.
   * @param UrlGeneratorInterface $url_generator
   *   The url generator service.
   * @param RequestStack $request_stack
   *   The request stack.
   * @param AccountInterface $account
   *   The current account.
   * @param PathValidatorInterface $path_validator
   *   The path validator.
   * @param LoggerInterface $logger
   *   A logger instance.
   * @param ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(SpidPaswManager $spid, CertsManager $certsManager, MicrospidDrupalAuth $microspid_drupalauth, UrlGeneratorInterface $url_generator, RequestStack $request_stack, AccountInterface $account, PathValidatorInterface $path_validator, LoggerInterface $logger, ConfigFactoryInterface $config_factory) {
    $this->spid = $spid;
    $this->certsManager = $certsManager;
    $this->microspidDrupalauth = $microspid_drupalauth;
    $this->urlGenerator = $url_generator;
    $this->requestStack = $request_stack;
    $this->account = $account;
    $this->pathValidator = $path_validator;
    $this->logger = $logger;
    $this->config = $config_factory->get('microspid.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('microspid.manager'),
      $container->get('microspid.certs.manager'),
      $container->get('microspid.drupalauth'),
      $container->get('url_generator'),
      $container->get('request_stack'),
      $container->get('current_user'),
      $container->get('path.validator'),
      $container->get('logger.factory')->get('microspid'),
      $container->get('config.factory')
    );
  }

  /**
   * Logs the user in via SPID federation.
   *
   * @return RedirectResponse
   *   A redirection to either a designated page or the user login page.
   */
  public function authenticate() {
    global $base_url;

    // Ensure the module has been turned on before continuing with the request.
    if (!$this->spid->isActivated()) {
      return $this->redirect('user.login');
    }

    // See if a URL has been explicitly provided in ReturnTo. If so, use it
    // otherwise, use the HTTP_REFERER. Each must point to the site to be valid.
    $request = $this->requestStack->getCurrentRequest();

    if (($return_to = $request->request->get('ReturnTo')) || ($return_to = $request->server->get('HTTP_REFERER'))) {
      if ($this->pathValidator->isValid($return_to) && UrlHelper::externalIsLocal($return_to, $base_url)) {
        $redirect = $return_to;
      }
    }

    // The user is not logged into Drupal.
    if ($this->account->isAnonymous()) {

      if (isset($redirect)) {
        // Set the cookie so we can deliver the user to the place they started.
        // @TODO probably a more symfony way of doing this
        setrawcookie('microspid_returnto', $redirect, time() + 60 * 60);
      }
      $this->spid->externalAuthenticate();
    }

    // Check to see if we've set a cookie. If there is one, give it priority.
    if ($request->cookies->has('microspid_returnto')) {
      $redirect = $request->cookies->get('microspid_returnto');

      // Unset the cookie.
      setrawcookie('microspid_returnto', '');
    }

    if (isset($redirect)) {
      // Avoid caching of redirect response object.
      \Drupal::service('page_cache_kill_switch')->trigger();
      $response = new RedirectResponse($redirect, RedirectResponse::HTTP_FOUND);
      return $response;
    }

    return $this->redirect('user.login');
  }

  /**
   * The AssertionConsumerService method
   * @return RedirectResponse
   */
  public function acs() {
    global $base_url;
    if (empty($_POST['SAMLResponse'])) {
      drupal_set_message($this->t('SAML response not found'), 'warning');
      $response = new RedirectResponse($base_url);
      return $response;
    }
    $resp = base64_decode($_POST['SAMLResponse']);
    if ($this->config->get('debug')) {
      $this->logger->debug('SAMLResponse: %resp', array('%resp' => $resp));
    }
    $ret = $this->spid->acs($resp);
    if (!is_bool($ret)) {
      drupal_set_message($ret, 'warning', TRUE);
      return $this->redirect('<front>');
    }

    if ($this->account->isAnonymous()) {
      // User is not logged in to Drupal.
      if ($this->spid->isAuthenticated()) {
        // User is logged in - SPID (but not Drupal).
        // Get unique identifier from saml attributes.
        $authname = $this->spid->getAuthname();

        if (!empty($authname)) {
          // User is logged in with SAML authentication and we got the unique identifier.
          // Try to log into Drupal.
          \Drupal::logger('microspid')->notice('User %authname (%cf) authenticated via SPID', ['%authname' => $authname, '%cf' => $this->spid->getFiscalNumber()]);
          $this->microspidDrupalauth->externalLoginRegister($authname);
          $_SESSION['spiduser'] = $authname;
          $_SESSION['NameID'] = $this->spid->getValue('nameId');
          $_SESSION['EntityID'] = $this->spid->getValue('entityID');
          $_SESSION['SessionIndex'] = (string) $this->spid->getValue('session_index');
          $_SESSION['IdP'] = $this->spid->getValue('idp_filename');
          $servicedir = $this->config->get('servicedir');
          if (!empty($servicedir)) {
            $response = new RedirectResponse($servicedir);
            return $response;
          }
          return $this->redirect('user.login');
        } // End if !empty authname.
      } // End if isset saml_session.
    } // End if user->uid.
    return $this->redirect('user.login');
  }
  
  /**
   * The SingleLogoutService method
   * @return RedirectResponse
   */
  public function logout() {
    $post = TRUE;
    if ($this->config->get('debug')) {
      $this->logger->debug('logout %post', array('%post' => $_REQUEST['SAMLResponse']));
    }
    if (!isset($_POST['SAMLResponse'])) {
      $post = FALSE;
      $request = isset($_REQUEST['SAMLResponse']) ? $_REQUEST['SAMLResponse'] : NULL;
    }
    else {
      $request = $_POST['SAMLResponse'];
    }
    if (empty($request)) {
      //exit('slo');
      $this->spid->getSloRequest();
    }
    $resp = base64_decode($request);
    if (!$post) {
      $resp = gzinflate($resp);
      if ($resp === FALSE) {
        drupal_set_message($this->t('error inflating response'), 'warning');
        return $this->redirect('<front>');
      }
    }
    $success = $this->spid->logoutResponseControl($resp, $post);
    if ($success) {
      /* Successful logout. */
      $msg = $this->t("You have been logged out.");
      $mode = 'status';
    }
    else {
      /* Logout failed. Tell the user to close the browser. */
      $msg = $this->t("We were unable to log you out of all your sessions. To be completely sure that you are logged out, you need to close your web browser.");
      $mode = 'warning';
    }
    drupal_set_message($msg, $mode);
    return $this->redirect('<front>');
  }
  
  /**
   * The SP Metadata method
   * @return RedirectResponse | NULL
   */
  public function metadata() {
    global $base_url;

    $path = $this->config->get('privatepath');
    if (empty($path)) {
      $path = \Drupal::service('file_system')->realpath('private://microspid') . '/cert';
    }
    //$certsManager = \Drupal::service('microspid.certs.manager');
    $cert = $this->certsManager->getCert($path . '/spid-sp.crt');
    if ($this->config->get('debug')) {
      $this->logger->debug('metadata-cert: %cert', array('%cert' => $cert));
    }
    if ($cert === FALSE) {
      drupal_set_message($this->t('certificate not found'), 'warning');
      $response = new RedirectResponse($base_url, RedirectResponse::HTTP_FOUND);
      return $response;
    }

    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header('Content-Description: File Transfer');
    header("Content-type: text/xml");
    header("Content-Disposition: attachment; filename=metadata.xml");
    header("Expires: 0");
    header("Pragma: public");

    $md = $this->spid->loadMetadata('/metadata.xml', TRUE);

    $dnode = $all = dom_import_simplexml($md);
    $dnode->setAttribute('ID', $this->spid->generateGUID());
    $id = $this->config->get('entityid');
    if (empty($id)) $id = $base_url . '/microspid_metadata';
    $dnode->setAttribute('entityID', $id);
    $metadata = $md->children("urn:oasis:names:tc:SAML:2.0:metadata");
    $dnode = dom_import_simplexml($metadata->SPSSODescriptor->SingleLogoutService);
    $dnode->setAttribute('Location', $base_url . '/microspid_logout');
    $dnode = dom_import_simplexml($metadata->SPSSODescriptor->AssertionConsumerService);
    $dnode->setAttribute('Location', $base_url . '/microspid_acs');
    $metadata->Organization->OrganizationName = $metadata->Organization->OrganizationDisplayName = \Drupal::config('system.site')->get('name');
    $metadata->Organization->OrganizationURL = $base_url;

    foreach ($metadata->SPSSODescriptor->KeyDescriptor as $key => $KeyDescriptor) {
      $ds = $KeyDescriptor->children('http://www.w3.org/2000/09/xmldsig#');
      $ds->KeyInfo->X509Data->X509Certificate = $cert;
    }

    $dom = $this->spid->addSign($md->asXML(), $cert);
    echo $dom->saveXML();
    exit;
  }
}
