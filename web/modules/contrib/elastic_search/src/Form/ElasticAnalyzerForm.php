<?php

namespace Drupal\elastic_search\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\elastic_search\Entity\ElasticAnalyzer;

/**
 * Class ElasticAnalyzerForm.
 *
 * @package Drupal\elastic_search\Form
 */
class ElasticAnalyzerForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var ElasticAnalyzer $elastic_analyzer */
    $elastic_analyzer = $this->entity;

    $form['warning'] = [
      '#markup' => 'WARNING: Analyzers are experimental and subject to breaking API changes before release',
    ];
    $form['label'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Label'),
      '#maxlength'     => 255,
      '#default_value' => $elastic_analyzer->label(),
      '#description'   => $this->t("Label for the Elastic analyzer."),
      '#required'      => TRUE,
    ];

    $form['internal'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Internal'),
      '#description'   => $this->t('If TRUE then this is taken as an internal elastic analyzer implementation and will not be added directly to the dsl mapping analyzer definition section'),
      '#default_value' => $elastic_analyzer->isInternal(),
    ];

    $form['id'] = [
      '#type'          => 'machine_name',
      '#default_value' => $elastic_analyzer->id(),
      '#machine_name'  => [
        'exists' => '\Drupal\elastic_search\Entity\ElasticAnalyzer::load',
      ],
      '#disabled'      => !$elastic_analyzer->isNew(),
    ];

    $form['analyzer'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Analyzer'),
      '#description'   => $this->t('Elastic Dsl analyzer description. This should be a json array which starts with the name of the analyzer eg {"std_folded": { "type": "custom","tokenizer": "standard","filter": ["lowercase","asciifolding"]}}'),
      '#default_value' => $elastic_analyzer->getAnalyzer(),
      '#suffix'        => '<div id="editor"/>',
      '#attributes'    => [
        'data-editor'       => ['json'],
        'data-editor-theme' => ['monokai'],
      ],
      '#attached'      => [
        'library' => [
          'elastic_search/ace_json',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $elastic_analyzer = $this->entity;
    $status = $elastic_analyzer->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Elastic analyzer.',
                                    [
                                      '%label' => $elastic_analyzer->label(),
                                    ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Elastic analyzer.',
                                    [
                                      '%label' => $elastic_analyzer->label(),
                                    ]));
    }
    $form_state->setRedirectUrl($elastic_analyzer->toUrl('collection'));
  }

}
