<?php

namespace Drupal\Tests\user_guide_tests\FunctionalJavascript;

/**
 * Builds the demo site for the User Guide, French, with screenshots.
 *
 * See README.txt file in the module directory for more information about
 * making screenshots.
 *
 * @group UserGuide
 */
class UserGuideDemoTestFr extends UserGuideDemoTestBase {

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
    'first_langcode' => "fr",
    'second_langcode' => "en",

    'site_name' => "Marché de la ferme",
    'site_slogan' => "Bons produits de la ferme",
    'site_mail' => "info@example.com",
    'site_default_country' => "FR",
    'date_default_timezone' => "Europe/Paris",

    'home_title' => "Accueil",
    'home_body' => "<p>Bienvenue au Marché- votre marché de la ferme de proximité!</p><p>Ouvert : Dimanche de 9h à 14h, d’Avril à Septembre</p><p>Localisation: Parking de la Poste, Place du village, centre-ville</p>",
    'home_summary' => "Horaires d’ouvrtures et localisation du Marché",
    'home_path' => "/home",
    'home_revision_log_message' => "Mise à jour des heures d’ouverture",

    'home_title_translated' => "Home ",
    'home_body_translated' => "<p>Welcome to City Market - your neighborhood farmers market!</p><p>Open: Sundays, 9 AM to 2 PM, April to September</p><p>Location: Parking lot of Trust Bank, 1st & Union, downtown</p>",
    'home_path_translated' => "/home",

    'about_title' => "A propos",
    'about_body' => "<p>Le Marché a démarré en Avril 1990 avec 5 vendeurs.</p><p>Aujourd’hui,c’est 100 vendeurs et environ 2000 visiteurs par jour.</p>",
    'about_path' => "/about",
    'about_description' => "Histoire du marché",

    'vendor_type_name' => "Vendeur",
    'vendor_type_machine_name' => "vendeur",
    'vendor_type_description' => "Informationà propos du vendeur",
    'vendor_type_title_label' => "Nom du vendeur",
    'vendor_field_url_label' => "URL du Vendeur",
    'vendor_field_url_machine_name' => "url_vendeur",
    'vendor_field_image_label' => "Image principale",
    'vendor_field_image_machine_name' => "image_principale",
    'vendor_field_image_directory' => "vendeurs",

    'vendor_1_title' => "Ferme Joyeuse",
    'vendor_1_path' => "/vendeurs/joyeuse_ferme",
    'vendor_1_summary' => "La Ferme Joyeuse fait pousser des légumes que vous aimerez.",
    'vendor_1_body' => "<p>La Ferme Joyeuse fait pousser des légumes que vous aimerez.</p><p>Nous cultivons des tomates, des carottes, des betteraves et de nombreuses variétés de salades verte.</p>",
    'vendor_1_url' => "http://happyfarm.com",
    'vendor_1_email' => "happy@example.com",

    'vendor_2_title' => "Miel Moelleux",
    'vendor_2_path' => "/vendeurs/miel_moelleux",
    'vendor_2_summary' => "Miel Moelleux produit du miel aux différentes saveurs tout au long de l’année.",
    'vendor_2_body' => "<p>Miel Moelleux produit du miel aux différentes saveurs tout au long de l’année.</p><p>Nos variétés inclues, le trefle , la fleur de pommier, et la fraise.</p>",
    'vendor_2_url' => "http://sweethoney.com",
    'vendor_2_email' => "honey@example.com",

    'recipe_type_name' => "Recette",
    'recipe_type_machine_name' => "recette",
    'recipe_type_description' => "Recette soumise par un vendeur",
    'recipe_type_title_label' => "Nom de la recette",
    'recipe_field_image_directory' => "recettes",
    'recipe_field_ingredients_label' => "Ingredients",
    'recipe_field_ingredients_machine_name' => "ingredients",
    'recipe_field_ingredients_help' => "Entrer les ingrédients pour lesquels un visiteur du site peut vouloir rechercher",
    'recipe_field_submitted_label' => "Soumis par",
    'recipe_field_submitted_machine_name' => "soumis_par",
    'recipe_field_submitted_help' => "Choisir le vendeur qui a proposé la recette",

    'recipe_field_ingredients_term_1' => "Beurre",
    'recipe_field_ingredients_term_2' => "Oeufs",
    'recipe_field_ingredients_term_3' => "Lait",
    'recipe_field_ingredients_term_4' => "Carottes",

    'recipe_1_title' => "Salade verte",
    'recipe_1_path' => "/recette/salade_verte",
    'recipe_1_body' => "Hacher vos légumes préférer et les mettre dans un bol.",
    'recipe_1_ingredients' => "Carottes",

    'recipe_2_title' => "Carottes fraiches",
    'recipe_2_path' => "/recettes/carottes",
    'recipe_2_body' => "Server des carottes multicolors dans un plat pour diner",
    'recipe_2_ingredients' => "Carottes",

    'image_style_label' => "Extra medium (300x200)",
    'image_style_machine_name' => "extra_medium_300x200",

    'hours_block_description' => "Bloc heures et localisation",
    'hours_block_title' => "Heures et localisation",
    'hours_block_title_machine_name' => "heures_localisation",
    'hours_block_body' => "<p>Ouvert : Dimanche de 9h à 14h, d’Avril à Septembre</p><p>Localisation: Parking de la Poste, Place du village, centre-ville</p>",

    'vendors_view_title' => "Vendeurs",
    'vendors_view_machine_name' => "vendeurs",
    'vendors_view_path' => "vendeurs",

    'recipes_view_title' => "Recettes",
    'recipes_view_machine_name' => "recettes",
    'recipes_view_path' => "recettes",
    'recipes_view_ingredients_label' => "Trouver une recette utilisant ..;",
    'recipes_view_block_display_name' => "Recettes récentes",
    'recipes_view_block_title' => "Nouvelles recettes",

    'recipes_view_title_translated' => "Recipes",
    'recipes_view_submit_button_translated' => "Appliquer",
    'recipes_view_ingredients_label_translated' => "Find recipes using...",
  ];

}
