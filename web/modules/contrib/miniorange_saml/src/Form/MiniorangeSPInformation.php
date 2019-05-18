<?php

/**
 * @file
 * Contains \Drupal\miniorange_saml\Form\MiniorangeSPInformation.
 */

namespace Drupal\miniorange_saml\Form;

use Drupal\Core\Form\FormBase;
use Drupal\miniorange_saml\Utilities;
use Drupal\miniorange_saml\MetadataReader;
use Drupal\miniorange_saml\mo_saml_visualTour;

class MiniorangeSPInformation extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'miniorange_sp_setup';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

  global $base_url;
  //global $css_dru = 'width:700px;background-image: inherit;background-color: white;-webkit-appearance: menulist;';

  $form['markup_library'] = array(
      '#attached' => array(
          'library' => array(
              "miniorange_saml/miniorange_saml.test",
              "miniorange_saml/miniorange_saml.admin",
          )
      )
  );
  $form['markup_top'] = array(
      '#markup' => '<div class="mo_saml_table_layout_1"><div class="mo_saml_table_layout mo_saml_container">'
  );

  Utilities::visual_tour_start($form, $form_state);

  $form['miniorange_saml_IDP_tab'] = array(
  '#attached' => array(
	'library' => array(
	    'miniorange_saml/miniorange_saml.test',
        'miniorange_saml/miniorange_saml.Vtour'
    )
  ),
  '#markup' => '<div id="tabhead"><br><h5> Enter the information gathered from your Identity Provider &nbsp; OR &nbsp;&nbsp;
                    <a id="showMetaButton" class="btn btn-primary btn-sm" onclick="testConfig()">Upload IDP Metadata</a>&nbsp;&nbsp;&nbsp;&nbsp;
                    <a id="Restart_moTour" class="btn btn-danger btn-sm" onclick="Restart_moTour()">Take a Tour</a><br><br><hr></h5></div>',
  );

  $form['metadata_1'] = array(
	'#markup' =>'<br><div border="1" id="upload_metadata_form" class="mo_saml_meta_upload">'
  .'				<h1>Upload IDP Metadata'
  .'		<span class="mo_saml_cancel_upload"><a id="hideMetaButton" class="btn btn-sm btn-danger" onclick = "testConfig()">Cancel</a></span>',
  );

      $form['metadata_idp_name'] = array(
          '#markup' =>'</td></td>'
              .'	</tr>'
              .'				<tr><td colspan="3" ></td></tr>'
              .'		    <tr>'
              .'			<td width="20%"><h4>Identity Provider name:</h4></td><td width="10%">',
      );

      $form['miniorange_saml_idp_name_3'] = array(
          '#type' => 'textfield',
          '#attributes' => array('placeholder' => 'Enter Identity Provider name.'),
      );

  $form['metadata_2'] = array(
        '#markup' =>' <br></h1><h4>Upload Metadata  :</h4>',
  );
  
  $form['metadata_file'] = array(
    '#type' => 'file',
  );
  
  $form['metadata_upload'] = array(
    '#type' => 'submit',
    '#value' => t('Upload'),
    '#submit' => array('::miniorange_saml_upload_file'),
      '#attributes' => array('style' => 'background: #337ab7;color: #ffffff;text-shadow: 0 -1px 1px #337ab7, 1px 0 1px #337ab7, 0 1px 1px #337ab7, -1px 0 1px #337ab7;box-shadow: 0 1px 0 #337ab7;border-color: #337ab7 #337ab7 #337ab7;'),
  );
  
  $form['metadata_3'] = array(
    '#markup' =>'<p>&emsp;&emsp;&emsp;&emsp;<b>OR</b></p>'
	.'			<h4>Enter metadata URL:</h4>',
  );
  
  $form['metadata_URL'] = array(
    '#type' => 'textfield',
	'#attributes' => array('placeholder' => 'Enter metadata URL of your IdP.'),
  );

      $form['miniorange_saml_fetch_metadata_1'] = array(
          '#type' => 'checkbox',
          '#title' => t('Update IdP settings by pinging metadata URL (We will store the metadata URL). <b><a href="' . $base_url . '/admin/config/people/miniorange_saml/Licensing">[Premium, Enterprise]</a></b>'),
          '#disabled' => TRUE,
      );

      $form['metadata_fetch_1'] = array(
          '#markup' => '<div class="mo_saml_highlight_background_note_1"><b>Note: </b>You can set how often you want to ping the IdP from <b><a target="_blank" href="' . $base_url . '/admin/config/system/cron "> Here</a> OR </b> you can goto <b>Configuration=>Cron=>Run Cron Every</b> section of your drupal site.</div><br>',
      );
	
   $form['metadata_fetch'] = array(
	'#type' => 'submit',
    '#value' => t('Fetch Metadata'),
    '#submit' => array('::miniorange_saml_fetch_metadata'),
       '#attributes' => array('style' => 'background: #337ab7;color: #ffffff;text-shadow: 0 -1px 1px #337ab7, 1px 0 1px #337ab7, 0 1px 1px #337ab7, -1px 0 1px #337ab7;box-shadow: 0 1px 0 #337ab7;border-color: #337ab7 #337ab7 #337ab7;'),
   );
   
   $form['metadata_5'] = array(
   '#markup' =>'<br><br><hr></div>'
	.'<div id="idpdata">',
   );

      $form['miniorange_saml_identity_provider_guide'] = array(
          '#type' => 'select',
          '#title' => t('Select your Identity Provider :'),
          '#options' => array(
              'select-idp'=>t('Select your Identity Provider'),
              'adfs' => t('ADFS'),
              'okta' => t('Okta'),
              'salesforce' => t('SalesForce'),
              'google-apps' => t('Google Apps'),
              'azure-ad' => t('Azure Ad'),
              'onelogin' => t('OneLogin'),
              'centrify' => t('Centrify'),
              'miniorange' => t('MiniOrange'),
              'bitium' => t('Bitium'),
              'other' => t('Other'),
          ),
          '#default_value' => \Drupal::config('miniorange_saml.settings')->get('miniorange_nameid_format'),
          '#attributes' => array('style' => 'width:700px;background-image: inherit;background-color: white;-webkit-appearance: menulist;','onchange'=>'idp_guide(value);'),
          '#description' => t('<b>Note : </b>Select your Identity Provider from the list above, and you can find the link to the guide for setting up SAML.<br> Please contact us if you don\'t find your IDP in the list.'),
      );
      ?>
      <script>
          function idp_guide(value) {
              if(value!="other" && value!="select-idp") {
                  window.open("https://plugins.miniorange.com/drupal-single-sign-sso-using-" + value + "-idp/", '_blank');
              }
          }
      </script>
      <?php


  $form['miniorange_saml_idp_name_div'] = array(
      '#markup' => '<div id = "miniorange_saml_idp_name_div">'
  );

  $form['miniorange_saml_idp_name'] = array(
    '#type' => 'textfield',
    '#title' => t('Identity Provider Name*'),
    '#default_value' => \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_idp_name'),
    '#attributes' => array('style' => 'width:700px', 'placeholder' => 'Identity Provider Name'),
  );

  $form['miniorange_saml_idp_name_div_end'] = array(
     '#markup' => '</div>'
  );

      $form['miniorange_saml_idp_issuer_div'] = array(
          '#markup' => '<div id = "miniorange_saml_idp_issuer_div">'
      );

  $form['miniorange_saml_idp_issuer'] = array(
    '#type' => 'textfield',
    '#title' => t('IdP Entity ID or Issuer*'),
    '#default_value' => \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_idp_issuer'),
    '#attributes' => array('style' => 'width:700px','placeholder' => 'IdP Entity ID or Issuer'),
	'#description' => t('<b>Note :</b> You can find the EntityID in Your IdP-Metadata XML file enclosed in <code>EntityDescriptor</code> tag having attribute as <code>entityID</code>'),
  );

      $form['premium_feature_1'] = array(
          '#markup' =>'</div>',
      );


      $form['premium_feature_2_end'] = array(
          '#markup' =>'<div id="miniorange_saml_idp_login_url_start">',
      );
      $form['miniorange_saml_idp_login_url'] = array(
          '#type' => 'textfield',
          '#title' => t('SAML Login URL*'),
          '#default_value' => \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_idp_login_url'),
          '#description' => t('<b>Note :</b> You can find the SAML Login URL in Your IdP-Metadata XML file enclosed in <code>SingleSignOnService</code> tag'),
          '#attributes' => array('style' => 'width:700px',
              'placeholder' => 'SAML Login URL'
          ),
      );

      $form['premium_feature_3'] = array(
          '#markup' =>'</div>',
      );


  $form['premium_feature_3_end'] = array(
      '#markup' =>'<div id="miniorange_saml_idp_x509_certificate_start">',
  );

  $form['miniorange_saml_idp_x509_certificate'] = array(
    '#type' => 'textarea',
    '#title' => t('x.509 Certificate Value'),
    '#cols' => '10',
    '#rows' => '5',
    '#default_value' => \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_idp_x509_certificate'),
    '#attributes' => array('style' => 'width:700px','placeholder' => 'Enter x509 Certificate Value'),
  );

  $form['markup_1'] = array(
    '#markup' => '</div><b>NOTE:</b> Format of the certificate:<br><b>-----BEGIN CERTIFICATE-----<br>'
  );

  $form['markup_2'] = array(
    '#markup' => 'XXXXXXXXXXXXXXXXXXXXXXXXXXX<br>-----END CERTIFICATE-----</b><br>'
  );

  $form['enable_login_with_saml_start'] = array(
    '#markup' => '<div id="enable_login_with_saml"><br>'
  );

  $form['miniorange_saml_enable_login'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable login with SAML'),
    '#default_value' => \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_enable_login'),
  );

  $form['enable_login_with_saml_end'] = array(
          '#markup' => '<br></div>'
  );

  $form['miniorange_saml_test_config_button'] = array(
	'#attached' => array (
	'library' => 'miniorange_saml/miniorange_saml.test',
	),
    '#markup' => '<a id="testConfigButton" class="btn btn-primary btn-sm" style="padding:6px 12px;" onclick="testConfig();">Test Configuration</a>'
  );
  
  $form['miniorange_saml_idp_config_submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save Configuration'),
      '#attributes' => array('style' => 'background: #337ab7;color: #ffffff;text-shadow: 0 -1px 1px #337ab7, 1px 0 1px #337ab7, 0 1px 1px #337ab7, -1px 0 1px #337ab7;box-shadow: 0 1px 0 #337ab7;border-color: #337ab7 #337ab7 #337ab7;'),
  );

  $form['miniorange_saml_test_show_SAML_request_button'] = array(
	'#attached' => array(
	'library' => 'miniorange_saml/miniorange_saml.button',
	),
    '#markup' => '<br><br><a id="showSAMLrequestButton" class="btn btn-primary btn-sm" style="padding:6px 12px;" onclick="showSAMLRequest();">Show SAML Request</a>&nbsp;&nbsp;&nbsp;&nbsp;'
  );

  $form['miniorange_saml_test_show_SAML_response_button'] = array(
   '#attached' => array(
	'library' => 'miniorange_saml/miniorange_saml.button',
	),
    '#markup' => '<a id="showSAMLresponseButton" class="btn btn-primary btn-sm" style="padding:6px 12px;" onclick="showSAMLResponse();">Show SAML Response</a>',
  );

  $form['main_layout_div_end'] = array(
      '#markup' => '</div><br><br></div>',
  );

  Utilities::AddsupportTab( $form, $form_state);

  return $form;
 }

    public function miniorange_saml_form_alter(array &$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id){
	  $form['actions']['submit']['#submit'][] = 'test';
	  return $form;
	}
 
 /**
 * Configure IdP.
 */
 public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
	
  global $base_url;
  $issuer = $form['miniorange_saml_idp_issuer']['#value'];
  $idp_name = $form['miniorange_saml_idp_name']['#value'];
  $nameid_format = 'urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified';       //Shree
  $login_url = $form['miniorange_saml_idp_login_url']['#value'];
  $x509_cert_value = Utilities::sanitize_certificate($form['miniorange_saml_idp_x509_certificate']['#value']);
  $enable_login_value = $form['miniorange_saml_enable_login']['#value'];

  if(empty($idp_name)||empty($issuer) || empty($login_url)){
     drupal_set_message(t('The <b><u>Identity Provider Name, IdP Entity ID or Issuer</u></b> and <b><u>SAML Login URL</u></b> fields are mandatory.'), 'error');
     return;
  }

  $enable_login = FALSE;
  if ($enable_login_value == 1) {
      $enable_login = TRUE;
  }

  $sp_issuer = $base_url . '/samlassertion';



     \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_base', $base_url)->save();
     \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_idp_name', $idp_name)->save();
  \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_sp_issuer', $sp_issuer)->save();
  \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_idp_issuer', $issuer)->save();
  \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_nameid_format', $nameid_format)->save();
  \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_idp_login_url', $login_url)->save();
  \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_idp_x509_certificate', $x509_cert_value)->save();
  \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_enable_login', $enable_login)->save();

  drupal_set_message(t('Identity Provider Configuration successfully saved'));
 }

 function saved_support(array &$form, \Drupal\Core\Form\FormStateInterface $form_state)
 {
     $email = $form['miniorange_saml_email_address_support']['#value'];
     $phone = $form['miniorange_saml_phone_number_support']['#value'];
     $query = $form['miniorange_saml_support_query_support']['#value'];
     Utilities::send_support_query($email, $phone, $query);
 }
 
 function miniorange_saml_upload_file(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    $idp_name = $form['miniorange_saml_idp_name_3']['#value'];
    if(empty($idp_name)&&empty(\Drupal::config('miniorange_saml.settings')->get('miniorange_saml_idp_name'))){
        drupal_set_message(t('Identity Provider Name is required.'),error);
    }
	$file_name = $_FILES['files']['tmp_name']['metadata_file'];
	$file = file_get_contents($file_name);
	$this->upload_metadata($file,$idp_name);
 }

 function miniorange_saml_fetch_metadata(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
     $idp_name = $form['miniorange_saml_idp_name_3']['#value'];
     if(empty($idp_name)&&empty(\Drupal::config('miniorange_saml.settings')->get('miniorange_saml_idp_name'))){
         drupal_set_message(t('Identity Provider Name is required.'),error);
     }
	$url=filter_var($form['metadata_URL']['#value'],FILTER_SANITIZE_URL);
	$arrContextOptions=array(
				"ssl"=>array(
				"verify_peer"=>false,
				"verify_peer_name"=>false,
				),
			);  
	$file = file_get_contents($url, false, stream_context_create($arrContextOptions));
	$this->upload_metadata($file,$idp_name);
 }

 public function upload_metadata($file,$idp_name){
	global $base_url;
		$document = new \DOMDocument();
		$document->loadXML($file);
		restore_error_handler();
		$first_child = $document->firstChild;

		if(!empty($first_child)) {
			$metadata = new MetadataReader($document);
			$identity_providers = $metadata->getIdentityProviders();
			if(empty($identity_providers)) {
				drupal_set_message(t('Please provide a valid metadata file.'),error);
			return;
			}
			
			foreach($identity_providers as $key => $idp) {

				$saml_login_url = $idp->getLoginURL('HTTP-Redirect');
				if(empty($saml_login_url)) {
					$saml_login_url = $idp->getLoginURL('HTTP-POST');
				}
				$saml_issuer = $idp->getEntityID();
				$saml_x509_certificate = $idp->getSigningCertificate();
				$sp_issuer = $base_url;
                if(!empty(\Drupal::config('miniorange_saml.settings')->get('miniorange_saml_idp_name'))&& empty($idp_name)){
                    $idp_name = \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_idp_name');
                    \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_idp_name', $idp_name)->save();
                }
                else{
                    \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_idp_name', $idp_name)->save();
                }

				  \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_sp_issuer', $sp_issuer)->save();
				  \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_idp_issuer', $saml_issuer)->save();
				  \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_idp_login_url', $saml_login_url)->save();
				  \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('miniorange_saml_idp_x509_certificate', $saml_x509_certificate[0])->save();
			}
				  drupal_set_message(t('Identity Provider Configuration successfully saved.'));
				  return;
		}else {
				  drupal_set_message(t('Please provide a valid metadata file.'),error);
				  return;
		}
 }
}