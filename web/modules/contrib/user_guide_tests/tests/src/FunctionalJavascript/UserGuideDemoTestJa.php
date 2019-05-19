<?php

namespace Drupal\Tests\user_guide_tests\FunctionalJavascript;

/**
 * Builds the demo site for the User Guide, Japanese, with screenshots.
 *
 * See README.txt file in the module directory for more information about
 * making screenshots.
 *
 * @group UserGuide
 */
class UserGuideDemoTestJa extends UserGuideDemoTestBase {

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
    'first_langcode' => "ja",
    'second_langcode' => "en",

    'site_name' => "ヒルズファーマーズマーケット",
    'site_slogan' => "産地直送フレッシュフード",
    'site_mail' => "info@example.com",
    'site_default_country' => "JP",
    'date_default_timezone' => "Asia/Tokyo",

    'home_title' => "ホーム",
    'home_body' => "<p>あなたの近所のファーマーズマーケット - シティマーケットへようこそ! </p>
<p>開催時間：4月から9月の日曜日 午前9時から午後2時</p>
<p>場所：ダウンタウン, 1st & ユニオン, トラストバンクの駐車場</p>",
    'home_summary' => "シティマーケットの開催時間と場所",
    'home_path' => "/home",
    'home_revision_log_message' => "開催時間を更新",

    'home_title_translated' => "Home",
    'home_body_translated' => "<p>Welcome to City Market - your neighborhood farmers market!</p><p>Open: Sundays, 9 AM to 2 PM, April to September</p><p>Location: Parking lot of Trust Bank, 1st & Union, downtown</p>",
    'home_path_translated' => "/home",

    'about_title' => "私たちについて",
    'about_body' => "<p>シティマーケットは1990年4月に5つの販売者でスタートしました。</p>
<p>今日では、100の販売者と1日平均2000人の来場者をむかえるようになっています。</p>",
    'about_path' => "/about",
    'about_description' => "マーケットの歴史",

    'vendor_type_name' => "販売者",
    'vendor_type_machine_name' => "vendor",
    'vendor_type_description' => "販売者についての情報",
    'vendor_type_title_label' => "販売者名",
    'vendor_field_url_label' => "販売者URL",
    'vendor_field_url_machine_name' => "vendor_url",
    'vendor_field_image_label' => "メイン画像",
    'vendor_field_image_machine_name' => "main_image",
    'vendor_field_image_directory' => "vendors",

    'vendor_1_title' => "ハッピーファーム",
    'vendor_1_path' => "/vendors/happy_farm",
    'vendor_1_summary' => "ハッピーファームはあなたが愛する野菜を栽培しています。",
    'vendor_1_body' => "<p>ハッピーファームはあなたが愛する野菜を栽培しています。</p><p>例えば、様々なサラダ用野菜だけでなく、トマト、ニンジン、ビートも栽培しています。</ p>",
    'vendor_1_url' => "http://happyfarm.com",
    'vendor_1_email' => "happy@example.com",

    'vendor_2_title' => "スイートハニー",
    'vendor_2_path' => "/vendors/sweet_honey",
    'vendor_2_summary' => "スウィートハニーは、年間を通して様々なフレーバーの蜂蜜を生産しています。",
    'vendor_2_body' => "<p>スウィートハニーは、年間を通して様々なフレーバーの蜂蜜を生産しています。</p><p>私たちが生産している品種は、クローバー、アップルブロッサム、ストロベリーなどです。</p>",
    'vendor_2_url' => "http://sweethoney.com",
    'vendor_2_email' => "honey@example.com",

    'recipe_type_name' => "レシピ",
    'recipe_type_machine_name' => "recipe",
    'recipe_type_description' => "販売者が投稿したレシピ",
    'recipe_type_title_label' => "レシピ名",
    'recipe_field_image_directory' => "recipes",
    'recipe_field_ingredients_label' => "材料",
    'recipe_field_ingredients_machine_name' => "ingredients",
    'recipe_field_ingredients_help' => "サイト訪問者が検索しそうな材料名を入力してください",
    'recipe_field_submitted_label' => "投稿者",
    'recipe_field_submitted_machine_name' => "submitted_by",
    'recipe_field_submitted_help' => "このレシピを投稿して販売者を選択してください",

    'recipe_field_ingredients_term_1' => "バター",
    'recipe_field_ingredients_term_2' => "卵",
    'recipe_field_ingredients_term_3' => "牛乳",
    'recipe_field_ingredients_term_4' => "ニンジン",

    'recipe_1_title' => "グリーンサラダ",
    'recipe_1_path' => "/recipes/green_salad",
    'recipe_1_body' => "あなたの好きな野菜を切り、ボウルに入れてください。",
    'recipe_1_ingredients' => "ニンジン",

    'recipe_2_title' => "新鮮なニンジン",
    'recipe_2_path' => "/recipes/carrots",
    'recipe_2_body' => "夕食のために多色のニンジンを盛り合わせる。",
    'recipe_2_ingredients' => "ニンジン",

    'image_style_label' => "中サイズ (300x200)",
    'image_style_machine_name' => "extra_medium_300x200",

    'hours_block_description' => "時間と場所のブロック",
    'hours_block_title' => "時間と場所",
    'hours_block_title_machine_name' => "hours_location",
    'hours_block_body' => "<p>開催時間：4月から9月の日曜日 午前9時から午後2時</p>
<p>場所：ダウンタウン, 1st & ユニオン, トラストバンクの駐車場</p>",

    'vendors_view_title' => "販売者",
    'vendors_view_machine_name' => "vendors",
    'vendors_view_path' => "vendors",

    'recipes_view_title' => "レシピ",
    'recipes_view_machine_name' => "recipes",
    'recipes_view_path' => "recipes",
    'recipes_view_ingredients_label' => "レシピをみつける",
    'recipes_view_block_display_name' => "最近のレシピ",
    'recipes_view_block_title' => "新規レシピ",

    'recipes_view_title_translated' => "Recipes",
    'recipes_view_submit_button_translated' => "Apply",
    'recipes_view_ingredients_label_translated' => "Find recipes using...",
  ];

}
