<?php
/**
 * @file
 * Contains \Drupal\pp_taxonomy_manager\Form\PPTaxonomyManagerExportForm.
 */

namespace Drupal\pp_taxonomy_manager\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\pp_taxonomy_manager\Entity\PPTaxonomyManagerConfig;
use Drupal\pp_taxonomy_manager\PPTaxonomyManager;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * The confirmation-form for the export of a taxonomy to a PoolParty server.
 */
class PPTaxonomyManagerExportForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pp_taxonomy_manager_export_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param PPTaxonomyManagerConfig $config
   *   The configuration of the PoolParty Taxonomy manager.
   * @param Vocabulary $taxonomy
   *   The taxonomy to use.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $config = NULL, $taxonomy = NULL) {
    // Check if concept scheme URI is given and is a url.
    // Check if taxonomy exists.
    if ($taxonomy === FALSE) {
      drupal_set_message(t('The selected taxonomy does not exists.'), 'error');
      return new RedirectResponse(Url::fromRoute('entity.pp_taxonomy_manager.edit_config_form', array('pp_taxonomy_manager' => $config->id()))->toString());
    }

    $connection = $config->getConnection();
    $settings = $config->getConfig();

    // Get the project.
    $project = NULL;
    $project_names = array();
    $pp_projects = $connection->getApi('PPT')->getProjects();
    foreach ($pp_projects as $pp_project) {
      $project_names[] = $pp_project['title'];
      if ($pp_project['id'] == $config->getProjectId()) {
        $project = $pp_project;
        break;
      }
    }
    if ($settings['root_level'] == 'conceptscheme' && is_null($project)) {
      drupal_set_message(t('The configured PoolParty project does not exists.'), 'error');
      return new RedirectResponse(Url::fromRoute('entity.pp_taxonomy_manager.edit_config_form', array('pp_taxonomy_manager' => $config->id()))->toString());
    }

    // Check if the taxonomy is connected with a concept scheme.
    if (isset($settings['taxonomies'][$taxonomy->id()])) {
      drupal_set_message(t('The taxonomy %taxonomy is already connected, please select another one.', array('%taxonomy' => $taxonomy->label())), 'error');
      return new RedirectResponse(Url::fromRoute('entity.pp_taxonomy_manager.edit_config_form', array('pp_taxonomy_manager' => $config->id()))->toString());
    }

    // Get the sum of all terms from the taxonomy.
    $tree = \Drupal::service('entity_type.manager')
      ->getStorage("taxonomy_term")
      ->loadTree($taxonomy->id());
    $count = count($tree);

    if ($settings['root_level'] == 'conceptscheme') {
      $description = t('A new concept scheme will be created in the project %project and %count terms will be inserted.', array(
        '%project' => $project['title'],
        '%count' => $count,
      ));
    }
    else {
      $form['project_names'] = array(
        '#type' => 'value',
        '#value' => $project_names,
      );
      $description = t('A new PoolParty project will be created and %count concepts / concept schemes will be inserted.', array(
        '%count' => $count,
      ));
    }
    $description .= '<br />' . t('This can take a while. Please wait until the export is finished.');
    $form['description'] = array(
      '#markup' => $description,
    );
    $form['concept_scheme_title'] = array(
      '#title' => t('Title of the new %rootobject', array('%rootobject' => (($settings['root_level'] == 'conceptscheme') ? t('concept scheme') : t('project')))),
      '#type' => 'textfield',
      '#default_value' => $taxonomy->label(),
      '#required' => TRUE,
    );

    // Language mapping.
    if (\Drupal::moduleHandler()->moduleExists('content_translation') && \Drupal::service('content_translation.manager')->isEnabled('taxonomy_term', $taxonomy->id())) {
      $available_languages = \Drupal::languageManager()->getLanguages();
    }
    else {
      $available_languages = array(\Drupal::languageManager()->getDefaultLanguage());
    }
    // Map the available languages and remove disabled ones.
    $enabled_languages = array();
    /** @var LanguageInterface $lang */
    foreach ($available_languages as $lang) {
      if (!$lang->isLocked()) {
        $enabled_languages[$lang->getId()] = $lang->getName();
      }
    }

    $default_language = \Drupal::languageManager()->getDefaultLanguage()->getId();
    if ($settings['root_level'] == 'project') {
      $form['default_language'] = array(
        '#type' => 'select',
        '#title' => t('Default project language'),
        '#description' => t('Select the default language of the new PoolParty project'),
        '#options' => $enabled_languages,
        '#default_value' => $default_language,
        '#required' => TRUE,
      );
    }

    $form['languages'] = array(
      '#type' => 'details',
      '#title' => t('Language Mapping'),
      '#description' => t('Map the Drupal languages with the PoolParty project languages.') . (count($enabled_languages) > 1 ? '<br />' . t('The term-translations of the non-selected languages are not exported.') : ''),
      '#open' => TRUE,
      '#tree' => TRUE,
    );

    $pp_languages = $connection->getApi('PPT')->getLanguages();
    if ($settings['root_level'] == 'conceptscheme') {
      $project_language_options = array();
      foreach ($project['availableLanguages'] as $project_language) {
        if (isset($pp_languages[$project_language])) {
          $project_language_options[$project_language] = $pp_languages[$project_language];
        }
      }
      asort($project_language_options);

      foreach ($enabled_languages as $lang_id => $lang_title) {
        $form['languages'][$lang_id] = array(
          '#type' => 'select',
          '#title' => t('Drupal language %language', array('%language' => $lang_title)),
          '#description' => t('Select the PoolParty project language'),
          '#options' => $project_language_options,
          '#empty_option' => '',
          '#default_value' => (isset($project_language_options[$lang_id]) ? $lang_id : ''),
          '#required' => ($lang_id == $default_language ? TRUE : FALSE),
        );
      }
    }
    else {
      foreach ($enabled_languages as $lang_id => $lang_title) {
        $form['languages'][$lang_id] = array(
          '#type' => 'select',
          '#title' => t('Drupal language %language', array('%language' => $lang_title)),
          '#description' => t('Select the PoolParty project language'),
          '#options' => $pp_languages,
          '#empty_option' => '',
          '#default_value' => isset($pp_languages[$lang_id]) ? $lang_id : NULL,
          '#required' => ($lang_id == $default_language ? TRUE : FALSE),
        );
      }
    }

    $form['terms_per_request'] = array(
      '#type' => 'textfield',
      '#title' => t('Taxonomy terms per request'),
      '#description' => t('The number of terms, that get processed during one HTTP request. (Allowed value range: 1 - 100)') . '<br />' . t('The higher this number is, the less HTTP requests have to be sent to the server until the batch finished exporting ALL your terms, what results in a shorter duration of the bulk exporting process.') . '<br />' . t('Numbers too high can result in a timeout, which will break the whole bulk exporting process.'),
      '#required' => TRUE,
      '#default_value' => 10,
    );

    $form['save'] = array(
      '#type' => 'submit',
      '#value' => t('Export taxonomy'),
    );
    $form['cancel'] = array(
      '#type' => 'link',
      '#title' => t('Cancel'),
      '#url' => Url::fromRoute('entity.pp_taxonomy_manager.edit_config_form', array('pp_taxonomy_manager' => $config->id())),
      '#suffix' => '</div>',
    );

    $form_state->set('config', $config);
    $form_state->set('taxonomy', $taxonomy);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Ignore empty languages.
    if (!empty($form_state->getValue('languages'))) {
      $form_state->setValue('languages', array_filter($form_state->getValue('languages')));
    }

    $values = $form_state->getValues();

    if (!empty($values['project_names']) && in_array($values['concept_scheme_title'], $values['project_names'])) {
      $form_state->setErrorByName('concept_scheme_title', t('A PoolParty project with the title "%title" already exists. Please select a different title.', array('%title' => $values['concept_scheme_title'])));
    }

    // Check whether all languages are different.
    $languages = array_unique($values['languages']);
    if (count($values['languages']) != count($languages)) {
      $form_state->setErrorByName('languages', t('The selected languages must be different.'));
    }

    /*if (count(array_filter($languages)) > 1 && !\Drupal::moduleHandler()->moduleExists('content_translation')) {
      $form_state->setErrorByName('languages', t('Module "Content Translation" needs to be enabled for multilingual operations.'));
    }*/

    $concepts_per_request = $values['terms_per_request'];
    if (empty($concepts_per_request) || !ctype_digit($concepts_per_request) || (int) $concepts_per_request == 0 || (int) $concepts_per_request > 100) {
      $form_state->setErrorByName('terms_per_request', t('Only values in the range of 1 - 100 are allowed for field "Taxonomy terms per request"'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    /** @var PPTaxonomyManagerConfig $config */
    $config = $form_state->get('config');
    /** @var Vocabulary $taxonomy */
    $taxonomy = $form_state->get('taxonomy');
    $settings = $config->getConfig();

    // Get the data properties for the data pushing process.
    $properties = PPTaxonomyManager::getTaxonomyFieldProperties();
    $data_properties = array_keys($properties);

    $terms_per_request = $values['terms_per_request'];
    $languages = PPTaxonomyManager::orderLanguages($values['languages']);

    $manager = PPTaxonomyManager::getInstance($config);

    // Add URI and alt. labels fields (if not exists) to the taxonomy.
    $manager->adaptTaxonomyFields($taxonomy);

    if ($settings['root_level'] == 'conceptscheme') {
      // Create the new concept scheme in the PoolParty thesaurus.
      $root_uri = $manager->createConceptScheme($taxonomy, $values['concept_scheme_title']);
    }
    else {
      $root_uri = $manager->createProject($taxonomy, $values['concept_scheme_title'], $values['default_language'], array_values($values['languages']));
    }

    if ($root_uri !== FALSE) {
      // Connect the taxonomy with the new concept scheme / project.
      $manager->addConnection($taxonomy->id(), $root_uri, $languages, $data_properties);

      // Export all taxonomy terms.
      $manager->exportTaxonomyTerms($taxonomy, $root_uri, $languages, $terms_per_request);
    }
    // The project could not be created.
    else {
      drupal_set_message(t('There was an error during the creation of the project.'), 'error');
    }

    $form_state->setRedirect('entity.pp_taxonomy_manager.edit_config_form', array('pp_taxonomy_manager' => $config->id()));
  }
}
?>