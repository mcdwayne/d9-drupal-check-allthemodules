<?php
namespace Drupal\miniorange_saml;

class mo_saml_visualTour {

    public static function genArray($overAllTour = 'tabTour'){
        $link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $exploded = explode('/', $link);
        $getPageName = end($exploded);
        $Tour_Token = \Drupal::config('miniorange_saml.settings')->get('mo_saml_tourTaken_' . $getPageName);
        if($overAllTour == 'overAllTour'){
            $getPageName = 'overAllTour';
        }
        //\Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('mo_saml_tourTaken_' . $getPageName, TRUE)->save();
        $moTourArr = array (
            'pageID' => $getPageName,
            'tourData' => mo_saml_visualTour::getTourData($getPageName),
            'tourTaken' => $Tour_Token,
            'addID' => mo_saml_visualTour::addID(),
            'pageURL' => $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
        );

        \Drupal::configFactory()->getEditable('miniorange_saml.settings')->set('mo_saml_tourTaken_' . $getPageName, TRUE)->save();
        $moTour = json_encode($moTourArr);
        return $moTour;
    }

    public static function addID()
    {
        $idArray = array(
            array(
                'selector'  =>'li.tabs__tab:nth-of-type(1)',
                'newID'     =>'mo_vt_idp_setup',
            ),
            array(
                'selector'  =>'li.tabs__tab:nth-of-type(2)',
                'newID'     =>'mo_vt_sp_setup',
            ),
            array(
                'selector'  =>'li.tabs__tab:nth-of-type(3)',
                'newID'     =>'mo_vt_mapping',
            ),
            array(
                'selector'  =>'li.tabs__tab:nth-of-type(4)',
                'newID'     =>'mo_vt_sign_sett',
            ),
            array(
                'selector'  =>'li.tabs__tab:nth-of-type(5)',
                'newID'     =>'mo_vt_export',
            ),
            array(
                'selector'  =>'li.tabs__tab:nth-of-type(6)',
                'newID'     =>'mo_vt_licensing',
            ),
            array(
                'selector'  =>'li.tabs__tab:nth-of-type(7)',
                'newID'     =>'mo_vt_account',
            ),
            array(
                'selector'  =>'table',
                'newID'     =>'mo_idp_url_table',
            ),
        );
        return $idArray;
    }
    public static function getTourData($pageID)
    {

        $tourData = array();
        $link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $exploded = explode('/', $link);
        $getPageName = end($exploded);
        $Tour_Token = \Drupal::config('miniorange_saml.settings')->get('mo_saml_tourTaken_' . $getPageName);

        if($Tour_Token == 0 || $Tour_Token == FALSE)
            $tab_index = 'idp_setup';
        else $tab_index = 'idp_tab';

        $tourData['idp_setup'] = array(
            0 => array(
                'targetE'       =>  'mo_idp_url_table',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>Service Provider Metadata URLs</h1>',
                'contentHTML'   =>  'You can manually configure your Identity Provider using the information given here.',
                'ifNext'        =>  true,
                'buttonText'    =>  'Next',
                'cardSize'      =>  'largemedium',
            ),
            1 => array(
                'targetE'       =>  'meta_data_url_for_IDP',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>Service Provider Metadata URL</h1>',
                'contentHTML'   =>  'Provide this <b>Metadata URL</b> to configure your Identity Provider',
                'ifNext'        =>  true,
                'buttonText'    =>  'Next',
                'cardSize'      =>  'medium',
            ),
            2 => array(
                'targetE' => 'ma_saml_support_query',
                'pointToSide' => 'right',
                'titleHTML' => '<h1>Support</h1>',
                'contentHTML' => 'Get in touch with us and we will help you setup the module in no time.',
                'ifNext' => true,
                'buttonText' => 'End Tour',
                'cardSize' => 'largemedium',
                'action' => '',
            ),
        );
        $tourData[$tab_index] = array(
            0 => array(
                'targetE' => 'ma_saml_support_query',
                'pointToSide' => 'right',
                'titleHTML' => '<h1>Support</h1>',
                'contentHTML' => 'Get in touch with us and we will help you setup the module in no time.',
                'ifNext' => true,
                'buttonText' => 'Next',
                'cardSize' => 'largemedium',
                'action' => '',
            ),
            1 => array(
                'targetE' => 'mo_vt_idp_setup',
                'pointToSide' => 'up',
                'titleHTML' => '<h1>Service Provider Metadata</h1>',
                'contentHTML' => 'This tab provides details to configure your <b>Identity Provider</b>.',
                'ifNext' => true,
                'buttonText' => 'Next',
                'cardSize' => 'largemedium',
                'action' => '',
            ),
            2 => array(
                'targetE' => 'mo_vt_sp_setup',
                'pointToSide' => 'up',
                'titleHTML' => '<h1>Service Provider Setup</h1>',
                'contentHTML' => 'Configure this tab using Identity provider information which you get from <b>IDP-Metadata XML</b>.',
                'ifNext' => true,
                'buttonText' => 'Next',
                'cardSize' => 'big',
                'action' => '',
            ),
            3 => array(
                'targetE' => 'mo_vt_mapping',
                'pointToSide' => 'up',
                'titleHTML' => '<h1>Mapping Tab</h1>',
                'contentHTML' => 'In this tab you can find <b>attribute mapping</b>, <b>role mapping</b> and more.',
                'ifNext' => true,
                'buttonText' => 'Next',
                'cardSize' => 'largemedium',
                'action' => '',
            ),
            4 => array(
                'targetE' => 'mo_vt_sign_sett',
                'pointToSide' => 'up',
                'titleHTML' => '<h1>Signin Settings Tab</h1>',
                'contentHTML' => 'This tab provides the information like <b>auto redirect</b>, <b>backdoor login</b> option and more.',
                'ifNext' => true,
                'buttonText' => 'End Tour',
                'cardSize' => 'big',
                'action' => '',
            ),
            5 => array(
                'targetE'       =>  'mo_idp_url_table',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>Service Provider Metadata URLs</h1>',
                'contentHTML'   =>  'You can manually configure your Identity Provider using the information given here.',
                'ifNext'        =>  true,
                'buttonText'    =>  'Next',
                'cardSize'      =>  'largemedium',
            ),
            6 => array(
                'targetE'       =>  'meta_data_url_for_IDP',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>Service Provider Metadata URL</h1>',
                'contentHTML'   =>  'Provide this <b>Metadata URL</b> to configure your Identity Provider',
                'ifNext'        =>  true,
                'buttonText'    =>  'Next',
                'cardSize'      =>  'medium',
            ),
        );
        $tourData['overAllTour'] = array(
            0 => array(
                'targetE' => 'ma_saml_support_query',
                'pointToSide' => 'right',
                'titleHTML' => '<h1>Support</h1>',
                'contentHTML' => 'Get in touch with us and we will help you setup the module in no time.',
                'ifNext' => true,
                'buttonText' => 'Next',
                'cardSize' => 'largemedium',
                'action' => '',
            ),
            1 => array(
                'targetE' => 'mo_vt_idp_setup',
                'pointToSide' => 'up',
                'titleHTML' => '<h1>Service Provider Metadata</h1>',
                'contentHTML' => 'This tab provides details to configure your <b>Identity Provider</b>.',
                'ifNext' => true,
                'buttonText' => 'Next',
                'cardSize' => 'largemedium',
                'action' => '',
            ),
            2 => array(
                'targetE' => 'mo_vt_sp_setup',
                'pointToSide' => 'up',
                'titleHTML' => '<h1>Service Provider Setup</h1>',
                'contentHTML' => 'Configure this tab using Identity provider information which you get from <b>IDP-Metadata XML</b>.',
                'ifNext' => true,
                'buttonText' => 'Next',
                'cardSize' => 'big',
                'action' => '',
            ),
            3 => array(
                'targetE' => 'mo_vt_mapping',
                'pointToSide' => 'up',
                'titleHTML' => '<h1>Mapping Tab</h1>',
                'contentHTML' => 'In this tab you can find <b>attribute mapping</b>, <b>role mapping</b> and more.',
                'ifNext' => true,
                'buttonText' => 'Next',
                'cardSize' => 'largemedium',
                'action' => '',
            ),
            4 => array(
                'targetE' => 'mo_vt_sign_sett',
                'pointToSide' => 'up',
                'titleHTML' => '<h1>Signin Settings Tab</h1>',
                'contentHTML' => 'This tab provides the information like <b>auto redirect</b>, <b>backdoor login</b> option and more.',
                'ifNext' => true,
                'buttonText' => 'End Tour',
                'cardSize' => 'big',
                'action' => '',
            ),
        );


        $tourData['customer_setup'] = array(
            0 =>    array(
                'targetE'       =>  'Register_Section',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>Register/Login with miniOrange</h1>',
                'contentHTML'   =>  'Just complete the short registration with miniOrange to upgrade the module.',
                'ifNext'        =>  true,
                'buttonText'    =>  'End Tour',
                'cardSize'      =>  'largemedium',
                'action'        =>  '',
                'ifskip'        =>  'hidden',
            ),
        );



        $tourData['sp_setup'] = array(
            0 =>    array(
                'targetE'       =>  'tabhead',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>Upload Your Metadata</h1>',
                'contentHTML'   =>  'If you have a metadata URL or file provided by your IDP, click on the <b>Upload Your Metadata</b> button or you can configure the module manually also.',
                'ifNext'        =>  true,
                'buttonText'    =>  'Next',
                'cardSize'      =>  'big',
                'action'        =>  '',
            ),
            1 =>    array(
                'targetE'       =>  'miniorange_saml_idp_name_div',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>Identity Provider Name</h1>',
                'contentHTML'   =>  'Enter appropriate name for your Identity Provider',
                'ifNext'        =>  true,
                'buttonText'    =>  'Next',
                'cardSize'      =>  'medium',
                'action'        =>  '',
            ),
            2 =>    array(
                'targetE'       =>  'miniorange_saml_idp_issuer_div',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>IdP Entity ID</h1>',
                'contentHTML'   =>  'You can find the <b>IDP EntityID/Issuer</b> in Your IdP-Metadata XML file enclosed in <b>EntityDescriptor</b> tag having attribute as entityID.',
                'ifNext'        =>  true,
                'buttonText'    =>  'Next',
                'cardSize'      =>  'big',
                'action'        =>  '',
            ),
            3 =>    array(
                'targetE'       =>  'miniorange_saml_idp_login_url_start',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>Login URL</h1>',
                'contentHTML'   =>  'You can find the <b>SAML Login URL</b> in Your IdP-Metadata XML file enclosed in <b>SingleSignOnService</b> tag (Binding type: HTTP-Redirect)',
                'ifNext'        =>  true,
                'buttonText'    =>  'Next',
                'cardSize'      =>  'big',
                'action'        =>  '',
            ),
            4 =>    array(
                'targetE'       =>  'miniorange_saml_idp_x509_certificate_start',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>x.509 Certificate</h1>',
                'contentHTML'   =>  'Public key of your IDP to read the signed SAML Assertion/Response',
                'ifNext'        =>  true,
                'buttonText'    =>  'Next',
                'cardSize'      =>  'medium',
                'action'        =>  '',
            ),
            5 =>    array(
                'targetE'       =>  'enable_login_with_saml',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>Enable login with SAML</h1>',
                'contentHTML'   =>  'Enable the checkbox if you want to enable SSO login with IdP credentials.',
                'ifNext'        =>  true,
                'buttonText'    =>  'Next',
                'cardSize'      =>  'medium',
                'action'        =>  '',
            ),
            6 =>    array(
                'targetE'       =>  'ma_saml_support_query',
                'pointToSide'   =>  'right',
                'titleHTML'     =>  '<h1>Supports</h1>',
                'contentHTML'   =>  'If you need any help, you can just send us a query so we can help you.',
                'ifNext'        =>  true,
                'buttonText'    =>  'End Tour',
                'cardSize'      =>  'largemedium',
                'action'        =>  '',
            ),
        );

        $tourData['signon_settings'] = array(

            0 =>    array(
                'targetE'       =>  'signon_settings_tab',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>Premium Features</h1>',
                'contentHTML'   =>  'Protect your website, auto redirect the user to IdP and backdoor login features and more.',
                'ifNext'        =>  true,
                'buttonText'    =>  'End Tour',
                'cardSize'      =>  'largemedium',
                'action'        =>  '',
                'ifskip'        =>  'hidden',
            ),
        );

        $tourData['Mapping'] = array(

            0 =>    array(
                'targetE'       =>  'mo_saml_username_div_start',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>Login Drupal</h1>',
                'contentHTML'   =>  'Login or create your Drupal account by using <b>Username</b> or <b>Email</b>.',
                'ifNext'        =>  true,
                'buttonText'    =>  'Next',
                'cardSize'      =>  'medium',
                'action'        =>  '',
            ),
            1 =>    array(
                'targetE'       =>  'configure_attribute_mapping_start',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>Configure Attribute Mapping</h1>',
                'contentHTML'   =>  'While auto registering the users in your Drupal site these attributes will automatically get mapped to your Drupal user details.',
                'ifNext'        =>  true,
                'buttonText'    =>  'Next',
                'cardSize'      =>  'big',
                'action'        =>  '',
            ),
            4 =>    array(
                'targetE'       =>  'Custom_Attribute_Mapping',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>Premium Feature</h1>',
                'contentHTML'   =>  '<b>Custom attribute mapping</b>, You can map custom attribute.',
                'ifNext'        =>  true,
                'buttonText'    =>  'End Tour',
                'cardSize'      =>  'medium',
                'action'        =>  '',
            ),
            2 =>    array(
                'targetE'       =>  'Enable_Rolemapping',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>Role Mapping</h1>',
                'contentHTML'   =>  'Check this option if you want to enable <b>Role Mapping</b>.',
                'ifNext'        =>  true,
                'buttonText'    =>  'Next',
                'cardSize'      =>  'medium',
                'action'        =>  '',
            ),
            3 =>    array(
                'targetE'       =>  'Default_Mapping',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>Default Group</h1>',
                'contentHTML'   =>  'You can select default group for a new user.',
                'ifNext'        =>  true,
                'buttonText'    =>  'Next',
                'cardSize'      =>  'medium',
                'action'        =>  '',
            ),
        );

        $tourData['Export'] = array(
            0 =>    array(
                'targetE'       =>  'Exort_Configuration',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>Download Configuration</h1>',
                'contentHTML'   =>  'You can download module configuration file from here.',
                'ifNext'        =>  true,
                'buttonText'    =>  'Next',
                'cardSize'      =>  'medium',
                'action'        =>  '',
            ),
            1 =>    array(
                'targetE'       =>  'Import_Configuration',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>Premium Feature</h1>',
                'contentHTML'   =>  'Upload Configuration, You can upload configuration file downloaded from export configuration section.',
                'ifNext'        =>  true,
                'buttonText'    =>  'End Tour',
                'cardSize'      =>  'largemedium',
                'action'        =>  '',
            ),
        );

            return $tourData[$pageID] ;

    }
}

/*
                            ********************************
                                    array terms :
                            ********************************
pageID              -   your Page ID, contains array of popups
0                   -   Popup/card number, goes from zero to n. For next Tab card use 'nextCard' instead of number
targetE             -   Element to target to. Has to be element ID without #. If no ID, add one. Empty For none, shows in centre of screen if empty
pointToSide         -   Direction of arrow to point to (up,down,left,right), for no arrow-keep empty (places at center keep targetE empty) //look at this fix
titleHTML           -   Title of card, can be HTML code
contentHTML         -   Content of card, can be HTML code
ifNext              -   if to show(true) Next Button or not(false), Keep False for Card Number('nextTab')
buttonText          -   Next Button Text
img                 -   image(icon) attributes ('src' should not be 'empty' with 'visible' true)
                        src     -   url of image(best for ico/transparent png) icon(https://visualpharm.com/assets/262/Comments-595b40b65ba036ed117d3e48.svg)
                        visible -   to show image or not, true or false
cardSize            -   Card has 3 difined sizes- big, medium and small. Recomended not to use image with small
nextTab             -   This is special card used if you want user to move to next tab during tour, disabled during restart tour

 */