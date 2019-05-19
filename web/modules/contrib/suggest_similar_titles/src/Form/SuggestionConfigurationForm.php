<?php

namespace Drupal\suggest_similar_titles\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class SuggestionConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'suggest_similar_titles_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'suggest_similar_titles.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $form = array();
		$form['fieldset_suggest_title'] = array(
			'#type' => 'fieldset',
	  	'#title' => t('Content types'),
	  	'#description' => t('Select content type to enable similar titles suggestion for.'),
		);
		$types = \Drupal\node\Entity\NodeType::loadMultiple();
		foreach ($types as $index => $value) {
			$form['fieldset_suggest_title'][$index . '_suggest_similar_titles'] = array(
				'#type' => 'checkbox',
				'#title' => $value->label(),
				'#default_value' => \Drupal::state()->get($index . "_suggest_similar_titles") ?: 0,
			);
		}
		$form['fieldset_suggest_title_d_settings'] = array(
			'#type' => 'fieldset',
			'#title' => t('Display settings'),
			'#description' => t('Select position where you want to show similar titles suggestion list.'),
		);
		$form['fieldset_suggest_title_d_settings']['suggest_similar_titles_settings'] = array(
			'#type' => 'select',
			'#title' => t('Position'),
			'#options' => array('top' => 'Top', 'bottom' => 'Bottom'),
			'#default_value' => \Drupal::state()->get("suggest_similar_titles_settings") ?: "top",
		);
		$form['fieldset_suggest_title_d_settings']['suggest_similar_titles_noof_nodes'] = array(
			'#type' => 'select',
			'#title' => t('No of nodes'),
			'#options' => array('1' => '1', '5' => '5', '10' => '10', '20' => '20'),
			'#default_value' => \Drupal::state()->get("suggest_similar_titles_noof_nodes") ?: "5",
			'#description' => t('Select maximum number of nodes to display as similar titles.'),
		);
		$form['fieldset_suggest_title_d_settings']['suggest_similar_titles_node_access'] = array(
			'#type' => 'select',
			'#title' => t('Consider node permissions'),
			'#options' => array('no' => 'No', 'yes' => 'Yes'),
			'#default_value' => \Drupal::state()->get("suggest_similar_titles_node_access") ?: "no",
			'#description' => t('Select whether system should check node view permission
				before display node in similar titles suggestion list.<br />
			Yes: System will not display restricted nodes to the user.<br />
			No: System will display all matching node titles regardless of permission settings.'),
		);
		$form['fieldset_suggest_title_settings'] = array(
			'#type' => 'fieldset',
			'#title' => t('Title compare patterns'),
		);
		$form['fieldset_suggest_title_settings']['suggest_similar_titles_ignored'] = array(
			'#type' => 'textfield',
			'#title' => t('Ignore keywords'),
			'#description' => t('Enter comma separated keywords to ignore in title comparison.'),
			'#default_value' => \Drupal::state()->get('suggest_similar_titles_ignored') ?: "the,is,a",
		);
		$form['fieldset_suggest_title_settings']['suggest_similar_titles_percentage'] = array(
			'#type' => 'textfield',
			'#title' => t('Percentage'),
			'#description' => t('Enter percentage how exact system should compare the title.
				For example, if you enter 75, then atleast 75% matching title will be considered similar.'),
			'#default_value' => \Drupal::state()->get("suggest_similar_titles_percentage") ?: 75,
			'#size' => 4,
			'#maxlength' => 2,
			'#field_suffix' => '%',
		);
	
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    \Drupal::state()->set('suggest_similar_titles_ignored', $values['suggest_similar_titles_ignored']);
    \Drupal::state()->set('suggest_similar_titles_settings', $values['suggest_similar_titles_settings']);
    \Drupal::state()->set('suggest_similar_titles_noof_nodes', $values['suggest_similar_titles_noof_nodes']);
    \Drupal::state()->set('suggest_similar_titles_node_access', $values['suggest_similar_titles_node_access']);
		\Drupal::state()->set('suggest_similar_titles_percentage', $values['suggest_similar_titles_percentage']);
	
		$types = \Drupal\node\Entity\NodeType::loadMultiple();
		foreach ($types as $index => $type) {
			\Drupal::state()->set($index . '_suggest_similar_titles', $values[$index . '_suggest_similar_titles']);
		}
		parent::submitForm($form, $form_state);
  }

}
