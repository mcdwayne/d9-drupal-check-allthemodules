<?php

namespace Drupal\live_css\Form;

use Drupal\system\SystemConfigFormBase;
// use Drupal\Core\Form\ConfigFormBase;

class LiveCSSAdmin extends SystemConfigFormBase {

	private $config = array();

	public function getFormID() {
		return 'css_settings_form';
	}

	public function settings(array $form, array &$form_state) {
		$form_state['build_info']['args'] = array();
		$form_state['build_info']['callback'] = array($this, 'buildForm');
		return drupal_build_form('css_settings_form', $form_state);
	}

	public function buildForm(array $form, array &$form_state) {
		$this->config = config('live_css.settings');
		$form['live_css_less'] = array(
			'#type' => 'checkbox',
			'#title' => t('Enable LESS Support'),
			'#default_value' => $this->config->get('live_css_less'),
			'#description' => t('Allows the live editing and display of LESS files on
			 the site, by simply embedding stylesheets with a "less" extension instead
			 of "css". The Less is parsed on each page load, even for anonymous
			 users. In production you may wish to disable this feature and use the
			 LESS module instead.'),
		);
		$form['live_css_flush'] = array(
			'#type' => 'checkbox',
			'#title' => t('CSS and JS cache flush'),
			'#default_value' => $this->config->get('live_css_flush'),
			'#description' => t('Flush CSS and Javascript cache on every save.'),
		);
		$form['live_css_hideadmin'] = array(
			'#type' => 'checkbox',
			'#title' => t('Hide Admin Menu'),
			'#default_value' => $this->config->get('live_css_hideadmin'),
			'#description' => t('Automatically hides the administration menu when
			 editing CSS.'),
		);
		$form['live_css_hidemodules'] = array(
			'#type' => 'checkbox',
			'#title' => t('Only show theme CSS'),
			'#default_value' => $this->config->get('live_css_hidemodules'),
			'#description' => t('Removes module and other styles from the CSS list.'),
		);
		$form['live_css_storage'] = array(
			'#type' => 'checkbox',
			'#title' => t('Consistent Editor State'),
			'#default_value' => $this->config->get('live_css_storage'),
			'#description' => t('Remembers the current file and file position to
			 maintain this between page loads.'),
		);
		$form['live_css_theme'] = array(
			'#type' => 'select',
			'#title' => t('Editor Theme'),
			'#default_value' => $this->config->get('live_css_theme'),
			'#options' => live_css_list_themes(),
		);
		$form['live_css_fontsize'] = array(
			'#type' => 'select',
			'#title' => t('Font Size'),
			'#default_value' => $this->config->get('live_css_fontsize'),
			'#options' => array(
				'8px' => '8px',
				'10px' => '10px',
				'11px' => '11px',
				'12px' => '12px',
				'14px' => '14px',
				'16px' => '16px',
				'18px' => '18px',
		));
		$form['live_css_softtabs'] = array(
			'#type' => 'checkbox',
			'#title' => t('Soft Tabs'),
			'#default_value' => $this->config->get('live_css_softtabs'),
			'#description' => t('Use spaces instead of a tab character.'),
		);
		$form['live_css_tabsize'] = array(
			'#type' => 'select',
			'#title' => t('Tab Size'),
			'#default_value' => $this->config->get('live_css_tabsize'),
			'#description' => t('When using soft tabs, specify how many spaces to
			 insert for the tab character.'),
			'#options' => array(
				1 => '1',
				2 => '2',
				3 => '3',
				4 => '4',
		));
		$form['actions']['#type'] = 'actions';
		$form['actions']['submit'] = array(
			'#type' => 'submit',
			'#value' => $this->t('Save configuration'),
			'#button_type' => 'primary',
		);
		$form['#theme'] = 'system_config_form';
		return $form;
	}

	public function submitForm(array &$form, array &$form_state) {
		$this->config->set('live_css_less', $form_state['values']['live_css_less']);
		$this->config->set('live_css_flush', $form_state['values']['live_css_flush']);
		$this->config->set('live_css_hideadmin', $form_state['values']['live_css_hideadmin']);
		$this->config->set('live_css_hidemodules', $form_state['values']['live_css_hidemodules']);
		$this->config->set('live_css_storage', $form_state['values']['live_css_storage']);
		$this->config->set('live_css_theme', $form_state['values']['live_css_theme']);
		$this->config->set('live_css_fontsize', $form_state['values']['live_css_fontsize']);
		$this->config->set('live_css_softtabs', $form_state['values']['live_css_softtabs']);
		$this->config->set('live_css_tabsize', $form_state['values']['live_css_tabsize']);
		$this->config->save();
		drupal_set_message(t('The Live CSS settings below have been applied.'));
	}
}
