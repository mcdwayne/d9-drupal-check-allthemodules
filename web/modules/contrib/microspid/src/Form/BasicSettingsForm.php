<?php

namespace Drupal\microspid\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Link;
use Drupal\Core\Url;
use \Drupal\block\entity\Block;

/**
 * Form builder for the microspid basic settings form.
 */
class BasicSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'microspid_basic_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['microspid.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    $config = $this->config('microspid.settings');
    $certman = \Drupal::service('microspid.certs.manager');
    
    $form['basic'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Basic settings'),
      '#collapsible' => FALSE,
    ];
    $desc = $this->t('Click to activate SPID. ');
    $exists = $certman->certExists();
    if (!$exists) {
      $desc .= Link::fromTextAndUrl($this->t('Create certificate'), Url::fromUri('internal:/admin/config/people/microspid/create_cert'))->toString();
    }
    $form['basic']['activate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Activate authentication via MicroSPID'),
      '#default_value' => $config->get('activate'),
      '#description' => $desc,
    ];
    $form['basic']['spid-button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Attiva bottone SPID'),
      '#default_value' => FALSE,
      '#description' => $this->t('Selezionando questa casella verrà attivato il bottone SPID se non già presente.'),
    ];
    $form['basic']['authlevel'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Livello di autenticazione SPID'),
      '#default_value' => $config->get('authlevel'),
      '#description' => $this->t('Autenticazione livello 1 o 2'),
      '#options' => array(
        'SpidL1' => 'Livello 1',
        'SpidL2' => 'Livello 2',
      ),
    );
    $form['basic']['index'] = array(
      '#type' => 'textfield',
      '#attributes' => array(
    // Insert space before attribute name :)
        ' type' => 'number',
      ),
      '#title' => $this->t('Service index'),
      '#default_value' => $config->get('index'),
      '#maxlength' => 2,
      '#size' => 2,
      '#description' => $this->t('Specify service index'),
    );
    $defval = @$config->get('entityid');
    if (empty($defval)) $defval = $base_url . '/microspid_metadata';
    $form['basic']['entityid'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('SP Entity ID'),
      '#default_value' => $defval,
      '#description' => $this->t('If service is 0 leave unchanged else use service 0 value'),
    );
    $form['basic']['servicedir'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Service path'),
      '#default_value' => $config->get('servicedir'),
      '#description' => $this->t('Service path/alias'),
    );
    $form['basic']['privatepath'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('private key path'),
      '#default_value' => $config->get('privatepath'),
      '#description' => $this->t('private key path, only if you change it'),
      '#element_validate' => array(array($this, 'validatePrivatepath')),
    );
    $form['basic']['header_no_cache'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use Header with: Cache-Control: no-cache'),
      '#default_value' => $config->get('header_no_cache'),
      '#description' => $this->t('Use a "Cache-Control: no-cache" header in the HTTP response to avoid the redirection be cached (e.g. when using a reverse-proxy layer).'),
    ];
    $form['basic']['single_logout'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Do Single Logout'),
      '#default_value' => $config->get('single_logout'),
      '#description' => $this->t('Enable Single Logout (only for level 1).'),
    ];
    $form['basic']['show_agid_link'] = [
      '#type' => 'checkbox',
      '#title' => t('mostrare idp Agid di Test'),
      '#default_value' => $config->get('show_agid_link'),
      '#description' => t('Va attivato per i controlli da parte di Agid.'),
    ];
    $form['basic']['test_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Test mode'),
      '#default_value' => $config->get('test_mode'),
      '#description' => $this->t('Use testing idp.'),
    ];

    $form['debugging'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Debugging'),
      '#collapsible' => FALSE,
    ];
    $form['debugging']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Turn on debugging messages'),
      '#default_value' => $config->get('debug'),
      '#description' => $this->t('Expand the level of Drupal logging to include debugging information.'),
    ];

    $form['user_provisioning'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('User Provisioning'),
      '#collapsible' => FALSE,
    ];
    $form['user_provisioning']['register_users'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Register users (i.e., auto-provisioning)'),
      '#default_value' => $config->get('register_users'),
      '#description' => $this->t('Determines whether or not the module should automatically create/register new Drupal accounts for users that authenticate using MicroSPiD. Unless you\'ve done some custom work to provision Drupal accounts with the necessary authmap entries you will want this checked.<br /><br />NOTE: If unchecked each user must already have been provisioned a Drupal account correctly linked to the SAML authname attribute (e.g. by creating Drupal users with "Enable this user to leverage SAML authentication" checked). Otherwise they will receive a notice and be denied access.'),
    ];

    $form['metadata'] = array(
    // Fieldset.
      '#type' => 'fieldset',
      '#title' => $this->t('Spid IdP metadata'),
      '#collapsible' => FALSE,
    );
    $form['metadata']['microspid_update'] = array(
      '#type' => 'button',
      '#default_value' => $this->t('Update IDPs metadata'),
      '#ajax' => array(
        'callback' => array($this, 'updateIDPs'),
        'progress' => array('type' => 'throbber'),
      ),
    );

    $form['multi'] = array(
    // Fieldset.
      '#type' => 'details',
      '#title' => $this->t('Multi portal setup'),
    );
    if ($config->get('index') > 0) {
      $form['multi']['#attributes'] = array('style' => 'display:none');
    }
 
    $form['multi']['metadata_population'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Metadata generation for multi-portal'),
      '#default_value' => $this->getMulti(), 
      '#description' => $this->t("A list of <strong>other</strong> services, one per line, in the form index|acs-location|service-name|service-description|extra-attributes. Default attributes are spidCode,name,familyName,fiscalNumber,email. Service 0 is not listed, so index starts from 1. Extra attributes are separated by colon. Example:<br /><em>1|https://miosito.it/cartella/microspid_acs|service-2|Service number 2|companyName:ivaCode</em>"),
    );
    $form['multi']['update_metadata'] = array(
      '#type' => 'button',
      '#default_value' => $this->t('Save infos as metadata'),
      '#ajax' => array(
        'callback' => array($this, 'updateMyMetadata'),
        'progress' => array('type' => 'none'),
      ),
    );
    $class = 'confirm-regenerate';
    $form['multi']['update_metadata']['#attributes']['class'][] = $class;
    $form['multi']['#attached']['library'][] = 'microspid/ajax-confirm';
    $form['multi']['#attached']['drupalSettings']['ajaxConfirm'][$class] = [
      'text' => $this->t('WARNING: your metadata will be regenerated! Are you sure?'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('microspid.settings');

    $config->set('activate', $form_state->getValue('activate'));
    $config->set('index', $form_state->getValue('index'));
    $config->set('entityid', $form_state->getValue('entityid'));
    $config->set('servicedir', $form_state->getValue('servicedir'));
    $config->set('privatepath', $form_state->getValue('privatepath'));
    $config->set('authlevel', $form_state->getValue('authlevel'));
    $config->set('show_agid_link', $form_state->getValue('show_agid_link'));
    $config->set('single_logout', $form_state->getValue('single_logout'));
    $config->set('test_mode', $form_state->getValue('test_mode'));
    $config->set('debug', $form_state->getValue('debug'));
    $config->set('register_users', $form_state->getValue('register_users'));
    $config->set('header_no_cache', $form_state->getValue('header_no_cache'));
    $config->save();
    if ($form_state->getValue('spid-button')) {
      $this->saveBlock();
    }
    Cache::invalidateTags(['rendered']);
  }

  /**
   * Download IDP metadata from Agid server
   */
  public function download() {
    $file = drupal_get_path('module', 'microspid') . '/metadata/spid-entities-idps.xml';
    $bak = $file . '.bak';
    $url = 'https://registry.spid.gov.it/metadata/idp/spid-entities-idps.xml';
    if (!@copy($file, $bak)) {
      return FALSE;
    }
    $success = @copy($url, $file);
    if (!$success && !file_exists($file)) {
      rename($bak, $file);
    }
    @unlink($bak);
    return $success;
  }

  /**
  Detects the end-of-line character of a string.
  @param string $str      The string to check.
  @return string The detected EOL, or default one.
  */
  public function detectEOL($str) {
    static $eols = array(
       0 => "\n\r",  // 0x0A - 0x0D - acorn BBC
       1 => "\r\n",  // 0x0D - 0x0A - Windows, DOS OS/2
       2 => "\n",    // 0x0A -      - Unix, OSX
       3 => "\r",    // 0x0D -      - Apple ][, TRS80
    );

    $curCount = 0;
    $curEol = "\n";
    foreach($eols as $k => $eol) {
       if( ($count = substr_count($str, $eol)) > $curCount) {
          $curCount = $count;
          $curEol = $eol;
      }
    }
    return $curEol;
  }  // detectEOL

  /**
   * converts from config textarea string to metadata template (saving it)
   * @method setMulti
   * @param $input string from configuration textarea 'microspid_metadata_population'
   * 
   */
  public function setMulti($input) {
    $spid = \Drupal::service('microspid.manager');
    $sep = $this->detectEOL($input);
    $lines = explode($sep, $input);
    $data = array();
    foreach($lines as $line) {
      if (empty($line)) {
        continue;
      }
      $data[] = explode('|', $line);
    }
    
    $md = $spid->loadMetadata('/templates/metadata.tpl.xml');
    $metadata = $md->children("urn:oasis:names:tc:SAML:2.0:metadata");
    $move_me = dom_import_simplexml($metadata->SPSSODescriptor->AttributeConsumingService);
    $parent = $move_me->parentNode;
    $move_me = $parent->removeChild($move_me);
    foreach ($data as $datum) {
      if (empty($datum)) {
        continue;
      }
      $tmp = $metadata->SPSSODescriptor->addChild('AssertionConsumerService');
      $tmp->addAttribute('Binding', "urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST");
      $tmp->addAttribute('Location', $datum[1]);
      $tmp->addAttribute('index', $datum[0]);
    }
    $parent->appendChild($move_me);
    foreach ($data as $datum) {
      if (empty($datum)) {
        continue;
      }
      $tmp = $metadata->SPSSODescriptor->addChild('AttributeConsumingService');
      $tmp->addAttribute('index', $datum[0]);
      $name = $tmp->addChild('ServiceName', $datum[2]);
      $name->addAttribute('xml:lang','it','xml');
      $desc = $tmp->addChild('ServiceDescription', $datum[3]);
      $desc->addAttribute('xml:lang','it','xml');
      $extras = explode(':', $datum[4]);
      $defs = ['spidCode','fiscalNumber','name','familyName','email'];
      foreach($defs as $def) {
        $attr = $tmp->addChild('RequestedAttribute');
        $attr->addAttribute('Name', $def);
        $attr->addAttribute('NameFormat', 'urn:oasis:names:tc:SAML:2.0:attrname-format:basic');
      }
      foreach($extras as $extra) {
        if (empty($extra)) {
          continue;
        }
        $attr = $tmp->addChild('RequestedAttribute');
        $attr->addAttribute('Name', $extra);
        $attr->addAttribute('NameFormat', 'urn:oasis:names:tc:SAML:2.0:attrname-format:basic');
      }
    }
    $destname = \Drupal::service('file_system')->realpath('public://microspid/metadata.xml');
    copy($destname, $destname . '.bak'); // make a backup
    $string = $md->asXML();
    $doc = new \DOMDocument();
    $doc->preserveWhiteSpace = FALSE;
    $doc->formatOutput = TRUE;
    $doc->loadXML($string);
    $doc->save($destname);
  }

  /**
   * converts from from metadata to config textarea string
   * @method getMulti
   * @return string to be used in configuration textarea 'microspid_metadata_population'
   * 
   */
  public function getMulti() {
    $spid = \Drupal::service('microspid.manager');
    $array1 = $array2 = array();
    $md = $spid->loadMetadata('/metadata.xml', TRUE);
    $metadata = $md->children("urn:oasis:names:tc:SAML:2.0:metadata");
    foreach($metadata->SPSSODescriptor->AssertionConsumerService as $acs) {
      $array1[] = $acs->attributes()['index'] . '|' . $acs->attributes()['Location'];
    }
    foreach($metadata->SPSSODescriptor->AttributeConsumingService as $service) {
      $extra = array();
      $def = ['spidCode','fiscalNumber','name','familyName','email'];
      foreach($service->RequestedAttribute as $attr) {
        $name = $attr->attributes()['Name'];
        if (in_array($name, $def)) {
          continue;
        }
        $extra[] = $name;
      }
      $extra_attr = implode (':', $extra);
      $array2[] = $service->ServiceName . '|' . $service->ServiceDescription . '|' . $extra_attr;
    }
    for ($i = 1; $i < count($array1); $i++) {
      $array1[$i] .= '|' . $array2[$i];
    }
    array_shift($array1);
    return implode("\n", $array1);
  }

  /**
   * Ajax callback function.
   */
  public function updateIDPs($form, &$form_state) : AjaxResponse {
    $success = $this->download();
    $result = $success ? $this->t('IDP metadata have been updated') : $this->t('Error: IDP metadata NOT updated!');
    
    $response = new AjaxResponse();
    $response->addCommand(new AppendCommand('body', '<script>alert("'.$result.'");</script>'));
    return $response;
  }

  /**
   * Ajax callback function.
   */
  public function updateMyMetadata($form, &$form_state) : AjaxResponse {
    $input = $form_state->getValue('metadata_population');
    $this->setMulti($input);
    $result = $this->t('Your metadata have been generated.');
    $response = new AjaxResponse();
    $response->addCommand(new AppendCommand('body', '<script>alert("'.$result.'");</script>'));
    return $response;
  }

  public function validatePrivatepath($element, &$form_state, $form) {
    $filepath = $element['#value'];
    if (!empty($filepath) && !file_exists($filepath)) {
      $form_state->setError($element, $this->t("Folder %filepath doesn't exist", array('%filepath' => $filepath)));
    }
  }

  public function saveBlock() {
    $blockEntityManager = \Drupal::service('entity.manager')->getStorage('block');
    $theme = \Drupal::config('system.theme')->get('default');
    $plugin_id = 'microspid_block';
    $vis_config = 
      [
        'request_path' => 
        [
           'id' => 'request_path',
           'pages' =>  '/user/login',
           'negate' => 0,
           'context_mapping' => [],
        ]
      ];

    $my_block = Block::load('spidauthstatus');
    if ($my_block && $my_block->getTheme() == $theme) {
        return;
    }
    if ($my_block) {
        $my_block->delete();
    }
	
    $my_block = $blockEntityManager->create(
      array(
        'id'=> 'spidauthstatus',
        'plugin' => $plugin_id,
	    'settings' => [
	      'id' => 'microspid_block',
		  'label' => $this->t('Otherwise'),
		  'provider' => 'microspid',
		  'label_display' => 'visible',
	    ],
	    'region' => 'content',
        'theme' => $theme,
	    'visibility' => $vis_config,
	    'weight' => 100,
      )
    );
    $my_block->save();
  }

}
