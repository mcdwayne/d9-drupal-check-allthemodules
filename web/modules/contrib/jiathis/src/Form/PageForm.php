<?php

/**
 * @file
 * Contains \Drupal\jiathis\Form\PageForm.
 */

namespace Drupal\jiathis\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Configure page settings for jiathis.
 */
class PageForm extends ConfigFormBase {

  /**
   * Constructs a PageForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jiathis_page_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['jiathis.page'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $site_config = $this->config('jiathis.page');
	  $locations = array();
	  $view_modes = \Drupal::entityManager()->getViewModes('node');
	  foreach ($view_modes as $name => $info) {
	    $locations[$name] = $info['label'];
	  }

		$form['display'] = array(
		    '#type' => 'fieldset',
		    '#title' => t('Display Settings'),
		    '#description' => t('These are settings for nodes: the button will be created dynamically for each node using its URL. <br />On the othesr hand, if you only need a block with a fixed URL like your homepage, go to the <a href="@blocks">blocks</a> page to enable and configure the <em>JiaThis Share Button</em> block.', array('@blocks' => \Drupal::url('block.admin_display'))),
		  );

		$form['display']['jiathis_node_types'] = array(
		    '#type' => 'checkboxes',
		    '#title' => t('Display the button on these content types:'),
		    '#options' => node_type_get_names(),
		    '#default_value' => $site_config->get('node_types'),
		  );

		  $form['display']['jiathis_node_location'] = array(
		    '#type' => 'checkboxes',
		    '#title' => t('Display the button in these view modes:'),
		    '#options' => $locations + array('link' => t('Node links')),
		    '#default_value' => $site_config->get('node_location'),
		  );

		  $form['display']['jiathis_weight'] = array(
		     '#type' => 'weight',
		     '#title' => t('Weight'),
		     '#delta' => 50,
		     '#default_value' => $site_config->get('weight'),
		     '#description' => t('Heavier items will sink. The default weight -50 will show the button at the top of the node content.'),
		  );
		  $available_sizes = array(
		  	'4'	=> t('Tiny'),
		    '3' => t('Small'),
		    '2' => t('Medium'),
		    '1' => t('Large'),
		  );
		  $available_styles = array(
		    '0' => t('Standard'),
		    '1' => t('Mini'),
		  );
		  $default = array(
				'iconstyle' => 0,
		    'count' => 0,
		    'size' => 1,  // standard
		    'css' => 'margin: 0 1em 1em 1em;float:right',
		  );

		  $button_settings = $site_config->get('button_settings');

		  $form['jiathis_button_settings'] = array(
		    '#type' => 'fieldset',
		    '#title' => t('Button Settings'),
		    '#tree' => TRUE, // All the options in this fieldset will be grouped in 1 single variable.
		  );
		  $form['jiathis_button_settings']['iconstyle'] = array(
		    '#type' => 'radios',
		    '#title' => t('Style'),
		    '#options' => $available_styles,
		    '#default_value' => $button_settings['iconstyle'],
			  );
		  $form['jiathis_button_settings']['count'] = array(
		    '#type' => 'radios',
		    '#title' => t('Include count?'),
		    '#options' => array(t('No'), t('Yes')),
		    '#default_value' => $button_settings['count'],
		  );
		  $form['jiathis_button_settings']['size'] = array(
		    '#type' => 'radios',
		    '#title' => t('Size'),
		    '#options' => $available_sizes,
		    '#default_value' => $button_settings['size'],
		  );
		  $form['jiathis_button_settings']['css'] = array(
		    '#type' => 'textfield',
		    '#title' => t('Optional wrapper with CSS'),
		    '#maxlength' => 256,
		    '#default_value' => $button_settings['css'],
		    '#description' => t('To help with the layout and placement of the button, it will be rendered by default inside a wrapper: <br />	&lt;div class="wb_sharebutton-wrapper"&gt;	&lt;/div&gt;<br/>You can enter CSS rules to style this wrapper. By default <em>margin: 0 1em 1em 1em;float:right</em><br />To disable totally the wrapper, input the word <em>nowrapper</em>'),
		  );
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
    $this->config('jiathis.page')
      ->set('node_types', $form_state->getValue('jiathis_node_types'))
      ->set('node_location', $form_state->getValue('jiathis_node_location'))
      ->set('weight', $form_state->getValue('jiathis_weight'))
      ->set('button_settings', $form_state->getValue('jiathis_button_settings'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
