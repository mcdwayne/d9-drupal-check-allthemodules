<?php

/**
 * @file
 * Contains \Drupal\wisski_apus\Plugin\Linkit\Matcher\WisskiEntityMatcher.
 */

namespace Drupal\wisski_apus\Plugin\Linkit\Matcher;

use Drupal\Core\Form\FormStateInterface;
use Drupal\linkit\Plugin\Linkit\Matcher\EntityMatcher;
use Drupal\linkit\Suggestion\EntitySuggestion;
use Drupal\linkit\Suggestion\SuggestionCollection;
use Drupal\wisski_core\WissKICacheHelper;
use Drupal\wisski_salz\AdapterHelper;

/**
 * @Matcher(
 *   id = "entity:wisski_individual",
 *   target_entity = "wisski_individual",
 *   label = @Translation("WissKI Content"),
 *   provider = "wisski_apus"
 * )
 */
class WisskiEntityMatcher extends EntityMatcher {
  
  protected $limit = 30;

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    $summery = '';
    return $summery;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + [
      'limit' => 30,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return parent::getConfiguration() + [
      'limit' => $this->limit,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    parent::setConfiguration($configuration);
    $this->limit = $configuration['limit'];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return parent::calculateDependencies() + [
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    
    $form['limit'] = [
      '#type' => 'number',
      '#title' => 'Limit results',
      '#description' => $this->t('Maximum number of results. 0 means no limit.'),
      '#min' => 0,
      '#max' => 100,
      '#default_value' => $this->limit,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->limit = $form_state->getValue('limit'); 
  }
    

  /**
   * {@inheritdoc}
   * this is for linkit version >=5
   */
  public function execute($string) {
    
    // TODO: reuse EntityMatcher's buildEntityQuery function as it accounts for
    // access restrictions etc.
    \Drupal::logger("WissKI APUS")->debug("linkit matcher query: $string");
    $suggestions = new SuggestionCollection();
    if ($string) {
      $bundles = array();
      $query = db_select('wisski_title_n_grams', 'm')
        ->fields('m', array('ent_num', 'bundle', 'ngram'))
        ->condition('ngram', '%' . db_like($string) . '%', 'LIKE')
        ->range(0, 2 * $this->limit);
      // Bundle check.
      if (!empty($this->configuration['bundles'])) {
        $query->condition('bundle', $this->configuration['bundles'], 'IN');
      }
      $results = $query->execute();
      $entities = array();
      while ($result = $results->fetchObject()) {
        $entities[$result->ent_num][$result->bundle] = $result->ngram;
      }
      
      $matches = 0;
      foreach ($entities as $entity_id => $bundled_title) {
        $uri = AdapterHelper::generateWisskiUriFromId($entity_id);
        if (empty($uri)) continue;

        $default_title = "";
        if (isset($bundled_title['default'])) {
          $default_title = $bundled_title['default'];
          unset($bundled_title['default']);
        }
        
        $entity_matched = FALSE;
        foreach ($bundled_title as $bundle_id => $title) {

          if (stripos($title,$string) !== FALSE) {
            
            if (!isset($bundles[$bundle_id])) {
              $bundles[$bundle_id] = entity_load('wisski_bundle', $bundle_id);
            }
            $bundle = $bundles[$bundle_id];
            
            if(empty($bundle))
              continue;
          
            $suggestion = new EntitySuggestion();
            $suggestion->setLabel($title)
              ->setGroup($bundle->label())
              ->setPath($uri);
            $suggestions->addSuggestion($suggestion);

            $entity_matched = TRUE;
            $matches++;
            if ($matches >= $this->limit) break 2;
          }
        }
        if (!$entity_matched && $default_title && stripos($default_title,$string) !== FALSE) {
            $suggestion = new EntitySuggestion();
            $suggestion->setLabel($title)
              ->setGroup('')
              ->setPath($uri);
            $suggestions->addSuggestion($suggestion);
            $matches++;
            if ($matches >= $this->limit) break 1;
        }
      }
    }

#    if(!empty($entities) && !empty($matches))
#    dpm($entities, "ent!");
#    dpm($matches, "mat!");
    if(is_array($entities) && is_array($matches)) 
      \Drupal::logger("WissKI APUS")->info("linkit matches are {n2} with {n1} entities", array("n1" => count($entities), "n2" => count($matches)));
    else
      \Drupal::logger("WissKI APUS")->info("linkit matches are {n2} with {n1} entities", array("n1" => count($entities), "n2" => $matches));
    return $suggestions;

  }


  /**
   * {@inheritdoc}
   * This is for linkit version <5
   */
  public function getMatches($string) {

    \Drupal::logger("WissKI APUS")->debug("linkit matcher query: $string");
    
    $matches = array();
    if ($string) {
      
      $results = db_select('wisski_title_n_grams', 'm')->fields('m', array('ent_num', 'bundle', 'ngram'))->condition('ngram', '%' . db_like($string) . '%', 'LIKE')->execute();
      $entities = array();
      while ($result = $results->fetchObject()) {
        $entities[$result->ent_num][$result->bundle] = $result->ngram;
      }
      
      foreach ($entities as $entity_id => $bundled_title) {
        $uri = AdapterHelper::generateWisskiUriFromId($entity_id);
        if (empty($uri)) continue;

        $default_title = "";
        if (isset($bundled_title['default'])) {
          $default_title = $bundled_title['default'];
          unset($bundled_title['default']);
        }
        
        $entity_matched = FALSE;
        foreach ($bundled_title as $bundle_id => $title) {

          if (stripos($title,$string) !== FALSE) {
          
            $entity = entity_load('wisski_bundle', $bundle_id);
            
            if(empty($entity))
              continue;
          
            $matches[] = [
              'title' => $title,
              'description' => '',
              'path' => $uri,
              'group' => $entity->label(),
            ];
            $entity_matched = TRUE;
            if (count($matches) >= $this->limit) break 2;
          }
        }
        if (!$entity_matched && $default_title && stripos($default_title,$string) !== FALSE) {
            $matches[] = [
              'title' => $default_title,
              'description' => '',
              'path' => $uri,
              'group' => "",
            ];
            if (count($matches) >= $this->limit) break 1;
        }
      }
    }
\Drupal::logger("WissKI APUS")->info("linkit matches are {n2} with {n1} entities", array("n1" => count($entities), "n2" => count($matches)));
  
    return $matches;

  }

}
