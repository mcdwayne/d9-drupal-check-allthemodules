<?php

/**
 * @file
 * Contains \Drupal\pp_taxonomy_manager\Form\PPTaxonomyManagerConfigForm.
 */

namespace Drupal\pp_taxonomy_manager\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\PluralTranslatableMarkup;
use Drupal\Core\Url;
use Drupal\powertagging\Entity\PowerTaggingConfig;
use Drupal\pp_taxonomy_manager\Entity\PPTaxonomyManagerConfig;
use Drupal\pp_taxonomy_manager\PPTaxonomyManager;
use Drupal\semantic_connector\Entity\SemanticConnectorPPServerConnection;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

class PPTaxonomyManagerConfigForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var PPTaxonomyManagerConfig $entity */
    $entity = $this->entity;

    $settings = $entity->getConfig();

    $connection_overrides = \Drupal::config('semantic_connector.settings')->get('override_connections');
    $overridden_values = array();
    if (isset($connection_overrides[$entity->id()])) {
      $overridden_values = $connection_overrides[$entity->id()];
    }

    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#description' => t('Name of the PoolParty Taxonomy Manager configuration.'). (isset($overridden_values['title']) ? ' <span class="semantic-connector-overridden-value">' . t('Warning: overridden by variable') . '</span>' : ''),
      '#size' => 35,
      '#maxlength' => 255,
      '#default_value' => $entity->getTitle(),
      '#required' => TRUE,
    );

    /** @var SemanticConnectorPPServerConnection $connection */
    $connection = $entity->getConnection();
    // Get the project title of the currently configured project.
    $project_title = '';
    if ($settings['root_level'] != 'project') {
      $project_title = '<invalid project selected>';
      $pp_server_projects = $entity->getConnection()
        ->getApi('PPT')
        ->getProjects();
      foreach ($pp_server_projects as $pp_server_project) {
        if ($pp_server_project['id'] == $entity->getProjectId()) {
          $project_title = $pp_server_project['title'];
          break;
        }
      }
    }

    // Add information about the connection.
    $connection_markup = '';
    // Check the PoolParty server version if required.
    if (\Drupal::config('semantic_connector.settings')->get('version_checking')) {
      $version_messages = array();

      $ppx_api_version_info = $connection->getVersionInfo('PPX');
      if (version_compare($ppx_api_version_info['installed_version'], $ppx_api_version_info['latest_version'], '<')) {
        $version_messages[] = t('The connected PoolParty server is not up to date. You are currently running version %installedversion, upgrade to version %latestversion to enjoy the new features.', array('%installedversion' => $ppx_api_version_info['installed_version'], '%latestversion' => $ppx_api_version_info['latest_version']));
      }

      if (!empty($version_messages)) {
        $connection_markup .= '<div class="messages warning"><div class="message">' . implode('</div><div class="message">', $version_messages) . '</div></div>';
      }
    }
    $connection_markup .= '<p id="pp-taxonomy-manager-connection-info">' . t('Connected PoolParty server') . ': <b>' . $connection->getTitle() . ' (' . $connection->getUrl() . ')</b><br />'
      . t('Selected taxonomy root level') . ': <b>' . ($settings['root_level'] == 'project' ? t('Project') : t('Concept Scheme')) . '</b><br />'
      . (!empty($project_title) ? t('Selected project') . ': <b>' . $project_title . '</b><br />' : '')
      . Link::fromTextAndUrl(t('Change the connected PoolParty server or project'), Url::fromRoute('entity.pp_taxonomy_manager.edit_form', array('pp_taxonomy_manager' => $entity->id())))->toString() . '</p>';
    $form['pp_connection_markup'] = array(
      '#type' => 'markup',
      '#markup' => $connection_markup,
    );

    // Get all available Drupal taxonomies.
    $taxonomies = Vocabulary::loadMultiple();

    // Get all taxonomies that are connected with a PowerTagging configuration.
    $powertaggings = array();
    $taxonomy_powertagging = array();
    if (\Drupal::moduleHandler()->moduleExists('powertagging')) {
      $powertagging_configs = PowerTaggingConfig::loadMultiple();
      /** @var PowerTaggingConfig $powertagging_config */
      foreach ($powertagging_configs as $powertagging_config) {
        $powertagging_config_settings = $powertagging_config->getConfig();
        if (isset($powertagging_config_settings['project'])) {
          $powertaggings[$powertagging_config->id()] = $powertagging_config;
          $vid = $powertagging_config_settings['project']['taxonomy_id'];
          $taxonomy_powertagging[$vid][] = $powertagging_config->id();
        }
      }
    }

    // Create a table with all existing and possible connections between Drupal
    // taxonomies and PoolParty concepts schemes for the selected project.

    // Create the table rows for all available concept schemes / projects not yet connected.
    $connected = array();
    $powertagging_ids = array();
    $rows = array();
    $connected_rows = array();
    if ($settings['root_level'] == 'project') {
      $projects = $entity->getConnection()
        ->getApi('PPT')
        ->getProjects();

      $used_projects = PPTaxonomyManager::getUsedProjects($entity->getConnection()->getUrl(), array($entity->id()));
      foreach ($projects as $project) {
        // The project is not yet used by a different configuration.
        if (!isset($used_projects[$project['id']])) {
          $operations = array();
          if (in_array($project['id'], $settings['taxonomies'])) {
            $connected[$project['id']] = $project;
            continue;
          }

          // Check if the project is in use by a PowerTagging configuration.
          $powertagging_id = 0;
          foreach ($powertaggings as $powertagging_config) {
            if ($entity->getConnection()->getUrl() == $powertagging_config->getConnection()->getUrl() && $project['id'] == $powertagging_config->getProjectId()) {
              $powertagging_id = $powertagging_config->powertagging_id;
              break;
            }
          }

          if ($powertagging_id == 0) {
            $operations[] = '&lArr; ' . Link::fromTextAndUrl(t('Import into Drupal'), Url::fromRoute('entity.pp_taxonomy_manager.import', array('config' => $entity->id()), array('query' => array('uri' => $project['id']))))->toString();
            $rows[] = array(
              new FormattableMarkup('<div class="semantic-connector-italic">' . t('not yet connected') . '</div>', array()),
              new FormattableMarkup(implode(' | ', $operations), array()),
              Link::fromTextAndUrl($project['title'], Url::fromUri($project['uri'], array('attributes' => array('title' => (isset($project['description']) && !empty($project['description'])) ? $project['description'] : NULL)))),
            );
          }
          else {
            $powertagging_ids[$project['id']] = $powertagging_id;
            $powertagging_config = $powertaggings[$powertagging_id];
            $powertagging_config_settings = $powertagging_config->getConfig();
            /** @var Vocabulary $taxonomy */
            $taxonomy = $taxonomies[$powertagging_config_settings['projects'][$powertagging_config->getProjectId()]['taxonomy_id']];
            $operations[] = '&lArr; ' . Link::fromTextAndUrl(t('Make complete taxonomy'), Url::fromRoute('entity.pp_taxonomy_manager.powertagging_taxonomy_update', array('config' => $entity->id(), 'powertagging_config' => $powertagging_id)))->toString();
            $rows[] = array(
              Link::fromTextAndUrl($taxonomy->label(), Url::fromRoute('entity.taxonomy_vocabulary.edit_form', array('taxonomy_vocabulary' => $taxonomy->id()), array('attributes' => array('title' => $taxonomy->getDescription()))))->toString() . t(' (created by PowerTagging configuration "%powertaggingtitle")', array('%powertaggingtitle' => $powertagging_config->getTitle())),
              new FormattableMarkup(implode(' | ', $operations), array()),
              Link::fromTextAndUrl($project['title'], Url::fromUri($project['uri'], array('attributes' => array('title' => (isset($project['description']) && !empty($project['description'])) ? $project['description'] : NULL)))),
            );
          }
        }
        // The project is already used by a different configuration.
        else {
          $rows[] = array(
            '-',
            new FormattableMarkup('<div class="semantic-connector-italic">' . t('Already in use by PoolParty Taxonomy Manager configuration "%config_title"', array('%config_title' => $used_projects[$project['id']])) . '</div>', array()),
            Link::fromTextAndUrl($project['title'], Url::fromUri($project['uri'], array('attributes' => array('title' => (isset($project['description']) && !empty($project['description'])) ? $project['description'] : NULL)))),
          );
        }
      }
    }
    // Load the Concept Schemes.
    else {
      $concept_schemes = $entity->getConnection()
        ->getApi('PPT')
        ->getConceptSchemes($entity->getProjectId());

      foreach ($concept_schemes as $scheme) {
        $operations = array();
        if (in_array($scheme['uri'], $settings['taxonomies'])) {
          $connected[$scheme['uri']] = $scheme;
          continue;
        }

        $operations[] = '&lArr; ' . Link::fromTextAndUrl(t('Import into Drupal'), Url::fromRoute('entity.pp_taxonomy_manager.import', array('config' => $entity->id()), array('query' => array('uri' => $scheme['uri']))))->toString();
        $rows[] = array(
          new FormattableMarkup('<div class="semantic-connector-italic">' . t('not yet connected') . '</div>', array()),
          new FormattableMarkup(implode(' | ', $operations), array()),
          Link::fromTextAndUrl($scheme['title'], Url::fromUri($scheme['uri'], array('attributes' => array('title' => ((isset($scheme['descriptions']) && !empty($scheme['descriptions'])) ? $scheme['descriptions'][0] : NULL))))),
        );
      }
    }

    // Check which taxonomies are already used by other PoolParty Taxonomy
    // Manager configurations.
    $used_taxonomies = array();
    $taxonomy_manager_configs = PPTaxonomyManagerConfig::loadMultiple();
    /** @var PPTaxonomyManagerConfig $taxonomy_manager_config */
    foreach ($taxonomy_manager_configs as $taxonomy_manager_config) {
      if ($taxonomy_manager_config->id() != $entity->id()) {
        $taxonomy_manager_config_settings = $taxonomy_manager_config->getConfig();
        foreach (array_keys($taxonomy_manager_config_settings['taxonomies']) as $vid) {
          $used_taxonomies[$vid] = $taxonomy_manager_config->getTitle();
        }
      }
    }

    // Create the table rows for all connected and disconnected Drupal taxonomies.
    /** @var Vocabulary $taxonomy */
    foreach ($taxonomies as $taxonomy) {
      $operations = array();
      // The taxonomy is not used by a different configuration.
      if (!isset($used_taxonomies[$taxonomy->id()])) {
        if (isset($settings['taxonomies'][$taxonomy->id()])) {
          $last_log = $entity->getLastLog($taxonomy->id());
          $sync_required = FALSE;

          // Create rows from connected taxonomies.
          if (isset($connected[$settings['taxonomies'][$taxonomy->id()]])) {
            $root_object = $connected[$settings['taxonomies'][$taxonomy->id()]];
            $action_update_taxonomy = Url::fromRoute('entity.pp_taxonomy_manager.sync' , array('config' => $entity->id(), 'taxonomy' => $taxonomy->id()));
            // If that taxonomy is already used for tagging hashes became
            // incorrect, therefore the PowerTagging update routing is
            // required.
            if (isset($taxonomy_powertagging[$taxonomy->id()]) && !empty($taxonomy_powertagging[$taxonomy->id()])) {
              $action_update_taxonomy = Url::fromRoute('entity.powertagging.update_vocabulary', ['powertagging_config' => $taxonomy_powertagging[$taxonomy->id()][0]]);
            }

            $operations[] = '&lArr; ' . Link::fromTextAndUrl(t('Sync from PoolParty'), $action_update_taxonomy)->toString();
            $operations[] = Link::fromTextAndUrl(t('Disconnect from PoolParty'), Url::fromRoute('entity.pp_taxonomy_manager.disconnect' , array('config' => $entity->id(), 'taxonomy' => $taxonomy->id())))->toString() . ' &rArr;';
            $concept_scheme = Link::fromTextAndUrl($root_object['title'], Url::fromUri($root_object['uri'], array(
              'absolut' => TRUE,
              'attributes' => array('title' => (isset($root_object['description']) ? $root_object['description'] : (isset($root_object->descriptions) && !empty($root_object['descriptions']) ? $root_object['descriptions'][0] : NULL))),
            )));

            // Check if there were any PoolPart side changes.
            $changes = PPTaxonomyManager::checkPPChanges($entity, ($settings['root_level'] == 'project' ? $root_object['id'] : $entity->getProjectId()), $taxonomy->id());
            $sync_required = !empty($changes);
          }
          else {
            $root_level = ($settings['root_level'] == 'project') ? 'project' : 'concept scheme';
            $operations[] = Link::fromTextAndUrl(t('Disconnect from PoolParty'), Url::fromRoute('entity.pp_taxonomy_manager.disconnect' , array('config' => $entity->id(), 'taxonomy' => $taxonomy->id())))->toString() . ' &rArr;';
            $concept_scheme = t('The %root_level could not be found in PoolParty.<br />Make sure to delete the connection if the concept scheme was deleted on purpose.', array('%root_level' => $root_level));
          }
          $taxonomy_link = Link::fromTextAndUrl($taxonomy->label(), Url::fromRoute('entity.taxonomy_vocabulary.edit_form', array('taxonomy_vocabulary' => $taxonomy->id()), array('attributes' => array('title' => $taxonomy->getDescription()))))->toString();
          if (isset($taxonomy_powertagging[$taxonomy->id()])) {
            $powertagging_config_labels = array();
            foreach ($taxonomy_powertagging[$taxonomy->id()] as $powertagging_id) {
              $powertagging_config = $powertaggings[$powertagging_id];
              $powertagging_config_labels[] = $powertagging_config->getTitle();
            }
            $config_plural = new PluralTranslatableMarkup(count($powertagging_config_labels), 'PowerTagging configuration', 'PowerTagging configurations');
            $taxonomy_link .= ' <span class="semantic-connector-italic">' . t('(used by @configpart "%powertaggingtitle")', array('@configpart' => $config_plural, '%powertaggingtitle' => implode('", "', $powertagging_config_labels))) . '</span>';
          }

          $connected_rows[] = array(
            new FormattableMarkup($taxonomy_link, array()),
            new FormattableMarkup(implode(' | ', $operations), array()),
            $concept_scheme,
            (($last_log !== FALSE) ? t('%sorttimestampstarted: @starttime%brfinished: @endtime%brby: %username', array('@starttime' => \Drupal::service('date.formatter')->format($last_log['start_time'], 'short'), '@endtime' => \Drupal::service('date.formatter')->format($last_log['end_time'], 'short'), '%username' => new FormattableMarkup(Link::fromTextAndUrl($last_log['name'], Url::fromRoute('entity.user.canonical', array('user' => $last_log['uid'])))->toString(), array()), '%br' => new FormattableMarkup('<br />', array()), '%sorttimestamp' => new FormattableMarkup('<span style="display:none;">' . $last_log['start_time'] . '</span>', array()))) : ''),
            new FormattableMarkup((($sync_required) ? '<b>' . t('YES') . '</b>' : t('no')), array()),
          );
        }
        elseif (!isset($taxonomy_powertagging[$taxonomy->id()])) {
          // Create rows from disconnected taxonomies.
          $operations[] = Link::fromTextAndUrl(t('Export to PoolParty'), Url::fromRoute('entity.pp_taxonomy_manager.export' , array('config' => $entity->id(), 'taxonomy' => $taxonomy->id())))->toString() . ' &rArr;';
          $rows[] = array(
            Link::fromTextAndUrl($taxonomy->label(), Url::fromRoute('entity.taxonomy_vocabulary.edit_form', array('taxonomy_vocabulary' => $taxonomy->id()), array('attributes' => array('title' => $taxonomy->getDescription())))),
            new FormattableMarkup(implode(' | ', $operations), array()),
            new FormattableMarkup('<div class="semantic-connector-italic">' . t('not yet connected') . '</div>', array()),
          );
        }
      }
      // The taxonomy is already used by a different configuration.
      else {
        $rows[] = array(
          Link::fromTextAndUrl($taxonomy->label(), Url::fromRoute('entity.taxonomy_vocabulary.edit_form', array('taxonomy_vocabulary' => $taxonomy->id()), array('attributes' => array('title' => $taxonomy->getDescription())))),
          new FormattableMarkup('<div class="semantic-connector-italic">' . t('Already in use by PoolParty Taxonomy Manager configuration "%config_title"', array('%config_title' => $used_taxonomies[$taxonomy->id()])) . '</div>', array()),
          '-',
        );
      }
    }

    // Already synchronized Drupal taxonomies.
    if (!empty($connected_rows)) {
      // Create the table for the connections.
      $table = array();
      $table['connections'] = array(
        '#theme' => 'table',
        '#header' => array(
          t('Drupal taxonomy'),
          t('Operations'),
          $settings['root_level'] == 'project' ? t('PoolParty project') : t('PoolParty concept scheme'),
          t('Last Sync'),
          t('Sync required'),
        ),
        '#rows' => $connected_rows,
        '#attributes' => array(
          'id' => 'pp-taxonomy-manager-synced-table',
          'class' => array('semantic-connector-tablesorter'),
        ),
      );

      $form['connections'] = array(
        '#type' => 'item',
        '#title' => '<h3 class="semantic-connector-table-title">' . t('Interconnection between the Drupal taxonomies and the PoolParty concept schemes') . '</h3>',
        '#markup' => \Drupal::service('renderer')->render($table),
      );
    }

    // Potential synchronization candidates.
    if (!empty($rows)) {
      $table = array();
      $table['connections'] = array(
        '#theme' => 'table',
        '#header' => array(
          t('Drupal taxonomy'),
          t('Operations'),
          $settings['root_level'] == 'project' ? t('PoolParty project') : t('PoolParty concept scheme'),
        ),
        '#rows' => $rows,
        '#attributes' => array(
          'id' => 'pp-taxonomy-manager-interconnection-table',
          'class' => array('semantic-connector-tablesorter'),
        ),
      );

      $form['potential_connections'] = array(
        '#type' => 'item',
        '#title' => '<h3 class="semantic-connector-table-title">' . t('Drupal taxonomies already connected with a PoolParty project via PowerTagging module') . '</h3>',
        '#markup' => \Drupal::service('renderer')->render($table),
      );
    }

    // Add CSS and JS.
    $form['#attached'] = array(
      'library' =>  array(
        'pp_taxonomy_manager/admin_area',
        'semantic_connector/tablesorter',
      ),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var PPTaxonomyManagerConfig $entity */
    $entity = $this->entity;

    // Update and save the entity.
    $entity->set('title', $form_state->getValue('title'));
    $entity->save();

    drupal_set_message(t('PoolParty Taxonomy Manager configuration %title has been updated.', array('%title' => $entity->getTitle())));
    $form_state->setRedirectUrl(Url::fromRoute('entity.pp_taxonomy_manager.collection'));
  }
}