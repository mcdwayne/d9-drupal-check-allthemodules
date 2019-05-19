<?php

namespace Drupal\Tests\user_guide_tests\FunctionalJavascript;

/**
 * Builds the demo site for the User Guide, Russian, with screenshots.
 *
 * See README.txt file in the module directory for more information about
 * making screenshots.
 *
 * @group UserGuide
 */
class UserGuideDemoTestRu extends UserGuideDemoTestBase {

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
    'first_langcode' => "ru",
    'second_langcode' => "en",

    'site_name' => "Рынок продуктов города N",
    'site_slogan' => "Свежие продукты от фермеских хозяйств",
    'site_mail' => "info@example.com",
    'site_default_country' => "RU",
    'date_default_timezone' => "Europe/Moscow",

    'home_title' => "Главная",
    'home_body' => "<p>Добро пожаловать на ярмарку города N – лучшие товары от производителей!</p><p>Часы работы: Воскресенье, 9:00 – 14:00, с Апреля по Сентябрь</p><p>Адрес: площадь Пушкина</p>",
    'home_summary' => "Часы работы и адрес городской ярмарки",
    'home_path' => "/home",
    'home_revision_log_message' => "Обновлены часы работы",

    'home_title_translated' => "Главная",
    'home_body_translated' => "<p>Добро пожаловать на ярмарку города N – лучшие товары от производителей!</p><p>Часы работы: Воскресенье, 9:00 – 14:00, с Апреля по Сентябрь</p><p>Адрес: площадь Пушкина</p>",
    'home_path_translated' => "/home",

    'about_title' => "О рынке",
    'about_body' => "<p>Городская ярмарка впервые открылась в апреле 1999 при участие 5 фермеских хозяйств.</p><p>Сегодня на ярмарке участвоваюсь 100 производителей и в среднем 2000 посетителей в день.</p>",
    'about_path' => "/about",
    'about_description' => "История ярмарки",

    'vendor_type_name' => "Производитель",
    'vendor_type_machine_name' => "vendor",
    'vendor_type_description' => "Информация о производителе",
    'vendor_type_title_label' => "Наименование производителя",
    'vendor_field_url_label' => "URL производителя",
    'vendor_field_url_machine_name' => "vendor_url",
    'vendor_field_image_label' => "Главное изображение",
    'vendor_field_image_machine_name' => "main_image",
    'vendor_field_image_directory' => "vendors",

    'vendor_1_title' => "Веселый молочник",
    'vendor_1_path' => "/vendors/happy_farm",
    'vendor_1_summary' => "Веселый молочник производит кисломолочные продукты с любовью.",
    'vendor_1_body' => "<p>Веселый молочник производит кисломолочные продукты с любовью.</p><p>Мы производим свежее молоко, сметану, творог и сыры.</p>",
    'vendor_1_url' => "http://happyfarm.com",
    'vendor_1_email' => "happy@example.com",

    'vendor_2_title' => "Алтайский мед",
    'vendor_2_path' => "/vendors/sweet_honey",
    'vendor_2_summary' => "Алтайский мед -  вкус лета круглый год!",
    'vendor_2_body' => "<p>Алтайский мед -  вкус лета круглый год!</p><p>Мы любим мед и знаем, где взять хороший, натуральный, вкусный и полезный!</p>",
    'vendor_2_url' => "http://sweethoney.com",
    'vendor_2_email' => "honey@example.com",

    'recipe_type_name' => "Рецепт",
    'recipe_type_machine_name' => "recipe",
    'recipe_type_description' => "Рецепт добавленный производителем",
    'recipe_type_title_label' => "Название рецепта",
    'recipe_field_image_directory' => "recipes",
    'recipe_field_ingredients_label' => "Ингредиенты",
    'recipe_field_ingredients_machine_name' => "ingredients",
    'recipe_field_ingredients_help' => "Добавьте ингредиенты, чтобы посетители сайта найти нужный рецепт",
    'recipe_field_submitted_label' => "Добавлен",
    'recipe_field_submitted_machine_name' => "submitted_by",
    'recipe_field_submitted_help' => "Выберите производителя, который добавил этот рецепт ",

    'recipe_field_ingredients_term_1' => "Масло",
    'recipe_field_ingredients_term_2' => "Яйца",
    'recipe_field_ingredients_term_3' => "Молоко",
    'recipe_field_ingredients_term_4' => "Морковь",

    'recipe_1_title' => "Зеленый салат",
    'recipe_1_path' => "/recipes/green_salad",
    'recipe_1_body' => "Нарежьте своих любимых овощей и положите их в чашу.",
    'recipe_1_ingredients' => "Морковь",

    'recipe_2_title' => "Свежая морковь",
    'recipe_2_path' => "/recipes/carrots",
    'recipe_2_body' => "Сервируйте тарелку разноцветной морковью.",
    'recipe_2_ingredients' => "Морковь",

    'image_style_label' => "Средний (300x200)",
    'image_style_machine_name' => "extra_medium_300x200",

    'hours_block_description' => "Часы работы и адрес блок",
    'hours_block_title' => "Часы работы и адрес",
    'hours_block_title_machine_name' => "hours_location",
    'hours_block_body' => "<p>Часы работы: Воскресенье, 9:00 – 14:00, с Апреля по Сентябрь</p><p>Адрес: площадь Пушкина</p>",

    'vendors_view_title' => "Производители",
    'vendors_view_machine_name' => "vendors",
    'vendors_view_path' => "vendors",

    'recipes_view_title' => "Рецепты",
    'recipes_view_machine_name' => "recipes",
    'recipes_view_path' => "recipes",
    'recipes_view_ingredients_label' => "Найти рецепты с…",
    'recipes_view_block_display_name' => "Недавние рецепты",
    'recipes_view_block_title' => "Новые рецепты",

    'recipes_view_title_translated' => "Рецепты",
    'recipes_view_submit_button_translated' => "Применить",
    'recipes_view_ingredients_label_translated' => "Найти рецепты с…",
  ];

}
