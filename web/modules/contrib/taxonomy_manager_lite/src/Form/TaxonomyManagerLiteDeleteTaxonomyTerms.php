<?php

namespace Drupal\taxonomy_manager_lite\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * File form class.
 *
 * @ingroup taxonomy_manager_lite
 */
class TaxonomyManagerLiteDeleteTaxonomyTerms extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'assign_taxonomy_terms';
  }
  
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('link_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $query = \Drupal::entityQuery('taxonomy_vocabulary');
    $all_ids = $query->execute();
    foreach (Vocabulary::loadMultiple($all_ids) as $vocabulary) {
      $vocs[$vocabulary->id()] = $this->t($vocabulary->label());
    }
    $form['taxonomy']['vocabulary'] = array(
      '#type' => 'select',
      '#title' => $this->t('Vocabulary'),
      '#name' => 'vocabulary',
      '#options' => $vocs,
      '#empty_option' => $this->t('-select-'),
      '#size' => 1,
      '#required' => TRUE,
    );
    $form['taxonomy']['validate'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#name' => 'submit',
    ];
    $form['taxonomy']['hidden'] = [
      '#type' => 'hidden',
      '#name' => 'hide',
    ];
    $form += $this->getTaxonomyListTable($form);
    $form['taxonomy']['delete'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
      '#name' => 'delete',
    ];
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    // Entity type.
    $vid = $values['vocabulary'];
    
    $form += $this->getTaxonomyListTable($form, $vid);
    // Prevent submit.
    $triElement = $form_state->getTriggeringElement();
    if ($triElement['#name'] != 'delete') {
      $form_state->setErrorByName('hidden','');
    }
  }

  /**
   * {@inheritdoc}
   * 
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    // Entity type.
    $vid = $values['vocabulary'];
    $tids = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', $vid)
      ->execute();
    $controller = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $entities = $controller->loadMultiple($tids);
    $controller->delete($entities);
    drupal_set_message($this->t('Successfully Deleted.'));
  }
  /**
   * Generate the node list as a table.
   */
  public function getTaxonomyListTable(&$form, $vid = NULL) {
    $form['taxonomy']['terms'] = array(
        '#type' => 'table',
        '#caption' => $this->t('The following topics will be deleted'),
        '#header' => array(
          $this->t('Taxonomy ID'),
          $this->t('Name'),
        ),
      );
      $terms = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree($vid);;
      $i = 0;
      if (!empty($terms) && $vid != NULL) {
        foreach ($terms as $term) {
          $i++;
          $form['taxonomy']['terms'][$i]['tid'] = array(
            '#markup' => $term->tid,
          );

          $form['taxonomy']['terms'][$i]['name'] = array(
            '#markup' => $term->name,
          );
        }
      }
      return $form['taxonomy']['terms'];
  }
}
