<?php

namespace Drupal\instagram_hashtag_fetcher\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Provides instagram pictures configuration form.
 */
class InstagramPicturesConfigForm extends ConfigFormBase
{

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames()
    {
        return ['instagram_hashtag_fetcher.settings'];
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'instagram_pictures_configuration';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form = parent::buildForm($form, $form_state);
        $config = $this->config('instagram_hashtag_fetcher.settings');

        $form['instagram_pictures_fieldset'] = array(
            '#type' => 'details',
            '#title' => $this->t('Instagram Pictures Configurations'),
            '#description' => $this->t('Configure instagram pictures settings.'),
            '#open' => TRUE,
        );

        $form['instagram_pictures_fieldset']['instagram_pictures_hashtag'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Hashtag'),
            '#default_value' => $config->get('hashtag'),
            '#description' => $this->t('Enter the hashtag which will be used to fetch recents pictures.'),
            '#required' => TRUE,
        );
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $config = $this->config('instagram_hashtag_fetcher.settings');
        $config->set('hashtag', $form_state->getValue('instagram_pictures_hashtag'));
        $config->save();
        drupal_flush_all_caches();
        return parent::submitForm($form, $form_state);
    }

}
