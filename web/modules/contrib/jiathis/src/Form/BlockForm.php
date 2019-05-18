<?php

/**
 * @file
 * Contains \Drupal\jiathis\Form\BlockForm.
 */

namespace Drupal\jiathis\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure block settings for jiathis.
 */
class BlockForm extends ConfigFormBase {


  /**
   * Constructs a BlockForm object.
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
    return 'jiathis_block_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['jiathis.block'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $site_config = $this->config('jiathis.block');
		$form['display'] = array(
		    '#type' => 'fieldset',
		    '#title' => t('Display Settings'),
		    '#description' => t('These are settings for the block. To use the block, go to the <a href="@blocks">blocks</a> page to enable and configure the <em>JiaThis Share Button</em> block.', array('@blocks' => \Drupal::url('block.admin_display'))),
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
				'iconstyle_block' => 0,
		    'count_block' => 0,
		    'size_block' => 1,  // standard
		    'css_block' => 'margin: 0 1em 1em 1em;float:right',
		  );

		  $button_settings = $site_config->get('button_settings');

		  $form['jiathis_block_button_settings'] = array(
		    '#type' => 'fieldset',
		    '#title' => t('Button Settings'),
		    '#tree' => TRUE, // All the options in this fieldset will be grouped in 1 single variable.
		  );
		  $form['jiathis_block_button_settings']['iconstyle_block'] = array(
		    '#type' => 'radios',
		    '#title' => t('Style'),
		    '#options' => $available_styles,
		    '#default_value' => $button_settings['iconstyle_block'],
			  );
		  $form['jiathis_block_button_settings']['count_block'] = array(
		    '#type' => 'radios',
		    '#title' => t('Include count?'),
		    '#options' => array(t('No'), t('Yes')),
		    '#default_value' => $button_settings['count_block'],
		  );
		  $form['jiathis_block_button_settings']['size_block'] = array(
		    '#type' => 'radios',
		    '#title' => t('Size'),
		    '#options' => $available_sizes,
		    '#default_value' => $button_settings['size_block'],
		  );
		  $form['jiathis_block_button_settings']['css_block'] = array(
		    '#type' => 'textfield',
		    '#title' => t('Optional wrapper with CSS'),
		    '#maxlength' => 256,
		    '#default_value' => $button_settings['css_block'],
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
    $this->config('jiathis.block')
      ->set('button_settings', $form_state->getValue('jiathis_block_button_settings'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
