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

class TaxonomyMoveForm extends FormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'taxonomy-move__move-form';
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
            lis += "<li>Move <i>" 
                + jQuery("[data-drupal-selector=\'edit-term\']").find("[value=\'" + termIds[i] + "\']").text() +
                "</i> from " + jQuery("#edit-source-vocabulary").find("[value=\'" + document.getElementById("edit-source-vocabulary").value + "\']").text() +
                " to " + jQuery("#edit-target-vocabulary").find("[value=\'" + document.getElementById("edit-target-vocabulary").value + "\']").text() +
                "</li>"
          }
          info.innerHTML = lis;
        ';
    $form['#attributes']['style'] = 'display: flex; justify-content: space-between; max-width: 1200px';

    $vocabularyNames = $this->getVocabularies();
    $form['source_vocabulary'] = [
      '#type' => 'select',
      '#title' => t('Select source vocabulary:'),
      '#required' => true,
      '#options' => $vocabularyNames,
      '#default_value' => $_GET['vid'] ?? '',
      '#ajax' => [
        'callback' => [$this, 'loadTerms'],
        'wrapper' => 'source-vocabulary-replace',
      ],
      '#attributes' => [
        'size' => count($vocabularyNames) + 1,
        'style' => 'background: none',
        'onchange' => $onChangeFunction,
      ],
    ];

    $vocabulary = $form_state->getValue('source_vocabulary');
    $terms = $this->getTermsOfVocabulary($vocabulary ?? $_GET['vid'] ?? '');
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

    $form['target_vocabulary'] = [
      '#type' => 'select',
      '#title' => t('Select target vocabulary:'),
      '#required' => true,
      '#options' => $vocabularyNames,
      '#attributes' => [
        'size' => count($vocabularyNames) + 1,
        'style' => 'background: none',
        'onchange' => $onChangeFunction,
      ],
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
      '#value' => t('Move terms'),
      '#button_type' => 'primary',
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
    $targetVocabulary = $form_state->getValue('target_vocabulary');
    $sourceVocabulary = $form_state->getValue('source_vocabulary');

    foreach ($termIds as $termId) {
      $term = Term::load($termId);

      if (Database::getConnection()->select('taxonomy_term__parent')
        ->condition('parent_target_id', $term->id())
        ->countQuery()
        ->execute()
        ->fetchField()
      ) { // do not move term if it has children
        drupal_set_message(Markup::create(sprintf(
          'The term %s could not be moved because it has children',
          $term->getName()
        )), 'error');
        continue;
      }

      $term->set('vid', $targetVocabulary);
      $term->set('parent', 0); // remove parent term
      $term->save();
      // If we don't do this, then $term->getVocabularyId() will still return
      // the outdated value on the next page.
      \Drupal::entityTypeManager()->getStorage('taxonomy_term')->resetCache([$term->id()]);

      list($oldAlias, $newAlias) = $this->updateUrlAlias($term);

      // "Unmanaged" does not get an alias
      if ('unmanaged' === $sourceVocabulary) {
        // if the term has been inside target vocabulary before it was moved into unmanaged there is
        // no new alias created because the old still exists
        if (null === $newAlias) {
          $newAlias = $oldAlias;
        }
        drupal_set_message(Markup::create(sprintf(
          'The term %s has been moved from %s to <a href="%s">%s (%s)</a>',
          $term->getName(),
          $this->vocabularyIdToName($sourceVocabulary),
          $newAlias,
          $this->vocabularyIdToName($targetVocabulary),
          $newAlias
        )));
      } else if ('unmanaged' === $targetVocabulary) {
        drupal_set_message(Markup::create(sprintf(
          'The term %s has been moved from <a href="%s">%s (%s)</a> to %s',
          $term->getName(),
          $oldAlias,
          $this->vocabularyIdToName($sourceVocabulary),
          $oldAlias,
          $this->vocabularyIdToName($targetVocabulary)
        )));
      } else {
        drupal_set_message(Markup::create(sprintf(
          'The term %s has been moved from <a href="%s">%s (%s)</a> to <a href="%s">%s (%s)</a>',
          $term->getName(),
          $oldAlias,
          $this->vocabularyIdToName($sourceVocabulary),
          $oldAlias,
          $newAlias,
          $this->vocabularyIdToName($targetVocabulary),
          $newAlias
        )));
      }
      // remove this term from field_tags if we move it to channel
      if ('channel' === $targetVocabulary) {
        $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(
          [
            'field_tags' => $term->id(),
          ]
        );
        foreach ($nodes as $node) {
          $tags = $node->get('field_tags')->getValue();
          $key = array_search($term->id(), array_column($tags, 'target_id'));
          $node->get('field_tags')->removeItem($key);
          $node->save();
        }
      }

      // update published state
      if ('unmanaged' === $targetVocabulary) {
        // unpublish
        $term = Term::load($termId);
        $term->set('status', 0);
        $term->save();
      } else if ('unmanaged' === $sourceVocabulary) {
        // publish
        $term = Term::load($termId);
        $term->set('status', 1);
        $term->save();
      } else {
        // leave as is
      }
    }
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
