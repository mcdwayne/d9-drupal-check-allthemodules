<?php

namespace Drupal\visualn_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Symfony\Component\HttpFoundation\Request;
use Drupal\visualn\Manager\DrawingFetcherManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\Element;

use Drupal\Core\Form\SubformStateInterface;
use Drupal\visualn_iframe\Entity\VisualNIFrame;

/**
 * Provides a 'VisualNBlock' block.
 *
 * @ingroup iframes_toolkit
 *
 * @Block(
 *  id = "visualn_block",
 *  admin_label = @Translation("VisualN Block"),
 * )
 */
class VisualNBlock extends BlockBase implements ContainerFactoryPluginInterface {

  const IFRAME_HANDLER_KEY = 'visualn_block_key';

  /**
   * The visualn drawing fetcher manager service.
   *
   * @var \Drupal\visualn\Manager\DrawingFetcherManager
   */
  protected $visualNDrawingFetcherManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.visualn.drawing_fetcher')
    );
  }

  /**
   * Constructs a VisualNFormatter object.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition
   * @param \Drupal\visualn\Manager\DrawingFetcherManager $visualn_drawing_fetcher_manager
   *   The visualn drawing fetcher manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DrawingFetcherManager $visualn_drawing_fetcher_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->visualNDrawingFetcherManager = $visualn_drawing_fetcher_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
         'fetcher_id' => '',
         'fetcher_config' => [],
         'sharing_settings' => [],
         'iframe_hash' => '',
        ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // Get fetcher plugins list for the drawing fetcher select.
    $fetchers_list = [];
    $definitions = $this->visualNDrawingFetcherManager->getDefinitions();
    foreach ($definitions as $definition) {
      // Exclude fetchers with which have at least one required context scince here no context is provided.
      if (!empty($definition['context'])) {
        foreach ($definition['context'] as $name => $context_definition) {
          if ($context_definition->isRequired()) {
            continue 2;
          }
        }
      }
      $fetchers_list[$definition['id']] = $definition['label'];
    }

    // @todo: review this check after the main issue in drupal core is resolved
    // @see https://www.drupal.org/node/2798261
    if ($form_state instanceof SubformStateInterface) {
      $fetcher_id = $form_state->getCompleteFormState()->getValue(['settings', 'fetcher_id']);
    }
    else {
      $fetcher_id = $form_state->getValue(['settings', 'fetcher_id']);
    }


    // If form is new and form_state is null for the fetcher_id, get fetcher_id from the block configuration.
    // Also we destinguish empty string and null because user may change fetcher
    // to '- Select drawing fetcher -' keyed by "", which is not null.
    if (is_null($fetcher_id)) {
      $fetcher_id = $this->configuration['fetcher_id'];
    }


    // select drawing fetcher plugin
    //$ajax_wrapper_id = 'visualn-block-fetcher-config-ajax-wrapper';
    $form_array_parents = isset($form['#array_parents']) ? $form['#array_parents'] : [];
    $ajax_wrapper_id = implode('-', array_merge($form_array_parents, ['fetcher_id'])) . '-visualn-block-ajax-wrapper';
    $form['fetcher_id'] = [
      '#type' => 'select',
      '#title' => t('Drawing fetcher plugin'),
      '#options' => $fetchers_list,
      '#default_value' => $fetcher_id,
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxCallback'],
        'wrapper' => $ajax_wrapper_id,
      ],
      '#empty_value' => '',
      '#empty_option' => t('- Select drawing fetcher -'),
    ];
    $form['fetcher_container'] = [
      '#prefix' => '<div id="' . $ajax_wrapper_id . '">',
      '#suffix' => '</div>',
      '#type' => 'container',
      '#weight' => '1',
      // add process at fetcher_container level since fetcher_id input should be already mapped (prepopulated)
      // for further processing (see FormBuilder::processForm())
      //'#process' => [[get_called_class(), 'processFetcherConfigurationSubform']],

      //'#process' => [[$this, 'processFetcherConfigurationSubform']],
    ];
    // Use #process callback for building the fetcher configuration form itself because it
    // may need #array_parents key to be already filled up (see PluginFormInterface::buildConfigurationForm()
    // method comments on https://api.drupal.org).
    $form['fetcher_container']['fetcher_config'] = [
      '#type' => 'container',
      '#process' => [[$this, 'processFetcherConfigurationSubform']],
    ];



    // @todo: review key names
    // check if visualn_iframe module is enabled
    $moduleHandler = \Drupal::service('module_handler');
    // @todo: check if blocks sharing enabled (in visualn_iframe settings) and permissions
    if ($moduleHandler->moduleExists('visualn_iframe')){
      $iframes_default_config = \Drupal::config('visualn_iframe.settings');
      $additional_config = \Drupal::config('visualn_block.iframe.settings');
      // check if blocks sharing allowed
      if ($additional_config->get('allow_blocks_sharing')) {

        $settings = $this->configuration['sharing_settings'];
        $form['sharing_settings'] = [
          '#type' => 'details',
          '#title' => $this->t('Sharing settings'),
          '#open' => TRUE,
          '#weight' => '1',
        ];
        // add hash link to the form
        // @todo: add hash link styles (for hash color depending on iframe pulishing status)
        $hash_label = \Drupal::service('visualn_iframe.builder')->getHashLabel($this->configuration['iframe_hash']);
        if ($hash_label) {
          $form['#attached']['library'][] = 'visualn_iframe/visualn-iframe-ui';
          $hash_label = " {$hash_label}";
        }
        $form['sharing_settings']['sharing_enabled'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Enable sharing') . $hash_label,
          '#default_value' => isset($settings['sharing_enabled']) ? $settings['sharing_enabled'] : FALSE,
        ];
        $form['sharing_settings']['use_defaults'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Use defaults'),
          // @todo: add to iframe settings
          '#default_value' => isset($settings['use_defaults']) ? $settings['use_defaults'] : FALSE,
          '#states' => [
            'visible' => [
              ':input[name="settings[sharing_settings][sharing_enabled]"]' => ['checked' => TRUE],
            ],
          ],
        ];
        $form['sharing_settings']['show_link'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Show origin link'),
          '#default_value' => isset($settings['show_link']) ? $settings['show_link'] : $iframes_default_config->get('default.show_link'),
          '#states' => [
            'visible' => [
              ':input[name="settings[sharing_settings][sharing_enabled]"]' => ['checked' => TRUE],
              ':input[name="settings[sharing_settings][use_defaults]"]' => ['checked' => FALSE],
            ],
          ],
        ];

        $form['sharing_settings']['origin_url'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Origin url'),
          '#default_value' => isset($settings['origin_url']) ? $settings['origin_url'] : $iframes_default_config->get('default.origin_url'),
          '#description' => $this->t('Leave blank to use default origin url'),
          '#attributes' => [
            'placeholder' => $iframes_default_config->get('default.origin_url'),
          ],
          '#states' => [
            'visible' => [
              ':input[name="settings[sharing_settings][sharing_enabled]"]' => ['checked' => TRUE],
              ':input[name="settings[sharing_settings][use_defaults]"]' => ['checked' => FALSE],
              ':input[name="settings[sharing_settings][show_link]"]' => ['checked' => TRUE],
            ],
          ],
          // @todo: try to validate user input (allow absolute or relative paths or tokens)
        ];
        $form['sharing_settings']['origin_title'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Origin title'),
          '#default_value' => isset($settings['origin_title']) ? $settings['origin_title'] : $iframes_default_config->get('default.origin_title'),
          '#description' => $this->t('Leave blank to use default origin title'),
          '#attributes' => [
            'placeholder' => $iframes_default_config->get('default.origin_title'),
          ],
          '#states' => [
            'visible' => [
              ':input[name="settings[sharing_settings][sharing_enabled]"]' => ['checked' => TRUE],
              ':input[name="settings[sharing_settings][use_defaults]"]' => ['checked' => FALSE],
              ':input[name="settings[sharing_settings][show_link]"]' => ['checked' => TRUE],
            ],
          ],
          // @todo: check if user input validation is needed
        ];
        $form['sharing_settings']['open_in_new_window'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Open in new window'),
          '#default_value' => isset($settings['open_in_new_window']) ? $settings['open_in_new_window'] : $iframes_default_config->get('default.open_in_new_window'),
          '#states' => [
            'visible' => [
              ':input[name="settings[sharing_settings][sharing_enabled]"]' => ['checked' => TRUE],
              ':input[name="settings[sharing_settings][use_defaults]"]' => ['checked' => FALSE],
              ':input[name="settings[sharing_settings][show_link]"]' => ['checked' => TRUE],
            ],
          ],
        ];
      }
    }
    // @todo: is this needed, i.e. maybe the value in $this->configuration is enough
    $form['iframe_hash'] = [
      '#type' => 'value',
      '#value' => $this->configuration['iframe_hash'],  // hash is set in blockSubmit()
    ];

    return $form;
  }

  /**
   * Return fetcher configuration form via ajax request at fetcher change
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state, Request $request) {
    return $form['settings']['fetcher_container'];
  }

  /**
   * Process fetcher configuration subform
   *
   * Here we use process callback, cinse fetcher_plugin::buildConfigurationForm() may need
   * element #array_parents keys (e.g. to define triggering element at ajax calls).
   */
  //public static function processFetcherConfigurationSubform(array $element, FormStateInterface $form_state, $form) {
  public function processFetcherConfigurationSubform(array $element, FormStateInterface $form_state, $form) {
    $fetcher_element_parents = array_slice($element['#parents'], 0, -2);
    $fetcher_id = $form_state->getValue(array_merge($fetcher_element_parents, ['fetcher_id']));
    // Whether fetcher_id is an empty string (which means changed to the Default option) or NULL (which means
    // that the form is fresh) there is nothing to attach for fetcher_config subform.
    //if (empty($fetcher_id)) {
    if (!$fetcher_id) {
      return $element;
    }

    //if (!is_null($fetcher_id) && $fetcher_id == $this->configuration['fetcher_id']) {
    if ($fetcher_id == $this->configuration['fetcher_id']) {
      // @note: plugins are instantiated with default configuration to know about it
      //    but at configuration form rendering always the form_state values are (should be) used
      $fetcher_config = $this->configuration['fetcher_config'];
    }
    else {
      $fetcher_config = [];
    }

    // Basically this check is not needed
    if ($fetcher_id) {
      // fetcher plugin buildConfigurationForm() needs Subform:createForSubform() form_state
      $subform_state = SubformState::createForSubform($element, $form, $form_state);

      // instantiate fetcher plugin
      $fetcher_plugin = $this->visualNDrawingFetcherManager->createInstance($fetcher_id, $fetcher_config);
      // attach fetcher configuration form
      // @todo: also fetcher_config_key may be added here as it is done for ResourceGenericDraweringFethcher
      //    and drawer_container_key.
      $element = $fetcher_plugin->buildConfigurationForm($element, $subform_state);

      // change fetcher configuration form container to fieldset if not empty
      if (Element::children($element)) {
        $element['#type'] = 'fieldset';
        $element['#title'] = t('Fetcher settings');
      }
/*
      $element['fetcher_config'] = [];
      $element['fetcher_config'] += [
        '#parents' => array_merge($element['#parents'], ['fetcher_config']),
        '#array_parents' => array_merge($element['#array_parents'], ['fetcher_config']),
      ];
*/
/*
      $element[$drawer_container_key]['drawer_config'] = [];
      $element[$drawer_container_key]['drawer_config'] += [
        '#parents' => array_merge($element['#parents'], [$drawer_container_key, 'drawer_config']),
        '#array_parents' => array_merge($element['#array_parents'], [$drawer_container_key, 'drawer_config']),
      ];
*/
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['fetcher_id'] = $form_state->getValue('fetcher_id');
    // @todo: also keep in mind that fetcher_container will be removed from form_state values after restructuring
    $this->configuration['fetcher_config'] = $form_state->getValue(['fetcher_container', 'fetcher_config'], []);
    // @todo: maybe just keep as configuration value without using form element?
    $this->configuration['iframe_hash'] = $form_state->getValue('iframe_hash');

    // @todo: extracting and restructuring values, if needed, would better be done on the element level,
    //    as it is done inside ResourceGenericDrawingFetcher for drawer_container with drawer_config and drawer_fields.

    $fetcher_id = $this->configuration['fetcher_id'];
    $fetcher_config = $this->configuration['fetcher_config'];
    $fetcher_plugin = $this->visualNDrawingFetcherManager->createInstance($fetcher_id, $fetcher_config);

    // @todo: maybe move fetcher_plugin::submitConfigurationForm() to #element_submit when introduced into core,
    //    currently can be also in #element_validate which is not correct strictly speaking
    $full_form = $form_state->getCompleteForm();
    $subform = $form['settings']['fetcher_container']['fetcher_config'];
    $subform_state = SubformState::createForSubform($subform, $full_form, $form_state->getCompleteFormState());
    $fetcher_plugin->submitConfigurationForm($subform, $subform_state);

    // @todo: check $this->configuration['label'] for new blocks, is it already set here



    // @todo: rework this and share link build
    // @todo: move this block into a visualn_iframe function or a class
    // @todo: service would be preferable to using \Drupal (assuming it's an option in current context)
    $moduleHandler = \Drupal::service('module_handler');
    // check if visualn_iframe module is enabled
    if ($moduleHandler->moduleExists('visualn_iframe')) {
      // @todo: what if drawings sharing is enabled while properties form and editing before submit?
      $additional_config = \Drupal::config('visualn_block.iframe.settings');
      if ($additional_config->get('allow_blocks_sharing')) {

        $this->configuration['sharing_settings'] = $form_state->getValue('sharing_settings');

        // @todo: store permissions info and block config in iframe entry context field
        //   add the field iteself

        // @todo: keep the whole config to load block by config and check permissions
        //   review the comment below
        //
        // VisualN Blocks are config entities which basically can't always be identified
        // by some kind of ID. E.g. such blocks used with Panels contrib module are stored
        // as part of panel configuration.
        // On the other hand it may be sill needed to check user permissions to view an
        // iframe based on block content. Because of practically unlimited ways that such
        // blocks can be used (regions, panels etc.) there is no certan way to load them
        // and thus no other way to get info on permissions other than to store the whole config.
        $data = [
          'fetcher_id' => $fetcher_id,
          'fetcher_config' => $fetcher_config,
        ];

        $settings = $this->configuration['sharing_settings'];

        $hash = $this->configuration['iframe_hash'] ?: '';
        if (empty($hash)) {
          $hash = \Drupal::service('visualn_iframe.builder')->generateHash();
          // @todo: check 'langcode' and 'status' values
          $params = [
            'hash' => $hash,
            'status' => 1,
            'langcode' => 'en',
            'name' => $this->configuration['label'],
            'user_id' => \Drupal::currentUser()->id(),
            'displayed' => FALSE,
            'viewed' => FALSE,
            'handler_key' => static::IFRAME_HANDLER_KEY,
            'settings' => $settings,
            'data' => $data,
            'implicit' => FALSE,
          ];
          $iframe_entity = \Drupal::service('visualn_iframe.builder')
            ->createIFrameEntity($params);

          $this->configuration['iframe_hash'] = $hash;

          // @todo: is it really needed, isn't configuration value enough?
          //   note that iframe_hash is set as a #value form element
          $form_state->setValue('iframe_hash', $hash);
        }
        else {
          // Update the iframe entry or create a new one with the same hash.
          $iframe_entity = VisualNIFrame::getIFrameEntityByHash($hash);
          if ($iframe_entity) {
            $iframe_entity->setSettings($settings);
            $iframe_entity->setData($data);
            $iframe_entity->setName($this->configuration['label']);
            $iframe_entity->save();
          }
          else {
            // When a block config is imported on other site, there is no iframe entry
            // so create a new one with the same hash. Also it can happen when
            // an iframe entry was deleted manually.
            //
            // @todo: review the approach
            // For now just create a new 'published' entry with the same hash.
            // There is no info in that case whether it is 'displayed' or 'viewed'.
            // Maybe log a message, or make the workflow configurable via admin UI.
            //
            // @todo: Manual deletion should be prevented by iframe content provider
            //   that would check if there is a block using the hash.


            // @todo: check 'langcode' and 'status' values
            $params = [
              'hash' => $hash,
              'status' => 1,
              'langcode' => 'en',
              'name' => $this->configuration['label'],
              'user_id' => \Drupal::currentUser()->id(),
              'displayed' => FALSE,
              'viewed' => FALSE,
              'handler_key' => static::IFRAME_HANDLER_KEY,
              'settings' => $settings,
              'data' => $data,
              'implicit' => FALSE,
            ];
            $iframe_entity = \Drupal::service('visualn_iframe.builder')
              ->createIFrameEntity($params);
          }
        }

        // @todo: reset cache tag for iframe (since block content could change)
        //   the cache tag itself should use hash a the only way to identify the block build iframe
      }
    }
  }


  /**
   * {@inheritdoc}
   */
  public function build() {
    $fetcher_id = $this->configuration['fetcher_id'];

    if (empty($fetcher_id)) {
      return ['#markup' => ''];
    }

    // create fetcher plugin instance
    $fetcher_config = $this->configuration['fetcher_config'];
    $fetcher_plugin = $this->visualNDrawingFetcherManager->createInstance($fetcher_id, $fetcher_config);

    // get markup from the drawing fetcher
    $build['visualn_block'] = $fetcher_plugin->fetchDrawing();

    // @todo: move this block into a visualn_iframe function or a class
    // @todo: service would be preferable to using \Drupal (assuming it's an option in current context)
    $moduleHandler = \Drupal::service('module_handler');
    // check if visualn_iframe module is enabled
    if ($moduleHandler->moduleExists('visualn_iframe') && $this->configuration['sharing_settings']['sharing_enabled']) {

      $additional_config = \Drupal::config('visualn_block.iframe.settings');
      if ($additional_config->get('allow_blocks_sharing')) {

        // @todo: deal with render cache here (e.g. if module was enabled, disabled and then re-enabled - link won't appear/disapper because of block cache)
        //   also if a visualn_iframe entry is removed manually
        // @todo: rename the variable (everywhere)
        //   initialize in ::create()
        $share_link_builder = \Drupal::service('visualn_iframe.builder');

        // Generate iframe url box markup and attach to the block build
        $hash = $this->configuration['iframe_hash'];
        // @todo: check if the hash belongs here (e.g. not taken drawing entity etc.) if needed
        if ($hash) {
          $iframe_url = NULL;
          // The iframe entry should be created/changed in the block Submit handler
          $iframe_entity = VisualNIFrame::getIFrameEntityByHash($hash);
          if ($iframe_entity) {
            $iframe_url = $share_link_builder->getIFrameUrl($hash);

            $update = FALSE;

            // only change location if empty
            $location = $iframe_entity->getLocation();
            if (empty($location)) {
              $location = \Drupal::service('path.current')->getPath();
              $iframe_entity->setLocation($location);
              $update = TRUE;
            }

            // @todo: use getters and setters
            if (!$iframe_entity->get('displayed')->value) {
              $iframe_entity->set('displayed', TRUE);
              $update = TRUE;
            }

            if ($update) {
              $iframe_entity->save();
            }
          }
          else {
            // @todo: the value should be taken from config (set on IFrame settings page)
            //   Not recommended to enable since no way to check the user
            //   also mention in the setting description text

            $create_by_hash = $additional_config->get('implicit_entries_restore');
            if ($create_by_hash) {
              $settings = $this->configuration['sharing_settings'];
              $data = [];

              // @todo: see the comment regarding adding the full config
              //   to the data in class::blockSubmit()
              $fetcher_id = $this->configuration['fetcher_id'];
              $fetcher_config = $this->configuration['fetcher_config'];
              $data = [
                'fetcher_id' => $fetcher_id,
                'fetcher_config' => $fetcher_config,
              ];

              // @todo: check 'langcode' and 'status' values
              $params = [
                'hash' => $hash,
                'status' => 1,
                'langcode' => 'en',
                'name' => $this->configuration['label'],
                'user_id' => \Drupal::currentUser()->id(),
                'displayed' => TRUE,
                'location' => \Drupal::service('path.current')->getPath(),
                'viewed' => FALSE,
                'handler_key' => static::IFRAME_HANDLER_KEY,
                'settings' => $settings,
                'data' => $data,
                'implicit' => TRUE,
              ];
              $iframe_entity = \Drupal::service('visualn_iframe.builder')
                ->createIFrameEntity($params);

              $iframe_url = $share_link_builder->getIFrameUrl($hash);
            }
            else {
              // If no iframe entry and recreate is not allowed, do not show the link
              // @todo: review message text
              \Drupal::logger('visualn_block')->warning($this->t('IFrame wasn\'t created due to the following reason: disallowed (hash: @hash, path: @path).', [
                '@hash' => $hash,
                '@path' => \Drupal::service('path.current')->getPath(),
              ]));
              //\Drupal::logger('visualn_block')->warning($this->t('IFrame wasn\'t created'));
            }
          }


          if ($iframe_url) {
            // Attach iframe url box markup to the block build
            $build['share_iframe_link'] = $share_link_builder->buildLink($iframe_url);
          }
        }
        else {
          // @todo: log a message (for the case when no hash with enabled sharing)
        }
      }
      // @todo: this should react only on specific settings changes that influence share
      //   link exposition
      $build['#cache']['tags'][] = 'visualn_block_iframe_settings';
      // invalidate cache tag e.g. on entity delete, to restore it if allowed
      if ($iframe_entity) {
        $build['#cache']['tags'][] = 'visualn_iframe:' . $iframe_entity->id();
      }
    }

    return $build;
  }

}

