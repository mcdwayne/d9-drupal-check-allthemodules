<?php
/** 
 * @file
 * @author  Er. Sandeep Jangra
 * Contains \Drupal\newsletter_digest\Form\DefaultCategoryForm.
 */
namespace Drupal\newsletter_digest\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class DefaultCategoryForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'newsletter_digest_default_category_form';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'newsletter_digest.newsletter_digest_category',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('newsletter_digest.newsletter_digest_category');
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', "nd_category");
    $tids = $query->execute();
    $terms = \Drupal\taxonomy\Entity\Term::loadMultiple($tids);
    $key = array();
    $val = array();
    foreach($terms as $term){
      $key[] = $term->id();
      $val[] = $term->getName();
    }
    $options = array_combine($key,$val);

    $form['default_category'] = array (
      '#type' => 'select',
      '#title' => ('Select Subscriber Newsletter Category'),
      '#default_value' => $config->get('default_category'),
      '#options' => $options,
    );  

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
      $this->config('newsletter_digest.newsletter_digest_category')
      ->set('default_category', $form_state->getValue('default_category'))
      ->save();

    parent::submitForm($form, $form_state);
   }
}
