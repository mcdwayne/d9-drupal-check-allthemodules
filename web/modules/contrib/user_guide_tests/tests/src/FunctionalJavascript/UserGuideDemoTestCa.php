<?php

namespace Drupal\Tests\user_guide_tests\FunctionalJavascript;

/**
 * Builds the demo site for the User Guide, Catalan, with screenshots.
 *
 * See README.txt file in the module directory for more information about
 * making screenshots.
 *
 * @group UserGuide
 */
class UserGuideDemoTestCa extends UserGuideDemoTestBase {

  /**
   * Non-override of UserGuideDemoTestBase::runList.
   *
   * If you want to run only some chapters, or want to make backups, change
   * the name of this variable (locally and temporarily) to $runList, and then
   * change 'skip' to one of the other values for each chapter you want to run.
   * See UserGuideDemoTestBase::runList for more information.
   */
  protected $notRunList = [
    'doPrefaceInstall' => 'skip',
    'doBasicConfig' => 'skip',
    'doBasicPage' => 'skip',
    'doContentStructure' => 'skip',
    'doUserAccounts' => 'skip',
    'doBlocks' => 'skip',
    'doViews' => 'skip',
    'doMultilingualSetup' => 'skip',
    'doTranslating' => 'skip',
    'doExtending' => 'skip',
    'doPreventing' => 'skip',
    'doSecurity' => 'skip',
  ];

  /**
   * {@inheritdoc}
   */
  protected $demoInput = [
    'first_langcode' => "ca",
    'second_langcode' => "en",

    'site_name' => "Mercat dels grangers de la ciutat",
    'site_slogan' => "Granja d'aliments frescos",
    'site_mail' => "info@example.com",
    'site_default_country' => "ES",
    'date_default_timezone' => "Europe/Madrid",

    'home_title' => "Portada",
    'home_body' => "<p>Benvingut/da al Mercat de la Ciutat – el mercat dels granjers del teu veïnat!</p><p>Obert: Diumenges de 9.00 a 14.00, des de l'abril al setembre</p><p>Localització: Aparcament del Banc de Confiança, 1r, al centre</p>",
    'home_summary' => "Horaris I localització del Mercat de la Ciutat",
    'home_path' => "/portada",
    'home_revision_log_message' => "Actualitzats els horaris d'obertura",

    'home_title_translated' => "Home",
    'home_body_translated' => "<p>Welcome to City Market - your neighborhood farmers market!</p><p>Open: Sundays, 9 AM to 2 PM, April to September</p><p>Location: Parking lot of Trust Bank, 1st & Union, downtown</p>",
    'home_path_translated' => "/home",

    'about_title' => "Sobre nosaltres",
    'about_body' => "<p>El Mercat de la Ciutat s'inaugurà a l'abril del 1990 amb cinc venedors.</p><p>Avui té més de 100 venedors i una mitjana de 2000 visites al dia.</p>",
    'about_path' => "/sobre",
    'about_description' => "Història del mercat",

    'vendor_type_name' => "Venedor",
    'vendor_type_machine_name' => "venedor",
    'vendor_type_description' => "Informació sobre un venedor",
    'vendor_type_title_label' => "Nom del venedor",
    'vendor_field_url_label' => "URL del venedor",
    'vendor_field_url_machine_name' => "venedor_url",
    'vendor_field_image_label' => "Imatge principal",
    'vendor_field_image_machine_name' => "imatge_principal",
    'vendor_field_image_directory' => "venedors",

    'vendor_1_title' => "Granja Feliç",
    'vendor_1_path' => "/venedors/granja_felic",
    'vendor_1_summary' => "Granja Feliç conrea verdures que li encantaran.",
    'vendor_1_body' => "<p>La Granja Feliç conrea verdures que li encantaran.</p><p>Conreem tomàquets, pastanagues I remolatxes a més a més d'una gran varietat d'enciams.</p>",
    'vendor_1_url' => "http://granjafelic.cat",
    'vendor_1_email' => "felic@example.com",

    'vendor_2_title' => "Dolça Mel",
    'vendor_2_path' => "/venedors/dolca_mel",
    'vendor_2_summary' => "Dolça Mel produeix mel amb diversos gustos durant tot l'any.",
    'vendor_2_body' => "<p>Dolça Mel produeix mel amb diversos gustos durant tot l'any.</p><p>Les nostres varietats inclouen trèvol, flor de la poma I maduixa.</p>",
    'vendor_2_url' => "http://dolcamel.cat",
    'vendor_2_email' => "mel@example.com",

    'recipe_type_name' => "Recepta",
    'recipe_type_machine_name' => "recepta",
    'recipe_type_description' => "Recepta enviada per un venedor",
    'recipe_type_title_label' => "Nom de la recepta",
    'recipe_field_image_directory' => "receptes",
    'recipe_field_ingredients_label' => "Ingredients",
    'recipe_field_ingredients_machine_name' => "ingredients",
    'recipe_field_ingredients_help' => "Introduïu ingredients que els visitants del lloc pot ser que vulguin buscar",
    'recipe_field_submitted_label' => "Enviat per",
    'recipe_field_submitted_machine_name' => "enviat_per",
    'recipe_field_submitted_help' => "Esculliu el venedor que va enviar aquesta recepta",

    'recipe_field_ingredients_term_1' => "Mantega",
    'recipe_field_ingredients_term_2' => "Ous",
    'recipe_field_ingredients_term_3' => "Llet",
    'recipe_field_ingredients_term_4' => "Pastanagues",

    'recipe_1_title' => "Amanida verda",
    'recipe_1_path' => "/receptes/amanida_verda",
    'recipe_1_body' => "Piqueu les vostres verdures preferides I poseu-les dins d'un bol.",
    'recipe_1_ingredients' => "Pastanagues",

    'recipe_2_title' => "Pastanagues fresques",
    'recipe_2_path' => "/receptes/pastanagues",
    'recipe_2_body' => "Serviu pastanagues de diversos colors en un plat per sopar.",
    'recipe_2_ingredients' => "Pastanagues",

    'image_style_label' => "Mitjana extra (300x200)",
    'image_style_machine_name' => "mitjana_extra_300x200",

    'hours_block_description' => "Bloc d'horari i localització",
    'hours_block_title' => "Horari i localització",
    'hours_block_title_machine_name' => "horari_localitzacio",
    'hours_block_body' => "<p>Obert: Diumenges de 9.00 a 14.00, des de l'abril al setembre</p><p>Localització: Aparcament del Banc de Confiança, 1r, al centre</p>",

    'vendors_view_title' => "Venedors",
    'vendors_view_machine_name' => "venedors",
    'vendors_view_path' => "venedors",

    'recipes_view_title' => "Receptes",
    'recipes_view_machine_name' => "receptes",
    'recipes_view_path' => "receptes",
    'recipes_view_ingredients_label' => "Cerqueu receptes usant…",
    'recipes_view_block_display_name' => "Darreres receptes",
    'recipes_view_block_title' => "Noves receptes",

    'recipes_view_title_translated' => "Recipes",
    'recipes_view_submit_button_translated' => "Apply",
    'recipes_view_ingredients_label_translated' => "Find recipes using...",

  ];

}
