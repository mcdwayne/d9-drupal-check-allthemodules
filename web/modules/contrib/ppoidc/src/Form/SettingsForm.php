<?php

namespace Drupal\pixelpin_openid_connect\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\pixelpin_openid_connect\Claims;
use Drupal\pixelpin_openid_connect\Plugin\OpenIDConnectClientManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\pixelpin_openid_connect\Form
 */
class SettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * Drupal\pixelpin_openid_connect\Plugin\OpenIDConnectClientManager definition.
   *
   * @var \Drupal\pixelpin_openid_connect\Plugin\OpenIDConnectClientManager
   */
  protected $pluginManager;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityFieldManager;

  /**
   * The OpenID Connect claims.
   *
   * @var \Drupal\pixelpin_openid_connect\Claims
   */
  protected $claims;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\pixelpin_openid_connect\Plugin\OpenIDConnectClientManager $plugin_manager
   *   The plugin manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\pixelpin_openid_connect\Claims $claims
   *   The claims.
   */
  public function __construct(
      ConfigFactoryInterface $config_factory,
      OpenIDConnectClientManager $plugin_manager,
      EntityFieldManagerInterface $entity_field_manager,
      Claims $claims
  ) {
    parent::__construct($config_factory);
    $this->pluginManager = $plugin_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->claims = $claims;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.pixelpin_openid_connect_client.processor'),
      $container->get('entity_field.manager'),
      $container->get('pixelpin_openid_connect.claims')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['pixelpin_openid_connect.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pixelpin_openid_connect_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings = $this->configFactory()
      ->getEditable('pixelpin_openid_connect.settings');

    $options = array();
    foreach ($this->pluginManager->getDefinitions() as $client_plugin) {
      $options[$client_plugin['id']] = $client_plugin['label'];
    }

    $clients_enabled = array();
    foreach ($this->pluginManager->getDefinitions() as $client_plugin) {
      $enabled = $this->configFactory()
        ->getEditable('pixelpin_openid_connect.settings.' . $client_plugin['id'])
        ->get('enabled');
      $clients_enabled[$client_plugin['id']] = (bool) $enabled ? $client_plugin['id'] : 0;
    }

    

    $form['#tree'] = TRUE;
    $form['clients_enabled'] = array(
      '#title' => t('Enable PixelPin OpenID Connect'),
      '#type' => 'checkboxes',
      '#options' => $options, 
      '#default_value' => $clients_enabled,
    );

    foreach ($this->pluginManager->getDefinitions() as $client_name => $client_plugin) {
      $form['#attached'] = array(
            'library' => array(
              'pixelpin_openid_connect/pixelpin_sso',
          ),
      );

      $configuration = $this->configFactory()
        ->getEditable('pixelpin_openid_connect.settings.' . $client_name)
        ->get('settings');

      /* @var \Drupal\pixelpin_openid_connect\Plugin\OpenIDConnectClientInterface $client */
      $client = $this->pluginManager->createInstance(
        $client_name,
        $configuration
      );

      $element = 'clients_enabled[' . $client_plugin['id'] . ']';
      $form['clients'][$client_plugin['id']] = array(
        '#title' => $client_plugin['label'],
        '#type' => 'fieldset',
        '#tree' => TRUE,
        '#states' => array(
          'visible' => array(
            ':input[name="' . $element . '"]' => array('checked' => TRUE),
          ),
        ),
      );
      $form['clients'][$client_plugin['id']]['settings'] = array();
      $form['clients'][$client_plugin['id']]['settings'] += $client->buildConfigurationForm([], $form_state);
    }

    $form['always_save_userinfo'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Save user claims on every login'),
      '#description' => $this->t('If disabled, user claims will only be saved when the account is first created.'),
      '#default_value' => $settings->get('always_save_userinfo'),
    );


    $ppssoVariables = $settings->get('ppsso_customise');

    $form['ppsso_customise'] = array(
      '#title' => t('Change the way the pixelpin log in button looks'),
      '#type' => 'fieldset',
    );
  
    $form['ppsso_customise']['enable'] = array(
        '#type' => 'select',
        '#title' => 'Do you want to customise the way the log in button looks?',
        '#options' => ['No','Yes'],
        '#attributes' => array('onchange' => 'toggleppssopreview();'),
        '#default_value' => $ppssoVariables['enable']
    );
  
    $form['ppsso_customise']['size'] = array(
        '#type' => 'select',
        '#title' => 'Log In Button Size',
        '#options' => ['Large', 'Medium', 'Small'],
        '#default_value' => $ppssoVariables['size'],
        '#attributes' => array('onchange' => 'toggleppssopreview();'),
        '#states' => array(
          'visible' => array(
            'select[name="ppsso_customise[enable]"]' => array('value' => '1')
          )
        ),
    );
  
    $form['ppsso_customise']['colour'] = array(
        '#type' => 'select',
        '#title' => 'Log In Button Colour',
        '#options' => ['Purple', 'Cyan', 'Pink', 'White'],
        '#default_value' => $ppssoVariables['colour'],
        '#attributes' => array('onchange' => 'toggleppssopreview();'),
        '#states' => array(
          'visible' => array(
            'select[name="ppsso_customise[enable]"]' => array('value' => '1')
          )
        ),
    );
  
    $form['ppsso_customise']['show_text'] = array(
        '#type' => 'select',
        '#title' => 'Do you want the button to contain text?',
        '#options' => ['Yes', 'No'],
        '#default_value' => $ppssoVariables['show_text'],
        '#attributes' => array('onchange' => 'toggleppssopreview();'),
        '#states' => array(
          'visible' => array(
            'select[name="ppsso_customise[enable]"]' => array('value' => '1')
          )
        ),
    );
  
    $form['ppsso_customise']['login_text'] = array(
        '#title' => t('Login Button Text'),
        '#type' => 'textfield',
        '#default_value' => $ppssoVariables['login_text'],
        '#attributes' => array('onchange' => 'toggleppssopreview();'),
        '#states' => array(
          'visible' => array(
            'select[name="ppsso_customise[enable]"]' => array('value' => '1'),
            'select[name="ppsso_customise[show_text]"]' => array('value' => '0')
          ),
        ),
    );
  
    $form['ppsso_customise']['register_text'] = array(
        '#title' => t('Register Button Text'),
        '#type' => 'textfield',
        '#default_value' => $ppssoVariables['register_text'],
        '#states' => array(
          'visible' => array(
            'select[name="ppsso_customise[enable]"]' => array('value' => '1'),
            'select[name="ppsso_customise[show_text]"]' => array('value' => '0')
          ),
        ),
    );

    $customSsoButton = $settings->get('ppsso');

    if($customSsoButton['sampleButton']){
      $sampleSsoButton = $customSsoButton['sampleButton'];
    } else {
      $sampleSsoButton = '<button name="pixelpin" id="previewSSObutton" class="ppsso-btn">Log in with <span class="ppsso-logotype">PixelPin</span></button>';
    }

    if($customSsoButton['loginButton']){
      $liveSsoButton = $customSsoButton['loginButton'];
    } else {
      $liveSsoButton = '<button name="pixelpin" id="edit-openid-connect-client-pixelpin-login-front-page" class="ppsso-btn">Log in with <span class="ppsso-logotype">PixelPin</span></button>';
    }

    $form['ppsso_customise']['preview_sso_styling_label'] = array(
      '#markup' => t('<label>Preview PixelPin SSO Styling </label>')
    );

    $form['ppsso_customise']['preview_sso_styling'] = array(
      '#markup' => t($sampleSsoButton)
    );

    $form['ppsso_customise']['live_sso_styling_label'] = array(
      '#markup' => t('<label>Live PixelPin SSO Styling </label>')
    );

    $form['ppsso_customise']['live_sso_styling'] = array(
      '#title' => t('Live PixelPin SSO Styling'),
      '#markup' => t($liveSsoButton)
    );

    $form['ppsso_customise']['notice'] = array(
      '#markup' => t("<p><b>IMPORTANT: </b>To apply the button stylings. You'll need to clear the site's cache.</p>")
    );

    $form['userinfo_mappings'] = array(
      '#title' => t('User claims mapping'),
      '#type' => 'fieldset',
    );

    $properties = $this->entityFieldManager->getFieldDefinitions('user', 'user');
    $properties_skip = _pixelpin_openid_connect_user_properties_to_skip();
    $claims = $this->claims->getOptions();
    $mappings = $settings->get('userinfo_mappings');
    foreach ($properties as $property_name => $property) {
      if (isset($properties_skip[$property_name])) {
        continue;
      }
      // Always map the timezone.
      $default_value = 0;
      if ($property_name == 'field_family_name') {
        $default_value = 'family_name';
      }
      if ($property_name == 'field_given_name') {
        $default_value = 'given_name';
      }
      if ($property_name == 'field_nickname') {
        $default_value = 'nickname';
      }
      if ($property_name == 'field_preferred_username') {
        $default_value = 'preferred_username';
      }

      $form['userinfo_mappings'][$property_name] = array(
        '#type' => 'select',
        '#title' => $property->getLabel(),
        '#description' => $property->getDescription(),
        '#options' => (array) $claims,
        '#empty_value' => 0,
        '#empty_option' => t('- No mapping -'),
        '#default_value' => isset($mappings[$property_name]) ? $mappings[$property_name] : $default_value,
      );
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $ppssoCustomiseEnabled = $form_state->getValue('ppsso_customise')['enable'];
    $ppssoCustomiseSize = $form_state->getValue('ppsso_customise')['size'];
    $ppssoCustomiseColour = $form_state->getValue('ppsso_customise')['colour'];
    $ppssoCustomiseShowText = $form_state->getValue('ppsso_customise')['show_text'];
    $ppssoCustomiseLoginText = $form_state->getValue('ppsso_customise')['login_text'];
    $ppssoCustomiseRegisterText = $form_state->getValue('ppsso_customise')['register_text'];

    if($ppssoCustomiseEnabled === '1'){
      if($ppssoCustomiseShowText === '0'){
        switch ($ppssoCustomiseSize){
          case 0:
            $customSsoButtonSize = '';
            break;
          case 1:
            $customSsoButtonSize = 'ppsso-md';
            break;
          case 2:
            $customSsoButtonSize = 'ppsso-sm';
            break;
        }
      } else {
        switch ($ppssoCustomiseSize){
          case 0:
            $customSsoButtonSize = 'ppsso-logo-lg';
            break;
          case 1:
            $customSsoButtonSize = 'ppsso-md ppsso-logo-md';
            break;
          case 2:
            $customSsoButtonSize = 'ppsso-sm ppsso-logo-sm';
            break;
        }
      }

      switch ($ppssoCustomiseColour){
        case 0:
          $customSsoColour = '';
          break;
        case 1:
          $customSsoColour = 'ppsso-cyan';
          break;
        case 2:
          $customSsoColour = 'ppsso-pink';
          break;
        case 3:
          $customSsoColour = 'ppsso-white';
          break;
      }


      $connectSsoButton = '<button name="pixelpin" id="previewSSObutton" class="ppsso-btn ' . $customSsoButtonSize . ' ' . $customSsoColour .'">Connect with <span class="ppsso-logotype">PixelPin</span></button>';
      $disconnectSsoButton = '<button name="pixelpin" id="previewSSObutton" class="ppsso-btn ' . $customSsoButtonSize . ' ' . $customSsoColour .'">Disconnect from <span class="ppsso-logotype">PixelPin</span></button>';

      if($ppssoCustomiseShowText === '0'){
        $loginSsoButton = '<button name="pixelpin" id="edit-openid-connect-client-pixelpin-login" type="submit" class="ppsso-btn ' . $customSsoButtonSize . ' ' . $customSsoColour .'">' . $ppssoCustomiseLoginText .' <span class="ppsso-logotype">PixelPin</span></button>';
        $registerSsoButton = '<button name="pixelpin" id="edit-openid-connect-client-pixelpin-register" type="submit" class="ppsso-btn ' . $customSsoButtonSize . ' ' . $customSsoColour .'">' . $ppssoCustomiseRegisterText .' <span class="ppsso-logotype">PixelPin</span></button>';
        $sampleSsoButton = '<button name="pixelpin" id="previewSSObutton" class="ppsso-btn ' . $customSsoButtonSize . ' ' . $customSsoColour .'">' . $ppssoCustomiseLoginText .' <span class="ppsso-logotype">PixelPin</span></button>';
      } else {
        $loginSsoButton = '<button name="pixelpin" id="edit-openid-connect-client-pixelpin-login" type="submit" class="ppsso-btn ' . $customSsoButtonSize . ' ' . $customSsoColour .'"></button>';
        $registerSsoButton = '<button name="pixelpin" id="edit-openid-connect-client-pixelpin-login-front-page" type="submit" class="ppsso-btn ' . $customSsoButtonSize . ' ' . $customSsoColour .'"></button>';
        $sampleSsoButton = '<button name="pixelpin" id="previewSSObutton" class="ppsso-btn ' . $customSsoButtonSize . ' ' . $customSsoColour .'"></button>';
      }
    } else {
      $loginSsoButton = NULL;
      $registerSsoButton = NULL;
      $sampleSsoButton = NULL;
      $connectSsoButton = NULL;
      $disconnectSsoButton = NULL;
    }

    $ppsso = array(
      'loginButton' => $loginSsoButton,
      'registerButton' => $registerSsoButton,
      'sampleButton' => $sampleSsoButton,
      'connectButton' => $connectSsoButton,
      'disconnectButton' => $disconnectSsoButton
    );

    $this->config('pixelpin_openid_connect.settings')
      ->set('always_save_userinfo', $form_state->getValue('always_save_userinfo'))
      ->set('userinfo_mappings', $form_state->getValue('userinfo_mappings'))
      ->set('ppsso_customise', $form_state->getValue('ppsso_customise'))
      ->set('ppsso', $ppsso)
      ->save();
    $clients_enabled = $form_state->getValue('clients_enabled');
    foreach ($clients_enabled as $client_name => $status) {
      $this->configFactory()
        ->getEditable('pixelpin_openid_connect.settings.' . $client_name)
        ->set('enabled', $status)
        ->save();
      if ((bool) $status) {
        $this->configFactory()
          ->getEditable('pixelpin_openid_connect.settings.' . $client_name)
          ->set('settings', $form_state->getValue(array(
            'clients',
            $client_name,
            'settings',
          )))
          ->save();
      }
    }
  }
}
