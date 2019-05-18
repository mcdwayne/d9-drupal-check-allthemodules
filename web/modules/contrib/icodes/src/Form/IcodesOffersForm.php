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

class IcodesOffersForm extends ConfigFormBase
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

        //state of new offers,published or not.
        $default = $config->get('icodes_offer_auto_publish');
        $form['icodes_offer_auto_publish'] = array(
            '#type' => 'checkbox',
            '#title' => t('Publish new offers'),
            '#description' => t('Should new offers be automatically published.'),
            '#default_value' => ($default !== null) ? $default : true,
        );

        $form['icodes_offer_hours'] = array(
            '#type' => 'select',
            '#options' => array(
                '1' => 'Add in last 1 hour',
                '2' => 'Add in last 2 hours',
                '4' => 'Add in last 4 hours',
                '8' => 'Add in last 8 hours',
                '12' => 'Add in last 12 hours',
                '24' => 'Add in last 24 hours',
                '36' => 'Add in last 36 hours',
                '48' => 'Add in last 48 hours',
                '72' => 'Add in last 72 hours',
                '168' => 'Add in last week',
                '720' => 'Add in last 30 days',
                '9999999' => 'All offers - Very Resource intensive',
            ),
            '#title' => t('Get the latest X hours worth of offers'),
            '#required' => TRUE,
            '#default_value' => $config->get('icodes_offer_hours'),
            '#description' => t('get all offers added in the last X hours.  Please note if a search option is selected below that will overide this setting'),
        );


        $form['search'] = array(
            '#type' => 'details',
            '#open' => false,
            '#title' => $this->t('Search feed for a subset of data'),
        );
        $form['search']['offer_search'] = array(
            '#type' => 'textfield',
            '#title' => t('Search paramater'),
            '#required' => false,
            '#default_value' => $config->get('offer_search'),
            '#description' => t('Search all offers starting with the value (A will produce all offers begining with A). This is usually left blank see the icodes webservices for more infro @webservicesLink.',
                array('@webservicesLink' => \Drupal::l(t('iCodes Dashboard'),
                    $webservice_link))),
        );


//        
        $categories = $this->contentTypeFieldValues("merchant", "node",
            "field_icodes_category");

        $categories = array("#empty_option" => t('- All -')) + $categories;

        $form['search']['icodes_offer_category_search'] = array(
            '#type' => 'select',
            '#options' => $categories,
            '#title' => t('Categories'),
            '#description' => t('Search all offers starting in this category. This is usually left blank see the icodes webservices for more infro @webservicesLink.',
                array('@webservicesLink' => \Drupal::l(t('iCodes Dashboard'),
                    $webservice_link))),
            '#default_value' => $config->get('icodes_merchant_category_search'),
        );


        //prepopulates the feeds tab to add into ultimate cron.
        //cron box to set when it should run(this is passed to ultimate cron) ("should only really run once a day, quite resource intensive")
        //Cron to execute and populte offers

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
            ->set('offer_search', $form_state->getValue('offer_search'))
            ->set('icodes_offer_hours', $form_state->getValue('icodes_offer_hours'))
            ->set('icodes_offer_auto_publish',
                $form_state->getValue('icodes_offer_auto_publish'))
            ->set('icodes_offer_category_search',
                $form_state->getValue('icodes_offer_category_search'))
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