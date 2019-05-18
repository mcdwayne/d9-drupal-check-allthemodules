<?php

namespace Drupal\instawidget\Form;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\instawidget\InstaLibrary;
use Symfony\Component\DependencyInjection\ContainerInterface;
/**
 * Configure custom settings for this site.
 */
class AccessTokenForm extends ConfigFormBase {

    /**
     * Constructor for SocialFeedsBlockForm.
     *
     * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
     *   The factory for configuration objects.
     */
    private $instalibrary;
    public function __construct(InstaLibrary $instalibrary) {

        $this->instalibrary = $instalibrary;
    }

    
    public static function create(ContainerInterface $container)
    {
        $instalibraryobject = $container->get('instawidget.instalibrary');
        return new static($instalibraryobject);
    }
    /**
     * Returns a unique string identifying the form.
     *
     * @return string
     *   The unique string identifying the form.
     */
    public function getFormId() {
        return 'instawidget_access_token_form';
    }

    /**
     * Gets the configuration names that will be editable.
     *
     * @return array
     *   An array of configuration object names that are editable if called in
     *   conjunction with the trait's config() method.
     */
    protected function getEditableConfigNames() {
        return ['config.instawidget_tokensettingsconfig'];
    }

    /**
     * Form constructor.
     *
     * @param array $form
     *   An associative array containing the structure of the form.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The current state of the form.
     *
     * @return array
     *   The form structure.
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('config.instawidget_tokensettingsconfig');
       
        $form['access_token'] = array(
            '#type' => 'textarea',
            '#title' => $this->t('Access Token'),
            '#default_value' => $config->get('access_token_string'),
            '#attributes' => [
                
                'class'    => ['insta-access-token']
            ],
            '#description' => 'Access Token For Instagram',
            '#maxlength' => 9999,
           
        );
        
        $form['actions']['submit_reset'] = [
             '#type' => 'submit',
             '#value' => t('Click To Generate Access Token'),
             '#submit' => array('::submitFormReset'),
        ];
        
         $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Save'),
        );
        return $form;
    }
    
    public function submitFormReset(array &$form, FormStateInterface $form_state) {
       $access_token = $this->instalibrary->getInstaAcessToken();
       $form_state->setResponse(new TrustedRedirectResponse($access_token, 302));
       
    }
    /**
     * Form submission handler.
     *
     * @param array $form
     *   An associative array containing the structure of the form.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The current state of the form.
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $config = $this->config('config.instawidget_tokensettingsconfig');
        $config->set('access_token_string', $form_state->getValue('access_token'));
        $config->save();
        drupal_set_message('Access Token Settings Saved');
    }

}
