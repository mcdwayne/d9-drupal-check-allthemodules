<?php

namespace Drupal\Tests\user_guide_tests\FunctionalJavascript;

/**
 * Builds the demo site for the User Guide, Simplified Chinese, with
 * screenshots.
 *
 * See README.txt file in the module directory for more information about
 * making screenshots.
 *
 * @group UserGuide
 */
class UserGuideDemoTestZhHans extends UserGuideDemoTestBase {

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
    'first_langcode' => "zh-hans",
    'second_langcode' => "en",

    'site_name' => "爱林镇农贸市场",
    'site_slogan' => "农场新鲜食材",
    'site_mail' => "info@example.com",
    'site_default_country' => "CN",
    'date_default_timezone' => "Asia/Shanghai",

    'home_title' => "首页",
    'home_body' => "<p>欢迎来到城市市场 -- 您的农贸市场邻居!</p><p>开放时间：4月 - 9月，星期日, 09:00 – 14:00</p><p>位置：市中心水滴大厦一楼</p>",
    'home_summary' => "开放时间和城市市场位置",
    'home_path' => "/home",
    'home_revision_log_message' => "更新开放时间",

    'home_title_translated' => "Home",
    'home_body_translated' => "<p>欢迎来到城市市场 -- 您的农贸市场邻居!</p><p>开放时间：4月 - 9月，星期日, 09:00 – 14:00</p><p>位置：市中心水滴大厦一楼</p>",
    'home_path_translated' => "/home",

    'about_title' => "关于",
    'about_body' => "<p>城市市场始于 1990 年，最开始只有 5 家摊贩</p><p>如今，它有 100 家摊贩，每日平均客流量 2000 人次。</p>",
    'about_path' => "/about",
    'about_description' => "市场历史",

    'vendor_type_name' => "摊贩",
    'vendor_type_machine_name' => "vendor",
    'vendor_type_description' => "摊贩类型",
    'vendor_type_title_label' => "摊贩名称",
    'vendor_field_url_label' => "摊贩 URL",
    'vendor_field_url_machine_name' => "vendor_url",
    'vendor_field_image_label' => "主图",
    'vendor_field_image_machine_name' => "main_image",
    'vendor_field_image_directory' => "vendors",

    'vendor_1_title' => "开心农场",
    'vendor_1_path' => "/vendors/happy_farm",
    'vendor_1_summary' => "开心农场，种植您喜爱的蔬菜。",
    'vendor_1_body' => "<p>开心农场，种植您喜爱的蔬菜。</p><p>我们种植番茄，胡萝卜，和甜菜，同时也种植各种沙拉蔬菜。</p>",
    'vendor_1_url' => "http://happyfarm.com",
    'vendor_1_email' => "happy@example.com",

    'vendor_2_title' => "甜蜜蜜蜂蜜",
    'vendor_2_path' => "/vendors/sweet_honey",
    'vendor_2_summary' => "甜蜜蜜蜂蜜常年供应各式口味蜂蜜",
    'vendor_2_body' => "<p>甜蜜蜜蜂蜜常年供应各种口味的蜂蜜</p><p>我们的口味包括三叶草，苹果花和草莓。</p>",
    'vendor_2_url' => "http://sweethoney.com",
    'vendor_2_email' => "honey@example.com",

    'recipe_type_name' => "食谱",
    'recipe_type_machine_name' => "recipe",
    'recipe_type_description' => "摊贩提交的视频",
    'recipe_type_title_label' => "食谱名称",
    'recipe_field_image_directory' => "recipes",
    'recipe_field_ingredients_label' => "原料",
    'recipe_field_ingredients_machine_name' => "ingredients",
    'recipe_field_ingredients_help' => "输入原料，网站访问可能想搜索它",
    'recipe_field_submitted_label' => "提交者",
    'recipe_field_submitted_machine_name' => "submitted_by",
    'recipe_field_submitted_help' => "选择提交改食谱的摊贩",

    'recipe_field_ingredients_term_1' => "黄油",
    'recipe_field_ingredients_term_2' => "鸡蛋",
    'recipe_field_ingredients_term_3' => "牛奶",
    'recipe_field_ingredients_term_4' => "胡萝卜",

    'recipe_1_title' => "蔬菜沙拉",
    'recipe_1_path' => "/recipes/green_salad",
    'recipe_1_body' => "剁碎你最喜欢的蔬菜，把它们放在碗里。",
    'recipe_1_ingredients' => "胡萝卜",

    'recipe_2_title' => "新鲜胡萝卜",
    'recipe_2_path' => "/recipes/carrots",
    'recipe_2_body' => "放置多彩的胡萝卜到晚餐碟子里",
    'recipe_2_ingredients' => "胡萝卜",

    'image_style_label' => "中（300x200）",
    'image_style_machine_name' => "extra_medium_300x200",

    'hours_block_description' => "开放时间和地点区块",
    'hours_block_title' => "开放时间和地铁",
    'hours_block_title_machine_name' => "hours_location",
    'hours_block_body' => "<p>开放时间：4月 - 9月，星期日, 09:00 - 14:00</p><p>位置：市中心水滴大厦一楼</p>",

    'vendors_view_title' => "摊贩",
    'vendors_view_machine_name' => "vendors",
    'vendors_view_path' => "vendors",

    'recipes_view_title' => "食谱",
    'recipes_view_machine_name' => "recipes",
    'recipes_view_path' => "recipes",
    'recipes_view_ingredients_label' => "查找食谱使用 …",
    'recipes_view_block_display_name' => "最新食谱",
    'recipes_view_block_title' => "新食谱",

    'recipes_view_title_translated' => "Recipes",
    'recipes_view_submit_button_translated' => "Apply",
    'recipes_view_ingredients_label_translated' => "Find recipes using...",

  ];

}
