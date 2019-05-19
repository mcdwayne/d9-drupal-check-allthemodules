<?php

namespace Drupal\Tests\user_guide_tests\FunctionalJavascript;

/**
 * Builds the demo site for the User Guide, Persian/Farsi, with screenshots.
 *
 * See README.txt file in the module directory for more information about
 * making screenshots.
 *
 * @group UserGuide
 */
class UserGuideDemoTestFa extends UserGuideDemoTestBase {

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
    'first_langcode' => "fa",
    'second_langcode' => "en",

    'site_name' => "فروشگاه محصولات کشاورزی",
    'site_slogan' => "خرید محصولات تازه",
    'site_mail' => "info@example.com",
    'site_default_country' => "IR",
    'date_default_timezone' => "Asia/Tehran",

    'home_title' => "سرآغاز",
    'home_body' => "<p>به فروشگاه محصولات کشاورزی خود خوش آمدید!</p><p>ساعت کاری: یکشنبه‌ها از ۰۹:۰۰ تا ۱۶:۰۰ - اردیبهشت تا شهریور</p><p>مکان: پارکینگ شعبه مرکزی بانک ملی، تهران</p>",
    'home_summary' => "ساعت کاری و مکان فروشگاه شهر",
    'home_path' => "/home",
    'home_revision_log_message' => "ساعت کاری بروزرسانی شد",

    'home_title_translated' => "Home",
    'home_body_translated' => "<p>Welcome to City Market - your neighborhood farmers market!</p><p>Open: Sundays, 9 AM to 2 PM, April to September</p><p>Location: Parking lot of Trust Bank, 1st & Union, downtown</p>",
    'home_path_translated' => "/home",

    'about_title' => "درباره",
    'about_body' => "<p>این فروشگاه در سال ۱۳۶۷ با پنج فروشنده اولیه آعاز بکار کرد.</p><p>امروزه، دارای بیش از ۱۰۰ فروشنده و میانگین بازدیدکنندگان روزانه به شمار ۲۰۰۰ نفر می‌باشد.</p>",
    'about_path' => "/about",
    'about_description' => "تاریخچه فروشگاه",

    'vendor_type_name' => "فروشنده",
    'vendor_type_machine_name' => "vendor",
    'vendor_type_description' => "اطلاعات درباره یک فروشنده",
    'vendor_type_title_label' => "نام فروشنده",
    'vendor_field_url_label' => "نشانی فروشنده",
    'vendor_field_url_machine_name' => "vendor_url",
    'vendor_field_image_label' => "تصویر اصلی",
    'vendor_field_image_machine_name' => "main_image",
    'vendor_field_image_directory' => "vendors",

    'vendor_1_title' => "مزرعه خوشحال",
    'vendor_1_path' => "/vendors/happy_farm",
    'vendor_1_summary' => "مزرعه خوشحال به کاشت سبزیجات مورد علاقه شما می‌پردازد.",
    'vendor_1_body' => "<p>مزرعه خوشحال به کاشت سبزیجات مورد علاقه شما می‌پردازد.</p><p>محصولات اصلی ما عبارتند از: گوجه‌فرنگی، هویج و سبزیجات مورد نیاز انواع سالادها.</p>",
    'vendor_1_url' => "http://happyfarm.com",
    'vendor_1_email' => "happy@example.com",

    'vendor_2_title' => "عسل خوشمزه",
    'vendor_2_path' => "/vendors/sweet_honey",
    'vendor_2_summary' => "عسل خوشمزه به تولید خوشمزه‌ترین عسل موجود در بازار مشغول است.",
    'vendor_2_body' => "<p>عسل خوشمزه به تولید باکیفیت‌ترین عسل در تولید سال معروف است.</p><p>عسل‌های ما با طعم‌های شکوفه درخت سیب، کلابی و توت‌فرنگی مشهور هستند.</p>",
    'vendor_2_url' => "http://sweethoney.com",
    'vendor_2_email' => "honey@example.com",

    'recipe_type_name' => "محصول",
    'recipe_type_machine_name' => "recipe",
    'recipe_type_description' => "محصولی که توسط یک فروشنده ثبت می‌شود",
    'recipe_type_title_label' => "نام محصول",
    'recipe_field_image_directory' => "recipes",
    'recipe_field_ingredients_label' => "مواد اولیه",
    'recipe_field_ingredients_machine_name' => "ingredients",
    'recipe_field_ingredients_help' => "مواد اولیه‌ای که بازدیدکنندگان سایت به دنبال آن‌ها هستند را وارد کنید",
    'recipe_field_submitted_label' => "ثبت توسط",
    'recipe_field_submitted_machine_name' => "submitted_by",
    'recipe_field_submitted_help' => "فروشنده‌ای که محصول را ثبت کرده است انتخاب کنید",

    'recipe_field_ingredients_term_1' => "کره",
    'recipe_field_ingredients_term_2' => "تخم مرغ",
    'recipe_field_ingredients_term_3' => "شیر",
    'recipe_field_ingredients_term_4' => "هویج",

    'recipe_1_title' => "سالاد سبز",
    'recipe_1_path' => "/recipes/green_salad",
    'recipe_1_body' => "سبزیجات مورد علاقه خود را تکه تکه کرده و درون کاسه قرار دهید.",
    'recipe_1_ingredients' => "هویج, کاهو, گوجه, خیار",

    'recipe_2_title' => "هویج‌های تازه",
    'recipe_2_path' => "/recipes/carrots",
    'recipe_2_body' => "هویج‌های چند رنگ را درون یک ظرف قرار دهید.",
    'recipe_2_ingredients' => "هویج",

    'image_style_label' => "متوسط (۳۰۰x۲۰۰)",
    'image_style_machine_name' => "medium_300x200",

    'hours_block_description' => "بلاک ساعت و مکان",
    'hours_block_title' => "ساعت و مکان",
    'hours_block_title_machine_name' => "hours_location",
    'hours_block_body' => "<p>ساعت کاری: یکشنبه‌ها از ۰۹:۰۰ تا ۱۶:۰۰ - اردیبهشت تا شهریور</p><p>مکان: پارکینگ شعبه مرکزی بانک ملی، تهران</p>",

    'vendors_view_title' => "فروشندگان",
    'vendors_view_machine_name' => "vendors",
    'vendors_view_path' => "vendors",

    'recipes_view_title' => "محصولات",
    'recipes_view_machine_name' => "recipes",
    'recipes_view_path' => "recipes",
    'recipes_view_ingredients_label' => "جستجوی محصولات توسط...",
    'recipes_view_block_display_name' => "محصولات اخیر",
    'recipes_view_block_title' => "محصولات جدید",

    'recipes_view_title_translated' => "Recipes",
    'recipes_view_submit_button_translated' => "Apply",
    'recipes_view_ingredients_label_translated' => "Find recipes using...",

  ];

}