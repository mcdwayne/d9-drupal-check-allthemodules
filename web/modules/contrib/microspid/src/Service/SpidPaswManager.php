<?php

namespace Drupal\microspid\Service;

use \DOMDocument;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Service to interact with the Spid authentication library.
 */
class SpidPaswManager {

  /**
   * A configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;
  
  /**
   * tracking service manager
   * 
   * 
   */
  protected $tracking;

  /**
   * important values to save.
   *
   */
  protected $entityID;
  protected $nameId;
  protected $session_index;
  protected $idp_filename;

  /**
   * Attributes for federated user.
   *
   * @var array
   */
  protected $attributes;

  /**
   *
   * spid authenticated user
   */
  protected $authenticated = FALSE;
  /**
   * {@inheritdoc}
   *
   * @param ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('microspid.settings');
    $this->tracking = \Drupal::service('microspid.tracking');
  }

  /**
   * Forwards the user to the IdP for authentication.
   */
  public function externalAuthenticate() {
    $uri = \Drupal::request()->getUri();
	  $options = array();
	
    if ((isset($_REQUEST['infocert_id']) && $_REQUEST['infocert_id'])) {
        $options['saml:idp'] = $_REQUEST['infocert_id'];
    } elseif ((isset($_REQUEST['poste_id']) && $_REQUEST['poste_id'])) {
        $options['saml:idp'] = $_REQUEST['poste_id'];
    } elseif ((isset($_REQUEST['tim_id']) && $_REQUEST['tim_id'])) {
        $options['saml:idp'] = $_REQUEST['tim_id'];
    } elseif ((isset($_REQUEST['sielte_id']) && $_REQUEST['sielte_id'])) {
        $options['saml:idp'] = $_REQUEST['sielte_id'];
    } elseif ((isset($_REQUEST['aruba_id']) && $_REQUEST['aruba_id'])) {
        $options['saml:idp'] = $_REQUEST['aruba_id'];
    } elseif ((isset($_REQUEST['namirial_id']) && $_REQUEST['namirial_id'])) {
        $options['saml:idp'] = $_REQUEST['namirial_id'];
    } elseif ((isset($_REQUEST['register_id']) && $_REQUEST['register_id'])) {
        $options['saml:idp'] = $_REQUEST['register_id'];
    } elseif ((isset($_REQUEST['intesa_id']) && $_REQUEST['intesa_id'])) {
        $options['saml:idp'] = $_REQUEST['intesa_id'];
    } elseif ((isset($_REQUEST['lepida_id']) && $_REQUEST['lepida_id'])) {
        $options['saml:idp'] = $_REQUEST['lepida_id'];
    } elseif ((isset($_REQUEST['test_id']) && $_REQUEST['test_id'])) {
      $options['saml:idp'] = $_REQUEST['test_id'];
    } elseif ((isset($_REQUEST['demo_id']) && $_REQUEST['demo_id'])) {
      $options['saml:idp'] = $_REQUEST['demo_id'];
    } elseif ((isset($_REQUEST['testonline_id']) && $_REQUEST['testonline_id'])) {
      $options['saml:idp'] = $_REQUEST['testonline_id'];
    } elseif ((isset($_REQUEST['agid_id']) && $_REQUEST['agid_id'])) {
      $options['saml:idp'] = $_REQUEST['agid_id'];
    } else {
        drupal_set_message(t('We\'re sorry. There was a problem. The issue has been logged for the administrator.'));
        $response = new RedirectResponse('');
        $response->send();
        exit;
    }
/*
		$authformat = 'https://www.spid.gov.it/%s';
		$authlevel = $this->config->get('authlevel');
        $options['saml:AuthnContextClassRef'] = sprintf($authformat, $authlevel);
        $options['samlp:RequestedAuthnContext'] = array("Comparison" => "minimum");
		$options['ReturnTo'] = $uri;
*/
	
    $this->authnRequest($options);
  }

  /**
   * Check whether user is authenticated by the IdP.
   *
   * @return bool
   *   If the user is authenticated by the IdP.
   */
  public function isAuthenticated() {
    return $this->authenticated; // TODO $this->instance->isAuthenticated();
  }

  /**
   * Gets the unique id of the user from the IdP.
   *
   * @return string
   *   The authname.
   */
  public function getAuthname() {
    $fn = $this->getFiscalNumber();
    if ($this->config->get('username_fiscalnumber')) {
      return $fn;
    }
    if ($this->config->get('cf') == '') {
       return $fn;
    }
    $account_search = \Drupal::service('entity.manager')->getStorage('user')->loadByProperties([$this->config->get('cf') => $fn]);
    if ($account_search)
      return reset($account_search)->getUsername();
    $firstname = $this->getAttribute('name');
    $lastname = $this->getAttribute('familyName');
    $compname = $this->getAttribute('companyName');
    if (!empty($compname) && (empty($lastname) || trim($lastname)=='')) {
      return $this->removeUnwantedChars(strtolower($compname));
    }
    $newname = $result = $this->removeUnwantedChars(sprintf("%s.%s", strtolower($lastname), strtolower($firstname)));
    if (strlen($newname) < 4) {
      return $fn;
    }
    $i = 0;
    do {
      if ($i > 0) {
        $result = $newname	. '.' . $i;
      }
      $i++;
    } while (\Drupal::service('entity.manager')->getStorage('user')->loadByProperties(['name' => $result])
      || \Drupal::service('entity.manager')->getStorage('user')->loadByProperties(['name' => 'microspid_' . $result])
      );
    return $result;
  }

  /**
   * @param string original string
   * @return string the input string without accents
   */   
  protected function removeUnwantedChars($str)
  {
    $str = \Drupal::service('transliteration')->transliterate($str);
    $a = array(' ');
    $b = array('_');
    return str_replace($a, $b, $str);
  }

  /**
   * Gets the fiscalNumber of the user from the IdP.
   *
   * @return string
   *   The fiscalNumber.
   */
  public function getFiscalNumber() {
    $cf = $this->getAttribute('fiscalNumber');
    if (empty($cf)) {
      throw new \Exception('Error in microspid.module: no valid fiscalNumber.');
    }
    if (strpos($cf, 'TINIT') === 0) {
      return substr($cf, 6);
    }
    return $cf;
  }

  /**
   * Gets the name attribute.
   *
   * @return string
   *   The name attribute.
  public function getDefaultName() {
    return $this->getAttribute($this->config->get('user_name'));
  }
   */

  /**
   * Gets the mail attribute.
   *
   * @return string
   *   The mail attribute.
   */
  public function getDefaultEmail() {
    return $this->getAttribute($this->config->get('mail_attr'));
  }

  /**
   * Gets all SimpleSAML attributes.
   *
   * @return array
   *   Array of SimpleSAML attributes.
   */
  public function getAttributes() {
    /*
    if (!$this->attributes) {
      $this->attributes = $this->instance->getAttributes();
    }
    */
    return $this->attributes;
  }

  /**
   * Get a specific SimpleSAML attribute.
   *
   * @param string $attribute
   *   The name of the attribute.
   *
   * @return mixed|bool
   *   The attribute value or FALSE.
   *
   */
  public function getAttribute($attribute) {
    $attributes = $this->getAttributes();

    if (isset($attributes)) {
      if (!empty($attributes[$attribute])) {
        return $attributes[$attribute];
      }
    }
    return NULL;
  }

  /**
   * Asks all modules if current federated user is allowed to login.
   *
   * @return bool
   *   Returns FALSE if at least one module returns FALSE.
   */
  public function allowUserByAttribute() {
    $attributes = $this->getAttributes();
    foreach (\Drupal::moduleHandler()->getImplementations('microspid_allow_login') as $module) {
      if (\Drupal::moduleHandler()->invoke($module, 'microspid_allow_login', [$attributes]) === FALSE) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Checks if microspid is enabled.
   *
   * @return bool
   *   Whether Spid authentication is enabled or not.
   */
  public function isActivated() {
    if ($this->config->get('activate') == 1) {
      return TRUE;
    }
    return FALSE;
  }

  protected function doSingleLogout() {
    if ($this->config->get('authlevel') == 'SpidL1'
    && $this->config->get('single_logout')
    ) {
      return TRUE;
    }
    return FALSE;
  }
  
  /**
   * Log a user out through the Spid instance.
   *
   * @param string $redirect_path
   *   The path to redirect to after logout.
   */
  public function logout($redirect_path = NULL) {
    if (!$redirect_path) {
      $redirect_path = base_path();
    }

    // Log user logout
    if (isset($_SESSION['spiduser'])) {
      \Drupal::logger('microspid')->notice('User %name disconnecting via SPID', array('%name' => $_SESSION['spiduser'] ));
    }
    if (isset($_SESSION['spiduser']) 
      && $_SESSION['spiduser'] == \Drupal::currentUser()->getUsername() 
      && $this->doSingleLogout()) {
      $options = array(
        'IdP' => $_SESSION['IdP'],
        'EntityID' => $_SESSION['EntityID'],
        'NameID' => $_SESSION['NameID'],
        'SessionIndex' => $_SESSION['SessionIndex'],
      );
      session_destroy();
      $this->logoutRequest($options);
    }
  }
  
  public function getValue($varname) {
    if (!property_exists($this, $varname)) {
      return NULL;
    }
    return $this->$varname;
  }
  
  protected function authnRequest ($options) {
    global $base_url;
    $idp_filename = $this->getIdp($options['saml:idp']);
    //die($idp_filename);
    $idp = $this->loadMetadata($idp_filename, FALSE, $options['saml:idp']);
    if (empty($idp)) {
      drupal_set_message(t('metadata not found'), 'warning', TRUE);
      $response = new RedirectResponse($base_url);
      return $response;
    }

    $url = $this->idpLoginUrl($idp, $post);
    if ($url === FALSE) {
      drupal_set_message(t('connection url not found'), 'warning', TRUE);
      $response = new RedirectResponse($base_url);
      return $response;
    }

    $md = $this->loadMetadata('templates/login.xml');
    $dnode = $all = dom_import_simplexml($md);
    $index = $this->config->get('index');
    $dnode->setAttribute('AssertionConsumerServiceIndex', $index);
    $dnode->setAttribute('AttributeConsumingServiceIndex', $index);
    $authn_req_id = '_' . md5(uniqid(mt_rand(), TRUE));
    $dnode->setAttribute('ID', $authn_req_id);
    $dnode->setAttribute('Destination', $idp->attributes()['entityID']);// $url);
    $instant = gmdate("Y-m-d\TH:i:s\Z");
    $dnode->setAttribute('IssueInstant', $instant);
    $saml = $md->children('urn:oasis:names:tc:SAML:2.0:assertion');
    $dnode = dom_import_simplexml($saml);
    $dnode->setAttribute('NameQualifier', $this->config->get('entityid'));
    $saml->Issuer =  $this->config->get('entityid');
    $protocol = $md->children("urn:oasis:names:tc:SAML:2.0:protocol");
    $saml2 = $protocol->RequestedAuthnContext->children("urn:oasis:names:tc:SAML:2.0:assertion");
    $saml2->AuthnContextClassRef = sprintf('https://www.spid.gov.it/%s',$this->config->get('authlevel'));

    // $xml_data = $md->asXML();
    $xml_data = $this->canonicalizeData($all);
    $this->tracking->createTrack($authn_req_id, $instant, $xml_data);
    
    if ($post === TRUE) {
      $this->post($xml_data, $url, md5($authn_req_id));
    }


    $mod_path = drupal_get_path('module', 'microspid');
    $xml_data = urlencode(base64_encode(gzdeflate($xml_data)));
    $rs = "&RelayState=" . urlencode((string) md5($authn_req_id));
    // 256.
    $sa = '&SigAlg=http%3A%2F%2Fwww.w3.org%2F2001%2F04%2Fxmldsig-more%23rsa-sha256';
    $data = "SAMLRequest=$xml_data$rs$sa";
    
    $certsManager = \Drupal::service('microspid.certs.manager');
    openssl_sign($data, $signature, file_get_contents($certsManager->getPrivateKeyPath()), OPENSSL_ALGO_SHA256);
    $si = '&Signature=' . urlencode(base64_encode($signature));


    header('Pragma: no-cache');
    header('Cache-Control: no-cache, must-revalidate');
    header("Location: $url?SAMLRequest=$xml_data$rs$sa$si");
    exit;
    
  }
  
  protected function logoutRequest ($options) {
    global $base_url;
    $idp = $this->loadMetadata($options['IdP'], FALSE, $options['EntityID']);
    if (empty($idp)) {
      drupal_set_message(t('metadata not found'), 'warning', TRUE);
      $response = new RedirectResponse($base_url);
      return $response;
    }

    $url = $this->idpLogoutUrl($idp, $post);
    if ($url === FALSE) {
      drupal_set_message(t('connection url not found'), 'warning', TRUE);
      $response = new RedirectResponse($base_url);
      return $response;
    }

    $req = $this->loadMetadata('templates/logout.xml');
    $dnode = dom_import_simplexml($req);
    $logout_req_id = '_' . md5(uniqid(mt_rand(), TRUE));
    $dnode->setAttribute('ID', $logout_req_id);
    $dnode->setAttribute('Destination', $idp->attributes()['entityID']);// $url);
    $dnode->setAttribute('IssueInstant', gmdate("Y-m-d\TH:i:s\Z"));
    $saml = $req->children('urn:oasis:names:tc:SAML:2.0:assertion');
    $dnode = dom_import_simplexml($saml);
    $dnode->setAttribute('NameQualifier', $this->config->get('entityid'));
    $saml->Issuer =  $this->config->get('entityid');
    $dnode = dom_import_simplexml($saml->NameID);
    $dnode->setAttribute('NameQualifier', $options['EntityID']);
    $saml->NameID = $options['NameID'];
    $protocol = $req->children("urn:oasis:names:tc:SAML:2.0:protocol");
    $protocol->SessionIndex = $options['SessionIndex'];
    $xml_data = $req->asXML();

    if ($post === TRUE) {
      $this->post($xml_data, $url, md5($logout_req_id));
    }

    $mod_path = drupal_get_path('module', 'microspid');
    $xml_data = urlencode(base64_encode(gzdeflate($xml_data)));
    $rs = "&RelayState=" . urlencode((string) md5($logout_req_id));
    $sa = '&SigAlg=http%3A%2F%2Fwww.w3.org%2F2001%2F04%2Fxmldsig-more%23rsa-sha256';
    $data = "SAMLRequest=" . $xml_data . $rs . $sa;
    $certsManager = \Drupal::service('microspid.certs.manager');
    openssl_sign($data, $signature, file_get_contents($certsManager->getPrivateKeyPath()), OPENSSL_ALGO_SHA256);
    $si = '&Signature=' . urlencode(base64_encode($signature));

    header('Pragma: no-cache');
    header('Cache-Control: no-cache, must-revalidate');
    header("Location: $url?$data$si");
    exit;
  }

  public function loadMetadata($filename, $public = FALSE, $entity_id = NULL) {
    $pathname = drupal_get_path('module', 'microspid') . '/metadata/' . $filename;
    if ($public) {
      $pathname = \Drupal::service('file_system')->realpath('public://microspid') . $filename;
    }
    if ($filename != 'spid-entities-idps.xml') {
      return simplexml_load_file($pathname);
    }
    $entities_file = simplexml_load_file($pathname);
    if ($entities_file === FALSE) {
      return NULL;
    }
    $entities = $entities_file->children("urn:oasis:names:tc:SAML:2.0:metadata");
    foreach ($entities->EntityDescriptor as $descriptor) {
      if ($descriptor->attributes()['entityID'] == $entity_id) {
        return $descriptor;
      }
    }
    return NULL;     
  }

  public function acs($resp) {
    global $base_url;
    
    $data = new \SimpleXMLElement($resp);

    // Prelevare inresponseto etc.
    $requestID = $data->attributes()['InResponseTo'];
    $responseID = $data->attributes()['ID'];
    $instant = $data->attributes()['IssueInstant'];

    // Controllare destination.
    $destination = $data->attributes()['Destination'];

    if ($destination != $base_url . '/microspid_acs') {
      return t('Invalid destination');
    }

    // Controllare issuer.
    $assertion = $data->children("urn:oasis:names:tc:SAML:2.0:assertion");
    $this->entityID = (string) $assertion->Issuer;
    $tmp = clone($data);
    $tmp->registerXPathNamespace('ds',"http://www.w3.org/2000/09/xmldsig#");
    $sign = $tmp->xpath('//ds:Signature');
    if (empty($sign)) {
      return(t('No signature found'));
    }
    $dom = dom_import_simplexml($sign[0]);
    $owner = $dom->ownerDocument;
    $dom->parentNode->removeChild($dom);
    $count = $this->tracking->updateTrack($requestID, $responseID, $instant, $owner->saveXML(), $this->entityID);
    if($count < 1) {
      return t('no such request');
    }
    $this->idp_filename = $this->getIdp($this->entityID);
    if (empty($this->idp_filename)) {
      return t("Can't find IdP metadata");
    }
    // Controllare firma.
    $metadata = $this->loadMetadata($this->idp_filename, FALSE, $this->entityID);
    $md = $metadata->children("urn:oasis:names:tc:SAML:2.0:metadata");
    $ds = $md->IDPSSODescriptor->KeyDescriptor->children("http://www.w3.org/2000/09/xmldsig#");
    $cert = $ds->KeyInfo->X509Data->X509Certificate;

    $test = $this->validateSign($resp, $cert);
    if ($test !== TRUE) {
      return($test === FALSE ? t('Invalid data') : $test);
    }
    // Controllare se successo.
    $protocol = $data->children("urn:oasis:names:tc:SAML:2.0:protocol");
    if ($protocol->Status->StatusCode->attributes()['Value'] == 'urn:oasis:names:tc:SAML:2.0:status:Success') {
      $this->authenticated = TRUE;
    }
    else {
      return $this->agidError((string) $protocol->Status->StatusMessage);
    }

    // Session id.
    $authn = $assertion->Assertion->AuthnStatement;
    $this->session_index = $authn->attributes()['SessionIndex'];

    // NameID.
    $this->nameId = (string) $assertion->Assertion->Subject->NameID;

    // Finestra temporale.
    if (property_exists($assertion->Assertion, 'Conditions')) {
      $before = $assertion->Assertion->Conditions->attributes()['NotBefore'];
      $onorafter = $assertion->Assertion->Conditions->attributes()['NotOnOrAfter'];
      if (!empty($before) && !empty($onorafter)) {
        $before = $this->samlToUnixTime($before);
        $onorafter = $this->samlToUnixTime($onorafter);
        $now = time();
        if (($now + 60) < $before || ($now - 60) >= $onorafter) {
          return t('time out of terms');
        }
      }
    }

    // Attributi SPID.
    $attributes = $assertion->Assertion->AttributeStatement;
    foreach ($attributes->Attribute as $attribute) {
      $this->attributes[(string) $attribute->attributes()['Name']] = (string)$attribute->AttributeValue;
      if ($this->config->get('debug')) {
        \Drupal::logger('microspid')->debug('%name: %attr', array('%name' => (string) $attribute->attributes()['Name'], '%attr' => (string)$attribute->AttributeValue));
      }
    }
    return TRUE;
  }

  /**
   * @TODO [microspid_get_slo_request description]
   * @method microspid_get_slo_request
   * @return [type]                    [description]
   */
  protected function getSloRequest() {
    $success = FALSE;
    $post = TRUE;
    if (!isset($_POST['SAMLRequest'])) {
      $post = FALSE;
      $request = isset($_REQUEST['SAMLRequest']) ? $_REQUEST['SAMLRequest'] : NULL;
      $relay = isset($_REQUEST['RelayState']) ? $_REQUEST['RelayState'] : NULL;
    }
    else {
      $request = $_POST['SAMLRequest'];
      $relay = isset($_POST['RelayState']) ? $_POST['RelayState'] : NULL;
    }
    // TODO! uncomment if block.
    if (empty($request)) {
      throw new \Exception(t('nothing to do'));
    }
    $request = base64_decode($request);
    if (!$post) {
      $request = gzinflate($request);
      // TODO! FALSE.
      if ($request === FALSE) {
        drupal_set_message(t('error inflating response'), 'warning');
        $response = new RedirectResponse('');
        $response->send();
        exit;
      }
    }
    // TODO! comment next line
    // $request = file_get_contents('/home/drupal/slo/2.xml');.
    $data = new \SimpleXMLElement($request);
    $requestID = $data->attributes()['ID'];
    $assertion = $data->children("urn:oasis:names:tc:SAML:2.0:assertion");
    $entityID = (string) $assertion->Issuer;
    $idp = $this->getIdp($entityID);
    if (empty($idp)) {
        drupal_set_message(t("Can't find IdP metadata"), 'warning');
        $response = new RedirectResponse('');
        $response->send();
        exit;
    }
    $metadata = $this->loadMetadata($idp, FALSE, $entityID);
    $md = $metadata->children("urn:oasis:names:tc:SAML:2.0:metadata");
    $ds = $md->IDPSSODescriptor->KeyDescriptor->children("http://www.w3.org/2000/09/xmldsig#");
    $cert = $ds->KeyInfo->X509Data->X509Certificate;

    if ($post) {
      $test = $this->validateSign($request, $cert);
    }
    else {
      $test = $this->validateSignRedirect($_SERVER['QUERY_STRING'], $cert);
    }
    // Uncomment if block.
    if ($test !== TRUE) {
      drupal_set_message($test === FALSE ? t('Invalid data') : $test, 'warning');
      $response = new RedirectResponse('');
      $response->send();
      exit;
    }

    $destroy = TRUE;
    $protocol = $data->children("urn:oasis:names:tc:SAML:2.0:protocol");
    if (empty($_SESSION['SessionIndex']) || $protocol->SessionIndex != $_SESSION['SessionIndex']) {
      $destroy = FALSE;
    }

    $this->sloResponse($metadata, $requestID, $entityID, $relay, $destroy);
  }

  /**
   * @TODO [microspid_slo_response description]
   * @method microspid_slo_response
   * @param  [type] $metadata
   *   [description].
   * @param  [type] $inrespto
   *   [description].
   * @param  [type] $destination
   *   [description].
   * @param  [type] $relay
   *   [description].
   * @param  [type] $destroy
   *   [description].
   * @return [type]                              [description]
   */
  protected function sloResponse($metadata, $inrespto, $destination, $relay, $destroy) {
    global $base_url;
    $url = $this->idpResponseUrl($metadata, $post);
    if ($url === FALSE) {
      drupal_set_message(t('connection url not found'));
      $response = new RedirectResponse('');
      $response->send();
      exit;
    }
    $md = $this->loadMetadata('templates/response.xml');
    $dnode = dom_import_simplexml($md);
    $resp_req_id = '_' . md5(uniqid(mt_rand(), TRUE));
    $dnode->setAttribute('ID', $resp_req_id);
    $dnode->setAttribute('InResponseTo', $inrespto);
    $dnode->setAttribute('Destination', $url /*$destination*/);
    $instant = gmdate("Y-m-d\TH:i:s\Z");
    $dnode->setAttribute('IssueInstant', $instant);
    $assertion = $md->children("urn:oasis:names:tc:SAML:2.0:assertion");
    $dnode = dom_import_simplexml($assertion);
    $dnode->setAttribute('NameQualifier', $this->config->get('entityid'));
    $assertion->Issuer = $this->config->get('entityid');

    if ($destroy) {
      session_destroy();
    }

    $xml_data = $md->asXML();

    if ($post === TRUE) {
      $this->post($xml_data, $url, $relay, TRUE);
    }

    $mod_path = drupal_get_path('module', 'microspid');
    $xml_data = urlencode(base64_encode(gzdeflate($xml_data)));
    $rs = $relay === NULL ? '' : "&RelayState=" . urlencode((string) $relay);
    $sa = '&SigAlg=http%3A%2F%2Fwww.w3.org%2F2001%2F04%2Fxmldsig-more%23rsa-sha256';
    $data = "SAMLResponse=" . $xml_data . $rs . $sa;
    openssl_sign($data, $signature, file_get_contents($this->getPrivateKeyPath()), OPENSSL_ALGO_SHA256);
    $si = '&Signature=' . urlencode(base64_encode($signature));

    header('Pragma: no-cache');
    header('Cache-Control: no-cache, must-revalidate');
    header("Location: $url?$data$si");
    exit;
  }

  public function logoutResponseControl($resp, $post) {
    $success = FALSE;
    $data = new \SimpleXMLElement($resp);
    $assertion = $data->children("urn:oasis:names:tc:SAML:2.0:assertion");
    $entityID = (string) $assertion->Issuer;
    $idp = $this->getIdp($entityID);
    if (empty($idp)) {
        drupal_set_message(t("Can't find IdP metadata"), 'warning');
        $response = new RedirectResponse('');
        $response->send();
        exit;
    }
    $metadata = $this->loadMetadata($idp, FALSE, $entityID);
    $md = $metadata->children("urn:oasis:names:tc:SAML:2.0:metadata");
    $ds = $md->IDPSSODescriptor->KeyDescriptor->children("http://www.w3.org/2000/09/xmldsig#");
    $cert = $ds->KeyInfo->X509Data->X509Certificate;

    if ($post) {
      $test = $this->validateSign($resp, $cert);
    }
    else {
      $test = $this->validateSignRedirect($_SERVER['QUERY_STRING'], $cert);
    }
    if ($test !== TRUE) {
      drupal_set_message($test === FALSE ? t('Invalid data') : $test, 'warning');
      $response = new RedirectResponse('');
      $response->send();
      exit;
    }
    $protocol = $data->children("urn:oasis:names:tc:SAML:2.0:protocol");
    if ($protocol->Status->StatusCode->attributes()['Value'] == 'urn:oasis:names:tc:SAML:2.0:status:Success') {
      $success = TRUE;
    }
    return $success;
  }
  
  /**
   * @TODO [microspid_generateGUID description]
   * @method microspid_generateGUID
   * @param  string $prefix
   *   [description].
   * @return [type]                         [description]
   */
  public function generateGUID($prefix = 'pfx') {
    $uuid = md5(uniqid(mt_rand(), TRUE));
    $guid = $prefix . substr($uuid, 0, 8) . "-" .
            substr($uuid, 8, 4) . "-" .
            substr($uuid, 12, 4) . "-" .
            substr($uuid, 16, 4) . "-" .
            substr($uuid, 20, 12);
    return $guid;
  }

  /**
   * @TODO [microspid_get_idp description]
   * @method microspid_get_idp
   * @param  [type] $entityID
   *   [description].
   * @return [type]                      [description]
   */
  
  protected function getIdp($entityID) {
    $entityID = trim($entityID);
    $array = [
      'https://loginspid.aruba.it' => 'spid-entities-idps.xml',
      'https://identity.infocert.it' => 'spid-entities-idps.xml',
      'https://spid.intesa.it' => 'spid-entities-idps.xml',
      'https://id.lepida.it/idp/shibboleth' => 'spid-entities-idps.xml',
      'https://idp.namirialtsp.com/idp' => 'spid-entities-idps.xml',
      'https://posteid.poste.it' => 'spid-entities-idps.xml',
      'https://spid.register.it' => 'spid-entities-idps.xml',
      'https://identity.sieltecloud.it' => 'spid-entities-idps.xml',
      'https://login.id.tim.it/affwebservices/public/saml2sso' => 'spid-entities-idps.xml',
      'spid-testenv-identityserver' => 'test.xml',
      'http://localhost:8088' => 'testenv2.xml',
      'https://idp.spid.gov.it' => 'test-online.xml',
      'https://validator.spid.gov.it' => 'agid.xml',
    ];
    
    return isset($array[$entityID])? $array[$entityID] : NULL;
  }
  

  protected function idpLoginUrl($xml, &$post) {
    $metadata = $xml->children("urn:oasis:names:tc:SAML:2.0:metadata");
    $one = $metadata->IDPSSODescriptor->SingleSignOnService[0];
    $two = $metadata->IDPSSODescriptor->SingleSignOnService[1];
    if ($one->attributes()['Binding'] == 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect') {
      $post = FALSE;
    }
    if ($one->attributes()['Binding'] == 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST') {
      $post = TRUE;
    }
    if ($post !== -1) {
      return $one->attributes()['Location'];
    }
    if ($two->attributes()['Binding'] == 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect') {
      $post = FALSE;
    }
    if ($two->attributes()['Binding'] == 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST') {
      $post = TRUE;
    }
    if ($post !== -1) {
      return $two->attributes()['Location'];
    }
    return FALSE;
  }
  
  protected function idpLogoutUrl($xml, &$post) {
    $post = -1;
    $metadata = $xml->children("urn:oasis:names:tc:SAML:2.0:metadata");
    $one = $metadata->IDPSSODescriptor->SingleLogoutService[0];
    $two = $metadata->IDPSSODescriptor->SingleLogoutService[1];
    if ($one->attributes()['Binding'] == 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect') {
      $post = FALSE;
    }
    if ($one->attributes()['Binding'] == 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST') {
      $post = TRUE;
    }
    if ($post !== -1) {
      return $one->attributes()['Location'];
    }
    if ($two->attributes()['Binding'] == 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect') {
      $post = FALSE;
    }
    if ($two->attributes()['Binding'] == 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST') {
      $post = TRUE;
    }
    if ($post !== -1) {
      return $two->attributes()['Location'];
    }
    return FALSE;
  }

  /**
   * @TODO [microspid_idp_response_url description]
   * @method microspid_idp_response_url
   * @param  [type] $xml
   *   [description].
   * @param  [type] $post
   *   [description].
   * @return [type]                           [description]
   */
  protected function idpResponseUrl($xml, &$post) {
    $post = -1;
    $metadata = $xml->children("urn:oasis:names:tc:SAML:2.0:metadata");
    $one = $metadata->IDPSSODescriptor->SingleLogoutService[0];
    $two = $metadata->IDPSSODescriptor->SingleLogoutService[1];
    if ($one->attributes()['Binding'] == 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect') {
      $post = FALSE;
    }
    if ($one->attributes()['Binding'] == 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST') {
      $post = TRUE;
    }
    $check = $_SERVER['REQUEST_METHOD'] === 'POST' ? TRUE : FALSE;
    if ($post === $check) {
      return isset($one->attributes()['ResponseLocation']) ? $one->attributes()['ResponseLocation'] : $one->attributes()['Location'];
    }
    if ($two->attributes()['Binding'] == 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect') {
      $post = FALSE;
    }
    if ($two->attributes()['Binding'] == 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST') {
      $post = TRUE;
    }
    if ($post !== -1) {
      return isset($two->attributes()['ResponseLocation']) ? $two->attributes()['ResponseLocation'] : $two->attributes()['Location'];
    }
    return FALSE;
  }

  protected function canonicalizeData($node, $arXPath = NULL, $prefixList = NULL) {
    $exclusive = TRUE;
    $withComments = FALSE;

    if (is_null($arXPath) && ($node instanceof DOMNode) && ($node->ownerDocument !== NULL) && $node->isSameNode($node->ownerDocument->documentElement)) {
      /* Check for any PI or comments as they would have been excluded */
      $element = $node;
      while ($refnode = $element->previousSibling) {
        if ($refnode->nodeType == XML_PI_NODE || (($refnode->nodeType == XML_COMMENT_NODE) && $withComments)) {
          break;
        }
        $element = $refnode;
      }
      if ($refnode == NULL) {
        $node = $node->ownerDocument;
      }
    }

    return $node->C14N($exclusive, $withComments, $arXPath, $prefixList);
  }

  protected function selectAlgo($uri) {
    switch ($uri) {
      case 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256':
        return OPENSSL_ALGO_SHA256;

      case 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha384':
        return OPENSSL_ALGO_SHA384;

      case 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha512':
        return OPENSSL_ALGO_SHA512;

      default:
        return FALSE;
    }
  }

  /**
   * @TODO [microspid_validate_sign description]
   * @method microspid_validate_sign
   * @param  [type] $xml
   *   [description].
   * @param  [type] $cert
   *   [description].
   * @return [type]                        [description]
   */
  public function validateSign($xml, $cert) {
    $dom = new \DOMDocument();
    $oldEntityLoader = libxml_disable_entity_loader(TRUE);
    $res = $dom->loadXML($xml);
    libxml_disable_entity_loader($oldEntityLoader);
    $rootNode = $dom->firstChild;
    $xpath = new \DOMXPath($dom);
    $xpath->registerNamespace('secdsig', 'http://www.w3.org/2000/09/xmldsig#');
    $query = ".//secdsig:Signature";
    $nodeset = $xpath->query($query, $rootNode);
    $sigNode = $nodeset->item(0);
    $query = "string(./secdsig:SignedInfo/secdsig:SignatureMethod/@Algorithm)";
    $algorithm = $xpath->evaluate($query, $sigNode);
    $algo = $this->selectAlgo($algorithm);
    if ($algo === FALSE) {
      return t('Invalid algorithm (') . $algorithm . ')';
    }
    $query = "./secdsig:SignedInfo";
    $nodeset = $xpath->query($query, $sigNode);
    if ($signInfoNode = $nodeset->item(0)) {
      $signedInfo = $this->canonicalizeData($signInfoNode);
    }
    $docElem = $dom->documentElement;
    if (!$docElem->isSameNode($sigNode)) {
      $sigNode->parentNode->removeChild($sigNode);
    }
    $query = "./secdsig:SignedInfo/secdsig:Reference";
    $nodeset = $xpath->query($query, $sigNode);
    $refNode = $nodeset->item(0);

    $query = './secdsig:Transforms/secdsig:Transform';
    $nodelist = $xpath->query($query, $refNode);
    $arXPath = NULL;
    $prefixList = NULL;
    foreach ($nodelist as $transform) {
      $node = $transform->firstChild;
      while ($node) {
        if ($node->localName == 'InclusiveNamespaces') {
          if ($pfx = $node->getAttribute('PrefixList')) {
            $arpfx = array();
            $pfxlist = explode(" ", $pfx);
            foreach ($pfxlist as $pfx) {
              $val = trim($pfx);
              if (!empty($val)) {
                $arpfx[] = $val;
              }
            }
            if (count($arpfx) > 0) {
              $prefixList = $arpfx;
            }
          }
          break;
        }
        $node = $node->nextSibling;
      }
    }

    $result = FALSE;
    if ($uri = $refNode->getAttribute("URI")) {
      $arUrl = parse_url($uri);
      if (empty($arUrl['path'])) {
        if ($identifier = $arUrl['fragment']) {
          $iDlist = '@Id="' . $identifier . '"';
          $iDlist .= " or @ID='$identifier'";
          $query = '//*[' . $iDlist . ']';
          $dataObject = $xpath->query($query)->item(0);
          $data = $this->canonicalizeData($dataObject, $arXPath, $prefixList);
          $query = 'string(./secdsig:DigestMethod/@Algorithm)';
          $digestAlgorithm = $xpath->evaluate($query, $refNode);
          switch ($digestAlgorithm) {
            case 'http://www.w3.org/2001/04/xmlenc#sha256':
              $alg = 'sha256';
              break;

            case 'http://www.w3.org/2001/04/xmldsig-more#sha384':
              $alg = 'sha384';
              break;

            case 'http://www.w3.org/2001/04/xmlenc#sha512':
              $alg = 'sha512';
              break;

            default:
              throw new Exception("Cannot validate digest: Unsupported Algorithm <$digestAlgorithm>");
          }
          $digest = hash($alg, $data, TRUE);
          $query = 'string(./secdsig:DigestValue)';
          $digestValue = $xpath->evaluate($query, $refNode);
          if ($digest != base64_decode($digestValue)) {
            return t('Digest value is not valid');
          }
          else {
            $result = TRUE;
          }
        }
      }
    }

    if ($result === FALSE) {
      return FALSE;
    }

    $query = "string(./secdsig:SignatureValue)";
    $sigValue = $xpath->evaluate($query, $sigNode);

    $cert = "-----BEGIN CERTIFICATE-----\n" . trim($cert) . "\n-----END CERTIFICATE-----\n";

    return openssl_verify($signedInfo, base64_decode($sigValue), $cert, $algo) == 1 ? TRUE : t('Invalid signature');
  }

  /**
   * @TODO [microspid_validate_sign_redirect description]
   * @method microspid_validate_sign_redirect
   * @param  [type] $query
   *   [description].
   * @param  [type] $cert
   *   [description].
   * @return [type]                                  [description]
   */
  public function validateSignRedirect($query, $cert) {
    $pos = strpos($query, '&Signature');
    if ($pos === FALSE) {
      return FALSE;
    }
    $check = substr($query, 0, $pos);
    $sigValue = urldecode(substr($query, $pos + 11));
    $pos = strpos($check, '&SigAlg');
    $algorithm = urldecode(substr($check, $pos + 8));
    $algo = $this->selectAlgo($algorithm);
    if ($algo === FALSE) {
      return t('Invalid algorithm (') . $algorithm . ')';
    }

    $cert = "-----BEGIN CERTIFICATE-----\n" . trim($cert) . "\n-----END CERTIFICATE-----\n";

    return openssl_verify($check, base64_decode($sigValue), $cert, $algo) == 1 ? TRUE : t('Invalid signature');
  }
  
  /**
   * @TODO [microspid_add_sign description]
   * @method microspid_add_sign
   * @param  [type] $xml
   *   [description].
   * @param  [type] $cert
   *   [description].
   */
  public function addSign($xml, $cert) {
    $dom = new \DOMDocument();
    $oldEntityLoader = libxml_disable_entity_loader(TRUE);
    $res = $dom->loadXML($xml);
    libxml_disable_entity_loader($oldEntityLoader);
    $rootNode = $dom->firstChild;
    // print_r($rootNode);exit;
    $template = '
  <ds:Signature xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
    <ds:SignedInfo>
      <ds:CanonicalizationMethod Algorithm="http://www.w3.org/2001/10/xml-exc-c14n#" />
      <ds:SignatureMethod Algorithm="http://www.w3.org/2001/04/xmldsig-more#rsa-sha256" />
    </ds:SignedInfo>
    <ds:KeyInfo>
      <ds:X509Data>
        <ds:X509Certificate></ds:X509Certificate>
      </ds:X509Data>
    </ds:KeyInfo>
  </ds:Signature>';
    $xmldsig = 'http://www.w3.org/2000/09/xmldsig#';
    $sigdoc = new \DOMDocument();
    $sigdoc->loadXML($template);
    $sigNode = $sigdoc->documentElement;

    $xpath = new \DOMXPath($sigdoc);
    $xpath->registerNamespace('secdsig', $xmldsig);
    $query = "./secdsig:SignedInfo";
    $nodeset = $xpath->query($query, $sigNode);
    $infoNode = $nodeset->item(0);

    $refNode = $sigdoc->createElementNS($xmldsig, 'ds:Reference');
    $infoNode->appendChild($refNode);
    $uri = $rootNode->getAttribute('ID');
    $refNode->setAttribute("URI", '#' . $uri);
    // Echo $sigdoc->saveXML();exit;
    $transNodes = $sigdoc->createElementNS($xmldsig, 'ds:Transforms');
    $refNode->appendChild($transNodes);
    $transNode = $sigdoc->createElementNS($xmldsig, 'ds:Transform');
    $transNodes->appendChild($transNode);
    $transNode->setAttribute('Algorithm', "http://www.w3.org/2000/09/xmldsig#enveloped-signature");
    $transNode = $sigdoc->createElementNS($xmldsig, 'ds:Transform');
    $transNodes->appendChild($transNode);
    $transNode->setAttribute('Algorithm', "http://www.w3.org/2001/10/xml-exc-c14n#");
    $canonicalData = $this->canonicalizeData($rootNode);
    $digValue = base64_encode(hash("sha256", $canonicalData, TRUE));

    $digestMethod = $sigdoc->createElementNS($xmldsig, 'ds:DigestMethod');
    $refNode->appendChild($digestMethod);
    $digestMethod->setAttribute('Algorithm', "http://www.w3.org/2001/04/xmlenc#sha256");

    $digestValue = $sigdoc->createElementNS($xmldsig, 'ds:DigestValue', $digValue);
    $refNode->appendChild($digestValue);

    $data = $this->canonicalizeData($infoNode);
    $certsManager = \Drupal::service('microspid.certs.manager');

    $pKeyPath = $certsManager->getPrivateKeyPath();
    if ($this->config->get('debug')) {
      \Drupal::logger('microspid')->debug('pkey path: %path', array('%path' => $pKeyPath));
    }

    // Fetch private key from file and ready it.
    $pKeyId = openssl_pkey_get_private('file://' . $pKeyPath);
    if (!$pKeyId) {
      \Drupal::logger('microspid')->error('failure on read private key');
      exit();
    }
    // Compute signature.
    openssl_sign($data, $signature, $pKeyId, OPENSSL_ALGO_SHA256);
    // Free the key from memory.
    openssl_free_key($pKeyId);
    $sigValue = base64_encode($signature);

    $sigValueNode = $sigdoc->createElementNS($xmldsig, 'ds:SignatureValue', $sigValue);
    if ($infoSibling = $infoNode->nextSibling) {
      $infoSibling->parentNode->insertBefore($sigValueNode, $infoSibling);
    }
    else {
      $sigNode->appendChild($sigValueNode);
    }

    $query = "./secdsig:KeyInfo/secdsig:X509Data/secdsig:X509Certificate";
    $nodeset = $xpath->query($query, $sigNode);
    $certNode = $nodeset->item(0);
    $certNode->nodeValue = $cert;

    $signatureElement = $dom->importNode($sigNode, TRUE);
    $insertBefore = $rootNode->firstChild;
    $messageTypes = array('AuthnRequest', 'Response', 'LogoutRequest', 'LogoutResponse');
    if (in_array($rootNode->localName, $messageTypes)) {
      $issuerNodes = $this->query($dom, '/' . $rootNode->tagName . '/saml:Issuer');
      if ($issuerNodes->length == 1) {
        $insertBefore = $issuerNodes->item(0)->nextSibling;
      }
    }

    $rootNode->insertBefore($signatureElement, $insertBefore);

    return $dom;
  }

  /**
   * @TODO [microspid_post description]
   * @method microspid_post
   * @param  [type] $xml
   *   [description].
   * @param  [type] $url
   *   [description].
   * @param  [type] $rs
   *   [description].
   * @param  bool $response
   *   [description].
   * @return [type]                   [description]
   */

  public function post($xml, $url, $rs, $response = FALSE) {
    $path = $this->config->get('privatepath');
    if (empty($path)) {
      $path = \Drupal::service('file_system')->realpath('private://microspid') . '/cert';
    }
    $certsManager = \Drupal::service('microspid.certs.manager');
    $cert = $certsManager->getCert($path.'/spid-sp.crt');
    $dom = $this->addSign($xml, $cert);

    if ($this->config->get('debug')) {
      \Drupal::logger('microspid')->debug('xml: %xml', array('%xml' => $dom->saveXML()));
    }

    $data = base64_encode($dom->saveXML());
    $action = $response ? "SAMLResponse" : "SAMLRequest";
    // $rs = microspid_get_relaystate(); // '_' . md5(uniqid(mt_rand(), true));//.
    $input = $rs === NULL ? '' : "<input type=\"hidden\" name=\"RelayState\" value=\"$rs\">";

    $page = <<<PAGINA
  <html>
    <body onload="javascript:document.forms[0].submit()">
      Attendere...
      <form method="post" action="$url">
        $input
        <input type="hidden" name="$action" value="$data">
        <input type="submit" style="display:none" value="Go"/>
      </form>
    </body>
  </html>
PAGINA;
    exit($page);
  }

  public function query($dom, $query, $context = NULL) {
    $xpath = new \DOMXPath($dom);
    $xpath->registerNamespace('samlp', 'urn:oasis:names:tc:SAML:2.0:protocol');
    $xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');
    $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
    if (isset($context)) {
      $res = $xpath->query($query, $context);
    }
    else {
      $res = $xpath->query($query);
    }
    return $res;
  }

  /**
   * @TODO [microspid_saml2unix_ts description]
   * @method microspid_saml2unix_ts
   * @param  [type] $time
   *   [description].
   * @return [type]                       [description]
   */
  function samlToUnixTime($time) {
    $matches = array();

    // We use a very strict regex to parse the timestamp.
    $regex = '/^(\\d\\d\\d\\d)-(\\d\\d)-(\\d\\d)T(\\d\\d):(\\d\\d):(\\d\\d)(?:\\.\\d+)?Z$/D';
    if (preg_match($regex, $time, $matches) == 0) {
      throw new \Exception(
            'Invalid SAML2 timestamp: ' . $time
        );
    }

    // Extract the different components of the time from the  matches in the regex.
    // intval will ignore leading zeroes in the string.
    $year   = intval($matches[1]);
    $month  = intval($matches[2]);
    $day    = intval($matches[3]);
    $hour   = intval($matches[4]);
    $minute = intval($matches[5]);
    $second = intval($matches[6]);

    // We use gmmktime because the timestamp will always be given
    // in UTC.
    $ts = gmmktime($hour, $minute, $second, $month, $day, $year);

    return $ts;
  }

  /**
   * @TODO [microspid_agid_error description]
   * @method microspid_agid_error
   * @param  [type] $error_code
   *   [description].
   * @return [type]                           [description]
   */
  protected function agidError($error_code) {
    $error_code_filtered = \Drupal\Component\Utility\Xss::filter($error_code);
    switch ($error_code) {
      case 'ErrorCode nr08':
        return $error_code . t('. Not a SAML request.');

      case 'ErrorCode nr09':
        return $error_code . t('. Version parameter incorrect.');

      case 'ErrorCode nr11':
        return $error_code . t('. ID incorrect.');

      case 'ErrorCode nr12':
        return $error_code . t('. RequestAuthnContext incorrect.');

      case 'ErrorCode nr13':
        return $error_code . t('. IssueInstant incorrect.');

      case 'ErrorCode nr14':
        return $error_code . t('. destination incorrect.');

      case 'ErrorCode nr15':
        return $error_code . t('. isPassive incorrect.');

      case 'ErrorCode nr16':
        return $error_code . t('. AssertionConsumerService incorrect.');

      case 'ErrorCode nr17':
        return $error_code . t('. NameIDPolicy/Format element incorrect.');

      case 'ErrorCode nr18':
        return $error_code . t('. AttributeConsumerServiceIndex incorrect.');

      case 'ErrorCode nr19':
        return $error_code . t('. Credentials incorrect.');

      case 'ErrorCode nr20':
        return $error_code . t('. Level mismatch.');

      case 'ErrorCode nr21':
        return $error_code . t('. Timeout.');

      case 'ErrorCode nr22':
        return $error_code . t('. User denies consent.');

      case 'ErrorCode nr23':
        return $error_code . t('. User credendials blocked.');

      case 'ErrorCode nr25':
        return $error_code . t('. User cancels request.');

      default:
        return $error_code_filtered . t('. Unknown issue.');
    }
  }

}
