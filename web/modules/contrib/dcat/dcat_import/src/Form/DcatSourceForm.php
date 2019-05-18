<?php

namespace Drupal\dcat_import\Form;

use Drupal\Core\Url;
use Drupal\dcat_import\Entity\DcatSource;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Class DcatSourceForm.
 *
 * @package Drupal\dcat_import\Form
 */
class DcatSourceForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var DcatSource $dcat_source */
    $dcat_source = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $dcat_source->label(),
      '#description' => $this->t("Label for the DCAT source."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $dcat_source->id(),
      '#machine_name' => [
        'exists' => '\Drupal\dcat_import\Entity\DcatSource::load',
      ],
      '#disabled' => !$dcat_source->isNew(),
    ];

    $form['iri'] = [
      '#type' => 'url',
      '#title' => $this->t('IRI'),
      '#maxlength' => 255,
      '#default_value' => $dcat_source->iri,
      '#description' => $this->t("IRI for the DCAT source."),
      '#required' => TRUE,
    ];

    $form['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Format'),
      '#default_value' => $dcat_source->format,
      '#description' => $this->t("Format of the DCAT source."),
      '#options' => [
        'guess' => $this->t('Guess'),
        'php' => $this->t('RDF/PHP'),
        'json' => $this->t('RDF/JSON Resource-Centric'),
        'jsonld' => $this->t('JSON-LD'),
        'ntriples' => $this->t('N-Triples'),
        'turtle' => $this->t('Turtle Terse RDF Triple Language'),
        'rdfxml' => $this->t('RDF/XML'),
        'rdfa' => $this->t('RDFa'),
      ],
      '#required' => TRUE,
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $dcat_source->description,
      '#description' => $this->t("Description for the DCAT source."),
    ];

    $form['global_theme'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use global theme'),
      '#default_value' => $dcat_source->global_theme,
      '#description' => $this->t("Remap the themes to the global themes as defined in <a href=':url'>DCAT import settings</a>", [
        ':url' => Url::fromRoute('dcat_import.admin_settings')->toString(),
      ]),
    ];

    $form['lowercase_taxonomy_terms'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Transform taxonomy terms to lowercase'),
      '#default_value' => $dcat_source->lowercase_taxonomy_terms,
      '#description' => $this->t("When checked taxonomy terms without an IRI (currently only keywords) are converted to lowercase to avoid duplicates."),
    ];

    if (\Drupal::moduleHandler()->moduleExists('language')) {
      $form['import_langcode'] = [
        '#type' => 'select',
        '#title' => $this->t('Import language'),
        '#default_value' => !empty($dcat_source->import_langcode) ? $dcat_source->import_langcode : LanguageInterface::LANGCODE_NOT_SPECIFIED,
        '#description' => $this->t("Select which language the imported datasets, distributions, vcards and agents should have."),
        '#options' => static::getLanguageOptions(),
      ];
    }


    return $form;
  }

  /**
   * Returns the options for the langcode form element.
   *
   * @return array
   *   An array containing the options.
   */
  protected static function getLanguageOptions() {
    $language_options = [];

    $languages = \Drupal::languageManager()->getLanguages(LanguageInterface::STATE_ALL);
    foreach ($languages as $langcode => $language) {
      $language_options[$langcode] = $language->isLocked() ? t('- @name -', array('@name' => $language->getName())) : $language->getName();
    }

    return $language_options;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var DcatSource $dcat_source */
    $dcat_source = $this->entity;
    $dcat_source->saveMigrations();
    $status = $dcat_source->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label DCAT source.', [
          '%label' => $dcat_source->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label DCAT source.', [
          '%label' => $dcat_source->label(),
        ]));
    }
    $form_state->setRedirectUrl($dcat_source->toUrl('collection'));
  }

}
