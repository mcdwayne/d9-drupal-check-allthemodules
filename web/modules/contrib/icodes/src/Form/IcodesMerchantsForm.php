<?php

namespace Drupal\icodes\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\field\FieldConfigInterface;

/*
 * Icodes settings form.
 */

class IcodesMerchantsForm extends ConfigFormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'icodes_settings_form';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return array('icodes.settings');
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('icodes.settings');

        $api_url = Url::fromUri('http://www.icodes.co.uk/webservices/index.php',
                array('attributes' => array('target' => '_blank')));

        $webservice_link = Url::fromUri('http://www.icodes.co.uk/webservices/webservices.php',
                array('attributes' => array('target' => '_blank')));

        $icodes_high_res_link = Url::fromUri('http://www.vclogos.co.uk',
                array('attributes' => array('target' => '_blank')));

        //state of new merchants,published or not.
        $default = $config->get('icodes_merchant_auto_publish');
        $form['icodes_merchant_auto_publish'] = array(
            '#type' => 'checkbox',
            '#title' => t('Publish new merchants'),
            '#description' => t('Should new merchants be automatically published.'),
            '#default_value' => ($default !== null) ? $default : true,
        );


        //high resoultion images (costs extra) subscription id. (this will switch the url used for the images)
        //http://www.vclogos.co.uk/logo.php?subid=53fde96fcc41&imgid=4431
        $default = $config->get('icodes_merchant_high_res');
        $form['icodes_merchant_high_res'] = array(
            '#type' => 'textfield',
            '#title' => t('Merchant high resolution images API key'),
            '#description' => t('Import Merchant images in high resolution (paid extra), more info here @icodes_high_res_link.',
                array('@icodes_high_res_link' => \Drupal::l(t('iCodes Merchant Logos'),
                    $icodes_high_res_link))),
            '#default_value' => ($default != null) ? $default : "",
        );

        //directory for the saved merchant images to live in
        $default = $config->get('icodes_merchant_images_directory');
        $form['icodes_merchant_images_directory'] = array(
            '#type' => 'textfield',
            '#title' => t('Merchant images directory'),
            '#description' => t('Directory inside public files (with leading public://)'),
            '#default_value' => ($default != null) ? $default : "public://icodes/merchant-logos",
        );



        $form['search'] = array(
            '#type' => 'details',
            '#open' => false,
            '#title' => $this->t('Search feed for a subset of data'),
        );


        $form['search']['merchant_search'] = array(
            '#type' => 'textfield',
            '#title' => t('Search paramater'),
            '#required' => false,
            '#default_value' => $config->get('merchant_search'),
            '#description' => t('Search all merchants starting with the value (A will produce all merchants begining with A). This is usually left blank see the icodes webservices for more infro @webservicesLink.',
                array('@webservicesLink' => \Drupal::l(t('iCodes Dashboard'),
                    $webservice_link))),
        );

        $categories = $this->contentTypeFieldValues("merchant", "node",
            "field_icodes_category");
        $categories = array("#empty_option" => t('- All -')) + $categories;

        $form['search']['icodes_merchant_category_search'] = array(
            '#type' => 'select',
            '#options' => $categories,
            '#title' => t('Categories'),
            '#description' => t('Search all merchants starting in this category. This is usually left blank see the icodes webservices for more infro @webservicesLink.',
                array('@webservicesLink' => \Drupal::l(t('iCodes Dashboard'),
                    $webservice_link))),
            '#default_value' => $config->get('icodes_merchant_category_search'),
        );


        $form['icodes_base_url'] = array(
            '#type' => 'select',
            '#options' => array(
                'http://webservices.icodes.co.uk/ws2.php' => 'UK',
                'http://webservices.icodes-us.com/ws2_us.php' => 'USA',
                'http://webservices.icodes-us.com/ws2_india.php' => 'India',
            ),
            '#title' => t('iCodes Subscription ID'),
            '#required' => TRUE,
            '#default_value' => $config->get('icodes_base_url'),
            '#description' => t('Base Url for feeds '),
        );



        //prepopulates the feeds tab to add into ultimate cron.
        //cron box to set when it should run(this is passed to ultimate cron) ("should only really run once a day, quite resource intensive")
        //Cron to execute and populte merchants

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        parent::validateForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $config = $this->config('icodes.settings');
        $config
            ->set('merchant_search', $form_state->getValue('merchant_search'))
            ->set('icodes_merchant_auto_publish',
                $form_state->getValue('icodes_merchant_auto_publish'))
            ->set('icodes_merchant_high_res',
                $form_state->getValue('icodes_merchant_high_res'))
            ->set('icodes_merchant_images_directory',
                $form_state->getValue('icodes_merchant_images_directory'))
            ->set('icodes_merchant_category_search',
                $form_state->getValue('icodes_merchant_category_search'))
            ->save();

        parent::submitForm($form, $form_state);
    }

    /**
     * 
     * @param type $contentType
     * @return type
     */
    public function contentTypeFieldValues($contentType, $entity_type = "node",
                                           $field)
    {

        $options = \Drupal\field\Entity\FieldConfig::loadByName($entity_type,
                $contentType, $field)->getSetting('allowed_values');
        return $options;
    }
}