<?php

namespace Drupal\Tests\user_guide_tests\FunctionalJavascript;

/**
 * Builds the demo site for the User Guide, Hungarian, with screenshots.
 *
 * See README.txt file in the module directory for more information about
 * making screenshots.
 *
 * @group UserGuide
 */
class UserGuideDemoTestHu extends UserGuideDemoTestBase {

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
    'first_langcode' => 'hu',
    'second_langcode' => 'en',

    'site_name' => 'Bárkifalva Termelői Piac',
    'site_slogan' => 'Zamatosat, frissen!',
    'site_mail' => 'irj.nekunk@pelda.hu',
    'site_default_country' => 'HU',
    'date_default_timezone' => 'Europe/Budapest',

    'home_title' => 'Címlap',
    'home_body' => '<p>Köszöntjük a Városi Piacon! Ez a helybeli termelői piacunk is.</p><p>Nyitvatartás: vasárnaponként 9 órától 14 óráig, áprilistól szeptemberig</p><p>Helyszín: Fő tér 1. a városközpontban, a Bizalom Bank parkolójában</p>',
    'home_summary' => 'Városi Piac nyitvatartás és helyszín',
    'home_path' => '/cimlap',
    'home_revision_log_message' => 'Új nyitvatartás',

    'home_title_translated' => 'Home',
    'home_body_translated' => '<p>Welcome to City Market - your neighborhood farmers market!</p><p>Open: Sundays, 9 AM to 2 PM, April to September</p><p>Location: Parking lot of Trust Bank, 1st & Union, downtown</p>',
    'home_path_translated' => '/home',

    'about_title' => 'Rólunk',
    'about_body' => '<p>A Városi Piacot 1990 árpilisában indítottuk 5 árussal.</p><p>Mára 100 kofa kínálja áruit átlagosan napi 2000 vevőnek.</p>',
    'about_path' => '/rolunk',
    'about_description' => 'A piac története',

    'vendor_type_name' => 'Árus',
    'vendor_type_machine_name' => 'arus',
    'vendor_type_description' => 'Ismertető az árusról',
    'vendor_type_title_label' => 'Árus neve',
    'vendor_field_url_label' => 'Árus webcíme',
    'vendor_field_url_machine_name' => 'arus_url',
    'vendor_field_image_label' => 'Fő kép',
    'vendor_field_image_machine_name' => 'fo_kep',
    'vendor_field_image_directory' => 'arusok',

    'vendor_1_title' => 'Víg Tanya',
    'vendor_1_path' => '/arusok/vig-tanya',
    'vendor_1_summary' => 'A Víg Tanyán zamatos zöldégeket termesztünk. ',
    'vendor_1_body' => '<p>A Víg Tanyán zamatos zöldségeket termesztünk.</p><p>Nő nálunk paradicsom, répa és cékla is, meg sokféle salátalevél.</p>',
    'vendor_1_url' => 'http://vigtanya.hu/',
    'vendor_1_email' => 'jozsef@vigtanya.hu',

    'vendor_2_title' => 'Csemege Méhészet',
    'vendor_2_path' => '/arusok/csemege-meheszet',
    'vendor_2_summary' => 'Méhészetünkben különböző fajtájú mézeket készítünk az év egész szakában.',
    'vendor_2_body' => '<p>Méhészetünkben különböző fajtájú mézeket készítünk az év egész szakában.</p><p>Ezen kívül pedig lóhere, almavirág és eper ízesítésű mézek is kaphatóak.</p>',
    'vendor_2_url' => 'http://csemegemeheszet.hu/',
    'vendor_2_email' => 'info@csemegemeheszet.hu',

    'recipe_type_name' => 'Recept',
    'recipe_type_machine_name' => 'recept',
    'recipe_type_description' => 'Árus által beküldött recept',
    'recipe_type_title_label' => 'Recept neve',
    'recipe_field_image_directory' => 'receptek',
    'recipe_field_ingredients_label' => 'Hozzávalók',
    'recipe_field_ingredients_machine_name' => 'hozzavalok',
    'recipe_field_ingredients_help' => 'répa, retek, burgonya',
    'recipe_field_submitted_label' => 'Beküldte',
    'recipe_field_submitted_machine_name' => 'bekuldo',
    'recipe_field_submitted_help' => 'Kiválasztható a receptet beküldő árus.',

    'recipe_field_ingredients_term_1' => 'Vaj',
    'recipe_field_ingredients_term_2' => 'Tojás',
    'recipe_field_ingredients_term_3' => 'Tej',
    'recipe_field_ingredients_term_4' => 'Répa',

    'recipe_1_title' => 'Zöldségsaláta',
    'recipe_1_path' => '/receptek/zoldsegsalata',
    'recipe_1_body' => 'Kedvenc zöldségeidet tisztítsd meg, aprítsd darabokra, és öntsd egy tálba.',
    'recipe_1_ingredients' => 'Répa',

    'recipe_2_title' => 'Friss répa nyersen',
    'recipe_2_path' => '/receptek/friss-repa-nyersen',
    'recipe_2_body' => 'Vegyes színű répákból készült mix vacsorára.',
    'recipe_2_ingredients' => 'Répa',

    'image_style_label' => 'Közepesen nagy (300x200)',
    'image_style_machine_name' => 'kozepesen_nagy_300x200',

    'hours_block_description' => 'Helyszín és nyitvatartás blokk',
    'hours_block_title' => 'Helyszín és nyitvatartás',
    'hours_block_title_machine_name' => 'hely_nyitvatartas',
    'hours_block_body' => '<p>Nyitvatartás: vasárnaponként 9 órától 14 óráig, áprilistól szeptemberig</p><p>Helyszín: Fő tér 1. a városközpontban, a Bizalom Bank parkolójában</p>',

    'vendors_view_title' => 'Árusok',
    'vendors_view_machine_name' => 'arusok',
    'vendors_view_path' => 'arusok',

    'recipes_view_title' => 'Receptek',
    'recipes_view_machine_name' => 'receptek',
    'recipes_view_path' => 'receptek',
    'recipes_view_ingredients_label' => 'Recept ezekkel a hozzávalókkal...',
    'recipes_view_block_display_name' => 'Legutóbbi receptek',
    'recipes_view_block_title' => 'Új receptek',

    'recipes_view_title_translated' => 'Recipes',
    'recipes_view_submit_button_translated' => 'Apply',
    'recipes_view_ingredients_label_translated' => 'Find recipes using...',

  ];

}
