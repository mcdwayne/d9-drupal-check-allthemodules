<?php

/**
 * @file
 * Contains \Drupal\miniorange_saml\Form\Export.
 */
namespace Drupal\miniorange_saml\Form;

use Drupal\Core\Form\FormBase;
use Drupal\miniorange_saml\Utilities;
use Drupal\miniorange_saml\mo_saml_visualTour;

include 'includes\miniorange_saml_enums.php';

define("Tab_Class_Names", serialize( array(
    "Identity_Provider" => 'mo_options_enum_identity_provider',
    "Service_Provider"  => 'mo_options_enum_service_provider',
) ) );

class Export extends FormBase
{	
  public function getFormId() 
  {
    return 'miniorange_saml_export';
  }
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) 
  {
      global $base_url;

      Utilities::visual_tour_start($form, $form_state);

        $form['markup_top'] = array(
            '#markup' => '<div class="mo_saml_table_layout_1"><div class="mo_saml_table_layout mo_saml_container">'
        );

		$form['markup_top_1'] = array (
            '#attached' => array(
                'library' => 'miniorange_saml/miniorange_saml.Vtour',
            ),
			'#markup' => '<h3>EXPORT CONFIGURATION &nbsp;&nbsp; <a id="Restart_moTour" class="btn btn-danger btn-sm" onclick="Restart_moTour()">Take a Tour</a></h3><hr/>'
		);

		$form['Exort_Configuration_Start'] = array(
          '#markup' => '<div id="Exort_Configuration">'
        );

		$form['markup_top_2'] = array (
           '#markup' =>'<p>This tab will help you to transfer your plugin configurations when you change your Drupal instance.</p>'
            . '<p>Download plugin configuration file by clicking on the button given below and send us this file along with your support query. <br/></p>',
        );

      $login_URL = \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_idp_login_url');
      $ACS_URL = \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_idp_issuer');
      $disableButton = FALSE;
      if($login_URL == NULL || $ACS_URL == NULL){
          $disableButton = TRUE;
          $form['markup_note'] = array(
              '#markup' => '<div class="mo_saml_highlight_background_note_1"><b>Note:</b> Please <a href="' . $base_url . '/admin/config/people/miniorange_saml/sp_setup">configure plugin </a> first to download configuration file Register/login with miniOrange to enable the module.</div><br>',
          );
      }

		$form['miniorange_saml_imo_option_exists_export'] = array(
			'#type' => 'submit',
			'#value' => t('Download Plugin Configuration'),
			'#submit' => array('::miniorange_import_export'),
            '#disabled' => $disableButton,
		);
      $form['Exort_Configuration_End'] = array(
          '#markup' => '<br/><br/></div><div id="Import_Configuration"><br/>'
      );

      $form['markup_import'] = array(
          '#markup' => '<h3>IMPORT CONFIGURATION</h3><hr><br>',
      );

      $form['markup_prem_plan'] = array(
          '#markup' => '<div class="mo_saml_highlight_background_note">Available in <b><a href="' . $base_url . '/admin/config/people/miniorange_saml/Licensing">Standard, Premium, Enterprise</a></b> versions of the module</div>',
      );

      $form['markup_import_note'] = array(
          '#markup' => '<p>This tab will help you to<span style="font-weight: bold"> Import your plugin configurations</span> when you change your Drupal instance.</p>
               <p>choose <b>"json"</b> Extened plugin configuration file and upload by clicking on the button given below. </p>',
      );

      $form['import_Config_file'] = array(
          '#type' => 'file',
          '#disabled' => TRUE,
      );

      $form['miniorange_saml_idp_import'] = array(
          '#type' => 'submit',
          '#value' => t('Upload'),
          '#disabled' => TRUE,
      );

      $form['Import_Configuration_End'] = array(
          '#markup' => '</div></div>'
      );

      Utilities::AddsupportTab( $form, $form_state);

	  return $form;
	}
	function miniorange_import_export() 
	{
		$tab_class_name = unserialize(Tab_Class_Names);
		$configuration_array = array();
		foreach($tab_class_name as $key => $value) 
		{	
			$configuration_array[$key] = $this -> mo_get_configuration_array($value);	
		}
		$configuration_array["Version_dependencies"] = $this -> mo_get_version_informations();
		header("Content-Disposition: attachment; filename = miniorange_saml_config.json");
		echo(json_encode($configuration_array, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
		exit;
	}

function mo_get_configuration_array($class_name) 
{
	if($class_name == "mo_options_enum_identity_provider")
	{
		$class_object = \Drupal\miniorange_saml\mo_options_enum_identity_provider:: getConstants();
	}
	else if($class_name == "mo_options_enum_service_provider")
	{
		$class_object = \Drupal\miniorange_saml\mo_options_enum_service_provider:: getConstants();
	}
    $mo_array = array();
    foreach($class_object as $key => $value) 
	{ 
        $mo_option_exists = \Drupal::config('miniorange_saml.settings')->get($value);	
	    if($mo_option_exists)
		{
            if(unserialize($mo_option_exists) !== false)
			{
                $mo_option_exists = unserialize($mo_option_exists);
            }
            $mo_array[$key] = $mo_option_exists;
        }
    }
    return $mo_array;
}



    function mo_get_version_informations()
    {
        $array_version = array();
        $array_version["PHP_version"] = phpversion();
        $array_version["Drupal_version"] = \DRUPAL::VERSION;
        $array_version["OPEN_SSL"] = $this -> mo_saml_is_openssl_installed();
        $array_version["CURL"] = $this -> mo_saml_is_curl_installed();
        $array_version["ICONV"] = $this -> mo_saml_is_iconv_installed();
        $array_version["DOM"] = $this -> mo_saml_is_dom_installed();
        return $array_version;
    }

	function mo_saml_is_openssl_installed()
	{
		if ( in_array( 'openssl', get_loaded_extensions() ) ) 
		{
			return 1;
		} 
		else
		{
			return 0;
		}
	}
    function mo_saml_is_curl_installed() {
        if ( in_array( 'curl', get_loaded_extensions() ) ) {
            return 1;
        } else {
            return 0;
        }
    }
    function mo_saml_is_iconv_installed(){

        if ( in_array( 'iconv', get_loaded_extensions() ) ) {
            return 1;
        } else {
            return 0;
        }
    }
    function mo_saml_is_dom_installed(){

        if ( in_array( 'dom', get_loaded_extensions() ) ) {
            return 1;
        } else {
            return 0;
        }
    }
	public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

	}
    function saved_support(array &$form, \Drupal\Core\Form\FormStateInterface $form_state)
    {
        $email = $form['miniorange_saml_email_address_support']['#value'];
        $phone = $form['miniorange_saml_phone_number_support']['#value'];
        $query = $form['miniorange_saml_support_query_support']['#value'];
        Utilities::send_support_query($email, $phone, $query);
    }

		
}