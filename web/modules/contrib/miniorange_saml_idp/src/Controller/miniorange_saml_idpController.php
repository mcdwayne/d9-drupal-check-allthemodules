<?php /**
 * @file
 * Contains \Drupal\miniorange_saml_idp\Controller\DefaultController.
 */

namespace Drupal\miniorange_saml_idp\Controller;

use Drupal\miniorange_saml_idp\GenerateResponse;
use Drupal\user\Entity\User;
use Drupal\Core\Session;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\miniorange_saml_idp\Utilities;
use Drupal\miniorange_saml_idp\MiniOrangeAuthnRequest;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\Cookie;

use Drupal\miniorange_saml_idp\XMLSecurityKey;
use Drupal\Core\DependencyInjection;
use DOMElement;
use DOMDocument;

class miniorange_saml_idpController extends ControllerBase {

function miniorange_saml_idp_metadata(){

    \Drupal\miniorange_saml_idp\Controller\miniorange_saml_idpController::_generate_metadata();
 
}

function _generate_metadata(){
 
  global $base_url; 
  
  $site_url = $base_url . '/';
  
  $entity_id = $site_url . '?q=admin/config/people/miniorange_saml_idp/';
  $login_url = $site_url . 'initiatelogon';
  $logout_url = $site_url;
  
  define('DRUPAL_BASE_ROOT', dirname(__FILE__));
  $module_path = drupal_get_path('module', 'miniorange_saml_idp');
  $certificate = file_get_contents( \Drupal::root() . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . 'miniorange_saml_idp' . DIRECTORY_SEPARATOR .  'resources' . DIRECTORY_SEPARATOR . 'idp-signing.crt' );
  
  $certificate = preg_replace("/[\r\n]+/", "", $certificate);
  $certificate = str_replace( "-----BEGIN CERTIFICATE-----", "", $certificate );
  $certificate = str_replace( "-----END CERTIFICATE-----", "", $certificate );
  $certificate = str_replace( " ", "", $certificate );
  
header('Content-Type: text/xml');
echo'<?xml version="1.0" encoding="UTF-8"?><md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="'.$entity_id.'"><md:IDPSSODescriptor WantAuthnRequestsSigned="true" protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol"><md:KeyDescriptor 
      use="signing">
      <ds:KeyInfo 
        xmlns:ds="http://www.w3.org/2000/09/xmldsig#">
        <ds:X509Data>
          <ds:X509Certificate>'.$certificate.'</ds:X509Certificate>
        </ds:X509Data>
      </ds:KeyInfo>
    </md:KeyDescriptor>

    <md:NameIDFormat>urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress</md:NameIDFormat>
    <md:NameIDFormat>urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified</md:NameIDFormat>
    <md:SingleSignOnService 
      Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" 
      Location="'.$login_url.'"/>
    <md:SingleSignOnService 
      Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" 
      Location="'.$login_url.'"/>
    <md:SingleLogoutService 
      Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" 
      Location="' . $logout_url . '"/>
    <md:SingleLogoutService 
      Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" 
      Location="' . $logout_url . '"/>
  </md:IDPSSODescriptor>
</md:EntityDescriptor>';
exit;
}

// public function saml_logout() {
   
//    global $base_url;
//    $base_url = $base_url . '/?q=admin/config/people/miniorange_saml_idp/';
   
// 	  if(is_null($_REQUEST['RelayState'])) {
// 		  $_REQUEST['RelayState'] = $base_url;
// 	  }
//     session_destroy();
// 	  $response = new RedirectResponse($_REQUEST['RelayState']);
//     $response->send();
//     exit;
// 	return;
//   }  
  
 public function test_configuration() {

  $relayState = '/';
  $acs = \Drupal::config('miniorange_saml_idp.settings')->get("miniorange_saml_idp_acs_url");
  $sp_issuer =\Drupal::config('miniorange_saml_idp.settings')->get("miniorange_saml_idp_entity_id");

      if($acs == '' || is_null($acs) || $sp_issuer == '' || is_null($sp_issuer)) {
          echo '<div style="font-family:Calibri;padding:0 3%;">';
          echo '<div style="color: #a94442;background-color: #f2dede;padding: 15px;margin-bottom: 20px;text-align:center;border:1px solid #E6B3B2;font-size:18pt;"> ERROR</div>
                <div style="color: #a94442;font-size:14pt; margin-bottom:20px;"><p><strong>Error: </strong>Please configure your Service Provider (SP) first and then click on Test Configuration.</p>
                <p><strong>Possible Cause: </strong> ACS URL or SP Entity ID not found.</p>
                
                </div>
                <div style="margin:3%;display:block;text-align:center;">';
          ?>
          <div style="margin:3%;display:block;text-align:center;"><input style="padding:1%;width:100px;background: #0091CD none repeat scroll 0% 0%;cursor: pointer;font-size:15px;border-width: 1px;border-style: solid;border-radius: 3px;white-space: nowrap;box-sizing: border-box;border-color: #0073AA;box-shadow: 0px 1px 0px rgba(120, 200, 230, 0.6) inset;color: #FFF;"type="button" value="Done" onClick="self.close();"></a></div>
          <?php
          exit;
      }
  \Drupal\miniorange_saml_idp\Controller\miniorange_saml_idpController::mo_idp_authorize_user($acs, $sp_issuer,$relayState );
  }


public function miniorange_saml_idp_login_request() {
  
   if(array_key_exists('SAMLRequest', $_REQUEST) && !empty($_REQUEST['SAMLRequest'])) {

   \Drupal\miniorange_saml_idp\Controller\miniorange_saml_idpController::_read_saml_request($_REQUEST,$_GET);    
   return new Response();
  }
}

public function _read_saml_request($REQUEST,$GET) {
  
  $samlRequest = $REQUEST['SAMLRequest'];
  $relayState = '/';
  if(array_key_exists('RelayState', $REQUEST)) {
  $relayState = $REQUEST['RelayState'];
  }
    
  $samlRequest = base64_decode($samlRequest);
  if(array_key_exists('SAMLRequest', $GET) && !empty($GET['SAMLRequest'])) {
    $samlRequest = gzinflate($samlRequest);
  }
    
  $document = new DOMDocument();
  $document->loadXML($samlRequest);
  $samlRequestXML = $document->firstChild;
 
  $authnRequest = new MiniOrangeAuthnRequest($samlRequestXML);
  
  $errors = '';
  if(strtotime($authnRequest->getIssueInstant()) > (time() + 60))
    $errors.= '<strong>INVALID_REQUEST: </strong>Request time is greater than the current time.<br/>';
  if($authnRequest->getVersion()!=='2.0')
    $errors.='We only support SAML 2.0! Please send a SAML 2.0 request.<br/>';
    
  $acs_url = \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_acs_url');
  $sp_issuer = \Drupal::config('miniorange_saml_idp.settings')->get('miniorange_saml_idp_entity_id');
  $acs_url_from_request = $authnRequest->getAssertionConsumerServiceURL();
  $sp_issuer_from_request = $authnRequest->getIssuer();
  
  if(empty($acs_url) || empty($sp_issuer)){
    $errors.= '<strong>INVALID_SP: </strong>Service Provider is not configured. Please configure your Service Provider.<br/>';
  }else{
    if(strcmp($acs_url,$acs_url_from_request) !== 0 ){
      $errors.= '<strong>INVALID_ACS: </strong>Invalid ACS URL!. Please check your Service Provider Configurations.<br/>';
  }
  if(strcmp($sp_issuer,$sp_issuer_from_request) !== 0){
      $errors.='<strong>INVALID_ISSUER: </strong>Invalid Issuer! Please check your configuration.<br/>';
  }
  }
  
  $inResponseTo = $authnRequest->getRequestID(); 
  
  if(empty($errors)){

  $module_path = drupal_get_path('module', 'miniorange_saml_idp');
  ?>
  <div style="vertical-align:center;text-align:center;width:100%;font-size:25px;background-color:white;">
    <img src="<?php echo $module_path;?>/includes/images/loader_gif.gif"></img>
    <h3>PROCESSING...PLEASE WAIT!</h3>
  </div>
  <?php


  \Drupal\miniorange_saml_idp\Controller\miniorange_saml_idpController::mo_idp_authorize_user($acs_url_from_request,$sp_issuer_from_request,$relayState,$inResponseTo);
  } else{

  echo sprintf($errors);
  exit;
  }
}
 public function mo_idp_authorize_user($acs_url,$audience,$relayState,$inResponseTo=null){
   
  if ( \Drupal::currentUser()->isAuthenticated()) {
    
   \Drupal\miniorange_saml_idp\Controller\miniorange_saml_idpController::mo_idp_send_reponse($acs_url,$audience,$relayState,$inResponseTo);
  
  } else {
    
    $saml_response_params = array('moIdpsendResponse' => "true" , "acs_url" => $acs_url , "audience" => $audience , "relayState" => $relayState,"inResponseTo" => $inResponseTo );
  
    $responsec = new Response();
            $cookie = new Cookie("response_params",json_encode($saml_response_params));
            $responsec->headers->setCookie($cookie);
            $responsec->send();

    global $base_url;
    $redirect_url = $base_url . '/user/login';
    $response = new RedirectResponse($redirect_url);

    $response->send();

  }
}

public function mo_idp_send_reponse($acs_url,$audience,$relayState, $inResponseTo=null){
  
  $user = \Drupal::currentUser();
  $email = $user->getEmail();
  $username = $user->getUsername();



	if(!in_array('administrator',$user->getRoles())){
	
	  ob_end_clean();
           
                echo '<div style="color: #a94442;background-color: #f2dede;padding: 15px;margin-bottom: 20px;text-align:center;border:1px solid #E6B3B2;font-size:18pt;"> ERROR</div>
				<div style="color: #a94442;font-size:14pt; margin-bottom:20px;"><p><strong>Single Sign On not Allowed</strong> </p>
				<p>This is a trial plugin meant for Super User/Administrator use only.</p>
				<p>The Single Sign On feature for end users is available in the premium version of the plugin.</p>
				</div>
				<div style="margin:3%;display:block;text-align:center;">';
				exit;
	}
	


  
  global $base_url;
  $issuer = $base_url . '/?q=admin/config/people/miniorange_saml_idp/';
  
  $name_id_attr =(\Drupal::config('miniorange_saml_idp.settings')->get("miniorange_saml_idp_nameid_attr_map") == '')?'emailAddress':\Drupal::config('miniorange_saml_idp.settings')->get("miniorange_saml_idp_nameid_attr_map");
  $name_id_attr_format =\Drupal::config('miniorange_saml_idp.settings')->get("miniorange_saml_idp_nameid_format"); 
  $idp_assertion_signed =\Drupal::config('miniorange_saml_idp.settings')->get("miniorange_saml_idp_assertion_signed"); 
  $state = \Drupal::config('miniorange_saml_idp.settings')->get("miniorange_saml_idp_relay_state"); 
  if(!empty($state) && !is_null($state)){
    $relayState = $state;
  }
  
  $saml_response_obj = new GenerateResponse($email,$username, $acs_url, $issuer, $audience,$inResponseTo, $name_id_attr,$name_id_attr_format,$idp_assertion_signed);
  
  $saml_response = $saml_response_obj->createSamlResponse();
  setcookie("response_params","");
  
   \Drupal\miniorange_saml_idp\Controller\miniorange_saml_idpController::_send_response($saml_response, $relayState,$acs_url);
}


public function _send_response($saml_response, $ssoUrl,$acs_url){
  
  $saml_response = base64_encode($saml_response);
  ?>
  <form id="responseform" action="<?php echo $acs_url; ?>" method="post">
    <input type="hidden" name="SAMLResponse" value="<?php echo htmlspecialchars($saml_response); ?>" />
    <input type="hidden" name="RelayState" value="<?php echo $ssoUrl; ?>" />
  </form>
  <script>
    setTimeout(function(){
      document.getElementById('responseform').submit();
    }, 100);  
  </script>
<?php
  exit;
}



}