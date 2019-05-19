<?php

/**
 * @file
 * Documentation landing page and topics.
 */

/**
 * @mainpage
 * Welcome to the VisualN API Documentation!
 *
 * This site is an API reference for VisualN, generated from comments embedded
 * in the source code.
 *
 * The up-to-date documentation version can be found at http://api.visualn.org/api/visualn
 *
 * Here are some topics to help you get started developing with VisualN.
 *
 * @section essentials Essential background concepts
 *
 * - @link drawings Drawings @endlink
 *
 * @section workflow_mechanics Workflow mechanics
 *
 * - @link chain_plugins Chain plugins @endlink
 * - @link builder_plugins Builders @endlink
 * - @link drawer_plugins Drawers @endlink
 * - @link adapter_plugins Adapters @endlink
 * - @link mapper_plugins Mappers @endlink
 *
 * @section interface User interface
 *
 * - @link visualn_styles Visualization styles @endlink
 * - @link setup_baker_plugins Setup Bakers @endlink
 * - @link fetcher_plugins Drawing Fetchers @endlink
 * - @link drawer_skin_plugins Drawer Skins @endlink
 * - @link visualn_fields Fields @endlink
 * - @link visualn_blocks Blocks @endlink
 * - @link visualn_views Views integration @endlink
 * - @link raw_resource_formats Raw Resource Format plugins @endlink
 *
 * @section data_sources Data sources
 *
 * - @link resource_plugins Resource plugins @endlink
 * - @link resource_proivder_plugins Resource providers @endlink
 * - @link data_generator_plugins Data generators @endlink
 * - @link data_set_entities Data Set entities @endlink
 * - @link data_sources Data Sources @endlink
 *
 * @section embedding_drawings Embedding drawings
 *
 * - @link drawing_entities Drawing entities and Library of drawings @endlink
 * - @link ckeditor_integration CKEditor integration @endlink
 * - @link iframes_toolkit IFrames toolkit @endlink
 * - @link drawing_tokens Drawing tokens @endlink
 *
 * @section subdrawers User-defined drawers (subdrawers)
 *
 * - @link subdrawer_entities Subdrawer entites @endlink
 * - @link drawer_modifiers Drawer modifiers @endlink
 *
 */

/**
 * @defgroup drawings Drawings
 * @{
 * Drawings types and overview
 *
 * A drawing is basically any piece of markup built around
 * and idea or purpose and representing a self-contained unit.
 *
 * As any html markup drawings can have scripts and styles attached.
 * Practically there is no limit of what a drawing may be: charts, image galleries,
 * embedded video, js apps, LaTeX images etc.
 * @}
 */

/**
 * @defgroup chain_plugins Workflows mechanics
 * @{
 * Chain plugins are base elements that compose a drawing building chain.
 *
 * Drawers, Adapters and Mappers are examples of chain plugins. They are
 * used by Builders to build a chain that is used to create a drawing
 * based on a given resource. All chain plugins implement a common
 * method ::prepareBuild() that allows each plugin to modify $build and $resource
 * and transfer it further down the chain to finally get a ready drawing build.
 * @}
 */

/**
 * @defgroup builder_plugins Builder plugins
 * @{
 * Builders are used to compose chain of plugins and create drawing build.
 *
 * Builder plugins main purpose is to compose a chain from adapter, mapper and
 * drawer plugins and apply it to the input resource object to get a drawing
 * build as a result.
 * Developers can create custom builders that would implement custom logic
 * if DefaultBuilder doesn't fit their needs.
 * @}
 */

/**
 * @defgroup drawer_plugins Drawer plugins
 * @{
 * Drawers are used to generate drawings markup.
 *
 * Drawers are the central part of the VisualN ecosystem. They allow
 * to generate any type of markup, clientside (js) or serverside, based
 * on provided data and configuration (or without them). Adapters and Mappers
 * make Drawers resource agnostic. The same drawer can be used to create
 * drawings based on uploaded files data, remote resources, generated data
 * or any other resource type.
 * @}
 */

/**
 * @defgroup adapter_plugins Adapter plugins
 * @{
 * Adapters convert resources from one type to another.
 *
 * Adapter plugins allow to make drawers resource agnostic, i.e. they allow
 * to use the same drawer plugin with any resource type: from views output
 * to uploaded files or remote resources available over http or other protocols.
 *
 * Adapters are the key part of the VisualN which provides its flexibility
 * and integration with other Drupal UIs such as fields, blocks or views
 * without coding.
 * @}
 */

/**
 * @defgroup mapper_plugins Mapper plugins
 * @{
 * Mappers change data keys to ones used by drawers.
 *
 * Mapper plugins allow users to set key mapping from ones used in resource
 * to ones expected by drawers.
 * @}
 */

/**
 * @defgroup visualn_styles Visualization styles
 * @{
 * Visualization styles store drawer configuration values for later reuse.
 *
 * Visualization style is a configuration entity used to store specific drawer
 * plugin configuration. This allows to reuse the same configuration in multiple
 * places and for multiple use cases without reentering the same values every time
 * a specific configuration is needed. Also visualization styles can be considered
 * as defaults or base settings since usually, when selected, UI allows to override
 * selected style settings (e.g. change some values and leave others unchanged).
 * There can be many visualization styles for the same drawer.
 * @}
 */

/**
 * @defgroup fetcher_plugins Drawing Fetcher plugins
 * @{
 * Drawing Fetchers implement some arbitrary logic to deliver a drawing build.
 *
 * Fecher plugins allow to implement an alternative logic to create drawings
 * even without relying on chain plugins, even without drawers (as an edge case).
 * Fetchers are commonly used by VisualN blocks to create drawings as blocks,
 * and by Drawing entities and to embed them into content via tokens or iframes.
 * @}
 */

/**
 * @defgroup drawer_skin_plugins Drawer Skin plugins
 * @{
 * Drawer Skins extend drawer behaviour or change its drawings appearance.
 *
 * Skin plugins allow to extend drawer behaviour without creating a new drawer.
 * Also skins can be used to change the look or structure of resultant drawings.
 * There is no limit or strict suggestions on how skins should be implemented - it is
 * specific to each drawer or family of drawers. Technically skins are just another
 * type of plugin. Skins may also be divided into types if a drawer allows different
 * types of skins, e.g. one type for resultant drawings appearance and another
 * extending drawer functionality.
 * @}
 */

/**
 * @defgroup raw_resource_formats Raw Resource Format plugins
 * @{
 * Raw Resource Formats describe real physical resources used.
 *
 * Raw resources are the real "stuff" which is converted/translated into a resource object
 * to be used to build a drawing.
 * Due to arbitrary nature of possible physical resources no strict assumptions can be made
 * about their structure, origin or location that would be common
 * for all possible physical (real) resources.
 *
 * Though every real resource has some common features, namely a set of comprised/provided
 * values/parameters of some nature, expected type of resultant resource object and
 * the way (logic) to convert those input values/parameters into a resource object.
 * These features constitute real resource formats which are implemented
 * in form of Raw Resource Format plugins.
 *
 * The plugins are commonly used as an entry point into drawing building chain. They are
 * used by VisualN fields to let user explicitly select the format of file or url resource
 * or seamlessly when resource object is created.
 *
 * Raw Resource Format plugins have "group" key to let modules group formats by some criteria.
 * The "default" group tells that the format should be used to create a resource object
 * of a given type by default. See VisualN::getResourceByOptions() helper.
 *
 * Each format plugin must have "output" key set in its annotation. It defines the type
 * of resultant resource object produced by the plugin.
 * @}
 */

/**
 * @defgroup resource_proivder_plugins Resource providers
 * @{
 * Provide Resource objects objects to create drawings.
 *
 * Resource providers are typically used by Drawing Fetchers or Data Set entities
 * via Resource provider field type.
 * There is not limit on type or types of resources that a given Resource provider
 * may return. It is more about contents of the resource returned (information)
 * than about its type. In this point it differs from Raw Resource Format plugins
 * which also return a resource but are more about format of *raw* resources trying
 * to describe real envorinment than about contents of that raw resources. And also
 * being limited to one specific resource type.
 * @}
 */

/**
 * @defgroup ckeditor_integration CKEditor integration
 * @{
 * Provides tools to embed Drawing entities into content using CKEditor.
 *
 * The toolkit provides CKEditor toolbar button and context menu items to embed,
 * create, preview and delete VisualN Drawing entities directly while editing
 * content.
 *
 * Also it provides a content filter to render embedded drawings.
 *
 * Embedded drawings are integrated with @link iframes_toolkit IFrames toolkit @endlink.
 * @}
 */

/**
 * @defgroup iframes_toolkit IFrames toolkit
 * @{
 * Provides tools to share drawings (and other content) via iframes.
 *
 * The toolkit basically doesn't depend on other VisualN modules though it is
 * integrated with them, e.g. to share drawings embedded into content via
 * ckeditor custom plugin or VisualN blocks content.
 * The toolkit can also be integrated with other third-party modules.
 * @}
 */

/**
 * Provide adapters subchain suggestions to be used by DefaultManager chain builder
 *
 * The 'adapters' items should correspond to the order adapters should be called in.
 *
 * @todo: this hook later may be changed or removed
 *
 * @param array $subchain_suggestions
 *   An of adapter subchains suggested by modules.
 *
 * @ingroup builder_plugins
 */
function hook_visualn_adapter_subchains_alter(&$subchain_suggestions) {

  // @todo: maybe use associative keys to uniquely identify a given suggestion
  $subchain_suggestions[] = [
    'adapters' => [
      'adapter_id_1',
      'adapter_id_2',
    ],
    'input' => 'custom_input_type',
    'output' => 'generic_data_array',
  ];
}

// @todo: add other similar hooks from other managers

/**
 * Alter ResourceFormat plugins definitions
 *
 * In particular, the hook is used to set/alter resource format 'groups' property
 *
 * @todo: this hook later may be changed or removed
 *
 * @todo: mention where the code is taken from (visualn.module)
 *
 * @todo: add a link to the RawResourceFormat manager class
 *
 * @ingroup raw_resource_formats
 */
function hook_visualn_raw_resource_format_info_alter(&$definitions) {

  // VisualN Resource field widget allows to select Raw Resource Format to be used
  // for input urls. It uses Raw Resource Format plugin annotation "groups" property
  // to filter only relative ones.
  $ids = ['visualn_json', 'visualn_csv', 'visualn_tsv', 'visualn_xml'];
  // @todo: maybe set group directly in plugins annotation
  foreach ($definitions as $k => $definition) {
    if (in_array($definition['id'], $ids)) {
      $definitions[$k]['groups'][] = 'visualn_url_widget';
    }
  }
}
