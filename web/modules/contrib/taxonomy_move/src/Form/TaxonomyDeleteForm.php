<?php

namespace Drupal\taxonomy_move\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\pathauto\PathautoGenerator;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\TermStorage;

class TaxonomyDeleteForm extends FormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'taxonomy-move__delete-form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $onChangeFunction = '
          var info = document.getElementById("taxonomy-move__sidebar__info");
          var lis = "";
          var termIds = jQuery("[data-drupal-selector=\'edit-term\']").val();
          if (null === termIds) {return;}
          for (var i = 0; i < termIds.length; i++) {
            lis += "<li>Delete " 
                + jQuery("[data-drupal-selector=\'edit-term\']").find("[value=\'" + termIds[i] + "\']").text() +
                "</li>"
          }
          info.innerHTML = lis;
        ';
    $form['#attributes']['style'] = 'display: flex; justify-content: space-between; max-width: 1200px';

    $terms = $this->getTermsOfVocabulary('unmanaged');
    $form['term'] = [
      '#type' => 'select',
      '#title' => t('Select terms'),
      '#options' => $terms,
      '#multiple' => true,
      '#required' => true,
      '#prefix' => '<div id="source-vocabulary-replace">',
      '#suffix' => '</div>',
      '#attributes' => [
        'style' => 'min-width: 250px; background: none',
        'size' => count($terms),
        'onchange' => $onChangeFunction,
      ]
    ];

    $form['sidebar'] = array(
      '#type' => 'fieldset',
      '#attributes' => [
        'style' => 'border: none; min-width: 400px; background: #e0e0d8'
      ],
    );
    $form['sidebar']['info'] = [
      '#type' => 'markup',
      '#markup' => Markup::create(
        '<ul id="taxonomy-move__sidebar__info" style="padding-bottom: 40px; list-style: none; padding-left: 0"></ul>'
      ),
    ];

    $form['sidebar']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Delete terms from unmanaged'),
      '#button_type' => 'danger',
      '#attributes' => [
        'style' => 'height: 2em; display: block'
      ]
    ];


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $termIds = $form_state->getValue('term');
    $storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $entities = $storage->loadMultiple($termIds);
    $storage->delete($entities);

    drupal_set_message(Markup::create(sprintf(
      '%s terms have been deleted.',
      count($termIds)
    )));

    $storage->resetCache();
  }

  public static function loadTerms(array $form, FormStateInterface $form_state)
  {
    return $form['term'];
  }

  protected function getTermsOfVocabulary($vocabulary)
  {
    if (empty($vocabulary)) {
      return [];
    }

    /** @var TermStorage $storage */
    $storage = \Drupal::getContainer()->get('entity.manager')->getStorage('taxonomy_term');
    $tree = $storage
      ->loadTree($vocabulary);

    $terms = [];
    foreach ($tree as $treeItem) {
      $terms[$treeItem->tid] = $treeItem->name . ' (' . $this->getNodeCount($treeItem->tid) . ' nodes)';
    }
    asort($terms);
    return $terms;
  }

  protected function updateUrlAlias(Term $term)
  {
    $aliasSource = '/' . Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $term->id()])->getInternalPath();
    // load old alias
    $statement = Database::getConnection()->select('url_alias')
      ->fields('url_alias')
      ->condition('source', $aliasSource)
      ->execute();

    $originalAlias = $statement->fetch();
    /** @var PathautoGenerator $pathAutoGenerator */
    $pathAutoGenerator = \Drupal::service('pathauto.generator');
    $pathAutoGenerator->resetCaches();

    return [
      $originalAlias ? $originalAlias->alias : '',
      $pathAutoGenerator->updateEntityAlias(Term::load($term->id()), 'update', [
        'force' => true,
      ])['alias']
    ];
  }

  protected function getVocabularies()
  {
    $vocabularies = [];
    $vids = taxonomy_vocabulary_get_names();
    foreach ($vids as $vid) {
      $vocabularies[$vid] = $this->vocabularyIdToName($vid);
    }
    return $vocabularies;
  }

  protected function vocabularyIdToName($vid)
  {
    $vocabulary = Vocabulary::load($vid);
    return $vocabulary->get('name');
  }

  protected function getNodeCount($tid)
  {
    return Database::getConnection()->select('taxonomy_index')
      ->fields('taxonomy_index')
      ->condition('tid', $tid)
      ->countQuery()
      ->execute()
      ->fetchField();
  }
}
