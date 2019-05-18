<?php

/**
 * @file
 * Contains \Drupal\miniorange_saml\Form\MiniorangeIDPSetup.
 */

namespace Drupal\miniorange_saml\Form;

use Drupal\Core\Form\FormBase;
use Drupal\miniorange_saml\Utilities;
use Drupal\miniorange_saml\mo_saml_visualTour;


class MiniorangeIDPSetup extends FormBase {

    public function getFormId() {
        return 'miniorange_saml_idp_setup';
    }

    public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

        global $base_url;
        $url = \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_base_url');
        $issuer = \Drupal::config('miniorange_saml.settings')->get('miniorange_saml_entity_id');

        $b_url = isset($url) && !empty($url)? $url:$base_url;
        $issuer_id = isset($issuer) && !empty($issuer)? $issuer:$base_url;

        Utilities::visual_tour_start($form, $form_state);

        $acs_url = $b_url . '/samlassertion';

        $form['header_top_style_1'] = array(
            '#markup' => '<div class="mo_saml_table_layout_1">',
        );

        $form['markup_top'] = array(
            '#markup' => '<div class="mo_saml_table_layout mo_saml_container">'
        );
        $form['miniorange_saml_tour_button'] = array(
            '#attached' => array(
                'library' => 'miniorange_saml/miniorange_saml.Vtour',
            ),
            '#markup' => '<div><h3>IDENTITY PROVIDER SETUP &nbsp;&nbsp; <a id="Restart_moTour" class="btn btn-danger btn-sm" onclick="Restart_moTour()">Take a Tour</a></h3><hr></div><br>',
        );

        $form['header'] = array(
            '#markup' => '<div class="mo_saml_text_center"><h3>You will need the following information to'
                . ' configure your IdP. Copy it and keep it handy</h3></div>',
        );

        $header = array(
            'attribute' => array('data' => t('Attribute'),),
            'value' => array('data' => t('Value'),),
        );

        $form['configure_IDP_url_startsss'] = array(
            '#markup' => '<div id="configure_IDP_url">'
        );

        $options = array();

        $options[0] = array(
            'attribute' => t('SP Entity ID/Issuer'),
            'value' => $issuer_id,
        );

        $options[1] = array(
            'attribute' => t('ACS URL'),
            'value' => $acs_url,
        );

        $options[2] = array(
            'attribute' => t('Audience URI'),
            'value' => $b_url,
        );

        $options[3] = array(
            'attribute' => t('Recipient URL'),
            'value' => $acs_url,
        );

        $options[4] = array(
            'attribute' => t('Destination URL'),
            'value' => $acs_url,
        );

        $options[5] = array(
            'attribute' => t('Single Logout URL'),
            'value' => t('Available in <b><a href="' . $base_url . '/admin/config/people/miniorange_saml/Licensing">Premium, Enterprise</a></b> versions of the module'),
        );

        $options[6] = array(
            'attribute' => t('NameID Format'),
            'value' => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
        );

        $form['configure_IDP_url_endss'] = array(
            '#markup' => '</div>'
        );

        $form['fieldset']['spinfo'] = array(
            '#theme' => 'table',
            '#header' => $header,
            '#rows' => $options,
        );

        $form['markup_sp_md_or'] = array(
            '#markup' => '<div class="mo_saml_text_center"><b>OR</b><br /><br></div>',
        );

        $form['markup_sp_md_1'] = array(
            '#markup' => 'You can provide this metadata URL to your Identity Provider.<br />',
        );

        $form['markupsp_sp_md_2'] = array(
            '#markup' => '<div id="meta_data_url_for_IDP" class="mo_saml_highlight_background_note"><code style="background-color:gainsboro;"><b>'
                . '<a target="_blank" href="' . $b_url . '/modules/miniorange_saml/includes/metadata/metadata.php">' . $b_url . '/modules/miniorange_saml/includes/metadata/metadata.php' . '</a></b></code></div><br><br></div>',
        );

        Utilities::AddsupportTab( $form, $form_state);

        return $form;

    }

    function saved_support($form, &$form_state)
    {
        $email = $form['miniorange_saml_email_address_support']['#value'];
        $phone = $form['miniorange_saml_phone_number_support']['#value'];
        $query = $form['miniorange_saml_support_query_support']['#value'];
        Utilities::send_support_query($email, $phone, $query);
    }

    public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

    }
}