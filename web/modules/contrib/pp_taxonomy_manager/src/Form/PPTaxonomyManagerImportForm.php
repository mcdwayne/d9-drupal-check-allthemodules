<?php
/**
 * @file
 * Contains \Drupal\pp_taxonomy_manager\Form\PPTaxonomyManagerImportForm.
 */

namespace Drupal\pp_taxonomy_manager\Form;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\pp_taxonomy_manager\Entity\PPTaxonomyManagerConfig;
use Drupal\pp_taxonomy_manager\PPTaxonomyManager;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * The confirmation-form for the import of a taxonomy from a PoolParty server.
 */
class PPTaxonomyManagerImportForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pp_taxonomy_manager_import_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param PPTaxonomyManagerConfig $config
   *   The configuration of the PoolParty Taxonomy manager.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $config = NULL) {
    // Check if concept scheme URI is given and is a url.
    $settings = $config->getConfig();
    $root_uri = $_GET['uri'];

    // Get the project.
    $connection = $config->getConnection();
    $pp_projects = $connection->getApi('PPT')->getProjects();
    $project = NULL;
    foreach ($pp_projects as $pp_project) {
      if ($pp_project['id'] == ($settings['root_level'] == 'project' ? $root_uri : $config->getProjectId())) {
        $project = $pp_project;
        break;
      }
    }
    if (is_null($project)) {
      drupal_set_message(t('The configured PoolParty project does not exists.'), 'error');
      return new RedirectResponse(Url::fromRoute('entity.pp_taxonomy_manager.edit_config_form', array('pp_taxonomy_manager' => $config->id()))->toString());
    }

    $root_object = NULL;
    if ($settings['root_level'] == 'conceptscheme') {
      // Check if concept scheme URI is given and is a url.
      if (!UrlHelper::isValid($root_uri, TRUE)) {
        drupal_set_message(t('The URI from the selected concept scheme is not valid.'), 'error');
        return new RedirectResponse(Url::fromRoute('entity.pp_taxonomy_manager.edit_config_form', array('pp_taxonomy_manager' => $config->id()))->toString());
      }

      $concept_schemes = $config->getConnection()
        ->getApi('PPT')
        ->getConceptSchemes($config->getProjectId());
      foreach ($concept_schemes as $scheme) {
        if ($scheme['uri'] == $root_uri) {
          $root_object = $scheme;
          break;
        }
      }
      if (is_null($root_object)) {
        drupal_set_message(t('The selected concept scheme does not exists.'), 'error');
        return new RedirectResponse(Url::fromRoute('entity.pp_taxonomy_manager.edit_config_form', array('pp_taxonomy_manager' => $config->id()))->toString());
      }
    }
    else {
      $root_object = $project;
    }

    // Check if the taxonomy is already connected with a concept scheme.
    if (in_array($root_uri, $settings['taxonomies'])) {
      drupal_set_message(t('The %rootobject is already connected, please select another one.', array('%rootobject' => (($settings['root_level'] == 'conceptscheme') ? t('concept scheme') : t('project')) . ' ' . $root_object['title'])), 'error');
      return new RedirectResponse(Url::fromRoute('entity.pp_taxonomy_manager.edit_config_form', array('pp_taxonomy_manager' => $config->id()))->toString());
    }

    // Check if the new taxonomy already exists in Drupal.
    $machine_name = PPTaxonomyManager::createMachineName($root_object['title']);
    $taxonomy = Vocabulary::load($machine_name);

    if ($settings['root_level'] == 'conceptscheme') {
      $description = t('A new taxonomy will be created and all concepts from the concept scheme %scheme will be inserted as terms.', array('%scheme' => $root_object['title']));
    }
    else {
      $description = t('A new taxonomy will be created and all concepts / concept schemes from the project %project will be inserted as terms.', array('%project' => $root_object['title']));
    }
    $description .= '<br />' . t('This can take a while. Please wait until the import is finished.');
    $form['description'] = array(
      '#markup' => $description,
    );
    $field_description = t('Please enter a name of a taxonomy, which does not yet exist.');
    if ($taxonomy) {
      $field_description .= '<br />' . t('The taxonomy %taxonomy (machine name: %machine_name) already exists. Its terms will be updated, deleted and/or created.', array(
          '%taxonomy' => $taxonomy->label(),
          '%machine_name' => $taxonomy->id(),
        ));
    }
    $form['taxonomy_name'] = array(
      '#title' => t('Name of the new taxonomy'),
      '#type' => 'textfield',
      '#default_value' => $root_object['title'],
      '#description' => $field_description,
      '#required' => TRUE,
    );

    // Language mapping.
    $available_languages = \Drupal::languageManager()->getLanguages();
    $default_language = \Drupal::languageManager()->getDefaultLanguage()->getId();
    $pp_languages = $connection->getApi('PPT')->getLanguages();
    $project_language_options = array();
    foreach ($project['availableLanguages'] as $project_language) {
      if (isset($pp_languages[$project_language])) {
        $project_language_options[$project_language] = $pp_languages[$project_language];
      }
    }
    $form['languages'] = array(
      '#type' => 'item',
      '#title' => t('Map the Drupal languages with the PoolParty project languages'),
      '#tree' => TRUE,
    );
    /** @var LanguageInterface $lang */
    foreach ($available_languages as $lang) {
      if (!$lang->isLocked()) {
        $form['languages'][$lang->getId()] = array(
          '#type' => 'select',
          '#title' => t('Drupal language %language', array('%language' => $lang->getName())),
          '#description' => t('Select the PoolParty project language'),
          '#options' => $project_language_options,
          '#empty_option' => '',
          '#default_value' => ($lang->getId() == $default_language ? $project['defaultLanguage'] : ''),
          '#required' => ($lang->getId() == $default_language ? TRUE : FALSE),
        );
      }
    }

    $form['preserve_concepts'] = array(
      '#type' => 'checkbox',
      '#title' => t('Preserve old concepts'),
      '#description' => t('If the concepts get imported into an existing taxonomy, taxonomy terms which can\'t be matched by URI or label normally get deleted') . '<br />' . t(' By checking "Preserve old concepts" instead of deleting them they get moved under a special concept named "Freeterms" without a URI.'),
      '#default_value' => FALSE,
    );

    PPTaxonomyManager::addDataPropertySelection($form, ['skos:altLabel', 'skos:hiddenLabel']);

    $form['concepts_per_request'] = array(
      '#type' => 'textfield',
      '#title' => t('PoolParty concepts per request'),
      '#description' => t('The number of concepts, that get processed during one HTTP request. (Allowed value range: 1 - 100)') . '<br />' . t('The higher this number is, the less HTTP requests have to be sent to the server until the batch finished updating ALL your concepts, what results in a shorter duration of the bulk updating process.') . '<br />' . t('Numbers too high can result in a timeout, which will break the whole bulk updating process.'),
      '#required' => TRUE,
      '#default_value' => 10,
    );

    $form['import'] = array(
      '#type' => 'submit',
      '#value' => t('Import taxonomy'),
    );
    $form['cancel'] = array(
      '#type' => 'link',
      '#title' => t('Cancel'),
      '#url' => Url::fromRoute('entity.pp_taxonomy_manager.edit_config_form', array('pp_taxonomy_manager' => $config->id())),
      '#suffix' => '</div>',
    );

    $form_state->set('config', $config);
    $form_state->set('root_object', $root_object);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $default_language = \Drupal::languageManager()->getDefaultLanguage()->getId();
    if (!\Drupal::moduleHandler()->moduleExists('content_translation')) {
      foreach ($values['languages'] as $drupal_lang => $pp_lang) {
        if (!empty($pp_lang) && $drupal_lang != Language::LANGCODE_NOT_SPECIFIED && $drupal_lang != $default_language) {
          $drupal_languages = \Drupal::languageManager()->getLanguages();
          $form_state->setErrorByName('languages][' . $drupal_lang, t('Language "%language" requires translation of taxonomies as it is not your default site language.<br /> Install and enable module "Content Translation" and its sub-module "Taxonomy translation" to make multilingual tagging possible.', array(
            '%language' => $drupal_languages[$drupal_lang]->getName(),
          )));
        }
      }
    }

    $languages = array_unique($values['languages']);
    if (count(array_filter($languages)) > 1 && !\Drupal::moduleHandler()->moduleExists('content_translation')) {
      $form_state->setErrorByName('languages', t('Module "Content Translation" needs to be enabled for multilingual operations.'));
    }

    $concepts_per_request = $values['concepts_per_request'];
    if (empty($concepts_per_request) || !ctype_digit($concepts_per_request) || (int) $concepts_per_request == 0 || (int) $concepts_per_request > 100) {
      $form_state->setErrorByName('concepts_per_request', t('Only values in the range of 1 - 100 are allowed for field "PoolParty concepts per request"'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    /** @var PPTaxonomyManagerConfig $config */
    $config = $form_state->get('config');
    $settings = $config->getConfig();
    $root_object = $form_state->get('root_object');
    $root_uri = ($settings['root_level'] == 'project') ? $root_object['id'] : $root_object['uri'];

    // Get the data properties for the data fetching process.
    $data_properties = [];
    if (isset($values['data_properties'])) {
      foreach ($values['data_properties'] as $property) {
        if ($property) {
          $data_properties[] = $property;
        }
      }
    }

    $concepts_per_request = $values['concepts_per_request'];
    $languages = PPTaxonomyManager::orderLanguages($values['languages']);
    $preserve_concepts = $values['preserve_concepts'];

    $manager = PPTaxonomyManager::getInstance($config);

    // Create the new taxonomy .
    $taxonomy = $manager->createTaxonomy($root_object, $values['taxonomy_name']);
    $manager->enableTranslation($taxonomy, $languages);

    // Add URI and alt. labels fields (if not exists) to the taxonomy.
    $manager->adaptTaxonomyFields($taxonomy);

    // Connect the new taxonomy with the concept scheme.
    $manager->addConnection($taxonomy->id(), $root_uri, $languages, $data_properties);

    // Import all concepts.
    try {
      $manager->updateTaxonomyTerms('import', $taxonomy, $root_uri, $languages, $data_properties, $preserve_concepts, $concepts_per_request);
    } catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
    }
    $form_state->setRedirect('entity.pp_taxonomy_manager.edit_config_form', array('pp_taxonomy_manager' => $config->id()));
  }
}
?>