<?php
/**
 * Created by PhpStorm.
 * User: WesselVrolijks
 * Date: 11/01/2018
 * Time: 14:08
 */

namespace Drupal\twizo\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\twizo\Api\TwizoApi;
use Drupal\user\UserDataInterface;
use Twizo\Api\Exception;

class TwizoAccountForm extends FormBase {
    private $twizo;
    private $uid;
    private $config;
    private $userData;


    public function __construct()
    {
        $this->twizo = new TwizoApi();
        $this->uid = \Drupal::currentUser()->id();
        $this->config = \Drupal::config('twizo.adminsettings');
        $this->userData = \Drupal::service('user.data');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'twizo_account_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        // Get uid
        $uid = $this->uid;
        $twizo = $this->twizo;
        $config = $this->config;

        /** @var UserDataInterface $userData */
        $userData = $this->userData;

        $verificationTypes = $this->twizo->getVerificationTypes();

        // Delete backupcodes from verificationtypes
        unset($verificationTypes[array_search('backupcode', $verificationTypes)]);

        // Get config data
        $number = $userData->get('twizo', $uid, 'number');
        $codesGenerated = $userData->get('twizo', $uid, 'codesGenerated');
        $identifier = $userData->get('twizo', $uid, 'identifier');
        $preferredMethod = $userData->get('twizo', $uid, 'preferredMethod');
        $widgetEnabled = $userData->get('twizo', $uid, 'widgetEnabled');
        $biovoiceRegistered = $userData->get('twizo', $uid, 'biovoiceRegistered');

        $totpUrl = $userData->get('twizo', $uid, 'totpUrl');

        // Check if admin entered the required credentials.
        if($this->twizo->validateApiCredentials() && $config->get('twizo_enable_2fa')) {
            $form['number'] = [
                '#type' => 'textfield',
                '#title' => t('Phone number'),
                '#description' => t('A confirmation code will be sent to via SMS to the number provided'),
                '#default_value' => isset($number) ? $number : '',
                '#size' => 40,
                '#weight' => 0,
                '#maxlength' => 255,
                '#required' => TRUE,
            ];
            if(isset($number)){
                $form['widgetEnabled'] = [
                    '#type' => 'checkbox',
                    '#title' => t('Enable two factor authentication'),
                    '#weight' => 1,
                    '#default_value' => $widgetEnabled,
                ];
                $form['totpdes']['text'] = [
                    '#markup' => '<h3>TOTP QR Code</h3>'
                ];
                $form['totp']['img'] = [
                    '#markup' => '<img src = "' . $this->twizo->getTotpQrUrl($totpUrl) .  '" title="totpQrCode"> </img>',
                ];
                $form['reset_number'] = [
                    '#type' => 'button',
                    '#value' => $this->t('Save configuration'),
                    '#weight' => 3,
                    '#ajax' => [
                        'callback' => 'Drupal\twizo\Controller\UserAccountController::saveChanges',
                        'wrapper' => 'twizo_ajax_delete_number',
                        'method' => 'replace',
                    ],
                ];
                if($widgetEnabled){
                    $form['preferredMethod'] = [
                        '#type' => 'select',
                        '#title' => t('Preferred login method'),
                        '#default_value' => array_search($preferredMethod, $verificationTypes),
                        '#weight' => 2,
                        '#options' => $verificationTypes,
                        '#description' => t('Select preferred login method'),
                    ];
                    if($config->get('twizo_enable_backupcodes')){
                        if($codesGenerated){
                            $form['ammountOfCodesLeft'] = [
                                '#markup' => '<p>' . $this->t('You have @codesLeft backupcodes left.', ['@codesLeft' => $twizo->getRemainingBackupCodes($identifier)]) . '</p>',
                                '#weight' => -1,
                            ];
                            $form['updateBackupCodes'] = array(
                                '#type' => 'button',
                                '#value' => $this->t('Update backup codes'),
                                '#weight' => 4,
                                '#ajax' => array(
                                    'callback' => 'Drupal\twizo\Controller\UserAccountController::updateBackupCodes',
                                    'wrapper' => 'twizo_update_backup_code_wrapper',
                                ),
                            );
                        } else{
                            $form['generateBackupCodes'] = array(
                                '#type' => 'button',
                                '#value' => $this->t('Generate backup codes'),
                                '#weight' => 4,
                                '#ajax' => array(
                                    'callback' => 'Drupal\twizo\Controller\UserAccountController::generateBackupCodes',
                                    'wrapper' => 'twizo_generate_backup_code_wrapper',
                                ),
                            );
                        }
                    }
                    if(!$biovoiceRegistered || !$twizo->getBiovoiceRegistrationStatus($number)) {
                        $form['registrateBiovoice'] = array(
                            '#type' => 'button',
                            '#value' => $this->t('Register biovoice'),
                            '#weight' => 4,
                            '#ajax' => array(
                                'callback' => 'Drupal\twizo\Controller\UserAccountController::registerBiovoice',
                                'wrapper' => 'twizo_biovoice_wrapper',
                            ),
                        );
                    }
                }
            } else {
                $form['validateNumber'] = [
                    '#type' => 'button',
                    '#value' => $this->t('Validate number'),
                    '#weight' => 4,
                    '#ajax' => [
                        'callback' => 'Drupal\twizo\Controller\UserAccountController::validateNumber',
                        'event' => 'click',
                        'progress' => [
                            'type' => 'throbber',
                            'message' => $this->t('Opening widget...'),
                        ],
                    ],
                ];
            }
        } elseif ($config->get('twizo_enable_2fa') == 0){
            // Twizo is disabled in the adminsettings
            $form['disabled']['title'] = [
                '#markup' => '<h1>' . $this->t('Twizo disabled') . '</h1>',
            ];
            $form['disabled']['description'] = [
                '#markup' => '<p>' . $this->t('Twizo two factor authentication is disabled by an admin.') . '</p>',
            ];
        } else {
            // Inform user that Twizo isn't setup correctly.
            $form['credentialfailure']['title'] = [
                '#markup' => '<h1>' . $this->t('Twizo error') . '</h1>',
            ];
            $form['credentialfailure']['description'] = [
                '#markup' => '<p>' . $this->t('The Twizo module is not set up correctly. Please contact an admin or enter the right credentials in the admin configuration.') . '</p>',
            ];
        }

        // Inject .js files for Twizo widget
        $form['#attached']['library']['twizo_external'] = 'twizo/twizo_widget_external_js';
        $form['#attached']['library']['twizo_widget_handler'] = 'twizo/twizo_widget_handler';

        return $form;
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
        parent::submitForm($form, $form_state);
        // Ajax callback is used for submission
    }
}