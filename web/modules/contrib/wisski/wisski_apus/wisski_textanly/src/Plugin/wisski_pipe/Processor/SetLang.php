<?php

/**
 * @file
 * Contains \Drupal\wisski_pipe\Plugin\wisski_pipe\Processor\SetLang.
 */

namespace Drupal\wisski_textanly\Plugin\wisski_pipe\Processor;

use Drupal\wisski_pipe\ProcessorInterface;
use Drupal\wisski_pipe\ProcessorBase;
use Drupal\wisski_pipe\Entity\Pipe;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * @Processor(
 *   id = "textanly_set_lang",
 *   label = @Translation("Set language"),
 *   description = @Translation("Set the lang column to a certain value."),
 *   tags = { "text", "language" }
 * )
 */
class SetLang extends ProcessorBase {
  use StringTranslationTrait;
  
  /**
  * The language that this class sets
  * @var string
  */
  protected $lang;


  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    parent::setConfiguration($configuration);
    $this->lang = $configuration['lang'];
  }


  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $conf = array(
      'lang' => $this->lang,
    ) + parent::getConfiguration();
    return $conf;
  }


  /**
   * {@inheritdoc}
   */
  public function doRun() {
    
    $text_struct = (array) $this->data;
    
    $text_struct['lang'] = $this->lang;

    $this->data = $text_struct;

  }

  
  /**
   * {@inheritdoc}
   */
  public function outputFields() {
    return parent::outputFields() + array(
      'lang' => 1,
    );
  }
      


  
  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    
    $form['lang'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Language'),
      '#default_value' => $this->lang,
      '#description' => $this->t('A language code like es, deu, en-US'),
    ];
    
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->lang = $form_state->getValue('lang');
  }
  


}
