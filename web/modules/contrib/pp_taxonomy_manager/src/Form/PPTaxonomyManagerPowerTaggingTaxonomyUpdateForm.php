<?php
/**
 * @file
 * Contains \Drupal\pp_taxonomy_manager\Form\PPTaxonomyManagerPowerTaggingTaxonomyUpdateForm.
 */

namespace Drupal\pp_taxonomy_manager\Form;
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
 * The confirmation-form for the sync of a Drupal taxonomy with a taxonomy from
 * a PoolParty server.
 */
class PPTaxonomyManagerPowerTaggingTaxonomyUpdateForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pp_taxonomy_manager_powertagging_taxonomy_update_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param PPTaxonomyManagerConfig $config
   *   The configuration of the PoolParty Taxonomy manager.
   * @param \Drupal\powertagging\Entity\PowerTaggingConfig $powertagging_config
   *   The PowerTagging configuration to use.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $config = NULL, $powertagging_config = NULL) {
    // Check if taxonomy exists.
    if (!is_object($powertagging_config)) {
      drupal_set_message(t('The PowerTagging configuration does not exists.'), 'error');
      return new RedirectResponse(Url::fromRoute('entity.pp_taxonomy_manager.edit_config_form', array('pp_taxonomy_manager' => $config->id()))->toString());
    }

    // Check if taxonomy exists.
    $powertagging_settings = $powertagging_config->getConfig();
    $taxonomy = Vocabulary::load($powertagging_settings['projects'][$powertagging_config->getProjectId()]['taxonomy_id']);
    if ($taxonomy === FALSE) {
      drupal_set_message(t('The selected taxonomy does not exists.'), 'error');
      return new RedirectResponse(Url::fromRoute('entity.pp_taxonomy_manager.edit_config_form', array('pp_taxonomy_manager' => $config->id()))->toString());
    }

    $connection = $config->getConnection();

    // Get the project.
    $project = NULL;
    $pp_projects = $connection->getApi('PPT')->getProjects();
    foreach ($pp_projects as $pp_project) {
      if ($pp_project->id == $powertagging_config->getProjectId()) {
        $project = $pp_project;
        break;
      }
    }
    if (is_null($project)) {
      drupal_set_message(t('The configured PoolParty project does not exist.'), 'error');
      return new RedirectResponse(Url::fromRoute('entity.pp_taxonomy_manager.edit_config_form', array('pp_taxonomy_manager' => $config->id()))->toString());
    }

    // Check if the taxonomy is connected with a concept scheme.
    $settings = $config->getConfig();
    if (isset($settings['taxonomies'][$taxonomy->id()])) {
      drupal_set_message(t('The taxonomy %taxonomy is already a completed taxonomy, please use the normal syncronization process.', array('%taxonomy' => $taxonomy->label())), 'error');
      return new RedirectResponse(Url::fromRoute('entity.pp_taxonomy_manager.edit_config_form', array('pp_taxonomy_manager' => $config->id()))->toString());
    }

    $description = t('The taxonomy %taxonomy will be converted into a completed taxonomy. It means that terms will be updated, deleted and/or created and hierarchy will be added.', array('%taxonomy' => $taxonomy->label()));
    $description .= '<br />' . t('This can take a while. Please wait until the synchronization is finished.');

    $form['description'] = array(
      '#markup' => $description,
    );

    // Language mapping.
    $available_languages = \Drupal::languageManager()->getLanguages();
    $default_language = \Drupal::languageManager()->getDefaultLanguage()->getId();

    $form['languages'] = array(
      '#type' => 'item',
      '#title' => t('Map the Drupal languages with the PoolParty project languages'),
      '#tree' => TRUE,
    );

    $pp_languages = $connection->getApi('PPT')->getLanguages();
    $project_language_options = array();
    foreach ($project['availableLanguages'] as $project_language) {
      if (isset($pp_languages[$project_language])) {
        $project_language_options[$project_language] = $pp_languages[$project_language];
      }
    }
    foreach ($available_languages as $lang) {
      if (!$lang->isLocked()) {
        $form['languages'][$lang->getId()] = array(
          '#type' => 'select',
          '#title' => t('Drupal language %language', array('%language' => $lang->getName())),
          '#description' => t('Select the PoolParty project language'),
          '#options' => $project_language_options,
          '#empty_option' => '',
          '#default_value' => isset($project_language_options[$lang->getId()]) ? $lang->getId() : '',
          '#required' => ($lang->getId() == $default_language ? TRUE : FALSE),
        );
      }
    }

    PPTaxonomyManager::addDataPropertySelection($form, []);

    $form['concepts_per_request'] = array(
      '#type' => 'textfield',
      '#title' => t('PoolParty concepts per request'),
      '#description' => t('The number of concepts, that get processed during one HTTP request. (Allowed value range: 1 - 100)') . '<br />' . t('The higher this number is, the less HTTP requests have to be sent to the server until the batch finished updating ALL your concepts, what results in a shorter duration of the bulk updating process.') . '<br />' . t('Numbers too high can result in a timeout, which will break the whole bulk updating process.'),
      '#required' => TRUE,
      '#default_value' => 10,
    );
    $form['save'] = array(
      '#type' => 'submit',
      '#value' => t('Synchronize taxonomy'),
    );
    $form['cancel'] = array(
      '#type' => 'link',
      '#title' => t('Cancel'),
      '#url' => Url::fromRoute('entity.pp_taxonomy_manager.edit_config_form', array('pp_taxonomy_manager' => $config->id())),
      '#suffix' => '</div>',
    );

    $form_state->set('config', $config);
    $form_state->set('taxonomy', $taxonomy);
    $form_state->set('powertagging_config', $powertagging_config);

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
            '%language' => $drupal_languages[$drupal_lang]->name,
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
    /** @var Vocabulary $taxonomy */
    $taxonomy = $form_state->get('taxonomy');
    /** @var \Drupal\powertagging\Entity\PowerTaggingConfig $powertagging_config */
    $powertagging_config = $form_state->get('powertagging_config');

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

    $manager = PPTaxonomyManager::getInstance($config);

    // Add URI and alt. label fields (if not exists) to the taxonomy.
    $manager->adaptTaxonomyFields($taxonomy);

    // Update the connection.
    $manager->addConnection($taxonomy->id(), $powertagging_config->getProjectId(), $languages, $data_properties);

    // Update all taxonomy terms.
    try {
      $manager->updateTaxonomyTerms('powertagging_taxonomy_update', $taxonomy, $powertagging_config->getProjectId(), $languages, $data_properties, TRUE, $concepts_per_request);
    } catch (\Exception $e) {
      drupal_set_message($e->getMessage(), 'error');
    }
    $form_state->setRedirect('entity.pp_taxonomy_manager.edit_config_form', array('pp_taxonomy_manager' => $config->id()));
  }
}
?>