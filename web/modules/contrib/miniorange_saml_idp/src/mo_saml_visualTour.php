<?php
namespace Drupal\miniorange_saml_idp;

class mo_saml_visualTour {

   public static function genArray(){
        $link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $exploded = explode('/', $link);
        $getPageName = end($exploded);

            $Tour_Token = \Drupal::config('miniorange_saml_idp.settings')->get('mo_saml_tourTaken_' . $getPageName);
            \Drupal::configFactory()->getEditable('miniorange_saml_idp.settings')->set('mo_saml_tourTaken_' . $getPageName, TRUE)->save();
            $moTourArr = array (
                'pageID' => $getPageName,
                'tourData' => mo_saml_visualTour::getTourData($getPageName),
                'tourTaken' => $Tour_Token,
                'addID' => mo_saml_visualTour::addID(),
                'pageURL' => $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
            );
           $moTour = json_encode($moTourArr);
           return $moTour;
    }

    public static function addID()
    {
        $idArray = array(
            array(
                'selector'  =>'li.tabs__tab:nth-of-type(1)',
                'newID'     =>'mo_vt_account',
            ),
            array(
                'selector'  =>'li.tabs__tab:nth-of-type(2)',
                'newID'     =>'mo_vt_idp_setup',
            ),
            array(
                'selector'  =>'li.tabs__tab:nth-of-type(3)',
                'newID'     =>'mo_vt_sp_setup',
            ),
            array(
                'selector'  =>'li.tabs__tab:nth-of-type(4)',
                'newID'     =>'mo_vt_mapping',
            ),
            array(
                'selector'  =>'li.tabs__tab:nth-of-type(5)',
                'newID'     =>'mo_vt_licensing',
            ),
            array(
                'selector'  =>'table',
                'newID'     =>'mo_SP_url_table',
            ),
        );
        return $idArray;
    }

    public static function getTourData($pageID)
    {
        $tourData = array();

        $tourData['customer_setup'] = array(
            0 =>    array(
                'targetE'       =>  'Support_Section',
                'pointToSide'   =>  'right',
                'titleHTML'     =>  '<h1>Support</h1>',
                'contentHTML'   =>  'Get in touch with us and we will help you setup the module in no time.',
                'ifNext'        =>  true,
                'buttonText'    =>  'Next',
                'cardSize'      =>  'medium',
                'action'        =>  '',
            ),
            1 =>    array(
                'targetE'       =>  'mo_vt_account',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>Customer Setup Tab</h1>',
                'contentHTML'   =>  'You can register/Login into your miniOrange account here.',
                'ifNext'        =>  true,
                'buttonText'    =>  'Next',
                'cardSize'      =>  'medium',
                'action'        =>  '',
            ),
            2 =>    array(
                'targetE'       =>  'mo_vt_idp_setup',
                'pointToSide'   =>  'up',
                'titleHTML'     =>  '<h1>Identity Provider Tab</h1>',
                'contentHTML'   =>  'Configure this tab using service provider information which you get from SP-Metadata XML',
                'ifNext'        =>  true,
                'buttonText'    =>  'Next',
                'cardSize'      =>  'big',
                'action'        =>  '',
            ),
            3 =>    array(
                'targetE'       =>  'mo_vt_sp_setup',
                'pointToSide'   =>  'up',
                'titleHTML'     =>  '<h1>Service Provider Tab</h1>',
                'contentHTML'   =>  'This tab provides details to configure your Service Provider.',
                'ifNext'        =>  true,
                'buttonText'    =>  'Next',
                'cardSize'      =>  'largemedium',
                'action'        =>  '',
            ),
            4 =>    array(
                'targetE'       =>  'mo_vt_mapping',
                'pointToSide'   =>  'up',
                'titleHTML'     =>  '<h1>Attribute Mapping Tab</h1>',
                'contentHTML'   =>  'In this tab you can find NameID attribute, Custom attribute mapping and more.',
                'ifNext'        =>  true,
                'buttonText'    =>  'Next',
                'cardSize'      =>  'big',
                'action'        =>  '',
            ),
            5 =>    array(
                'targetE'       =>  'mo_vt_licensing',
                'pointToSide'   =>  'up',
                'titleHTML'     =>  '<h1>Licensing Tab</h1>',
                'contentHTML'   =>  'You can find premium features and can upgrade to our premium plans.',
                'ifNext'        =>  true,
                'buttonText'    =>  'Next',
                'cardSize'      =>  'largemedium',
                'action'        =>  '',
            ),
            6 =>    array(
                'targetE'       =>  'Register_Section',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>Register/Login with miniOrange</h1>',
                'contentHTML'   =>  'Just complete the short registration with miniOrange to use module.',
                'ifNext'        =>  true,
                'buttonText'    =>  'Close',
                'cardSize'      =>  'medium',
                'action'        =>  '',
            ),
        );

        $tourData['idp_setup'] = array(
            0 =>    array(
                'targetE'       =>  'edit-miniorange-saml-idp-name',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>Service Provider Name</h1>',
                'contentHTML'   =>  'Enter appropriate name for your Service Provider',
                'ifNext'        =>  true,
                'buttonText'    =>  'Next',
                'cardSize'      =>  'medium',
                'action'        =>  '',
            ),
            1 =>    array(
                'targetE'       =>  'edit-miniorange-saml-idp-entity-id',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>SP Entity ID/Issuer</h1>',
                'contentHTML'   =>  'You can find the EntityID in your SP-Metadata XML file enclosed in <code>entityDescriptor</code> tag having attribute as entityID.',
                'ifNext'        =>  true,
                'buttonText'    =>  'Next',
                'cardSize'      =>  'big',
                'action'        =>  '',
            ),
            2 =>    array(
                'targetE'       =>  'edit-miniorange-saml-idp-nameid-format',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>NameID Format</h1>',
                'contentHTML'   =>  'You can select NameID format to send in SAML response.',
                'ifNext'        =>  true,
                'buttonText'    =>  'Next',
                'cardSize'      =>  'medium',
                'action'        =>  '',
            ),
            3 =>    array(
                'targetE'       =>  'edit-miniorange-saml-idp-acs-url',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>ACS URL</h1>',
                'contentHTML'   =>  'You can find the SAML login URL in Your SP-Metadata XML file enclosed in <code>AssertionConsumerService</code> tag having attribute as Location.',
                'ifNext'        =>  true,
                'buttonText'    =>  'Next',
                'cardSize'      =>  'big',
                'action'        =>  '',
            ),
            4 =>    array(
                'targetE'       =>  'edit-miniorange-saml-idp-relay-state',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>Relay State (Optional)</h1>',
                'contentHTML'   =>  'You can give URL here, on which user will be redirected after successful SSO.',
                'ifNext'        =>  true,
                'buttonText'    =>  'Next',
                'cardSize'      =>  'largemedium',
                'action'        =>  '',
            ),
            5 =>    array(
                'targetE'       =>  'assertion_signed',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>Assertion Signed</h1>',
                'contentHTML'   =>  'Select this option if you want to sign SAML Assertion',
                'ifNext'        =>  true,
                'buttonText'    =>  'Next',
                'cardSize'      =>  'medium',
                'action'        =>  '',
            ),
            6 =>    array(
                'targetE'       =>  'Support_Section',
                'pointToSide'   =>  'right',
                'titleHTML'     =>  '<h1>Support</h1>',
                'contentHTML'   =>  'Get in touch with us and we will help you setup the module in no time.',
                'ifNext'        =>  true,
                'buttonText'    =>  'Close',
                'cardSize'      =>  'medium',
                'action'        =>  '',
            ),
        );

        $tourData['sp_setup'] = array(
            0 =>    array(
                'targetE'       =>  'mo_SP_url_table',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>IDP Metadata URLs</h1>',
                'contentHTML'   =>  'You can manually configure your Service Provider using the information given here.',
                'ifNext'        =>  true,
                'buttonText'    =>  'Next',
                'cardSize'      =>  'largemedium',
                'action'        =>  '',
            ),
            1 =>    array(
                'targetE'       =>  'meta_data_url_for_SP',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>IDP Metadata URL</h1>',
                'contentHTML'   =>  'Provide this Metadata URL to configure your Service Provider',
                'ifNext'        =>  true,
                'buttonText'    =>  'Close',
                'cardSize'      =>  'medium',
            ),
        );

        $tourData['Mapping'] = array(
            0 =>    array(
                'targetE'       =>  'edit-miniorange-saml-idp-nameid-attr-map',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>NameID Attribute</h1>',
                'contentHTML'   =>  'This attribute value is sent in SAML Response. Users in your Service Provider will be searched (existing users) or created (new users) based on this attribute.',
                'ifNext'        =>  true,
                'buttonText'    =>  'Next',
                'cardSize'      =>  'big',
                'action'        =>  '',
            ),
            1 =>    array(
                'targetE'       =>  'Custom_Attribute_Mapping_start',
                'pointToSide'   =>  'left',
                'titleHTML'     =>  '<h1>Premium Feature</h1>',
                'contentHTML'   =>  '<b>Custom attribute mapping</b>, You can select attributes to send in SAML response.',
                'ifNext'        =>  true,
                'buttonText'    =>  'Close',
                'cardSize'      =>  'largemedium',
                'action'        =>  '',
            ),
        );
        return $tourData[$pageID];
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