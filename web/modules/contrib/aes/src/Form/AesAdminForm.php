<?php

namespace Drupal\aes\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\aes\AES;
use Drupal\Core\Config\FileStorageFactory;

/**
 * Provides a fields form controller.
 */
class AesAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'aes_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // $config = $this->config('aes.settings')->getRawData();
    $fileStorage = FileStorageFactory::getActive();
    $config = $fileStorage->read('aes.settings');

    $phpsec_loaded = AES::load_phpsec(FALSE);

    $form = array();

    $form['aes'] = array(
      '#type' => 'fieldset',
      '#title' => t('AES settings'),
      '#collapsible' => FALSE,
    );

    $encryption_implementations = array();
    if ($phpsec_loaded) {
      $encryption_implementations['phpseclib'] = t('PHP Secure Communications Library (phpseclib)');
    }
    if (extension_loaded('mcrypt')) {
      $encryption_implementations['mcrypt'] = t('Mcrypt extension');
    }

    if (!empty($encryption_implementations['mcrypt']) && !empty($encryption_implementations['phpseclib'])) {
      $implementations_description = t('The Mcrypt implementation is faster than phpseclib and also lets you define the cipher to be used, other than that, the two core implementations are equivalent. Additional implementations might be added via plugin system.');
    }
    elseif (!empty($encryption_implementations['mcrypt']) && empty($encryption_implementations['phpseclib'])) {
      $implementations_description = t('The Mcrypt extension is the only installed implementation.') . $phpseclib_error_msg;
    }
    elseif (empty($encryption_implementations['mcrypt']) && !empty($encryption_implementations['phpseclib'])) {
      $implementations_description = t('PHP Secure Communications Library is the only installed implementation.');
    }

    /* @var \Drupal\aes\Plugin\AESPluginManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.aes');
    $plugin_definitions = $plugin_manager->getDefinitions();
    foreach ($plugin_definitions as $plugin_definition) {
      $encryption_implementations[$plugin_definition['id']] = 'Plugin: ' . $plugin_definition['label'];
    }

    if (empty($encryption_implementations)) {
      drupal_set_message(t('You do not have an AES implementation installed! For correct AES work you need an encryption library (PhpSecLib, MCrypt) or own plugin implementation. Consult READMDE.txt for more details.'), 'error');
      return array();
    }

    $form['aes']['implementation'] = array(
      '#type' => 'select',
      '#title' => t('AES implementation'),
      '#options' => $encryption_implementations,
      '#default_value' => $config['implementation'],
      '#description' => $implementations_description,
    );

    $form['aes']['cipher'] = array(
      '#type' => 'select',
      '#title' => t('Cipher'),
      '#options' => array(
        'rijndael-128' => 'Rijndael 128',
        'rijndael-192' => 'Rijndael 192',
        'rijndael-256' => 'Rijndael 256',
      ),
      '#default_value' => $config['cipher'],
      '#states' => array(
        'disabled' => array(
          ':input[name="implementation"]' => array('value' => 'phpseclib'),
        ),
      ),
    );
    $form['aes']['cipher_comment'] = array(
      '#type' => 'item',
      '#description' => t('Cipher can be chosen for MCrypt implementation only. The phpseclib implementation is locked to Rijndael 128.'),
      '#states' => array(
        'visible' => array(
          ':input[name="implementation"]' => array('value' => 'phpseclib'),
        ),
      ),
    );

    $form['aes']['key'] = array(
      '#type' => 'textfield',
      '#title' => t('Key'),
      '#description' => t("The key for your encryption system. You normally don't need to worry about this since this module will generate a key for you if none is specified. However you have the option of using your own custom key here."),
      '#required' => TRUE,
      '#default_value' => $config['key'],
    );

    $form['aes']['key_confirm'] = array(
      '#type' => 'textfield',
      '#title' => t('Confirm key'),
      '#required' => TRUE,
      '#default_value' => $config['key'],
    );

    $form['aes']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('key') != $form_state->getValue('key_confirm')) {
      $form_state->setErrorByName('key', t("The encryption keys didn't match."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::logger('aes')->notice('Saving config...');
    // $config = $this->config('aes.settings')->getRawData();
    $fileStorage = FileStorageFactory::getActive();
    $config = $fileStorage->read('aes.settings');

    // If the cipher has changed...
    $old_cipher = $config['cipher'];
    $new_cipher = $form_state->getValue('cipher');
    if ($form_state->getValue('cipher') != $config['cipher']) {
      $config['cipher'] = $form_state->getValue('cipher');
      FileStorageFactory::getActive()->write('aes.settings', $config);

      // Get the old iv.
      $old_iv = $config['mcrypt_iv'];
      // create a new iv to match the new cipher
      AES::make_iv();
      // get the new iv
      $config = $fileStorage->read('aes.settings');
      $new_iv = $config['mcrypt_iv'];
    }

    // If the key has changed...
    if ($form_state->getValue('key') != $config['key']) {
      $config['key'] = $form_state->getValue('key');
      FileStorageFactory::getActive()->write('aes.settings', $config);

      drupal_set_message(t('Key changed.'));
      // @todo: invoke hook?
    }

    // If the implementation has changed...
    if ($form_state->getValue('implementation') != $config['implementation']) {

      $config['implementation'] = $form_state->getValue('implementation');
      FileStorageFactory::getActive()->write('aes.settings', $config);

      if ($form_state->getValue('implementation') == 'phpseclib') {
        // If we have switched to phpseclib implementation, set the cipher to 128, since it's the only one phpseclib supports.
        $config['cipher'] = 'rijndael-128';
        FileStorageFactory::getActive()->write('aes.settings', $config);

        // Create a new IV, this IV won't actually be used by phpseclib, but it's needed if the implementation is switched back to mcrypt.
        AES::make_iv(TRUE);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'aes.settings',
    ];
  }
}
