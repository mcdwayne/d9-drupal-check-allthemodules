<?php

namespace Drupal\custom_db_table_views\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the configuration export form.
 */
class DBTableExportForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_customtable_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['custom_db_table_views.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('custom_db_table_views.settings');
    $form['db_table_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Table Name'),
      '#required' => TRUE,
      '#default_value' => $config->get('db_table_name'),
    ];
    $form['db_views_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Views Name'),
      '#required' => TRUE,
      '#default_value' => $config->get('db_views_name'),
    ];
	$form['relation_ship'] = [
       '#type' => 'radios',
       '#title' => $this->t('Relation Type (Optional)'),
       '#default_value' => $config->get('db_reference_type'),
       '#options' => array(0 => $this->t('Node'), 1 => $this->t('User'),3 => $this->t('Taxonomy')),
    ];
	$form['column_name'] = [
       '#type' => 'textfield',
       '#title' => $this->t('Relational Table Column Name'),
       '#default_value' => $config->get('db_cloumn_name'),
    ];
	 $form['custom_note'] = [
     '#markup' => '<strong>Note :</strong> If any column is date then please make table column name like : timestamp or created or changed and column type will be "int" and value of date will be timestamp format "1502340716"',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('custom_db_table_views.settings');
    $config->set('db_table_name', $form_state->getValue('db_table_name'));
    $config->set('db_views_name', $form_state->getValue('db_views_name'));
	$config->set('db_cloumn_name', $form_state->getValue('column_name'));
	$config->set('db_reference_type', $form_state->getValue('relation_ship'));
    $config->save();
    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $table_name = $form_state->getValue('db_table_name');
    $column_name = $form_state->getValue('column_name');
   
    $table_existance = db_table_exists($form_state->getValue('db_table_name'));
	if($form_state->getValue('relation_ship')!='' && empty($form_state->getValue('column_name'))) {
	$form_state->setErrorByName('column_name', $this->t('Please Enter Column Name.'));
	}
     if($form_state->getValue('column_name')!='' && $form_state->getValue('relation_ship')=='') {
		$form_state->setErrorByName('relation_ship', $this->t('Please Select Relation Type.'));
	}
    if ($table_existance == FALSE) {
      $form_state->setErrorByName('db_table_name', $this->t('Please enter a valid existing table name.'));
    } else {
		 $result = db_query("SHOW COLUMNS FROM  $table_name LIKE '$column_name'")->fetchAll();
		  if($result == FALSE){
             $form_state->setErrorByName('column_name', $this->t('Please enter a valid existing column name.'));
		  }
    }

  }

}
