<?php
/**
 * @file
 * Contains \Drupal\tmgmt_wordbee\WordbeeTranslatorUi.
 */

namespace Drupal\tmgmt_wordbee;

use Drupal\tmgmt\TranslatorPluginUiBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tmgmt\Translator\AvailableResult;
use Drupal\tmgmt\JobInterface;

/**
 * Beebox translator UI.
 */
class BeeboxTranslatorUi extends TranslatorPluginUiBase {

    /**
     * {@inheritdoc}
     */
    public function checkoutInfo(JobInterface $job) {
        $form = array();
        if ($job->isActive()) {
            $form['actions']['pull'] = array(
                '#type' => 'submit',
                '#value' => t('Refresh job'),
                '#submit' => array('_tmgmt_wordbee_pull_submit'),
                '#weight' => -10,
            );
        }
        return $form;
    }

	/**
	 * {@inheritdoc}
	 */
	public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
		$form = parent::buildConfigurationForm($form, $form_state);

        $module_infos = system_get_info('module', 'tmgmt_'.$this->getBaseId());

		/** @var \Drupal\tmgmt\TranslatorInterface $translator */
		$translator = $form_state->getFormObject()->getEntity();
		$form['url'] = array(
            '#type' => 'textfield',
            '#title' => 'Beebox URL',
			'#required' => TRUE,
            '#default_value' => $translator->getSetting('url'),
            '#description' => 'Please enter the URL of your Beebox',
        );
        $form['projectKey'] = array(
            '#type' => 'textfield',
            '#title' => 'Project Key',
			'#required' => TRUE,
            '#default_value' => $translator->getSetting('projectKey'),
            '#description' => 'Please enter your Beebox Account Key',
        );
        $form['username'] = array(
            '#type' => 'textfield',
            '#title' => 'Username',
			'#required' => TRUE,
            '#default_value' => $translator->getSetting('username'),
            '#description' => 'Please enter your Beebox Username',
        );
        $form['password'] = array(
            '#type' => 'password',
            '#title' => 'Password',
			'#required' => TRUE,
            '#default_value' => $translator->getSetting('password'),
            '#description' => 'Please enter your Beebox password',
        );
        $form['leave_xliff_target_empty'] = array(
            '#type' => 'checkbox',
            '#title' => 'Leave XLIFF files target element empty',
            '#default_value' => $translator->getSetting('leave_xliff_target_empty'),
            '#description' => 'If you don\'t know what to do with this option, just leave it checked ',
        );
        $form['version'] = array(
            '#type' => 'textfield',
            '#title' => 'Plugin version',
            '#default_value' => $module_infos['version'],
            '#description' => 'You can get the latest version of this connector on <a href="http://www.beeboxlinks.com/download" target="_blank">www.beeboxlinks.com/download</a>',
            '#disabled' => TRUE
        );

		//$form += parent::addConnectButton();

		return $form;
	}

	/**
	 * {@inheritdoc}
	 */
	public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
		parent::validateConfigurationForm($form, $form_state);
		/** @var \Drupal\tmgmt\TranslatorInterface $translator */
		$translator = $form_state->getFormObject()->getEntity();

        // check if we can reach the beebox
		$connected = $translator->getPlugin()->checkAvailable($translator);
        if(!$connected->getSuccess()) {
            $form_state->setErrorByName('settings][url', t('@message', array('@message' => $connected->getReason())));
			$form_state->setErrorByName('settings][projectKey', t('@message', array('@message' => $connected->getReason())));
            $form_state->setErrorByName('settings][username', t('@message', array('@message' => $connected->getReason())));
            $form_state->setErrorByName('settings][password', t('@message', array('@message' => $connected->getReason())));
        }
    }
}
