<?php
/**
 * @file
 * Contains \Drupal\smart_glossary\Form\SmartGlossaryConfigForm.
 */

namespace Drupal\smart_glossary\Form;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\semantic_connector\SemanticConnector;
use Drupal\smart_glossary\Entity\SmartGlossaryConfig;
use Drupal\smart_glossary\SmartGlossary;

class SmartGlossaryConfigForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\smart_glossary\Entity\SmartGlossaryConfig $entity */
    $entity = $this->entity;
    $connection = $entity->getConnection();

    $form['title'] = array(
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#description' => t('Name of the Smart Glossary configuration'),
      '#size' => 35,
      '#maxlength' => 60,
      '#required' => TRUE,
      '#default_value' => $entity->getTitle(),
    );

    $form['base_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Base Path'),
      '#description' => 'The URL path where the glossary can be found. The default path is "glossary".',
      '#default_value' => $entity->getBasePath(),
      '#required' => TRUE,
    );

    // Define the container for the vertical tabs.
    $form['settings'] = array(
      '#type' => 'vertical_tabs',
    );

    // Tab: Connection settings.
    $form['connection_settings'] = array(
      '#type' => 'details',
      '#title' => t('Connection settings'),
      '#group' => 'settings',
    );

    $form['connection_settings']['connection_id'] = array(
      '#type' => 'hidden',
      '#value' => $entity->getConnectionID(),
    );

    $form['connection_settings']['sparql_endpoint'] = array(
      '#type' => 'fieldset',
      '#title' => t('SPARQL Endpoint'),
      '#description' => t('URL and credentials for the SPARQL-Endpoint'),
    );

    $sparql_endpoints = SemanticConnector::getConnectionsByType('sparql_endpoint');
    $servers = SemanticConnector::getConnectionsByType('pp_server');

    if (!empty($sparql_endpoints)) {
      $connection_options = array();
      $already_added_urls = array();

      // Add all SPARQL-endpoints of a configured PoolParty server first.
      /** @var \Drupal\semantic_connector\Entity\SemanticConnectorPPServerConnection $server */
      foreach ($servers as $server) {
        $server_config = $server->getConfig();
        if (isset($server_config['projects'])) {
          $server_project_uris = array();
          foreach ($server_config['projects'] as $project) {
            if (isset($project['sparql_endpoint_url'])) {
              $server_project_uris[] = $project['sparql_endpoint_url'];
            }
          }
          if (!empty($server_project_uris)) {
            $already_added_urls = array_merge($already_added_urls, $server_project_uris);
            /** @var \Drupal\semantic_connector\Entity\SemanticConnectorSparqlEndpointConnection $sparql_endpoint */
            foreach ($sparql_endpoints as $sparql_endpoint) {
              if (in_array($sparql_endpoint->getUrl(), $server_project_uris)) {
                $credentials = $sparql_endpoint->getCredentials();
                $endpoint_key = array(
                  $sparql_endpoint->getTitle(),
                  $sparql_endpoint->getUrl(),
                  $credentials['username'],
                  $credentials['password'],
                );
                $connection_options[$server->getTitle()][implode('|', $endpoint_key)] = $sparql_endpoint->getTitle();
              }
            }
          }
        }
      }

      // Add all customized SPARQL-endpoints afterwards.
      foreach ($sparql_endpoints as $sparql_endpoint) {
        if (!in_array($sparql_endpoint->getUrl(), $already_added_urls)) {
          $credentials = $sparql_endpoint->getCredentials();
          $endpoint_key = array(
            $sparql_endpoint->getTitle(),
            $sparql_endpoint->getUrl(),
            $credentials['username'],
            $credentials['password'],
          );
          $connection_options['Custom SPARQL-Endpoints'][implode('|', $endpoint_key)] = $sparql_endpoint->getTitle();
        }
      }

      $form['connection_settings']['sparql_endpoint']['load_connection'] = array(
        '#type' => 'select',
        '#title' => t('Load an available SPARQL endpoint'),
        '#options' => $connection_options,
        '#empty_option' => '',
        '#default_value' => '',
      );

      $form['connection_settings']['sparql_endpoint']['load_connection_button'] = array(
        '#type' => 'button',
        '#value' => t('Load'),
        '#attributes' => array(
          'onclick' => '
          var connection_value = (jQuery(\'#edit-load-connection\').val());
          if (connection_value.length > 0) {
            var connection_details = connection_value.split(\'|\');
            jQuery(\'#edit-endpoint-title\').val(connection_details[0]);
            jQuery(\'#edit-url\').val(connection_details[1]);
            jQuery(\'#edit-username\').val(connection_details[2]);
            jQuery(\'#edit-password\').val(connection_details[3]);
          }
          return false;',
        ),
      );
    }

    $form['connection_settings']['sparql_endpoint']['endpoint_title'] = array(
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#description' => t('A short title for the SPARQL Endpoint'),
      '#required' => TRUE,
      '#default_value' => $connection->getTitle(),
    );
    $form['connection_settings']['sparql_endpoint']['url'] = array(
      '#type' => 'textfield',
      '#title' => t('Url'),
      '#required' => TRUE,
      '#default_value' => $connection->getUrl(),
    );

    $credentials = $connection->getCredentials();
    $form['connection_settings']['sparql_endpoint']['credentials'] = array(
      '#type' => 'fieldset',
      '#title' => t('Credentials'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['connection_settings']['sparql_endpoint']['credentials']['username'] = array(
      '#type' => 'textfield',
      '#title' => t('Username'),
      '#default_value' => $credentials['username'],
    );
    $form['connection_settings']['sparql_endpoint']['credentials']['password'] = array(
      '#type' => 'textfield',
      '#title' => t('Password'),
      '#default_value' => $credentials['password'],
    );

    // Tab: Language and wording settings.
    $form['language_mapping'] = array(
      '#type' => 'details',
      '#title' => t('Language and wording settings'),
      '#tree' => TRUE,
      '#group' => 'settings',
    );

    $language_mapping = $entity->getLanguageMapping();
    $installed_languages = \Drupal::languageManager()->getLanguages();
    // array_unshift($installed_languages, (object) array('language' => '', 'name' => t('Language neutral')));

    /** @var \Drupal\Core\Language\LanguageInterface $installed_language */
    foreach ($installed_languages as $installed_language) {
      $lang_code = $installed_language->getId();
      $mapping = $language_mapping[$lang_code];

      $form['language_mapping']['languages_' . $lang_code] = array(
        '#type' => 'fieldset',
        '#title' => t('Mapping for %language', array('%language' => $installed_language->getName())),
        '#tree' => TRUE,
      );
      $form['language_mapping']['languages_' . $lang_code]['page_language'] = array(
        '#type' => 'value',
        '#value' => $lang_code,
      );
      $form['language_mapping']['languages_' . $lang_code]['glossary_languages'] = array(
        '#type' => 'textfield',
        '#title' => t('Glossary language codes'),
        '#default_value' => implode(', ', $mapping['glossary_languages']),
        '#description' => t('Enter language codes comma separated (e.g. en, de, fr).'),
        '#required' => TRUE,
      );

      // Wording settings for every selected language.
      $wording = $mapping['wording'];
      $form['language_mapping']['languages_' . $lang_code]['wording'] = array(
        '#type' => 'fieldset',
        '#title' => t('Wording'),
        '#collapsible' => TRUE,
        '#collapsed' => TRUE,
      );

      $form['language_mapping']['languages_' . $lang_code]['wording']['glossaryRoot'] = array(
        '#type' => 'textfield',
        '#title' => t('Glossary Root'),
        '#default_value' => $wording['glossaryRoot'],
      );
      $form['language_mapping']['languages_' . $lang_code]['wording']['homeButton'] = array(
        '#type' => 'textfield',
        '#title' => t('Home Button'),
        '#default_value' => $wording['homeButton'],
      );
      $form['language_mapping']['languages_' . $lang_code]['wording']['showDefinitionButton'] = array(
        '#type' => 'textfield',
        '#title' => t('Show definition button'),
        '#default_value' => $wording['showDefinitionButton'],
      );
      $form['language_mapping']['languages_' . $lang_code]['wording']['noDefinition'] = array(
        '#type' => 'textfield',
        '#title' => t('No definition'),
        '#default_value' => $wording['noDefinition'],
      );
      $form['language_mapping']['languages_' . $lang_code]['wording']['showContentButton'] = array(
        '#type' => 'textfield',
        '#title' => t('Show content button'),
        '#default_value' => $wording['showContentButton'],
      );
      $form['language_mapping']['languages_' . $lang_code]['wording']['legendConceptScheme'] = array(
        '#type' => 'textfield',
        '#title' => t('Legend: Concept schemes'),
        '#default_value' => $wording['legendConceptScheme'],
      );
      $form['language_mapping']['languages_' . $lang_code]['wording']['legendParent'] = array(
        '#type' => 'textfield',
        '#title' => t('Legend: Broader'),
        '#default_value' => $wording['legendParent'],
      );
      $form['language_mapping']['languages_' . $lang_code]['wording']['legendChildren'] = array(
        '#type' => 'textfield',
        '#title' => t('Legend: Narrower'),
        '#default_value' => $wording['legendChildren'],
      );
      $form['language_mapping']['languages_' . $lang_code]['wording']['legendRelated'] = array(
        '#type' => 'textfield',
        '#title' => t('Legend: Related'),
        '#default_value' => $wording['legendRelated'],
      );
      $form['language_mapping']['languages_' . $lang_code]['wording']['currentConcept'] = array(
        '#type' => 'textfield',
        '#title' => t('Current concept'),
        '#default_value' => $wording['currentConcept'],
      );
      $form['language_mapping']['languages_' . $lang_code]['wording']['helpButton'] = array(
        '#type' => 'textfield',
        '#title' => t('Help button'),
        '#default_value' => $wording['helpButton'],
      );
      $form['language_mapping']['languages_' . $lang_code]['wording']['helpText'] = array(
        '#type' => 'text_format',
        '#title' => t('Help text'),
        '#default_value' => $wording['helpText']['value'],
        '#format' => $wording['helpText']['format'],
      );
    }

    // Tab: Visual Mapper settings
    $form['visual_mapper_settings'] = array(
      '#type' => 'details',
      '#title' => t('Visual Mapper settings'),
      '#tree' => TRUE,
      '#group' => 'settings',
    );

    if (SemanticConnector::visualMapperUsable()) {
      $visual_mapper_settings = $entity->getVisualMapperSettings();

      // Dimension selection for the elements of the Visual Mapper.
      $form['visual_mapper_settings']['enabled'] = array(
        '#type' => 'checkbox',
        '#title' => t('Show Visual Mapper'),
        '#default_value' => $visual_mapper_settings['enabled'],
        '#parents' => array('visual_mapper_settings', 'enabled'),
      );
      // Dimension selection for the elements of the Visual Mapper.
      $form['visual_mapper_settings']['width'] = array(
        '#type' => 'textfield',
        '#title' => t('Width in "px"'),
        '#default_value' => $visual_mapper_settings['width'],
        '#parents' => array('visual_mapper_settings', 'width'),
      );
      $form['visual_mapper_settings']['height'] = array(
        '#type' => 'textfield',
        '#title' => t('Height in "px"'),
        '#default_value' => $visual_mapper_settings['height'],
        '#parents' => array('visual_mapper_settings', 'height'),
      );

      $form['visual_mapper_settings']['chartTypes'] = array(
        '#type' => 'checkboxes',
        '#title' => t('Selectable visualisation types'),
        '#options' => array(
          'spider' => 'Visual Mapper (circle visualisation)',
          'tree' => 'Tree View',
        ),
        '#default_value' => $visual_mapper_settings['chartTypes'],
        '#description' => t('Selecting multiple visualisation types will allow the user to switch between the visualisation types.'),
        '#required' => TRUE,
        '#parents' => array('visual_mapper_settings', 'chartTypes'),
      );

      $form['visual_mapper_settings']['enableExport'] = array(
        '#type' => 'checkbox',
        '#title' => t('Enable exporting'),
        '#default_value' => $visual_mapper_settings['export']['enabled'],
        '#description' => t('Enables the export of the currently displayed visualisation as an image.'),
        '#parents' => array('visual_mapper_settings', 'export', 'enabled'),
      );

      $form['visual_mapper_settings']['transitionDuration'] = array(
        '#type' => 'textfield',
        '#title' => t('Transition duration'),
        '#default_value' => $visual_mapper_settings['transitionDuration'],
        '#description' => t('The duration of transitions in the visualisation in ms.') . '<br />' . t('Value 0 removes all transitions of the visualisation.'),
        '#parents' => array('visual_mapper_settings', 'transitionDuration'),
      );

      // Color definitions for the Visual Mapper.
      $form['visual_mapper_settings']['colors'] = array(
        '#type' => 'details',
        '#title' => t('Color Options'),
      );

      $form['visual_mapper_settings']['colors']['backColor'] = array(
        '#type' => 'textfield',
        '#title' => t('Background-color of the Visual Mapper'),
        '#default_value' => $visual_mapper_settings['backColor'],
        '#parents' => array('visual_mapper_settings', 'backColor'),
      );
      $form['visual_mapper_settings']['colors']['backColorItems'] = array(
        '#type' => 'textfield',
        '#title' => t('Background-color of items inside the Visual Mapper'),
        '#default_value' => $visual_mapper_settings['backColorItems'],
        '#parents' => array('visual_mapper_settings', 'backColorItems'),
      );
      $form['visual_mapper_settings']['colors']['headerColor'] = array(
        '#type' => 'textfield',
        '#title' => t('Header-color'),
        '#default_value' => $visual_mapper_settings['headerColor'],
        '#parents' => array('visual_mapper_settings', 'headerColor'),
      );
      $form['visual_mapper_settings']['colors']['inactiveColor'] = array(
        '#type' => 'textfield',
        '#title' => t('Inactive color'),
        '#description' => t('The color used for inactive elements like inactive pagination and smaller objects like the inner circle of the spider chart.'),
        '#default_value' => $visual_mapper_settings['inactiveColor'],
        '#parents' => array('visual_mapper_settings', 'inactiveColor'),
      );
      $form['visual_mapper_settings']['colors']['selectedColor'] = array(
        '#type' => 'textfield',
        '#title' => t('Selected items color'),
        '#description' => t('The color used for selected elements like the selected concept in the tree view.'),
        '#default_value' => $visual_mapper_settings['selectedColor'],
        '#parents' => array('visual_mapper_settings', 'selectedColor'),
      );

      // Brights colors.
      $form['visual_mapper_settings']['colors']['brightColors'] = array(
        '#type' => 'fieldset',
        '#title' => t('Bright Colors'),
      );

      $form['visual_mapper_settings']['colors']['brightColors']['parent'] = array(
        '#type' => 'textfield',
        '#title' => t('Parent'),
        '#default_value' => $visual_mapper_settings['brightColors']['parent'],
        '#parents' => array(
          'visual_mapper_settings',
          'brightColors',
          'parent',
        ),
      );
      $form['visual_mapper_settings']['colors']['brightColors']['children'] = array(
        '#type' => 'textfield',
        '#title' => t('Children'),
        '#default_value' => $visual_mapper_settings['brightColors']['children'],
        '#parents' => array(
          'visual_mapper_settings',
          'brightColors',
          'children',
        ),
      );
      $form['visual_mapper_settings']['colors']['brightColors']['related'] = array(
        '#type' => 'textfield',
        '#title' => t('Related'),
        '#default_value' => $visual_mapper_settings['brightColors']['related'],
        '#parents' => array(
          'visual_mapper_settings',
          'brightColors',
          'related',
        ),
      );
      $form['visual_mapper_settings']['colors']['brightColors']['conceptScheme'] = array(
        '#type' => 'textfield',
        '#title' => t('Concept Scheme'),
        '#default_value' => $visual_mapper_settings['brightColors']['conceptScheme'],
        '#parents' => array(
          'visual_mapper_settings',
          'brightColors',
          'conceptScheme',
        ),
      );

      // Dark colors.
      $form['visual_mapper_settings']['colors']['darkColors'] = array(
        '#type' => 'fieldset',
        '#title' => t('Dark Colors'),
      );

      $form['visual_mapper_settings']['colors']['darkColors']['parent'] = array(
        '#type' => 'textfield',
        '#title' => t('Parent'),
        '#default_value' => $visual_mapper_settings['darkColors']['parent'],
        '#parents' => array(
          'visual_mapper_settings',
          'darkColors',
          'parent',
        ),
      );
      $form['visual_mapper_settings']['colors']['darkColors']['children'] = array(
        '#type' => 'textfield',
        '#title' => t('Children'),
        '#default_value' => $visual_mapper_settings['darkColors']['children'],
        '#parents' => array(
          'visual_mapper_settings',
          'darkColors',
          'children',
        ),
      );
      $form['visual_mapper_settings']['colors']['darkColors']['related'] = array(
        '#type' => 'textfield',
        '#title' => t('Related'),
        '#default_value' => $visual_mapper_settings['darkColors']['related'],
        '#parents' => array(
          'visual_mapper_settings',
          'darkColors',
          'related',
        ),
      );
      $form['visual_mapper_settings']['colors']['darkColors']['conceptScheme'] = array(
        '#type' => 'textfield',
        '#title' => t('Concept Scheme'),
        '#default_value' => $visual_mapper_settings['darkColors']['conceptScheme'],
        '#parents' => array(
          'visual_mapper_settings',
          'darkColors',
          'conceptScheme',
        ),
      );

      // Spider chart settings.
      $form['visual_mapper_settings']['spiderChart'] = array(
        '#type' => 'details',
        '#title' => t('Advanced Chart Configuration "Visual Mapper"'),
        '#states' => array(
          'visible' => array(
            ':input[name="visual_mapper_settings[chartTypes][spider]"]' => array('checked' => TRUE),
          ),
        ),
      );

      // Legend settings.
      $form['visual_mapper_settings']['spiderChart']['legend'] = array(
        '#type' => 'details',
        '#title' => t('Legend Settings'),
      );

      $form['visual_mapper_settings']['spiderChart']['legend']['legendSize'] = array(
        '#type' => 'textfield',
        '#title' => t('Legend size'),
        '#description' => t('The font-size and space between every line of the legend in "px"'),
        '#default_value' => $visual_mapper_settings['spiderChart']['legendSize'],
        '#parents' => array(
          'visual_mapper_settings',
          'spiderChart',
          'legendSize',
        ),
      );
      $form['visual_mapper_settings']['spiderChart']['legend']['legendPositionX'] = array(
        '#type' => 'select',
        '#title' => t('Legend position X'),
        '#options' => array(
          'left' => t('Left'),
          'right' => t('Right'),
        ),
        '#description' => t('The horizontal position of the legend in reference to the chart itself.'),
        '#default_value' => $visual_mapper_settings['spiderChart']['legendPositionX'],
        '#parents' => array(
          'visual_mapper_settings',
          'spiderChart',
          'legendPositionX',
        ),
      );
      $form['visual_mapper_settings']['spiderChart']['legend']['legendPositionY'] = array(
        '#type' => 'select',
        '#title' => t('Legend position Y'),
        '#options' => array(
          'top' => t('Top'),
          'bottom' => t('Bottom'),
        ),
        '#description' => t('The vertical position of the legend in reference to the chart itself.'),
        '#default_value' => $visual_mapper_settings['spiderChart']['legendPositionY'],
        '#parents' => array(
          'visual_mapper_settings',
          'spiderChart',
          'legendPositionY',
        ),
      );
      $form['visual_mapper_settings']['spiderChart']['legend']['legendStyle'] = array(
        '#type' => 'select',
        '#title' => t('Legend style'),
        '#options' => array(
          'none' => t('No legend required'),
          'list' => t('As a list'),
          'circle' => t('As a labeled circle'),
        ),
        '#description' => t('The way the legend has to be presented to the users.'),
        '#default_value' => $visual_mapper_settings['spiderChart']['legendStyle'],
        '#parents' => array(
          'visual_mapper_settings',
          'spiderChart',
          'legendStyle',
        ),
      );

      $form['visual_mapper_settings']['spiderChart']['rootInnerRadius'] = array(
        '#type' => 'textfield',
        '#title' => t('Root-Circle: Inner radius'),
        '#description' => t('The inner radius of the root-circle in "px"'),
        '#default_value' => $visual_mapper_settings['spiderChart']['rootInnerRadius'],
        '#parents' => array(
          'visual_mapper_settings',
          'spiderChart',
          'rootInnerRadius',
        ),
      );
      $form['visual_mapper_settings']['spiderChart']['rootOuterRadius'] = array(
        '#type' => 'textfield',
        '#title' => t('Root-circle: Outer radius'),
        '#description' => t('The outer radius of the root-circle in "px"'),
        '#default_value' => $visual_mapper_settings['spiderChart']['rootOuterRadius'],
        '#parents' => array(
          'visual_mapper_settings',
          'spiderChart',
          'rootOuterRadius',
        ),
      );
      $form['visual_mapper_settings']['spiderChart']['rootFontSize'] = array(
        '#type' => 'textfield',
        '#title' => t('Root-circle: Font-size'),
        '#description' => t('Font-size of the root-circle-text in "px"'),
        '#default_value' => $visual_mapper_settings['spiderChart']['rootFontSize'],
        '#parents' => array(
          'visual_mapper_settings',
          'spiderChart',
          'rootFontSize',
        ),
      );
      $form['visual_mapper_settings']['spiderChart']['rootCharactersPerLine'] = array(
        '#type' => 'textfield',
        '#title' => t('Root-circle: Characters per line'),
        '#description' => t('The maximum characters per line in the text of the root-circle'),
        '#default_value' => $visual_mapper_settings['spiderChart']['rootCharactersPerLine'],
        '#parents' => array(
          'visual_mapper_settings',
          'spiderChart',
          'rootCharactersPerLine',
        ),
      );
      $form['visual_mapper_settings']['spiderChart']['conceptMaxRadius'] = array(
        '#type' => 'textfield',
        '#title' => t('Concept-circle: Maximum radius'),
        '#description' => t('The maximum radius of the concept circles in "px"'),
        '#default_value' => $visual_mapper_settings['spiderChart']['conceptMaxRadius'],
        '#parents' => array(
          'visual_mapper_settings',
          'spiderChart',
          'conceptMaxRadius',
        ),
      );
      $form['visual_mapper_settings']['spiderChart']['conceptMinRadius'] = array(
        '#type' => 'textfield',
        '#title' => t('Concept-circle: Minimum radius'),
        '#description' => t('The minimum radius of the concept circles in "px"'),
        '#default_value' => $visual_mapper_settings['spiderChart']['conceptMinRadius'],
        '#parents' => array(
          'visual_mapper_settings',
          'spiderChart',
          'conceptMinRadius',
        ),
      );
      $form['visual_mapper_settings']['spiderChart']['conceptFontSize'] = array(
        '#type' => 'textfield',
        '#title' => t('Concept-circle: Font-size'),
        '#description' => t('The font-size of the concept-circle-text in "px"'),
        '#default_value' => $visual_mapper_settings['spiderChart']['conceptFontSize'],
        '#parents' => array(
          'visual_mapper_settings',
          'spiderChart',
          'conceptFontSize',
        ),
      );
      $form['visual_mapper_settings']['spiderChart']['conceptCharactersPerLine'] = array(
        '#type' => 'textfield',
        '#title' => t('Concept-circle: Characters per line'),
        '#description' => t('The maximum characters per line in the text of the concept-circles'),
        '#default_value' => $visual_mapper_settings['spiderChart']['conceptCharactersPerLine'],
        '#parents' => array(
          'visual_mapper_settings',
          'spiderChart',
          'conceptCharactersPerLine',
        ),
      );
      $form['visual_mapper_settings']['spiderChart']['sectorMinArc'] = array(
        '#type' => 'textfield',
        '#title' => t('Minimum arc-sector'),
        '#description' => t('The minimum radius of the sectors in RAD'),
        '#default_value' => $visual_mapper_settings['spiderChart']['sectorMinArc'],
        '#parents' => array(
          'visual_mapper_settings',
          'spiderChart',
          'sectorMinArc',
        ),
      );
      $form['visual_mapper_settings']['spiderChart']['paginationAngle'] = array(
        '#type' => 'textfield',
        '#title' => t('Page button radius'),
        '#description' => t('The preferred radius of one page of the pagination area in RAD'),
        '#default_value' => $visual_mapper_settings['spiderChart']['paginationAngle'],
        '#parents' => array(
          'visual_mapper_settings',
          'spiderChart',
          'paginationAngle',
        ),
      );
      $form['visual_mapper_settings']['spiderChart']['rectHeight'] = array(
        '#type' => 'textfield',
        '#title' => t('Concept space height'),
        '#description' => t('The height of the space required for all elements of one concept circle in "px"'),
        '#default_value' => $visual_mapper_settings['spiderChart']['rectHeight'],
        '#parents' => array(
          'visual_mapper_settings',
          'spiderChart',
          'rectHeight',
        ),
      );
      $form['visual_mapper_settings']['spiderChart']['rectWidth'] = array(
        '#type' => 'textfield',
        '#title' => t('Concept space width'),
        '#description' => t('The width of the space required for all elements of one concept circle in "px"'),
        '#default_value' => $visual_mapper_settings['spiderChart']['rectWidth'],
        '#parents' => array(
          'visual_mapper_settings',
          'spiderChart',
          'rectWidth',
        ),
      );

      // Tree View settings.
      $form['visual_mapper_settings']['treeView'] = array(
        '#type' => 'details',
        '#title' => t('Advanced Chart Configuration "Tree View"'),
        '#states' => array(
          'visible' => array(
            ':input[name="visual_mapper_settings[chartTypes][tree]"]' => array('checked' => TRUE),
          ),
        ),
      );

      $form['visual_mapper_settings']['treeView']['barHeight'] = array(
        '#type' => 'textfield',
        '#title' => t('Bar height'),
        '#description' => t('The height of the bar of a single concept in px'),
        '#default_value' => $visual_mapper_settings['treeView']['barHeight'],
        '#parents' => array(
          'visual_mapper_settings',
          'treeView',
          'barHeight',
        ),
      );
      $form['visual_mapper_settings']['treeView']['collapseCircleRadius'] = array(
        '#type' => 'textfield',
        '#title' => t('Collapse circle radius'),
        '#description' => t('The radius of the button to open / close a concept in px'),
        '#default_value' => $visual_mapper_settings['treeView']['collapseCircleRadius'],
        '#parents' => array(
          'visual_mapper_settings',
          'treeView',
          'collapseCircleRadius',
        ),
      );
      $form['visual_mapper_settings']['treeView']['conceptFontSize'] = array(
        '#type' => 'textfield',
        '#title' => t('Concept font size'),
        '#description' => t('The font-size of the text inside the concept bars in px'),
        '#default_value' => $visual_mapper_settings['treeView']['conceptFontSize'],
        '#parents' => array(
          'visual_mapper_settings',
          'treeView',
          'conceptFontSize',
        ),
      );

      // Export settings.
      $form['visual_mapper_settings']['export'] = array(
        '#type' => 'details',
        '#title' => t('Export Settings'),
        '#states' => array(
          'visible' => array(
            ':input[name="visual_mapper_settings[export][enabled]"]' => array('checked' => TRUE),
          ),
        ),
      );

      $form['visual_mapper_settings']['export']['exportButtonRadius'] = array(
        '#type' => 'textfield',
        '#title' => t('Export button radius'),
        '#description' => t('The radius of the export button in "px"'),
        '#default_value' => $visual_mapper_settings['export']['exportButtonRadius'],
        '#parents' => array(
          'visual_mapper_settings',
          'export',
          'exportButtonRadius',
        ),
      );
      $form['visual_mapper_settings']['export']['exportButtonPositionX'] = array(
        '#type' => 'select',
        '#title' => t('Export button position X'),
        '#options' => array(
          'left' => t('Left'),
          'right' => t('Right'),
        ),
        '#description' => t('The horizontal position of the export button in reference to the chart itself.'),
        '#default_value' => $visual_mapper_settings['export']['exportButtonPositionX'],
        '#parents' => array(
          'visual_mapper_settings',
          'export',
          'exportButtonPositionX',
        ),
      );
      $form['visual_mapper_settings']['export']['exportButtonPositionY'] = array(
        '#type' => 'select',
        '#title' => t('Export button position Y'),
        '#options' => array(
          'top' => t('Top'),
          'bottom' => t('Bottom'),
        ),
        '#description' => t('The vertical position of the export button in reference to the chart itself.'),
        '#default_value' => $visual_mapper_settings['export']['exportButtonPositionY'],
        '#parents' => array(
          'visual_mapper_settings',
          'export',
          'exportButtonPositionY',
        ),
      );
      $form['visual_mapper_settings']['export']['exportFileName'] = array(
        '#type' => 'textfield',
        '#title' => t('Exported file name'),
        '#description' => t('The name of the exported file without any file extension.'),
        '#default_value' => $visual_mapper_settings['export']['exportFileName'],
        '#parents' => array(
          'visual_mapper_settings',
          'export',
          'exportFileName',
        ),
      );
    }
    else {
      $form['visual_mapper_settings']['info'] = array(
        '#markup' => '<div class="messages warning">' . t('To use the Visual Mapper library all its requirements need to be met.') . '</div>',
      );
    }

    // Tab: Advanced settings.
    $advanced_settings = $entity->getAdvancedSettings();
    $form['advanced_settings'] = array(
      '#type' => 'details',
      '#title' => t('Advanced settings'),
      '#tree' => TRUE,
      '#group' => 'settings',
    );

    $form['advanced_settings']['data_updates'] = array(
      '#type' => 'fieldset',
      '#title' => t('Data updates'),
    );
    $form['advanced_settings']['data_updates']['interval'] = array(
      '#type' => 'select',
      '#title' => t('Automatic character list update time period'),
      '#description' => t('Unused letters in the character list will be greyed out after the update'),
      // Time in seconds.
      '#options' => array(
        '3600' => t('Every hour'),
        '86400' => t('Every day'),
        '604800' => t('Every week'),
        '2592000' => t('Every month'),
        '0' => t('Manual regeneration only'),
      ),
      '#default_value' => $advanced_settings['interval'],
    );
    $form['advanced_settings']['data_updates']['next_run'] = array(
      '#type' => 'value',
      '#value' => $advanced_settings['next_run'],
    );
    $form['advanced_settings']['data_updates']['char_a_z'] = array(
      '#type' => 'value',
      '#value' => $advanced_settings['char_a_z'],
    );
    $form['advanced_settings']['data_updates']['update_character_list'] = array(
      '#type' => 'submit',
      '#value' => t('Update the character list now'),
      '#submit' => array('::updateCharacterListSubmit'),
    );

    $form['connection_settings']['graph_uri'] = array(
      '#type' => 'textfield',
      '#title' => t('Graph URI'),
      '#default_value' => $advanced_settings['graph_uri'],
    );

    $htaccess = array();
    if (!empty($entity->getBasePath())) {
      $htaccess[] = '
  #---------------------------------------------------------
  # Content Negotiation from Drupal Module "Smart Glossary"
  #---------------------------------------------------------';
      $htaccess[] = '
  # RDF/XML rewrite rule for .rdf extension
  RewriteCond %{QUERY_STRING} ^uri=(.*)$
  RewriteRule ^' . $entity->getBasePath() . '\/(.*)\.rdf$   ' . $connection->getUrl() . '?query=CONSTRUCT+{+\%3C%1\%3E+\%3Fp+\%3Fo.+}+WHERE+{+\%3C%1\%3E+\%3Fp+\%3Fo.+}&format=application/rdf\%2Bxml    [NE,L,P]';
      $htaccess[] = '
  # N3 rewrite rule for .n3 extension
  RewriteCond %{QUERY_STRING} ^uri=(.*)$
  RewriteRule ^' . $entity->getBasePath() . '\/(.*)\.n3$   ' . $connection->getUrl() . '?query=CONSTRUCT+{+\%3C%1\%3E+\%3Fp+\%3Fo.+}+WHERE+{+\%3C%1\%3E+\%3Fp+\%3Fo.+}&format=text/rdf\%2Bn3    [NE,L,P]';
      $htaccess[] = '
  # RDF/XML content negotiation for accept header = application/rdf+xml
  RewriteCond %{HTTP_ACCEPT}  application/rdf\+xml
  RewriteCond %{QUERY_STRING} ^uri=(.*)$
  RewriteRule ^(' . $entity->getBasePath() . ')\/(.*)$    $1/$2.rdf?uri=%1    [L,R=303]';
      $htaccess[] = '
  # N3 content negotiation for accept header = text/rdf+n3
  RewriteCond %{HTTP_ACCEPT}  text/n3 [OR]
  RewriteCond %{HTTP_ACCEPT}  text/rdf\+n3
  RewriteCond %{QUERY_STRING} ^uri=(.*)$
  RewriteRule ^(' . $entity->getBasePath() . ')\/(.*)$    $1/$2.n3?uri=%1   [L,R=303]';
      $htaccess[] = '
  #---------------------------------------------------------
  # End of Content Negotiation
  #---------------------------------------------------------';
    }
    $form['advanced_settings']['rdf'] = array(
      '#type' => 'fieldset',
      '#title' => t('RDF'),
    );
    $form['advanced_settings']['rdf']['add_rdf_link'] = array(
      '#type' => 'checkbox',
      '#title' => t('Add a link to RDF/XML'),
      '#description' => t('Adds a link to get the concept data in RDF/XML format.'),
      '#default_value' => $advanced_settings['add_rdf_link'],
    );
    $form['advanced_settings']['rdf']['add_endpoint_link'] = array(
      '#type' => 'checkbox',
      '#title' => t('Add a link to the SPARQL endpoint'),
      '#description' => t('Adds a link to the specified SPARQL endpoint.'),
      '#default_value' => $advanced_settings['add_endpoint_link'],
    );
    $form['advanced_settings']['rdf']['text'] = array(
      '#type' => 'textarea',
      '#title' => t('Add content negotiation'),
      '#description' => t('Copy and paste it into the .htaccess file below "RewriteBase /"'),
      '#default_value' => implode("\n", $htaccess),
      '#rows' => 25,
      '#resizable' => FALSE,
      '#attributes' => array('readonly' => 'readonly'),
    );

    if (!$entity->isNew()) {
      $form['sg_id'] = array(
        '#type' => 'hidden',
        '#value' => $entity->id(),
      );
    }

    // Tab: Semantic module connection.
    $form['semantic_connection'] = array(
      '#type' => 'details',
      '#title' => t('Semantic module connection'),
      '#group' => 'settings',
      '#tree' => TRUE,
    );

    if (\Drupal::moduleHandler()->moduleExists('powertagging')) {
      $form['semantic_connection']['add_show_content_link'] = array(
        '#type' => 'checkbox',
        '#title' => t('Add a "Show Content"-link'),
        '#description' => t('Add a link to the list of content tagged with the current root concept in the visual mapper if a corresponding taxonomy term exists.'),
        '#default_value' => $advanced_settings['semantic_connection']['add_show_content_link'],
      );
    }
    else {
      $form['semantic_connection']['add_show_content_link'] = array(
        '#type' => 'value',
        '#value' => FALSE,
        '#parents' => array('semantic_connection', 'add_show_content_link'),
      );
    }

    $form['semantic_connection']['show_in_destinations'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show as concept destination'),
      '#description' => t('Add this Smart Glossary to a list of destinations for a concept link if applicable.'),
      '#default_value' => $advanced_settings['semantic_connection']['show_in_destinations'],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /** @var SmartGlossaryConfig $entity */
    $entity = $this->entity;
    $is_new = !$entity->getOriginalId();

    if (!UrlHelper::isValid($form_state->getValue('url'), TRUE)) {
      $form_state->setErrorByName('url', t('The field URL must be a valid URL.'));
    }
    if ($form_state->getValue('base_path') == '<none>') {
      $form_state->setErrorByName('base_path', t('Please select a valid base path.'));
    }

    // Check if base path is unique.
    $query = \Drupal::entityQuery('smart_glossary')
      ->condition('base_path', $form_state->getValue('base_path'));
    if (!$is_new) {
      $query->condition('id', $form_state->getValue('sg_id'), '<>');
    }

    if ($query->count()->execute() > 0) {
      $form_state->setErrorByName('base_path', t('The base path must be unique.'));
    }

    if (!empty($form_state->getValue('visual_mapper_settings'))) {
      // Fix the values from checkboxes-selection.
      $visual_mapper_settings = $form_state->getValue('visual_mapper_settings');
      $visual_mapper_settings['chartTypes'] = array_values(
        array_filter($visual_mapper_settings['chartTypes'])
      );

      // Replace numeric strings with numbers.
      $this->formValuesStringToNumber($visual_mapper_settings);
      $form_state->setValue('visual_mapper_settings', $visual_mapper_settings);
    }
  }

  /**
   * Update the entity with the selected form values.
   *
   * @param array $form
   *   The form variable.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state variable.
   */
  protected function formToEntity(array $form, FormStateInterface $form_state) {
    /** @var SmartGlossaryConfig $entity */
    $entity = $this->entity;

    // Get the languages into one variable.
    $language_mapping = array();
    foreach ($form_state->getValue('language_mapping') as $mapping) {
      $mapping['glossary_languages'] = explode(',', preg_replace('/ +/', '', $mapping['glossary_languages']));
      if (!empty($mapping['glossary_languages'])) {
        $language_mapping[$mapping['page_language']] = array(
          'glossary_languages' => $mapping['glossary_languages'],
          'wording' => $mapping['wording'],
        );
      }
    }
    $entity->set('language_mapping', $language_mapping);

    // Advanced settings.
    $advanced_settings_values = $form_state->getValue('advanced_settings');
    $advanced_settings = $advanced_settings_values['data_updates'];
    $advanced_settings['add_rdf_link'] = $advanced_settings_values['rdf']['add_rdf_link'];
    $advanced_settings['add_endpoint_link'] = $advanced_settings_values['rdf']['add_endpoint_link'];
    $advanced_settings['semantic_connection'] = $form_state->getValue('semantic_connection');
    $advanced_settings['graph_uri'] = $form_state->getValue('graph_uri');
    $entity->set('advanced_settings', $advanced_settings);

    // VisualMapper settings.
    $visual_mapper_settings = $form_state->getValue('visual_mapper_settings');
    $entity->set('visual_mapper_settings', (!is_null($visual_mapper_settings) ? $visual_mapper_settings : array()));
  }
  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->formToEntity($form, $form_state);

    /** @var SmartGlossaryConfig $entity */
    $entity = $this->entity;
    $is_new = !$entity->getOriginalId();
    if ($is_new) {
      // Configuration entities need an ID manually set.
      $entity->set('id', SemanticConnector::createUniqueEntityMachineName('smart_glossary', $entity->get('title')));
      drupal_set_message(t('Smart Glossary %title has been created.', array('%title' => $entity->get('title'))));
    }
    else {
      drupal_set_message(t('Updated Smart Glossary %title.', array('%title' => $entity->get('title'))));
    }

    // Always create a new connection, if URL and type are the same the old one
    // will be used anyway.
    $connection = SemanticConnector::createConnection('sparql_endpoint', $form_state->getValue('url'), $form_state->getValue('endpoint_title'), array(
      'username' => $form_state->getValue('username'),
      'password' => $form_state->getValue('password'),
    ));
    $entity->set('connection_id', $connection->getId());
    $entity->save();

    drupal_flush_all_caches();
    $form_state->setRedirectUrl(Url::fromRoute('entity.smart_glossary.collection'));
  }

  public function updateCharacterListSubmit(array &$form, FormStateInterface $form_state) {
    $this->formToEntity($form, $form_state);

    /** @var SmartGlossaryConfig $entity */
    $entity = $this->entity;
    $smart_glossary = SmartGlossary::getInstance($entity);
    $smart_glossary->updateCharacterList();

    drupal_set_message(t('The character list has been updated successfully.'));
  }

  /**
   * Cast numeric strings to double recursively in a given array.
   *
   * @param array $array
   *   The array to check for numeric strings.
   */
  private function formValuesStringToNumber(&$array) {
    foreach ($array as &$value) {
      if (is_array($value)) {
        $this->formValuesStringToNumber($value);
      }
      elseif (is_numeric($value)) {
        $value = (double) $value;
      }
    }
  }
}
