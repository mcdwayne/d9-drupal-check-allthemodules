<?php

/**
 * @file
 * Contains \Drupal\wisski_pipe\Plugin\wisski_pipe\Processor\RegExp.
 */

namespace Drupal\wisski_textanly\Plugin\wisski_pipe\Processor;

use Drupal\wisski_pipe\ProcessorInterface;
use Drupal\wisski_pipe\ProcessorBase;
use Drupal\wisski_pipe\Entity\Pipe;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * @Processor(
 *   id = "textanly_regexp",
 *   label = @Translation("Regular Expressions"),
 *   description = @Translation("Creates annotations based on regular expressions."),
 *   tags = { "pipe", "recursive", "text", "analysis", "xhtml" }
 * )
 */
class RegExp extends ProcessorBase {
  use StringTranslationTrait;
  
  /**
  * The regular expression pattern
  * @var string
  */
  protected $pattern;

  
  /**
  * The class for annotations found by this analyser
  * @var string
  */
  protected $clazz;
  

  /**
  * The uri for annotations found by this analyser
  * @var string
  */
  protected $uri;
  

  /**
  * A base value for the annotation's rank
  * @var float
  */
  protected $rank_offset;


  /**
  * A factor for reranking the annotation that is applied to the match's length
  * @var float
  */
  protected $rank_length_factor;

  



  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    parent::setConfiguration($configuration);
    $this->pattern = $configuration['pattern'];
    $this->clazz = $configuration['clazz'];
    $this->uri = $configuration['uri'];
    $this->rank_offset = $configuration['rank_offset'];
    $this->rank_length_factor = $configuration['rank_length_factor'];
  }


  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    $conf = [
        'pattern' => $this->pattern,
        'clazz' => $this->clazz,
        'uri' => $this->uri,
        'rank_offset' => $this->rank_offset,
        'rank_length_factor' => $this->rank_length_factor,
      ] + parent::getConfiguration();
    return $conf;
  }


  /**
   * {@inheritdoc}
   */
  public function doRun() {
    
    if (!isset($this->pattern) || (!isset($this->clazz) && !isset($this->uri))) return array();
    
    $text_struct = (array) $this->data;
      
    if (!isset($text_struct['annos'])) $text_struct['annos'] = array();

    $pattern = $this->pattern;
    $clazz = $this->clazz;
    $uri = $this->uri;
    $rank_offset = isset($this->rank_offset) ? $this->rank_offset : 1;
    $rank_length_factor = isset($this->rank_length_factor) ? $this->rank_length_factor : 0.1;

    if (preg_match_all("/$pattern/u", $text_struct['text'], $matches, PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE)) {

      foreach ($matches[0] as $match) {
        
        $start = $match[1];
        // The following line is a workaround:
        // As preg captures the byte pos, not char pos (!), we have to
        // create the leading string with the single-byte function and then
        // compute the lenght with the multibyte pendant.
        $start = mb_strlen(substr($text_struct['text'], 0, $start));
        $len = mb_strlen($match[0]);
        $end = $start + $len;

        $anno = array(
            'range' => array($start, $end),
            'rank' => $rank_offset + ($len * $rank_length_factor),
            );
        if ($clazz) $anno['class'] = $clazz;
        if ($uri) $anno['uri'] = $uri;
        $text_struct['annos'][] = $anno;

      }

    }

    $this->data = $text_struct;

  }

  
  /**
   * {@inheritdoc}
   */
  public function inputFields() {
    return parent::inputFields() + array(
      'text' => $this->t('Mandatory. The regular expression will be applied to this string.'),
    );
  }
      

  /**
   * {@inheritdoc}
   */
  public function outputFields() {
    return parent::outputFields() + array(
      'annos' => $this->t('Regular expression matches will be converted to annotations which are added to this field'),
    );
  }
      


  
  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    
    $clazzes = array(
      'person' => 'Person',
      'place' => 'Place',
      'time' => 'Time',
      'org' => 'Organisation',
      'event' => 'Event',
      'rel' => 'Relation',
      'type' => 'Type/Concept/Term',
    );

    $fieldset = array();
    $fieldset['pattern'] = array(
      '#type' => 'textfield',
      '#title' => t('pattern'),
      '#default_value' => $this->pattern,
    );  
    $fieldset['clazz'] = array(
      '#type' => 'select',
      '#title' => t('Annotation class'),
      '#multiple' => false,
      '#options' => $clazzes,
      '#default_value' =>  $this->clazz,
    );
    $fieldset['uri'] = array(
      '#type' => 'textfield',
      '#title' => t('Entity URI'),
      '#default_value' =>  $this->uri,
    );
    $fieldset['rank_offset'] = array(
      '#type' => 'textfield',
      '#title' => t('rank offset'),
      '#default_value' => $this->rank_offset,
    );
    $fieldset['rank_length_factor'] = array(
      '#type' => 'textfield',
      '#title' => t('rank length factor'),
      '#default_value' => $this->rank_length_factor,
    );         

    return $fieldset;     

  }


  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->pattern = $form_state->getValue('pattern');
    $this->clazz = $form_state->getValue('clazz');
    $this->uri = $form_state->getValue('uri');
    $this->rank_offset = $form_state->getValue('rank_offset');
    $this->rank_length_factor = $form_state->getValue('rank_length_factor');
  }
  


}
