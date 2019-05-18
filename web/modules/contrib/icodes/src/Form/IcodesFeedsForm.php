<?php

namespace Drupal\icodes\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/*
 * Icodes settings form.
 */

class IcodesFeedsForm extends ConfigFormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'icodes_settings_form_feeds';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return array('icodes.settings');
    }

    /**
     *
     * @param array $form
     * @param FormStateInterface $form_state
     * @return type
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('icodes.settings');
        $cron_overview = Url::fromRoute('entity.ultimate_cron_job.collection');

        /*
         * mercahnt settings
         */
        $merchant_feed = $this->generateMerchantFeedUrl($config);

        //intergrate with ultimate cron
        $merchant_cron_url = Url::fromRoute('entity.ultimate_cron_job.edit_form',
                array('ultimate_cron_job' => 'icodes_merchants_cron'));

        $merchant_cron_url_run = Url::fromRoute('icodes.process_merchants');


        /*
         * voucher settings
         */
        $voucher_feed = $this->generateVoucherFeedUrl($config);

        $voucher_cron_url = Url::fromRoute('entity.ultimate_cron_job.edit_form',
                array('ultimate_cron_job' => 'icodes_vouchers_cron'));

        $voucher_cron_url_run = Url::fromRoute('icodes.process_merchants');



        /*
         * offer settings
         */
        $offer_feed = $this->generateOfferFeedUrl($config);

        $offer_cron_url = Url::fromRoute('entity.ultimate_cron_job.edit_form',
                array('ultimate_cron_job' => 'icodes_offers_cron'));

        $offer_cron_url_run = Url::fromRoute('icodes.process_merchants');



        /*
         * Merchant settings
         */
        $form['merchant'] = array(
            '#type' => 'fieldset',
            '#title' => t('Merchant feed settings'),
        );

        //state of new merchants feed
        $default = $config->get('icodes_feeds_merchant_enable');
        $form['merchant']['icodes_feeds_merchant_enable'] = array(
            '#type' => 'checkbox',
            '#title' => t('Enable the merchant feed'),
            '#default_value' => ($default != null) ? $default : false,
        );

        $default = $config->get('merchant_feed_url');
        $form['merchant']['merchant_feed_url'] = array(
            '#type' => 'textarea',
            '#default_value' => ($default != null) ? $default : $merchant_feed,
            '#title' => t('Merchant Feed URL'),
            '#description' => t('This is your URL that is generated from the options in the merchants tab and the Global Settings. You can alter this feed here if you have any special requirements')
        );

        $form['merchant']['icodes_merchant_feed_url_reset'] = array(
            '#type' => 'checkbox',
            '#title' => t('Reset this feed URL to the automatically generated version.'),
            '#default_value' => false,
        );

        $form['merchant']['icodes_feeds_merchant_cron_settings'] = array(
            '#type' => 'item',
            '#title' => t('Merchant feed cron'),
            '#description' => t('Merchant Feed Importer Cron Settings can be managed here: @merchant_cron_url This can be run manually here but please be aware that some requests can only be run a limited amount of times in a 24 hour period. @merchant_cron_url_run ',
                array(
                '@merchant_cron_url' => \Drupal::l(t('Merchant Cron Settings'),
                    $merchant_cron_url),
                '@merchant_cron_url_run' => \Drupal::l(t('Debug Merchant Cron'),
                    $merchant_cron_url_run),
            ))
        );


        /*
         * voucher settings
         */
        $form['voucher'] = array(
            '#type' => 'fieldset',
            '#title' => t('Voucher feed settings'),
        );

        //state of new vouchers feed
        $default = $config->get('icodes_feeds_voucher_enable');
        $form['voucher']['icodes_feeds_voucher_enable'] = array(
            '#type' => 'checkbox',
            '#title' => t('Enable the voucher feed'),
            '#default_value' => ($default != null) ? $default : false,
        );

        $default = $config->get('voucher_feed_url');
        $form['voucher']['voucher_feed_url'] = array(
            '#type' => 'textarea',
            '#default_value' => ($default != null) ? $default : $voucher_feed,
            '#title' => t('Voucher Feed URL'),
            '#description' => t('This is your URL that is generated from the options in the merchants tab and the Global Settings. You can alter this feed here if you have any special requirements')
        );

        $form['voucher']['icodes_voucher_feed_url_reset'] = array(
            '#type' => 'checkbox',
            '#title' => t('Reset this feed URL to the automatically generated version.'),
            '#default_value' => false,
        );

        $form['voucher']['icodes_feeds_voucher_cron_settings'] = array(
            '#type' => 'item',
            '#title' => t('Voucher feed cron'),
            '#description' => t('Voucher Feed Importer Cron Settings can be managed here: @voucher_cron_url This can be run manually here but please be aware that some requests can only be run a limited amount of times in a 24 hour period. @voucher_cron_url_run ',
                array(
                '@voucher_cron_url' => \Drupal::l(t('Voucher Cron Settings'),
                    $voucher_cron_url),
                '@voucher_cron_url_run' => \Drupal::l(t('Debug Voucher Cron'),
                    $voucher_cron_url_run),
            ))
        );



        /*
         * offers settings
         */
        $form['offer'] = array(
            '#type' => 'fieldset',
            '#title' => t('Offer feed settings'),
        );

        //state of new offers feed
        $default = $config->get('icodes_feeds_offer_enable');
        $form['offer']['icodes_feeds_offer_enable'] = array(
            '#type' => 'checkbox',
            '#title' => t('Enable the offer feed'),
            '#default_value' => ($default != null) ? $default : false,
        );

        $default = $config->get('offer_feed_url');
        $form['offer']['offer_feed_url'] = array(
            '#type' => 'textarea',
            '#default_value' => ($default != null) ? $default : $offer_feed,
            '#title' => t('Offer Feed URL'),
            '#description' => t('This is your URL that is generated from the options in the merchants tab and the Global Settings. You can alter this feed here if you have any special requirements')
        );

        $form['offer']['icodes_offer_feed_url_reset'] = array(
            '#type' => 'checkbox',
            '#title' => t('Reset this feed URL to the automatically generated version.'),
            '#default_value' => false,
        );

        $form['offer']['icodes_feeds_offer_cron_settings'] = array(
            '#type' => 'item',
            '#title' => t('Offer feed cron'),
            '#description' => t('Offer Feed Importer Cron Settings can be managed here: @offer_cron_url This can be run manually here but please be aware that some requests can only be run a limited amount of times in a 24 hour period. @offer_cron_url_run ',
                array(
                '@offer_cron_url' => \Drupal::l(t('Offer Cron Settings'),
                    $offer_cron_url),
                '@offer_cron_url_run' => \Drupal::l(t('Debug Offer Cron'),
                    $offer_cron_url_run),
            ))
        );

        $form['overview'] = array(
            '#type' => 'fieldset',
            '#title' => $this->t('Feeds'),
        );

        $form['overview']['settings'] = array(
            '#type' => 'item',
            '#description' => t('A full set of cron settings for this site: @cron_overview.',
                array('@cron_overview' => \Drupal::l(t('Cron Settings'),
                    $cron_overview)))
        );


        return parent::buildForm($form, $form_state);
    }

    /**
     *
     * @return string
     */
    public function generateMerchantFeedUrl($config)
    {
        $merchant_feed_arguments = array(
            'UserName' => $config->get('icodes_username'),
            'SubscriptionID' => $config->get('api_key'),
            'RequestType' => 'MerchantList',
            'Action' => 'Full',
            'Relationship' => 'joined',
        );


        if ($config->get('merchant_search') != "") {
            $merchant_feed_arguments["Action"] = "Search";
            $merchant_feed_arguments["Query"] = $config->get('merchant_search');
            $merchant_feed_arguments["GroupBy"] = 'Merchant';
        }

        //TODO add category group by
        $merchant_feed = t($config->get('icodes_base_url'))."?".http_build_query($merchant_feed_arguments);

        return $merchant_feed;
    }

    /**
     *
     * @return string
     */
    public function generateVoucherFeedUrl($config)
    {
        $voucher_feed_arguments = array(
            'UserName' => $config->get('icodes_username'),
            'SubscriptionID' => $config->get('api_key'),
            'RequestType' => 'Codes',
            'Action' => 'Full',
            'Relationship' => 'joined',
        );

        if ($config->get('merchant_search') != "") {
            $voucher_feed_arguments["Action"] = "Search";
            $voucher_feed_arguments["Query"] = $config->get('voucher_search');
            $voucher_feed_arguments["GroupBy"] = 'Merchant';
        }   else if ($config->get('icodes_voucher_hours') !== "9999999") {
            $voucher_feed_arguments["Action"] = "New";
            $voucher_feed_arguments["Hours"] = $config->get('icodes_voucher_hours');
            unset($voucher_feed_arguments["Relationship"]);
        }

      

        $voucher_feed = t($config->get('icodes_base_url'))."?".http_build_query($voucher_feed_arguments);

        return $voucher_feed;
    }

    /**
     *
     * @return string
     */
    public function generateOfferFeedUrl($config)
    {
//        dpm("regenerate offer");
        $voucher_feed_arguments = array(
            'UserName' => $config->get('icodes_username'),
            'SubscriptionID' => $config->get('api_key'),
            'RequestType' => 'Offers',
            'Action' => 'Full',
            'Relationship' => 'joined',
        );

        if ($config->get('offer_search') != "") {
            $voucher_feed_arguments["Action"] = "Merchants";
            $voucher_feed_arguments["Query"] = $config->get('offer_search');
            $voucher_feed_arguments["GroupBy"] = 'Merchant';
        } else {
            $voucher_feed_arguments["Action"] = "New";
            $voucher_feed_arguments["Hours"] = $config->get('icodes_offer_hours');
            unset( $voucher_feed_arguments["Relationship"]);
        }

        $voucher_feed = t($config->get('icodes_base_url'))."?".http_build_query($voucher_feed_arguments);

        return $voucher_feed;
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

        //reset feed to autogenerated version
        if ($form_state->getValue('icodes_merchant_feed_url_reset') == true) {
            $merchant_feed = $this->generateMerchantFeedUrl($config);
            $config->set('merchant_feed_url', $merchant_feed);
        } else {
            $config->set('merchant_feed_url',
                $form_state->getValue('merchant_feed_url'));
        }

        if ($form_state->getValue('icodes_voucher_feed_url_reset') == true) {
            $voucher_feed = $this->generateVoucherFeedUrl($config);
            $config->set('voucher_feed_url', $voucher_feed);
        } else {
            $config->set('voucher_feed_url',
                $form_state->getValue('voucher_feed_url'));
        }

        if ($form_state->getValue('icodes_offer_feed_url_reset') == true) {
            $offer_feed = $this->generateOfferFeedUrl($config);
            $config->set('offer_feed_url', $offer_feed);
        } else {
            $config->set('offer_feed_url',
                $form_state->getValue('offer_feed_url'));
        }


        $config->set('icodes_feeds_voucher_enable',
            $form_state->getValue('icodes_feeds_voucher_enable'))->save();

        $config->set('icodes_feeds_offer_enable',
            $form_state->getValue('icodes_feeds_offer_enable'))->save();

        $config->set('icodes_feeds_merchant_enable',
            $form_state->getValue('icodes_feeds_merchant_enable'))->save();

        parent::submitForm($form, $form_state);
    }
}