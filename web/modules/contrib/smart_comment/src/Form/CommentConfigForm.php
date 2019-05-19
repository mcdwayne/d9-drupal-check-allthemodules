<?php

namespace Drupal\smart_comment\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;


/**
* Config form for admin
*/
class CommentConfigForm extends ConfigFormBase{

  public function getFormId(){
    return "smart_config_configcomment";
  }

  /**
  * {@inheritdoc}
  */

  public function buildForm(array $form, FormStateInterface $form_state){
    $config = $this->config('smart_comment.settings');

    $form['smart_comment_container'] = [
      '#type' => 'textarea',
      '#title' => 'Stop words, if any commnet will have any word among listed, it will not get publish. Use comma(,) to separate word',
      '#default_value' => $config->get('smart_comment_container' ),
    ];

    $form['smart_comment_error_message'] = [
      '#type' => 'textfield',
      '#title' => 'Configure error message',
      '#default_value' => $config->get('smart_comment_error_message'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
  * {@inheritdoc}
  */

  protected function getEditableConfigNames(){
    return[
      'smart_comment.settings',
    ];
  }

  /**
  * {@inheritdoc}
  */

  public function submitForm(array &$form, FormStateInterface $form_state){
    $config = $this->configFactory->getEditable('smart_comment.settings');
    $config
      ->set('smart_comment_container',$form_state->getValue('smart_comment_container'))
      ->set('smart_comment_error_message',$form_state->getValue('smart_comment_error_message'))
      ->save();
    parent::submitForm($form, $form_state);

  }

}
