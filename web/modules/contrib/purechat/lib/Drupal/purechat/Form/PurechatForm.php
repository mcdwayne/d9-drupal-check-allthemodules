<?php

/**
 * @file
 * Contains \Drupal\purechat\Form\PurechatForm.
 */

namespace Drupal\purechat\Form;

use Drupal\Core\Form\FormInterface;

/**
 * Implementation of Form Interface for configuring the module
 */
class PurechatForm implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'purechat_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $form['purechat'] = array(
      '#type' => 'vertical_tabs',
			'#title' => t('Purechat settings'),
      //'#parents' => array('visibility'),
    );
		
		$config = \Drupal::config('purechat.settings');
		// Get the purechat visibility.
		$config->get('purechat_visibility');
		// Get the pages to remove tracking
		$config->get('purechat_pages');
		
		// General Settings
    $form['purechat']['account'] = array(
      '#type' => 'details',
      '#title' => t('General settings'),
      '#collapsed' => TRUE,
      '#group' => 'purechat',
      '#weight' => 0,
    );
    
    $form['purechat']['account']['purechat_account'] = array(
      '#type' => 'textfield',
      '#title' => t('Purechat account number'),
      '#default_value' => $config->get('purechat_account'),
      '#size' => 40,
      '#maxlength' => 40,
      '#required' => TRUE,
      '#description' => '<p>' . t('The account number is unique to the websites domain and can be found in the script given to you by the Purechat account settings.') . '</p>' .
                        '<p>' . t('Go to !url, login, click the settings tab and look at the code you are asked to paste into your site.', array('!url' => l('www.purechat.com/settings', 'https://www.purechat.com/Settings', array('attibutes' => array('target' => '_blank'))))) . '</p>' .
                        '<p>' . t("The part of the code you need is !code, where the x's represent your account number.", array('!code' => '<code>var w = new PCWidget({ c: \'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx\', f: true });</code>')),
    );
    // END General Settings

		// Role specific visibility configurations.
		$form['purechat']['role_vis_settings'] = array(
      '#type' => 'details',
      '#title' => t('Role specific script settings'),
      '#collapsed' => TRUE,
      '#group' => 'purechat',
      '#weight' => 1,
    );

    $role_options = array_map('check_plain', user_role_names());
		$form['purechat']['role_vis_settings']['purechat_roles'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Remove script for specific roles'),
      '#default_value' => $config->get('purechat_roles'),
      '#options' => $role_options,
      '#description' => t('Remove script only for the selected role(s). If none of the roles are selected, all roles will have the script. Otherwise, any roles selected here will NOT have the script.'),
    );
    // END Role specific visibility configurations.

    // Page specific visibility configurations.
		$form['purechat']['page_vis_settings'] = array(
      '#type' => 'details',
      '#title' => t('Page specific script settings'),
      '#collapsed' => TRUE,
      '#group' => 'purechat',
      '#weight' => 2,
    );
		
    $access = user_access('use PHP for Purechat visibility');
    $visibility = $config->get('purechat_visibility');
    
    if ($visibility == 2 && !$access) {
      $form['purechat']['page_vis_settings'] = array();
      $form['purechat']['page_vis_settings']['visibility'] = array('#type' => 'value', '#value' => 2);
      $form['purechat']['page_vis_settings']['pages'] = array('#type' => 'value', '#value' => $pages);
    }
    else {
      $options = array(t('Add to every page except the listed pages.'), t('Add to the listed pages only.'));
      $description = t("Enter one page per line as Drupal paths. The '*' character is a wildcard. Example paths are %blog for the blog page and %blog-wildcard for every personal blog. %front is the front page.", array('%blog' => 'blog', '%blog-wildcard' => 'blog/*', '%front' => '<front>'));
      if ($access AND module_exists('php')) {
        $options[] = t('Add if the following PHP code returns <code>TRUE</code> (PHP-mode, experts only).');
        $description .= ' '. t('If the PHP-mode is chosen, enter PHP code between %php tags. Note that executing incorrect PHP-code can break your Drupal site.', array('%php' => '<?php ?>'));
      }
      $form['purechat']['page_vis_settings']['purechat_visibility'] = array(
        '#type' => 'radios',
        '#title' => t('Add script to specific pages'),
        '#options' => $options,
        '#default_value' => $visibility,
      );
      $form['purechat']['page_vis_settings']['purechat_pages'] = array(
        '#type' => 'textarea',
        '#title' => t('Pages'),
        '#default_value' => $config->get('purechat_pages'),
        '#description' => $description,
        '#wysiwyg' => FALSE,
      );
    }
		$form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#name' => 'op',
      '#type' => 'submit',
      '#value' => t('Save'),
      '#button_type' => 'primary',
    );
    // END Page specific visibility configurations.
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
		if (empty($form_state['values']['purechat_account'])) {
      form_set_error('purechat_account', t('A valid Purechat account number is needed.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    if ($form_state['values']['op'] == t('Save')) {
			$config = \Drupal::config('purechat.settings');
			// Page specific script settings
			//$pages = @explode("\n" , $form_state['values']['purechat_pages']);
			// Set the purechat account.
	    $config->set('purechat_account', $form_state['values']['purechat_account']);
	    // Set the purechat visibility.
	    $config->set('purechat_visibility', $form_state['values']['purechat_visibility']);
      // Set the pages to remove tracking
	    $config->set('purechat_pages', $form_state['values']['purechat_pages']);
			// Set the role specific visibility configurations
	    $config->set('purechat_roles', array_filter($form_state['values']['purechat_roles']));
	    // Save your data to the file system.
	    $config->save();
			
      drupal_set_message(t('The configuration options have been saved.'));
    }
  }
}
