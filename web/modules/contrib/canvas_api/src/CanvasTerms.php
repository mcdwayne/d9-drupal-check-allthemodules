<?php
/**
 * @file
 * Helper functions that utilize Canvas' Terms APIs
 *
 * See @link https://canvas.instructure.com/doc/api/enrollment_terms.html @endlink
 *
 */
namespace Drupal\canvas_api;


/**
 * {@inheritdoc}
 */
class CanvasTerms extends Canvas {

  /**
   * List Canvas terms
   *
   * See @link https://canvas.instructure.com/doc/api/enrollment_terms.html#method.terms_api.index @endlink
   *
   * Example:
   *
   *  $canvas_api = \Drupal::service('canvas_api.terms');
   *  $terms = $canvas_api->listTerms();
   *
   * @return array
   */
  public function listTerms($accountID = 1){
    $this->path = "accounts/$accountID/terms";
    return $this->get();
  }

  /**
   * Create Canvas term
   *
   * See @link https://canvas.instructure.com/doc/api/enrollment_terms.html#method.terms.create @endlink
   *
   * Example:
   *
   *  $canvas_api = \Drupal::service('canvas_api.terms');
   *  $canvas_api->params = array(
   *    'enrollment_term' => array(
   *       'name' => 'My New Term',
   *       'sis_term_id' => 'SISTERMID',
   *    ),
   *  );
   *  $term = $canvas_api->createTerm();
   *
   * @return array
   */
  
 public function createTerm($accountID = 1){
   $this->path = "accounts/$accountID/terms";
   return $this->post();
 }
 
  /**
   * Update Canvas term
   *
   * See @link https://canvas.instructure.com/doc/api/enrollment_terms.html#method.terms.update @endlink
   *
   * Example:
   *
   *  $canvas_api = \Drupal::service('canvas_api.terms');
   *  $canvas_api->params = array(
   *    'enrollment_term' => array(
   *       'name' => 'My Updated Term',
   *    ),
   *  );
   *  $term = $canvas_api->updateTerm('sis_term_id:SISTERMID');
   *
   * @return array
   */
 public function updateTerm($termID,$accountID = 1){
   $this->path = "accounts/$accountID/terms/$termID";
   return $this->put();
 } 


  /**
   * Delete Canvas term
   *
   * See @link https://canvas.instructure.com/doc/api/enrollment_terms.html#method.terms.destroy @endlink
   *
   * Example:
   *
   *  $canvas_api = \Drupal::service('canvas_api.terms');
   *  $term = $canvas_api->deleteTerm(157);
   *
   * @return array
   */
 public function deleteTerm($termID,$accountID = 1){
   $this->path = "accounts/$accountID/terms/$termID";
   return $this->delete();
 } 
}