<?php

namespace Drupal\context_node_template\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;

/**
 * Class ContextNodeTemplateSettingForm.
 *
 * @package Drupal\context_node_template\Form
 */
class ContextNodeTemplateSettingForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'context_node_template_setting';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    //get all templates of current theme.
    $tpls = _get_page_templates();
    
    $form['node_templates'] = array();
    $form['node_templates_alias'] = array('#tree' => TRUE);
    foreach ($tpls as $tpl_key => $tpl) {
      if($tpl_key != 'default'){
        $form['node_templates'][$tpl_key]['#template_name'] = $tpl_key;
        $form['node_templates_alias'][$tpl_key] = array(
          '#type' => 'textfield',
          '#default_value' => $tpl,
          '#size' => 10
        );
      }
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save all the values in an expirable key value store.
    $template_alias = $form_state->getValue('node_templates_alias');

    if (!empty($template_alias)) {
      foreach($template_alias as $template => $alias){
        $result = db_query('SELECT * FROM {node_template_alias} WHERE template = :template', array(':template' => $template))->fetchField();
        if (!empty($result)) {
          db_update('node_template_alias')
            ->fields(array('template_alias' => $alias))
            ->condition('template', $template)
            ->execute();
        }else{
          db_insert('node_template_alias')
            ->fields(array(
              'template' => $template,
              'template_alias' => $alias,         
            ))
            ->execute();
        }
      }

      drupal_set_message(t('submit success.')); 
    }
  }

}
