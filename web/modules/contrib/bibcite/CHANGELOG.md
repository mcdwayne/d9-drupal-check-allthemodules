# Changelog


## [8.x-1.0-alpha9] - 2019-05-08

### Added
- [#3050347] by antongp: Integrate contributor full name (computed) field with Views
- [#3020717] by antongp: Implement hook_requirements() in the module and submodules so errors are shown if required libraries are missing

### Fixed
- [#3053035] by antongp: Import and Populate Reference fail with error on Drupal 8.7


## [8.x-1.0-alpha8] - 2018-12-06

### Important
- [#2974615] by trustypelletgun: Endnote 7 XML secondary-title field is not imported.
  **Note: incorrect Endnote 7 XML title-secondary and title-short fields were renamed to correct secondary-title and
  short-title accordingly.**
  This means that data, exported to Endnote 7 XML format before this change, will lose these
  fields if imported back from that export after this change. In this case make sure database contains latest data
  before updating the module, i.e. import back from export file before updating if needed.
- [#3013783] by flocondetoile: Provide a template for Reference entity to facilitate theming.
  As a result, new wrapper element is added to rendered reference entity markup. In rare cases this may affect
  appearance depending on applied CSS styles.

### Added
- [#3013783] by flocondetoile: Provide a template for Reference entity to facilitate theming

### Fixed
- [#2974615] by trustypelletgun: Endnote 7 XML secondary-title field is not imported
- [#3005950] by fmr: Serialization of references doesn't work properly with hal_json format
- [#2983288] by Web-Beest: REST not working
- [#3003787] by lbundt: When used with the Stacks module, there are errors when Contributors are viewed, edited, etc.
- [#3008366] by antongp: Avoid using deprecations where possible
- [#3006209] by antongp, adci_contributor: Endnote encode test fails
- [#3006184] by deepanker_bhalla: Export all reference data error
- [#3005002] by fmr: User module's user.private_tempstore and user.shared_tempstore services moved to core
- [#3002901] by kairos: Warnings when installing the Bibliography & Citation - Entity module
- [#2974615] by trustypelletgun: Endnote import fails on serializer->decode
- [#2970961] by antongp: Show correct version constraint for adci/full-name-parser package in help
- [#2965604] by gkaas: Unexpected error Endnote X3 XML export


## [8.x-1.0-alpha7] - 2018-04-16

### Fixed
- [#2960637] by xenahort, littledynamo: Citation no longer displays author names after upgrade to alpha-6


## [8.x-1.0-alpha6] - 2018-04-09

### Important
- [#2954101] by antongp: Routing cleanup
  **Note: menu items paths were changed.**
  Changed entities paths for Contributor, Keyword and Reference from "/admin/content/bibcite/\*" pattern to "/bibcite/\*".
  Changed path for managing references types.

### Added
- [#2788407] by kruhak: Implement tests
- [#2939860] by mgwisni: Add Author/Contributor Initials

### Changed
- [#2954571] by antongp: Contributors and Keywords entities are created before saving Reference entity when Populate Reference form is used
- [#2878975] Improve module permissions. Modify access handlers to cache permissions.
- [#2872606] by kruhak: Improve structure and look of configuration pages
- [#2954101] by antongp: Routing cleanup  
- [#2945406] by robcast: Import breaks on author with multiple initials. Update name parser package.

### Fixed
- [#2954624] by hunterbuchanan: Drupal lists module as Uninstalled with Invalid Info after installing dev version via Composer
- [#2945229] by littledynamo: Malformed inline YAML string when enabling bibcite_endnote module
- [#2939817] by littledynamo: Year of Publication field does not allow 'Submitted' or 'In Press', contrary to field description
- [#2946773] by antongp: Fatal error on import (from file) form when content cannot be parsed for some reason


## [8.x-1.0-alpha5] - 2018-02-03

### Important
- [#2878975] Improve module permissions.
  **Note: Bibliography & Citation - Entity module's permissions were renamed.**

  **Both machine-names and labels:**
  - Create new Reference entities --> Create new Reference entity of any type
  - Edit all Reference entities  --> Edit any Reference entity of any type
  - Edit own Reference entities --> Edit own Reference entity of any type
  - Delete all Reference entities --> Delete any Reference entity of any type
  - Delete own Reference entities --> Delete own Reference entity of any type

  **Only machine-names:**
  - Administer Reference entities
  - View Reference entities
  - Administer Contributor entities
  - Create new Contributor entities
  - Delete Contributor entities
  - Edit Contributor entities
  - View Contributor entities
  - Administer Keyword entities
  - Create new Keyword entities
  - Delete Keyword entities
  - Edit Keyword entities
  - View Keyword entities

  **The module will update these permissions in roles automatically on update.php run. 
  If you used checking for these permissions in some other places, for example in Views or custom code, please update these usages manually.**
  **In all admin views provided by the module access setting will be forced to new administrative permissions on update.
  If you have these views overridden and changed access setting in them and want to preserve these changes on update,
  please export/save configs before performing update and then import it after performing update,
  or just re-set needed access settings after update via UI.**
  Also new create/edit/delete permissions per reference entity bundle were added. Please note that "any type" permissions take a precedence over particular type permissions.
- [#2865678] Improve view modes handling.
  **Note: update creates new "Table" view mode which enforces Reference entity be outputted as table.**
  If you had "Display override" option from the settings page enabled before update
  and outputted Reference entities in "Default" view mode (as tables) somewhere else, 
  not only on Reference entity own page, then use new "Table" view mode instead of "Default"
  in those places after update.
- [#2879865] Update mapping for RIS.
  **Note: update forces resetting mapping to new version, even if there were changes from
  defaults.** In most cases new mapping should work well. If you have custom mapping for RIS
  and want to preserve it on update, please export/save bibcite_entity.mapping.ris config
  before performing update and then import it after performing update.
  Mapping changes:
  - AD (Author Address field): None --> Author Address
  - TI (Title): No changes
  - T1 (Primary Title): Title --> None
  - ST (Short Title): Title --> Short Title
  - CT (Title of unpublished reference): Title --> None
  - BT (This field can contain alphanumeric characters. There is no practical limit to the length of this field): Title --> None
  - User definable
    - U1: Custom 1 --> None 
    - U2: Custom 2 --> None 
    - U3: Custom 3 --> None 
    - U4: Custom 4 --> None 
    - U5: Custom 5 --> None 
  - Custom fields
    - C1: None --> Custom 1
    - C2: None --> Custom 2
    - C3: None --> Custom 3
    - C4: None --> Custom 4
    - C5: None --> Custom 5
    - C6: None --> Custom 6
    - C7: None --> Custom 7
  - IS (Issue number): No changes
  - CP (This field can contain alphanumeric characters. There is no practical limit to the length of this field): Issue --> None
  - JO (Journal/Periodical name: full format. This is an alphanumeric field of up to 255 characters): Short Title --> None
  - J1 (Periodical name: user abbreviation 1. This is an alphanumeric field of up to 255 characters): Short Title --> None
  - J2 (Alternate Title (this field is used for the abbreviated title of a book or journal name, the latter mapped to T2): Short Title --> None
  - T2 (Secondary Title (journal title, if applicable)): No changes
  - JA (Periodical name: standard abbreviation. This is the periodical in which the article was (or is to be, in the case of in-press references) published. This is an alphanumeric field of up to 255 characters): Secondary Title --> None
  - JF (Journal/Periodical name: full format. This is an alphanumeric field of up to 255 characters): Secondary Title --> None
  - LA (Language): None --> Publication Language
  - M1 (Number): None --> Number
  - M3 (Type of Work): None --> Type of Work
  - NV (Number of Volumes): None --> Number of Volumes
  - Y1 (Primary Date): Year of Publication --> None
  - PY (Publication year): None --> Year of Publication
  - RN (Research Notes): None --> Research Notes
  - SE (Section): None --> Section
  - SP (Start Page): None --> Number of Pages
  - TT (Translated Title): None --> Translated Title

### Added
- [#2940219]: Add extra field with reference type to reference entities
- [#2936662] by antongp: Add options to show role and/or category in contributor field formatter
- [#2865678] by kruhak: Add "Page" view mode to Reference entity type and configure it to display table by default  
Update Citation view mode to show extra field for citation output only, make all other fields hidden. 
Citation view mode doesn't force fields now to be hidden independently on view mode configuration.
Only table view mode is show as a table.
- [#2872598] by kruhak: Implement hook_help with basic information about modules
- [#2909824] by antongp: Add CHANGELOG
- [#2794159] by kruhak, antongp, adci_contributor: Implement format: MARC
- [#2794161] by kruhak: Implement format: EndNote Tagged
- [#2794165] by kruhak: Implement format: EndNote X3 XML
- [#2794163] by khandeli, kruhak: Implement format: EndNote 7 XML
- [#2794159] by kruhak: Implement format: MARC
- [#2903950] by camilocodes: Add publication language to BibTex mapping  
Add language field handling to BibTeX, map it appropriately.
- [#2865665]: Implement lookup and export links as plugins and make it available as extra field and views handler
- [#2865644]: Add action for multiple export references from admin view

### Changed
- [#2878975] by kruhak: Improve module permissions
- [#2930424] by antongp: Use "BibTeX" spelling in texts, not "Bibtex", "bibtex", etc
- [#2865620] by kruhak: Better error messages for Populate form
- [#2865648] by kruhak: Update default reference types  
Add description to reference types, prefill it with sensible defaults. On reference type edit form do not fill label textfields if default values are not overridden.
- [#2879865] by camilocodes: Missing bibcite fields for RefWorks-exported RIS
- [#2794001] by kruhak: Improve quality of human names parsing
- [#2794159] by kruhak: Implement format: MARC - update requirements in the README.md
- [#2865648] by kruhak: Update default reference types
- [#2879865] by camilocodes: Missing bibcite fields for RefWorks-exported RIS
- [#2890060] by jazztini: Bibtex entry types are case sensitive
- [#2904701] by camilocodes: RefWorks exported BibTex gets keywords imported into BibCite as single string
- [#2865622]: Update default configuration of admin views
- [#2870650] by Pavan B S, antongp: Make the module's code satisfy Drupal coding standards

### Fixed
- [#2941835] by antongp: Authored By field is available on reference create/edit forms to users without administrative permissions
- [#2940220]: Update bibcite_entity_update_8006() not always properly configures Default and Table display modes
- [#2930990] by Toki, antongp: New Reference entities, when populated via "Populate reference values", save only first author in the list
- [#2916433] by antongp: BibTeX and RIS exports fail
- [#2916115] by Shawn DeArmond: Mapping error when importing BibTex in Drupal 8.3
- [#2915126] by antongp: Import fails on Drupal 8.4
- [#2910276] by rfmarcelino: PHP error when Inline entity form is used
- [#2882855] by camilocodes: EndNote-exported BibTex: Issues with "type"
- [#2875764] by camilocodes: Carriage returns prevent RefWorks-exported BibTeX from being imported
- [#2878836] by camilocodes: Reference type field not displayed for users with all bibcite permissions
- [#2875387]: Keywords not being imported, Contributors are
- [#2875764] by camilocodes: Carriage returns prevent RefWorks-exported BibTeX from being imported
- [#2877810] by camilocodes: Adding the "Edit all Reference entities" permission to a role does not grant the ability to do so
- Fix merge actions schema definition
- [#2865631]: After first use of "Export all references" form files list does not appear
- [#2870641] by Pavan B S, antongp, dhruveshdtripathi: No configure link in module listing

### Removed
- [#2865633]: Remove "Enable export formats" setting
- [#2865625]: Delete inline classes from Author field widget


## [8.x-1.0-alpha4] - 2017-04-25

### Changed
- [#2865621]: Set default reference entity from hook, not from form class

### Fixed
- [#2870635]: Check entity keys, not format
- [#2870635]: Temporary normalizer fix for 8.3


## [8.x-1.0-alpha3] - 2017-03-30

### Added
- [#2860034] by Bwolf: Ability to Merge Duplicate Contributor Entries After Import
- [#2864557]: Views integration
- [#2864560]: Citation as extra field for Reference entity type
- [#2849258] by Bwolf: Include Bibliography & Citation entity display to be managed by Display Suite
- add schema for actions configuration
- [#2849617] by kruhak, pukku, Bwolf: Create actions (bulk operations) for bibliographic entity types
- [#2859088]: Create reference entity from one format entry

### Fixed
- Fix cancel routing name for ReferenceTypeDeleteForm
- [#2849617]: Fix update function, install action configs.
- [#2859088]: Fix temporary store identifiers


## [8.x-1.0-alpha2] - 2017-03-06

### Added
- [#2810581]: How to reuse keywords on import, not create new duplicate keyword records
- Add workarounds for BibtexParser library, explode unparsed keywords list
- Add workarounds for LibRIS library, optimize normalizer
- Add basic test for import of RIS format
- [#2832969]: Entity "Reference" - Add uid field and improve permissions
- [#2832987]: "Export all" form - Create a custom routing for downloading of generated files
- [#2832990]: Entity "Reference" - Create a form display for better UX with "Inline entity form"
- [#2832979]: Entity "Reference type" - Add ability to override labels and visibility of Reference fields
- [#2833305] by kruhak: Add bundles support for "Reference" entity type
- [#2832981] by kruhak: Add weight attribute to "Contributor role" and "Contributor category" entities

### Changed
- Optimize normalizers, use one denormalize method from base class
- Update tests, enable "user" module
- [#2836337]: Reference entity - Auto create keywords entries

### Fixed
- Fix "format" value in RIS format mapping


## [8.x-1.0-alpha1] - 2016-12-05

### Added
- Add example of composer commands to the README file
- [#2794157]: Implement format: RIS
- [#2788509]: Make mappings configurable
- [#2802791]: Management system for processor CSL styles
- [#2793977]: Contributor: Create full name string from name parts based on configurable policy
- [#2793983]: Contributor widget: create entity using full name string
- [#2793969]: Create/update Contributor entity using full name string
- [#2791563]: Use Drupal language in the citation processor
- [#2794049]: Export bibliographic data using Views and Action plugin
- [#2794003]: Create a simple interface for exporting all entities to available formats
- [#2794005]: Create batch processing for multiple bibliographic entities
- Added basic test for rendering entity to citation
- [#2791539]: Publication types as a configuration entity
- Added basic test for import module. Test decoding and denormalization.
- Added base test modules and simple test for main export functions
- [#2792531] by Bwolf: Added README.md file
- Add dependencies from export and import modules to the bibcite_entity module
- [#2788405]: Add entity_revers handlers to Contributor and Keyword entities
- [#2788405]: Add views data for Author and Keywords fields
- [#2788361] by discipolo: Add onKernelRequest event subscribers for export formats
- add configurable export links to the table view of bibliography entity
- add links element to the table view of bibliography entity
- get list of import plugins from plugin manager, implement DI
- create keywords entities from bibtex normalizer
- proceed import using batch
- add basic import form
- basic denormalization for bibtex format
- allow to create entities by reference field
- check if entity property is empty
- add HumanNameParser service
- bibcite_ris: allow to decode ris file via a library
- bibcite_ris: allow to export an entity to ris file
- bibcite_ris: normalize RIS format
- add import support to bibtex
- add bibcite_import module
- create configuration for lookup links
- add simple route for multiple export
- bibcite_ris allows to work with RIS format
- make action configurable
- add export action
- add views data for bibliography entity
- return string from encoder
- add links container
- add export links to bibliography full view
- add module settings
- add bibcite_export module with basic export route
- basic bibtex encoder
- add todo comment
- add "citation" view mode for bibliography entity
- add base theme hooks for citation elements
- static fields describe for bibliography entity
- mapping from entity fields to csl fields based on property
- add bibcite_bibtex module
- load default processor in getter
- add cite() method for rendering bibliography entity to citation
- use default processor if not set
- add Styler service and move default_style setting to main configuration
- get info about CSL fields and types from YAML files
- table view for bibliography entity
- add label formatter for contributor entity
- basic entity view with theme hooks
- CSL normalization for entity
- add basic serialization
- add configuration form for citeproc-php processor
- base csl fields
- module entities
- contributor fields
- base entity types and contributor field

### Changed
- Update CSL style updated time only from the form submit
- Use "bibliography_table" theme on the "default" view mode
- move keywords field to other tab
- Bibtex format: Change isset check to empty.
- [#2793991]: Contributor field/widget: Move contributor roles and categories to configuration level
- [#2791539]: Update type mappings for existing formats
- [#2794151]: Restructure of export and import services
- BibTex format decoder: Concat pages array to string.
- [#2788415]: Restructure of the module permissions and make entities accessible by users
- [#2788415]: Group properties to the vertical tabs using process function
- [#2788403]: Restructuring of Bibliography entity properties
- modules restructuring
- move human_name_parser service to the bibcite module
- Merge remote-tracking branch 'origin/entity' into entity
- Merge branch 'entity' of gitlab.com:adci/sc_pub into entity
- reorganize theme hooks
- Merge remote-tracking branch 'origin/entity' into entity
- Escaping special characters for yaml files
- change page title for settings page
- integrate bibtex format with export module
- bibtex normalization
- optimize normalizers for bibliography entity
- optimize entities paths
- use anonymous functions to set bibliography fields
- call services in cite method only
- rework citeproc plugin, move all logic to plugin class
- rename modules folders
- rename project to bibcite
- do not use count
- rename config properties and implement dependency injection
- move citeproc processor to independent service with basic settings
- settings form, handle processor description as render array
- rename entity module, add main module

### Fixed
- Fix configuration schema
- [#2811279] by JacobSanford, marqpdx, bibdoc: NameSpace Confusion
- [#2813871]: Unable to install Bibliography & Citation Entity, bibcite_entity_mapping.csl have dependencies not found
- [#2811281] by camilocodes: RIS import resulted in "bibliography" entities with no title
- Fixed CSL style form validation. Allow to update style
- Fixed CSL style form validation
- [#2804761] by antongp: Export links are not properly added to citations.
- Fix entity storage method declaration
- Fix Bibliography form. Use "#group" property for form restructuring.
- [#2791911] by Bwolf: Unable to configure settings: Parse Error When Trying to Edit Settings
- composer.json fixes
- fix unused statement
- fix serialization dependency
- fix dependencies
- fix array issue
- fix undefined title
- fix type key
- fix class paths
- fix styler service name
- fix default style validation
- fix cache id

### Removed
- Deleted label key from Contributor entity
- [#2788415]: Delete identifiers from the ListBuilders and add type field to the BibliographyListBuilder
- [#2788415]: Delete deprecated methods from ListBuilder classes
- remove some mess from comments
- delete unused import, add lost commentary
- Delete custom view builder and use default theme hook with different content
- delete unused variables
- delete CslDataProvider class and service, change plugin manager service name
- delete CslKeyConverter class
- delete unused files
- delete unused imports


[//]: # "Releases links"
[Unreleased]: https://www.drupal.org/project/bibcite/releases/8.x-1.x-dev
[8.x-1.0-alpha1]: https://www.drupal.org/project/bibcite/releases/8.x-1.0-alpha1
[8.x-1.0-alpha2]: https://www.drupal.org/project/bibcite/releases/8.x-1.0-alpha2
[8.x-1.0-alpha3]: https://www.drupal.org/project/bibcite/releases/8.x-1.0-alpha3
[8.x-1.0-alpha4]: https://www.drupal.org/project/bibcite/releases/8.x-1.0-alpha4
[8.x-1.0-alpha5]: https://www.drupal.org/project/bibcite/releases/8.x-1.0-alpha5
[8.x-1.0-alpha6]: https://www.drupal.org/project/bibcite/releases/8.x-1.0-alpha6
[8.x-1.0-alpha7]: https://www.drupal.org/project/bibcite/releases/8.x-1.0-alpha7
[8.x-1.0-alpha8]: https://www.drupal.org/project/bibcite/releases/8.x-1.0-alpha8
[8.x-1.0-alpha9]: https://www.drupal.org/project/bibcite/releases/8.x-1.0-alpha9


[//]: # "Issues links alpha1"
[#2794157]: https://www.drupal.org/node/2794157
[#2788509]: https://www.drupal.org/node/2788509
[#2802791]: https://www.drupal.org/node/2802791
[#2793977]: https://www.drupal.org/node/2793977
[#2793983]: https://www.drupal.org/node/2793983
[#2793969]: https://www.drupal.org/node/2793969
[#2791563]: https://www.drupal.org/node/2791563
[#2794049]: https://www.drupal.org/node/2794049
[#2794003]: https://www.drupal.org/node/2794003
[#2794005]: https://www.drupal.org/node/2794005
[#2791539]: https://www.drupal.org/node/2791539
[#2792531]: https://www.drupal.org/node/2792531
[#2788405]: https://www.drupal.org/node/2788405
[#2788361]: https://www.drupal.org/node/2788361
[#2793991]: https://www.drupal.org/node/2793991
[#2794151]: https://www.drupal.org/node/2794151
[#2788415]: https://www.drupal.org/node/2788415
[#2788403]: https://www.drupal.org/node/2788403
[#2811279]: https://www.drupal.org/node/2811279
[#2813871]: https://www.drupal.org/node/2813871
[#2811281]: https://www.drupal.org/node/2811281
[#2804761]: https://www.drupal.org/node/2804761
[#2791911]: https://www.drupal.org/node/2791911


[//]: # "Issues links alpha2"
[#2810581]: https://www.drupal.org/node/2810581
[#2832969]: https://www.drupal.org/node/2832969
[#2836337]: https://www.drupal.org/node/2836337
[#2832987]: https://www.drupal.org/node/2832987
[#2832990]: https://www.drupal.org/node/2832990
[#2832979]: https://www.drupal.org/node/2832979
[#2833305]: https://www.drupal.org/node/2833305
[#2832981]: https://www.drupal.org/node/2832981


[//]: # "Issues links alpha3"
[#2860034]: https://www.drupal.org/node/2860034
[#2864557]: https://www.drupal.org/node/2864557
[#2864560]: https://www.drupal.org/node/2864560
[#2849258]: https://www.drupal.org/node/2849258
[#2849617]: https://www.drupal.org/node/2849617
[#2859088]: https://www.drupal.org/node/2859088


[//]: # "Issues links alpha4"
[#2870635]: https://www.drupal.org/node/2870635
[#2865621]: https://www.drupal.org/node/2865621


[//]: # "Issues links alpha5"
[#2941835]: https://www.drupal.org/node/2941835
[#2878975]: https://www.drupal.org/node/2878975
[#2940219]: https://www.drupal.org/node/2940219
[#2940220]: https://www.drupal.org/node/2940220
[#2930990]: https://www.drupal.org/node/2930990
[#2936662]: https://www.drupal.org/node/2936662
[#2865678]: https://www.drupal.org/node/2865678
[#2872598]: https://www.drupal.org/node/2872598
[#2930424]: https://www.drupal.org/node/2930424
[#2909824]: https://www.drupal.org/node/2909824
[#2865620]: https://www.drupal.org/node/2865620
[#2865648]: https://www.drupal.org/node/2865648
[#2879865]: https://www.drupal.org/node/2879865
[#2916433]: https://www.drupal.org/node/2916433
[#2916115]: https://www.drupal.org/node/2916115
[#2915126]: https://www.drupal.org/node/2915126
[#2794001]: https://www.drupal.org/node/2794001
[#2794159]: https://www.drupal.org/node/2794159
[#2910276]: https://www.drupal.org/node/2910276
[#2890060]: https://www.drupal.org/node/2890060
[#2794161]: https://www.drupal.org/node/2794161
[#2794165]: https://www.drupal.org/node/2794165
[#2794163]: https://www.drupal.org/node/2794163
[#2903950]: https://www.drupal.org/node/2903950
[#2865665]: https://www.drupal.org/node/2865665
[#2865644]: https://www.drupal.org/node/2865644
[#2865648]: https://www.drupal.org/node/2865648
[#2879865]: https://www.drupal.org/node/2879865
[#2904701]: https://www.drupal.org/node/2904701
[#2865622]: https://www.drupal.org/node/2865622
[#2870650]: https://www.drupal.org/node/2870650
[#2882855]: https://www.drupal.org/node/2882855
[#2875764]: https://www.drupal.org/node/2875764
[#2878836]: https://www.drupal.org/node/2878836
[#2875387]: https://www.drupal.org/node/2875387
[#2877810]: https://www.drupal.org/node/2877810
[#2865631]: https://www.drupal.org/node/2865631
[#2870641]: https://www.drupal.org/node/2870641
[#2865633]: https://www.drupal.org/node/2865633
[#2865625]: https://www.drupal.org/node/2865625


[//]: # "Issues links alpha6"
[#2954571]: https://www.drupal.org/node/2954571
[#2954624]: https://www.drupal.org/node/2954624
[#2788407]: https://www.drupal.org/node/2788407
[#2872606]: https://www.drupal.org/node/2872606
[#2954101]: https://www.drupal.org/node/2954101
[#2945229]: https://www.drupal.org/node/2945229
[#2939817]: https://www.drupal.org/node/2939817
[#2945406]: https://www.drupal.org/node/2945406
[#2946773]: https://www.drupal.org/node/2946773
[#2939860]: https://www.drupal.org/node/2939860


[//]: # "Issues links alpha7"
[#2960637]: https://www.drupal.org/node/2960637


[//]: # "Issues links alpha8"
[#2974615]: https://www.drupal.org/node/2974615
[#3005950]: https://www.drupal.org/node/3005950
[#2983288]: https://www.drupal.org/node/2983288
[#3013783]: https://www.drupal.org/node/3013783
[#3003787]: https://www.drupal.org/node/3003787
[#3008366]: https://www.drupal.org/node/3008366
[#3006209]: https://www.drupal.org/node/3006209
[#3006184]: https://www.drupal.org/node/3006184
[#3005002]: https://www.drupal.org/node/3005002
[#3002901]: https://www.drupal.org/node/3002901
[#2974615]: https://www.drupal.org/node/2974615
[#2970961]: https://www.drupal.org/node/2970961
[#2965604]: https://www.drupal.org/node/2965604


[//]: # "Issues links alpha9"
[#3050347]: https://www.drupal.org/node/3050347
[#3020717]: https://www.drupal.org/node/3020717
[#3053035]: https://www.drupal.org/node/3053035
