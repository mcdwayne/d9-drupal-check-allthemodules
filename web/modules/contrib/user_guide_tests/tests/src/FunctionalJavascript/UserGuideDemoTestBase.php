<?php

namespace Drupal\Tests\user_guide_tests\FunctionalJavascript;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Gettext\PoStreamReader;
use Drupal\Core\Site\Settings;
use Drupal\Core\Database\Database;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\locale\PoDatabaseWriter;
use Drupal\user\Entity\User;
use BackupMigrate\Core\Config\Config;
use BackupMigrate\Core\Destination\DirectoryDestination;
use BackupMigrate\Core\File\TempFileAdapter;
use BackupMigrate\Core\File\TempFileManager;
use BackupMigrate\Core\Filter\CompressionFilter;
use BackupMigrate\Core\Filter\DBExcludeFilter;
use BackupMigrate\Core\Filter\FileExcludeFilter;
use BackupMigrate\Core\Filter\FileNamer;
use BackupMigrate\Core\Main\BackupMigrate;
use BackupMigrate\Core\Service\TarArchiveReader;
use BackupMigrate\Core\Service\TarArchiveWriter;
use BackupMigrate\Core\Source\FileDirectorySource;
use BackupMigrate\Core\Source\MySQLiSource;
use WebDriver\Exception\UnknownError;

/**
 * Base class for tests that automate screenshots for the User Guide.
 *
 * See the README.txt file in the project top directory for notes on how
 * to run tests, make a test for a new language, and fix test fails.
 */
abstract class UserGuideDemoTestBase extends ScreenshotTestBase {

  /**
   * Strings and other information to input into the demo site.
   *
   * This information is translated into other languages in the
   * specific-language test classes.
   *
   * @var array
   */
  protected $demoInput = [
    // Default and second languages for the site.
    'first_langcode' => 'en',
    'second_langcode' => 'es',

    // Basic site information.
    'site_name' => 'Anytown Farmers Market',
    'site_slogan' => 'Farm Fresh Food',
    'site_mail' => 'info@example.com',
    'site_default_country' => 'US',
    'date_default_timezone' => 'America/Los_Angeles',

    // General note about machine names: All machine names must contain only
    // lower-case letters, numbers, and underscores. They should be lower-case,
    // ASCII version of the human-readable names from the line above. In an
    // interactive environment, these names will be created automatically, but
    // in this test, we have to supply them manually. Also be careful about
    // the directory names for image uploads, and URL paths.

    // Home page content item.
    'home_title' => 'Home',
    'home_body' => "<p>Welcome to City Market - your neighborhood farmers market!</p><p>Open: Sundays, 9 AM to 2 PM, April to September</p><p>Location: Parking lot of Trust Bank, 1st & Union, downtown</p>",
    'home_summary' => 'Opening times and location of City Market',
    'home_path' => '/home',
    'home_revision_log_message' => 'Updated opening hours',

    // Translation of Home page content item into second language.
    'home_title_translated' => 'Página principal',
    'home_body_translated' => "<p>Bienvenido al mercado de la ciudad - ¡el mercado de agricultores de tu barrio!</p></p>Horario: Domingos de 9:00 a 14:00. Desde Abril a Septiembre Lugar: parking del Banco Trust número 1. En el centro de la ciudad</p>",
    'home_path_translated' => '/pagina-principal',

    // About page content item.
    'about_title' => 'About',
    'about_body' => "<p>City Market started in April 1990 with five vendors.</p><p>Today, it has 100 vendors and an average of 2000 visitors per day.</p>",
    'about_path' => '/about',
    'about_description' => 'History of the market',

    // Vendor content type settings. Type name and machine name are also
    // used for the Vendor role.
    'vendor_type_name' => 'Vendor',
    'vendor_type_machine_name' => 'vendor',
    'vendor_type_description' => 'Information about a vendor',
    'vendor_type_title_label' => 'Vendor name',
    'vendor_field_url_label' => 'Vendor URL',
    'vendor_field_url_machine_name' => 'vendor_url',
    'vendor_field_image_label' => 'Main image',
    'vendor_field_image_machine_name' => 'main_image',
    'vendor_field_image_directory' => 'vendors',

    // Vendor 1 content item and user account.
    'vendor_1_title' => 'Happy Farm',
    'vendor_1_path' => '/vendors/happy_farm',
    'vendor_1_summary' => 'Happy Farm grows vegetables that you will love.',
    'vendor_1_body' => '<p>Happy Farm grows vegetables that you will love.</p><p>We grow tomatoes, carrots, and beets, as well as a variety of salad greens.</p>',
    'vendor_1_url' => 'http://happyfarm.com',
    'vendor_1_email' => 'happy@example.com',

    // Vendor 2 content item and user account.
    'vendor_2_title' => 'Sweet Honey',
    'vendor_2_path' => '/vendors/sweet_honey',
    'vendor_2_summary' => 'Sweet Honey produces honey in a variety of flavors throughout the year.',
    'vendor_2_body' => '<p>Sweet Honey produces honey in a variety of flavors throughout the year.</p><p>Our varieties include clover, apple blossom, and strawberry.</p>',
    'vendor_2_url' => 'http://sweethoney.com',
    'vendor_2_email' => 'honey@example.com',

    // Recipe content type settings.
    'recipe_type_name' => 'Recipe',
    'recipe_type_machine_name' => 'recipe',
    'recipe_type_description' => 'Recipe submitted by a vendor',
    'recipe_type_title_label' => 'Recipe name',
    'recipe_field_image_directory' => 'recipes',
    'recipe_field_ingredients_label' => 'Ingredients',
    'recipe_field_ingredients_machine_name' => 'ingredients',
    'recipe_field_ingredients_help' => 'Enter ingredients that site visitors might want to search for',
    'recipe_field_submitted_label' => 'Submitted by',
    'recipe_field_submitted_machine_name' => 'submitted_by',
    'recipe_field_submitted_help' => 'Choose the vendor that submitted this recipe',

    // Recipe ingredients terms added.
    'recipe_field_ingredients_term_1' => 'Butter',
    'recipe_field_ingredients_term_2' => 'Eggs',
    'recipe_field_ingredients_term_3' => 'Milk',
    'recipe_field_ingredients_term_4' => 'Carrots',

    // Recipe 1 content item.
    'recipe_1_title' => 'Green Salad',
    'recipe_1_path' => '/recipes/green_salad',
    'recipe_1_body' => 'Chop up your favorite vegetables and put them in a bowl.',
    'recipe_1_ingredients' => 'Carrots, Lettuce, Tomatoes, Cucumbers',

    // Recipe 2 content item.
    'recipe_2_title' => 'Fresh Carrots',
    'recipe_2_path' => '/recipes/carrots',
    'recipe_2_body' => 'Serve multi-colored carrots on a plate for dinner.',
    'recipe_2_ingredients' => 'Carrots',

    // Image style.
    'image_style_label' => 'Extra medium (300x200)',
    'image_style_machine_name' => 'extra_medium_300x200',

    // Hours and location block.
    'hours_block_description' => 'Hours and location block',
    'hours_block_title' => 'Hours and location',
    'hours_block_title_machine_name' => 'hours_location',
    'hours_block_body' => "<p>Open: Sundays, 9 AM to 2 PM, April to September</p><p>Location: Parking lot of Trust Bank, 1st & Union, downtown</p>",

    // Vendors view.
    'vendors_view_title' => 'Vendors',
    'vendors_view_machine_name' => 'vendors',
    'vendors_view_path' => 'vendors',

    // Recipes view.
    'recipes_view_title' => 'Recipes',
    'recipes_view_machine_name' => 'recipes',
    'recipes_view_path' => 'recipes',
    'recipes_view_ingredients_label' => 'Find recipes using...',
    'recipes_view_block_display_name' => 'Recent recipes',
    'recipes_view_block_title' => 'New recipes',

    // Recipes view translated.
    'recipes_view_title_translated' => 'Recetas',
    'recipes_view_submit_button_translated' => 'Applicar',
    'recipes_view_ingredients_label_translated' => 'Encontrar recetas usando...',

  ];

  /**
   * Which chapters to run, and which to save backups for.
   *
   * Each key in this array is the name of a method to run. The values are:
   * - run: Run normally. Assumes previous methods have been run or restored.
   *   This is the default in the base class, for all chapters.
   * - restore: Restore from the previous method's backup, and then run this
   *   method.
   * - backup: Run this method, and create a backup afterwards.
   * - restore_backup: Restore from previous method's backup, then run this
   *   method, then make a backup.
   * - skip: Do nothing.
   *
   * Created backups are stored in a temporary directory inside /tmp on your
   * local machine. There will be lines in the output telling you where they
   * are, saying:
   * "BACKUP MADE TO: ____".
   *
   * After verifying, save the backups for later restoration in the
   * backups/LANGUAGE_CODE/CHAPTER_METHOD directories.
   *
   * @var array
   */
  protected $runList = [
    'doPrefaceInstall' => 'backup',
    'doBasicConfig' => 'backup',
    'doBasicPage' => 'backup',
    'doContentStructure' => 'backup',
    'doUserAccounts' => 'backup',
    'doBlocks' => 'backup',
    'doViews' => 'backup',
    'doMultilingualSetup' => 'backup',
    'doTranslating' => 'backup',
    'doExtending' => 'backup',
    'doPreventing' => 'backup',
    'doSecurity' => 'backup',
  ];

  /**
   * For our demo site, start with the standard profile install.
   */
  protected $profile = 'standard';

  /**
   * Modules needed for this test.
   */
  public static $modules = ['update', 'user_guide_tests'];

  /**
   * We don't care about schema checking.
   */
  protected $strictConfigSchema = FALSE;

  /**
   * The directory where asset files can be found, relative to site root.
   *
   * This is set in the testBuildDemoSite() method.
   */
  protected $assetsDirectory;

  /**
   * Builds the entire demo site and makes screenshots.
   *
   * Note that the method name starts with "test" so that it will be detected
   * as a "test" to run, in the specific-language classes.
   */
  public function testBuildDemoSite() {
    $this->drupalLogin($this->rootUser);

    // Figure out where the assets directory is.
    $this->assetsDirectory = drupal_get_path('module', 'user_guide_tests') . '/assets/';
    $this->assertTrue(is_readable(DRUPAL_ROOT . '/' . $this->assetsDirectory . 'farm.jpg'), 'Farm asset file exists and is readable');

    // Create subdirectories for backups and screenshots, and verify temp
    // directory.
    $backup_write_dir = $this->htmlOutputDirectory . '/' .
      $this->databasePrefix . '/backups';
    $this->ensureDirectoryWriteable($backup_write_dir, 'backups');
    $this->logTestMessage('BACKUPS GOING TO: ' . $backup_write_dir);

    $this->setUpScreenshots();

    $this->ensureDirectoryWriteable($this->tempFilesDirectory, 'temp');

    // Run all the desired chapters.
    $backup_read_dir = drupal_realpath(drupal_get_path('module', 'user_guide_tests') . '/backups/' . $this->demoInput['first_langcode']);
    $previous = '';
    foreach ($this->runList as $method => $op) {
      if (($op == 'restore' || $op == 'restore_backup') && $previous) {
        // Restore the database from the backup of the previous topic.
        $this->restoreBackup($backup_read_dir . '/' . $previous);
      }
      $previous = $method;

      if ($op != 'skip') {
        // Run this topic.
        call_user_func([$this, $method]);
      }

      if ($op == 'backup' || $op == 'restore_backup') {
        // Make a backup of this topic.
        $this->makeBackup($backup_write_dir . '/' . $method);
      }
    }
  }

  /**
   * Makes screenshots for Preface and Install chapters.
   */
  protected function doPrefaceInstall() {

    // Add the first language, set the default language to that, and delete
    // English, to simulate having installed in a different language. No
    // screen shots for this!
    if ($this->demoInput['first_langcode'] != 'en') {
      // Note that the buttons should still be in English until after
      // the other language is set as the default language.
      // Turn on the language and locale modules.
      $this->drupalGet('admin/modules');
      $this->drupalPostForm(NULL, [
          'modules[language][enable]' => TRUE,
          'modules[locale][enable]' => TRUE,
          'modules[config_translation][enable]' => TRUE,
        ], 'Install');
      $this->flushAll();

      // Add the main language and fully import translations.
      $this->fixTranslationSettings();
      $this->drupalPostForm('admin/config/regional/language/add', [
          'predefined_langcode' => $this->demoInput['first_langcode'],
        ], 'Add language');
      $this->importTranslations($this->demoInput['first_langcode']);

      // Set the new language to default. After this, the UI should be
      // translated.
      $this->drupalPostForm('admin/config/regional/language', [
          'site_default_language' => $this->demoInput['first_langcode'],
        ], 'Save configuration');
      $this->flushAll();

      // Delete English and flush caches.
      $this->drupalPostForm('admin/config/regional/language/delete/en', [], $this->callT('Delete'));
      $this->flushAll();

      $this->verifyTranslations();
    }

    // Topic: preface-conventions: Conventions of the user guide.
    $this->drupalGet('admin/config');
    // Top navigation bar on any admin page, with Manage menu showing.
    $this->makeScreenShot('preface-conventions-top-menu.png', $this->addBorder('#toolbar-bar', '#ffffff') . $this->hideArea('header, .region-breadcrumb, .page-content, .toolbar-toggle-orientation') . $this->setWidth('#toolbar-bar, #toolbar-item-administration-tray', 1100) . 'jQuery(\'*\').css(\'box-shadow\', \'none\');' . $this->setBodyColor());
    // This is a copy of the previous screenshot.
    $this->makeScreenShot('config-overview-toolbar.png');

    $this->drupalGet('admin/config');
    // System section of admin/config page.
    $this->makeScreenShot('preface-conventions-config-system.png', $this->showOnly('.panel:has(a[href$="admin/config/system/site-information"])'));

    // Topic: block-regions - postpone until after theme is configured.

    // Topic: install-prepare - Preparing to install. Skip -- manual
    // screenshots.

    // Topic: install-run - Running the installer. Skip -- manual screenshots.
  }

  /**
   * Makes screenshots for the Basic Site Configuration chapter.
   */
  protected function doBasicConfig() {
    $this->verifyTranslations();

    // Topic: config-overview - Concept: Administrative overview.

    // config-overview-toolbar.png screenshot was made in the Preface chapter.

    // Put the toolbar into vertical orientation.
    $this->drupalGet('admin/config');
    $this->waitForInteraction('css', '.toolbar-toggle-orientation button');
    $this->assertSession()->assertWaitOnAjaxRequest();
    // Vertical orientation toolbar.
    $this->makeScreenShot('config-overview-vertical-menu.png', $this->showOnly('#toolbar-item-administration-tray') . $this->removeScrollbars() . $this->setBodyColor() . "jQuery('#toolbar-bar').css('box-shadow', 'none');", '', TRUE);

    // Toggle the toolbar back to horizontal.
    $this->waitForInteraction('css', '.toolbar-toggle-orientation button');

    // config-overview-pencils -- postpone until after the coloring is done.

    // Topic: config-basic - Editing basic site information.
    $this->drupalGet('<front>');
    $this->clickLink($this->callT('Configuration'));
    $this->assertText($this->callT('System'));
    // Here, you would ideally want to click the "Basic site settings" link.
    // However, the link text includes a span that says this, plus a div with
    // the description, so using clickLink is not really feasible. So, just
    // assert the text, and visit the URL. These can be problematic in
    // non-English languages...
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertText($this->callT('Basic site settings'));
    }
    $this->drupalGet('admin/config/system/site-information');
    $this->assertText($this->callT('Site name'));
    $this->assertText($this->callT('Slogan'));
    $this->assertText($this->callT('Email address'));
    $this->assertText($this->callT('Default front page'));
    $this->drupalPostForm(NULL, [
        'site_name' => $this->demoInput['site_name'],
        'site_slogan' => $this->demoInput['site_slogan'],
        'site_mail' => $this->demoInput['site_mail'],
      ], $this->callT('Save configuration'));

    // In this case, we want the screen shot made after we have entered the
    // information, because for a normal user, this information would have
    // been set up during the install.
    $this->drupalGet('admin/config/system/site-information');
    // Site details section of admin/config/system/site-information.
    $this->makeScreenShot('config-basic-SiteInfo.png', $this->showOnly('#edit-site-information') . $this->setWidth('#edit-site-information'));

    $this->drupalGet('<front>');
    $this->assertText($this->demoInput['site_name']);
    $this->assertText($this->demoInput['site_slogan']);

    $this->clickLink($this->callT('Configuration'));
    $this->assertText($this->callT('Regional and language'));
    // Here, you would ideally want to click the "Regional settings" link.
    // However, the link text includes a span that says this, plus a div with
    // the description, so using clickLink is not really feasible. So, just
    // assert the text, and visit the URL. These can be problematic in
    // non-English languages...
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertText($this->callT('Regional settings'));
    }
    $this->drupalGet('admin/config/regional/settings');
    $this->assertText($this->callT('Locale'));
    $this->assertText($this->callT('Default country'));
    $this->assertText($this->callT('First day of week'));
    $this->assertText($this->callT('Time zones'));
    $this->assertText($this->callT('Default time zone'));
    $this->drupalPostForm(NULL, [
      'site_default_country' => $this->demoInput['site_default_country'],
      'date_default_timezone' => $this->demoInput['date_default_timezone'],
      'configurable_timezones' => FALSE,
      ], $this->callT('Save configuration'));

    $this->drupalGet('admin/config/regional/settings');
    // Locale and Time Zones sections of admin/config/regional/settings.
    $this->makeScreenShot('config-basic-TimeZone.png', $this->showOnly('.page-content') . $this->setWidth('#edit-locale') . $this->setWidth('#edit-timezone') . $this->removeScrollbars());

    // Topic: config-install -- Installing a module.

    $this->drupalGet('<front>');
    $this->clickLink($this->callT('Extend'));
    // Names of modules are not translated.
    $this->assertText('Activity Tracker');
    $this->assertText('tracker');

    // Top part of Core section of admin/modules, with Activity Tracker checked.
    $this->makeScreenShot('config-install-check-modules.png', 'jQuery(\'#edit-modules-tracker-enable\').attr(\'checked\', 1);' . $this->hideArea('#toolbar-administration, header, .region-pre-content, .region-highlighted, .help, .action-links, .region-breadcrumb, #edit-filters, #edit-actions') . $this->hideArea('#edit-modules-core-experimental, #edit-modules-field-types, #edit-modules-multilingual, #edit-modules-other, #edit-modules-administration, #edit-modules-testing, #edit-modules-web-services, #edit-modules-migration') . $this->hideArea('#edit-modules-core table tbody tr:gt(4)'));

    $this->drupalPostForm('admin/modules', [
        'modules[tracker][enable]' => TRUE,
      ], $this->callT('Install'));

    // Due to a core bug, installing a module corrupts translations. So,
    // import translations again.
    $this->importTranslations($this->demoInput['first_langcode']);
    $this->verifyTranslations();

    // Topic: config-uninstall - Uninstalling unused modules.

    $this->drupalGet('<front>');
    $this->clickLink($this->callT('Extend'));
    $this->clickLink($this->callT('Uninstall'));
    // Names of modules are not translated.
    $this->assertText('Activity Tracker');
    $this->assertText('Search');
    $this->assertText('History');
    $this->assertText('File');
    $this->assertText('Text Editor');
    $this->assertText('CKEditor');
    $this->assertText('Image');

    // Top part of admin/modules/uninstall, with Activity Tracker checked.
    $this->makeScreenShot('config-uninstall_check-modules.png', 'jQuery(\'#edit-uninstall-tracker\').attr(\'checked\', 1); ' . $this->showOnly('table thead, table tbody tr:lt(4)'));

    $this->drupalGet('admin/modules/uninstall');
    $this->waitForInteraction('css', '#edit-uninstall-tracker');
    $this->waitForInteraction('css', '#edit-uninstall-history');
    $this->waitForInteraction('css', '#edit-uninstall-search');
    $this->drupalPostForm(NULL, [
        'uninstall[tracker]' => TRUE,
        'uninstall[search]' => TRUE,
        'uninstall[history]' => TRUE,
      ], $this->callT('Uninstall'));
    // Uninstall confirmation screen, after checking Activity Tracker, History,
    // and Search modules from admin/modules/uninstall.
    $this->makeScreenShot('config-uninstall_confirmUninstall.png', $this->hideArea('#toolbar-administration') . $this->setWidth('.block-system-main-block') . $this->setWidth('header', 640) . $this->removeScrollbars());
    $this->drupalPostForm(NULL, [], $this->callT('Uninstall'));
    $this->flushAll();

    // Topic: config-user - Configuring user account settings.

    $this->drupalGet('<front>');
    $this->clickLink($this->callT('Configuration'));
    $this->assertText($this->callT('People'));
    // Here, you would ideally want to click the "Account settings" link.
    // However, the link text includes a span that says this, plus a div with
    // the description, so using clickLink is not really feasible. So, just
    // assert the text, and visit the URL. These can be problematic in
    // non-English languages...
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertText($this->callT('Account settings'));
    }
    $this->drupalGet('admin/config/people/accounts');
    $this->assertText($this->callT('Registration and cancellation'));
    $this->assertText($this->callT('Administrators only'));
    $this->assertText($this->callT('Require email verification when a visitor creates an account'));
    $this->assertText($this->callT('Emails'));
    $this->assertText($this->callT('Welcome (new user created by administrator)'));

    $this->drupalGet('admin/config/people/accounts');
    $this->drupalPostForm(NULL, [
        'user_register' => 'admin_only',
      ], $this->callT('Save configuration'));
    // Registration and cancellation section of admin/config/people/accounts.
    $this->makeScreenShot('config-user_account_reg.png', 'window.scroll(0,500);' . $this->showOnly('#edit-registration-cancellation') . $this->setWidth('#edit-registration-cancellation'));
    // Email address section of admin/config/people/accounts.
    $this->makeScreenShot('config-user_from_email.png', 'window.scroll(0,500);' . $this->showOnly('.form-item-mail-notification-address') . $this->setWidth('.form-item-mail-notification-address'));
    // Emails section of admin/config/people/accounts.
    $this->makeScreenShot('config-user_email.png', 'window.scroll(0,5000); ' . $this->showOnly('div.form-type-vertical-tabs') . $this->hideArea('div.form-type-vertical-tabs details:gt(0)') . $this->removeScrollbars());

    // Topic: config-theme - Configuring the theme.

    $this->drupalGet('<front>');
    $this->clickLink($this->callT('Appearance'));
    // This text is part of a plural translation, so only test in English.
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertText($this->callT('Installed themes'));
    }
    // Theme names are not translated.
    $this->assertText('Bartik');
    $this->assertText($this->callT('default theme'));

    // Bartik section of admin/appearance.
    $this->makeScreenShot('config-theme_bartik_settings.png', $this->showOnly('.system-themes-list-installed') . $this->hideArea('.theme-admin'));

    $this->drupalGet('admin/appearance');
    $this->clickLink($this->callT('Settings'), 1);
    $this->assertText($this->callT('Color scheme'));
    $this->assertText($this->callT('Header background top'));
    $this->assertText($this->callT('Header background bottom'));
    $this->assertText($this->callT('Main background'));
    $this->assertText($this->callT('Sidebar background'));
    $this->assertText($this->callT('Sidebar borders'));
    if ($this->demoInput['first_langcode'] == 'en') {
      // This assertion seems to be problematic in some languages.
      $this->assertText($this->callT('Footer background'));
    }
    $this->assertText($this->callT('Title and slogan'));
    $this->assertText($this->callT('Text color'));
    $this->assertText($this->callT('Link color'));
    $this->assertText($this->callT('Logo image'));
    $this->assertText($this->callT('Use the logo supplied by the theme'));
    $this->scrollWindowUp();
    $this->getSession()->getPage()->uncheckField('edit-default-logo');
    $this->scrollWindowUp();
    $this->waitForInteraction('css', '#edit-logo-upload');
    $this->assertText($this->callT('Upload logo image'));
    $this->assertText($this->callT('Preview'));

    // For this screenshot, before the settings are changed, use JavaScript to
    // scroll down to the bottom and outline the logo upload box.
    // Logo upload section of admin/appearance/settings/bartik.
    $this->makeScreenShot('config-theme_logo_upload.png', 'window.scroll(0,6000); ' . $this->addBorder('#edit-logo-upload') . $this->showOnly('#edit-logo') . $this->setWidth('#edit-logo'), "jQuery('*').show();");

    $this->drupalPostForm(NULL, [
        'scheme' => '',
        'palette[top]' => '#7db84a',
        'palette[bottom]' => '#2a3524',
        'palette[bg]' => '#ffffff',
        'palette[sidebar]' => '#f8bc65',
        'palette[sidebarborders]' => '#e96b3c',
        'palette[footer]' => '#2a3524',
        'palette[titleslogan]' => '#ffffff',
        'palette[text]' => '#000000',
        'palette[link]' => '#2a3524',
        'default_logo' => FALSE,
        'logo_path' => $this->assetsDirectory . 'AnytownFarmersMarket.png',
      ], $this->callT('Save configuration'));

    $this->drupalGet('admin/appearance/settings/bartik');
    // Color settings section of admin/appearance/settings/bartik.
    $this->makeScreenShot('config-theme_color_scheme.png', 'window.scroll(0,200);' . $this->showOnly('#color_scheme_form') . $this->hideArea('h2') . $this->hideArea('.color-preview') . $this->setWidth('#color_scheme_form', 800) . $this->removeScrollbars());
    // Preview section of admin/appearance/settings/bartik.
    $this->makeScreenShot('config-theme_color_scheme_preview.png', 'window.scroll(0,1000);' . $this->showOnly('.color-preview') . $this->setWidth('#color_scheme_form', 700) . "jQuery('#color_scheme_form').css('border', 'none').css('background', 'white');" . $this->removeScrollbars());

    $this->drupalGet('admin/appearance/settings/bartik');
    $this->clickLink($this->callT('Home'));
    if ($this->demoInput['first_langcode'] == 'en') {
      // This string is part of a complicated config string now, and checking
      // for the whole string doesn't work in tests. So, just check in English
      // for part of the string.
      $this->assertText('No front page content has been created yet.');
    }

    // Home page after theme settings are finished.
    $this->makeScreenShot('config-theme_final_result.png', $this->hideArea('#toolbar-administration, .contextual') . $this->removeScrollbars());

    // Back to topic: block-regions.
    $this->drupalGet('admin/structure/block/demo/bartik');
    // Bartik theme region preview at admin/structure/block/demo/bartik,
    // after configuring the theme for the Farmers Market scenario.
    $this->makeScreenShot('block-regions-bartik.png', $this->showOnly('#page-wrapper') . $this->removeScrollbars());

    // Back to screenshot: config-overview-pencils.
    $this->drupalGet('<front>');
    $this->waitForInteraction('css', 'button.toolbar-icon-edit');
    // Pencils for contextual links showing on site home page.
    $this->makeScreenShot('config-overview-pencils.png', $this->removeScrollbars());

    // Toggle the pencils back off.
    $this->waitForInteraction('css', 'button.toolbar-icon-edit');
  }

  /**
   * Makes screenshots for the Basic Page Management chapter.
   */
  protected function doBasicPage() {
    $this->verifyTranslations();

    // Topic: content-create - Creating a Content Item
    // Create a Home page.
    $this->drupalGet('<front>');
    $this->clickLink($this->callT('Content'));
    // clickLink ran into problems here, so assert text and then go to page.
    $this->assertText($this->callT('Add content'));
    $this->drupalGet('node/add');
    // Here, you would ideally want to click the "Basic page" link.
    // However, the link text includes a span that says this, plus a div with
    // the description, so using clickLink is not really feasible. So, just
    // assert the text, and visit the URL. These can be problematic in
    // non-English languages...
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertText($this->callT('Basic page'));
    }
    $this->drupalGet('node/add/page');
    $this->assertText($this->callT('Create @name', TRUE, ['@name' => $this->callT('Basic page')]));
    $this->assertText($this->callT('Title'));
    // The assert for the 'Summary' text is checked in the fillInSummary()
    // method.
    $this->assertText($this->callT('Body'));
    $this->assertText($this->callT('URL alias'));
    $this->assertText($this->callT('Published'));
    $this->waitForInteraction('css', '#edit-submit', 'focus');
    $this->assertRaw((string) $this->callT('Save'));
    $this->waitForInteraction('css', '#edit-preview', 'focus');
    $this->assertRaw((string) $this->callT('Preview'));

    // Fill in the body text. Also open up the path edit area.
    $this->waitForInteraction('css', '#edit-path-0 summary');
    $this->fillInBody($this->demoInput['home_body']);
    $this->fillInSummary($this->demoInput['home_summary']);

    // Partly filled-in node/add/page.
    $this->makeScreenShot('content-create-create-basic-page.png', 'jQuery(\'#edit-title-0-value\').val("' . $this->demoInput['home_title'] . '"); jQuery(\'#edit-path-0-alias\').val(\'' . $this->demoInput['home_path'] . '\');' . $this->hideArea('#toolbar-administration') . $this->removeScrollbars(), "jQuery('body').css('overflow', 'scroll');");

    // Submit the rest of the form.
    $this->drupalPostForm(NULL, [
        'title[0][value]' => $this->demoInput['home_title'],
        'path[0][alias]' => $this->demoInput['home_path'],
      ], $this->callT('Save'));

    // Create About page. No screenshots.
    $this->drupalGet('node/add/page');
    $this->waitForInteraction('css', '#edit-submit', 'focus');
    $this->waitForInteraction('css', '#edit-path-0 summary');
    $this->fillInBody($this->demoInput['about_body']);
    $this->drupalPostForm(NULL, [
        'title[0][value]' => $this->demoInput['about_title'],
        'path[0][alias]' => $this->demoInput['about_path'],
      ], $this->callT('Save'));

    // Topic: content-edit - Editing a content item
    $this->drupalGet('<front>');
    $this->clickLink($this->callT('Content'));
    $this->assertLink($this->callT('Edit'));
    $this->assertText($this->callT('Content type'));
    $this->assertText($this->callT('Title'));
    // Some of these filters are mentioned on other topics.
    $this->assertText($this->callT('Language'));
    $this->assertText($this->callT('Published status'));
    $this->assertRaw((string) $this->callT('Filter'));

    // Content list on admin/content, with filters above.
    $this->makeScreenShot('content-edit-admin-content.png', $this->showOnly('.block-system-main-block') . $this->hideArea('.secondary-action') . $this->setBodyColor());

    // To avoid having to decide which Edit button to click, navigate to the
    // correct edit page.
    $this->drupalGet('node/1/edit');
    $this->assertText($this->callT('Body'));
    $this->assertText($this->callT('Create new revision'));
    $this->assertText($this->callT('Revision log message'));

    // Revision area of the content node edit page.
    $this->makeScreenShot('content-edit-revision.png', $this->showOnly('#edit-meta') . 'jQuery(\'#edit-revision\').attr(\'checked\', 1); jQuery(\'#edit-revision-log-0-value\').append("' . $this->demoInput['home_revision_log_message'] . '");');
    // Submit the revision.
    $this->drupalPostForm('node/1/edit', [
        'revision_log[0][value]' => $this->demoInput['home_revision_log_message'],
      ], $this->callT('Save'));

    // Updated content message.
    // Difficult to assert the whole message, as it has a URL in it.
    $this->assertText($this->callT('Basic page'));
    $this->makeScreenShot('content-edit-message.png', $this->showOnly('.highlighted') . $this->setWidth('.highlighted') . $this->setBodyColor() . $this->removeScrollbars());

    // Topic: content-in-place-edit - it does not seem possible to make these
    // screenshots automatically. Skip.

    // Topic: menu-home - Designating a Front Page for your Site
    $this->drupalGet('<front>');
    $this->clickLink($this->callT('Configuration'));
    $this->assertText($this->callT('System'));
    // Here, you would ideally want to click the "Basic site settings" link.
    // However, the link text includes a span that says this, plus a div with
    // the description, so using clickLink is not really feasible. So, just
    // assert the text, and visit the URL. These can be problematic in
    // non-English languages...
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertText($this->callT('Basic site settings'));
    }
    $this->drupalGet('admin/config/system/site-information');
    $this->assertText($this->callT('Front page'));

    $this->drupalPostForm(NULL, [
        'site_frontpage' => $this->demoInput['home_path'],
      ], $this->callT('Save configuration'));
    // Fix the prefix showing the site URL to say example.com.
    // Front page section of admin/config/system/site-information.
    $this->makeScreenShot('menu-home_new_text_field.png', $this->showOnly('#edit-front-page') . $this->setWidth('#edit-front-page') . 'jQuery(\'.form-item-site-frontpage .field-prefix\').text(\'http://example.com\');');

    $this->drupalGet('<front>');
    // Site front page after configuring it to point to the Home content item.
    $this->makeScreenShot('menu-home_final.png', $this->hideArea('#toolbar-administration, footer, .contextual') . $this->setBodyColor() . $this->removeScrollbars());

    // UI text tests from Topic: menu-concept.txt: Concept: Menu.
    // For some reason, these texts in particular have some strange HTML
    // entity stuff going on in them (mismatches between screen and raw text
    // that amount to HTML entities being present or decoded), so only test in
    // English.
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->drupalGet('admin/structure/menu');
      $this->assertRaw((string) $this->callT('Main navigation'));
      $this->assertRaw((string) $this->callT('Administration'));
      $this->assertRaw((string) $this->callT('User account menu'));
      $this->assertRaw((string) $this->callT('Footer'));
      $this->assertRaw((string) $this->callT('Tools'));
    }

    // Topic: menu-link-from-content: Adding a page to the navigation.
    $this->drupalGet('<front>');
    $this->clickLink($this->callT('Content'));
    $this->assertLink($this->callT('Edit'));
    // Content table from admin/content page, with a red border around the Edit
    // button for the About page.
    $this->makeScreenShot('menu-link-from-content_edit_page.png', $this->showOnly('.views-table') . $this->addBorder('table.views-view-table tbody tr:last .dropbutton-widget') . $this->hideArea('.secondary-action'));

    // To avoid having to decide which Edit button to click, navigate to the
    // correct edit page.
    $this->drupalGet('node/2/edit');
    // Open up the menu area and click the add a menu button.
    $this->waitForInteraction('css', '#edit-menu summary');
    $this->waitForInteraction('css', '#edit-menu-enabled');

    $this->assertText($this->callT('Menu settings'));
    $this->assertText($this->callT('Provide a menu link'));
    $this->assertText($this->callT('Menu link title'));
    $this->assertText($this->callT('Description'));
    $this->assertText($this->callT('Parent item'));
    $this->assertText($this->callT('Weight'));

    $this->drupalPostForm(NULL, [
        'menu[enabled]' => TRUE,
        'menu[title]' => $this->demoInput['about_title'],
        'menu[description]' => $this->demoInput['about_description'],
        'menu[weight]' => -2,
      ], $this->callT('Save'));

    $this->drupalGet('node/2/edit');
    // Menu settings section of content editing page.
    $this->makeScreenShot('menu-link-from-content.png', $this->showOnly('#edit-menu'));

    $this->drupalGet('<front>');
    // Home page after adding About to the navigation.
    $this->makeScreenShot('menu-link-from-content-result.png', $this->hideArea('#toolbar-administration, .contextual, footer') . $this->setBodyColor() . $this->removeScrollbars());

    // Topic: menu-reorder - Changing the order of navigation.
    $this->drupalGet('<front>');
    $this->clickLink($this->callT('Structure'));
    // Here, you would ideally want to click the "Menus" link.
    // However, the link text includes a span that says this, plus a div with
    // the description, so using clickLink is not really feasible. So, just
    // assert the text, and visit the URL. These can be problematic in
    // non-English languages...
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertText($this->callT('Menus'));
    }
    $this->drupalGet('admin/structure/menu');
    $this->assertLink($this->callT('Edit menu'));
    $this->assertText($this->callT('Operations'));
    // Menu names are in English, so do not translate this text. See also
    // https://www.drupal.org/project/user_guide/issues/2959852
    $this->assertText('Main navigation');

    // Menu list section of admin/structure/menu, with Edit menu button on Main
    // navigation menu highlighted.
    $this->makeScreenShot('menu-reorder_menu_titles.png', $this->showOnly('table') . $this->addBorder('tr:eq(3) .dropbutton-widget') . $this->hideArea('.secondary-action'));

    // To avoid having to figure out which menu edit button to click, go
    // directly to the page.
    $this->drupalGet('admin/structure/menu/manage/main');
    if ($this->demoInput['first_langcode'] == 'en') {
      // Menu names are in English, so do not translate this text. See also
      // https://www.drupal.org/project/user_guide/issues/2959852
      $this->assertRaw((string) $this->callT('Edit menu %label', TRUE, ['%label' => 'Main navigation']));
    }
    $this->assertRaw((string) $this->callT('Save'));
    $this->assertLink($this->callT('Home'));
    $this->assertLink($this->demoInput['about_title']);

    // Menu links section of admin/structure/menu/manage/main.
    $this->makeScreenShot('menu-reorder_edit_menu.png', $this->hideArea('#toolbar-administration, header, .region-breadcrumb, #block-seven-local-actions, .form-type-textfield, .tabledrag-toggle-weight') . $this->setWidth('table'));

    // Simulating dragging on the ordering screen is a bit complex.
    // Menu links section of admin/structure/menu/manage/main, after
    // changing the order.
    $this->makeScreenShot('menu-reorder_reorder.png', $this->hideArea('#toolbar-administration, header, .region-breadcrumb, #block-seven-local-actions, .form-type-textfield, .tabledrag-toggle-weight-wrapper') . 'jQuery(\'table\').before(\'<div style="display: block; width: 600px;" class="tabledrag-changed-warning messages messages--warning" role="alert"><abbr class="warning tabledrag-changed">*</abbr>' . $this->callT('You have unsaved changes.') . '</div>\');' . 'var r = jQuery(\'table tbody tr:last\').detach(); jQuery(\'table tbody\').prepend(r); jQuery(\'table tbody tr:first\').toggleClass(\'drag-previous\');' . $this->setWidth('table'));

    // Actually figuring out what to submit on the editing page is difficult,
    // because the field name has some config hash in it. So instead, to make
    // the change in the test, go back to the about page and edit the weight
    // there.
    $this->drupalGet('node/2/edit');
    $this->drupalPostForm(NULL, [
        'menu[weight]' => 10,
      ], $this->callT('Save'));
    $this->drupalGet('<front>');
    // Header section of Home page with reordered menu items.
    $this->makeScreenShot('menu-reorder_final_order.png', $this->showOnly('header') . $this->hideArea('.visually-hidden, .contextual, .contextual-links, .menu-toggle') . $this->setWidth('header') . $this->setBodyColor() . $this->removeScrollbars());

  }

  /**
   * Makes screenshots for the Content Structure chapter.
   */
  protected function doContentStructure() {
    $this->verifyTranslations();

    // Set up some helper variables.
    $vendor = $this->demoInput['vendor_type_machine_name'];
    $recipe = $this->demoInput['recipe_type_machine_name'];
    $vendor_url = $this->demoInput['vendor_field_url_machine_name'];
    $vendor_url_hyphens = str_replace('_', '-', $vendor_url);
    $main_image = $this->demoInput['vendor_field_image_machine_name'];
    $main_image_hyphens = str_replace('_', '-', $main_image);
    $ingredients = $this->demoInput['recipe_field_ingredients_machine_name'];
    $ingredients_hyphens = str_replace('_', '-', $ingredients);
    $submitted_by = $this->demoInput['recipe_field_submitted_machine_name'];

    // Topic: structure-content-type - Adding a Content Type.
    // Create the Vendor content type.

    $this->drupalGet('<front>');
    $this->clickLink($this->callT('Structure'));
    // Here, you would ideally want to click the "Content types" link.
    // However, the link text includes a span that says this, plus a div with
    // the description, so using clickLink is not really feasible. So, just
    // assert the text, and visit the URL. These can be problematic in
    // non-English languages...
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertText($this->callT('Content types'));
    }
    $this->drupalGet('admin/structure/types');

    $this->clickLink($this->callT('Add content type'));
    $this->assertRaw((string) $this->callT('Add content type'));
    $this->assertText($this->callT('Name'));
    $this->assertText($this->callT('Description'));
    $this->assertText($this->callT('Submission form settings'));
    $this->assertText($this->callT('Title field label'));
    $this->assertText($this->callT('Preview before submitting'));
    $this->assertText($this->callT('Explanation or submission guidelines'));
    // Reload the page from the URL to make sure we are at the right place.
    $this->drupalGet('admin/structure/types/add');
    // Open the publishing options section and uncheck "Promoted..".
    $this->clickLink($this->callT('Publishing options'));
    $this->waitForInteraction('css', '#edit-options-promote');
    $this->assertText($this->callT('Published'));
    $this->assertText($this->callT('Promoted to front page'));
    $this->assertText($this->callT('Sticky at top of lists'));
    $this->assertText($this->callT('Create new revision'));
    // Open the display settings section and uncheck 'Display author..'.
    $this->scrollWindowUp();
    $this->clickLink($this->callT('Display settings'));
    $this->waitForInteraction('css', '#edit-display-submitted');
    $this->assertText($this->callT('Display author and date information'));
    // Open the menu settings section and uncheck Main navigation menu.
    $this->scrollWindowUp();
    $this->clickLink($this->callT('Menu settings'));
    $this->waitForInteraction('css', '#edit-menu-options-main');
    $this->assertText($this->callT('Available menus'));
    // Open submission form settings sections.
    $this->scrollWindowUp();
    $this->clickLink($this->callT('Submission form settings'));
    $this->waitForInteraction('css', '#edit-title-label', 'focus');
    $this->waitForInteraction('css', '#edit-save-continue', 'focus');

    // Top of admin/structure/types/add, with Name and Description fields.
    $this->makeScreenShot('structure-content-type-add.png', 'jQuery(\'#edit-name\').val("' . $this->demoInput['vendor_type_name'] . '"); jQuery(\'#edit-name-machine-name-suffix\').show(); jQuery(\'#edit-name\').trigger(\'formUpdated.machineName\'); jQuery(\'.machine-name-value\').html("' . $vendor . '").parent().show(); ' . $this->hideArea('.form-type-vertical-tabs, #toolbar-administration, #edit-actions, header, .region-breadcrumbs') . $this->setWidth('.layout-container') . 'jQuery(\'#edit-description\').append("' . $this->demoInput['vendor_type_description'] . '");', "jQuery('.form-type-vertical-tabs, #edit-actions').show();");

    // Open machine name section and submit form.
    $this->openMachineNameEdit();
    $this->drupalPostForm(NULL, [
        'name' => $this->demoInput['vendor_type_name'],
        'type' => $vendor,
        'description' => $this->demoInput['vendor_type_description'],
        'title_label' => $this->demoInput['vendor_type_title_label'],
      ], $this->callT('Save and manage fields'));
    $this->assertRaw((string) $this->callT('Manage fields'));

    // Manage fields page after adding Vendor content type.
    $this->makeScreenShot('structure-content-type-add-confirmation.png', $this->hideArea('#toolbar-administration') . $this->setWidth('header, .page-content', 800));

    // Go back to editing the content type to make screenshots with the
    // right values in the form.
    $this->drupalGet('admin/structure/types/manage/' . $vendor);
    // Submission form settings section of admin/structure/types/add.
    $this->makeScreenShot('structure-content-type-add-submission-form-settings.png', $this->setWidth('.layout-container') . $this->hideArea('#toolbar-administration, header, .region-breadcrumb, .help, .form-item-name, .form-item-description, #edit-actions'));
    $this->drupalGet('admin/structure/types/manage/' . $vendor);
    // Publishing settings section of admin/structure/types/add.
    $this->makeScreenShot('structure-content-type-add-Publishing-Options.png', 'jQuery(\'#edit-workflow\').show(); jQuery(\'.vertical-tabs li:eq(0)\').toggleClass(\'is-selected\'); jQuery(\'.vertical-tabs li:has(a[href="#edit-workflow"])\').toggleClass(\'is-selected\'); ' . $this->setWidth('.layout-container') . $this->hideArea('#toolbar-administration, header, .region-breadcrumb, .help, .form-item-name, .form-item-description, #edit-actions, #edit-submission'));
    $this->drupalGet('admin/structure/types/manage/' . $vendor);
    // Display settings section of admin/structure/types/add.
    $this->makeScreenShot('structure-content-type-add-Display-settings.png', 'jQuery(\'#edit-display\').show(); jQuery(\'.vertical-tabs li:eq(0)\').toggleClass(\'is-selected\'); jQuery(\'.vertical-tabs li:has(a[href="#edit-display"])\').toggleClass(\'is-selected\'); '. $this->setWidth('.layout-container') . $this->hideArea('#toolbar-administration, header, .region-breadcrumb, .help, .form-item-name, .form-item-description, #edit-submission, #edit-actions'));
    $this->drupalGet('admin/structure/types/manage/' . $vendor);
    // Menu settings section of admin/structure/types/add.
    $this->makeScreenShot('structure-content-type-add-Menu-settings.png', 'jQuery(\'#edit-menu\').show(); jQuery(\'.vertical-tabs li:eq(0)\').toggleClass(\'is-selected\'); jQuery(\'.vertical-tabs li:has(a[href="#edit-menu"])\').toggleClass(\'is-selected\'); ' . $this->setWidth('.layout-container') . $this->hideArea('#toolbar-administration, header, .region-breadcrumb, .help, .form-item-name, .form-item-description, #edit-submission, #edit-actions'));

    // Add content type for Recipe. No screen shots.
    $this->drupalGet('admin/structure/types/add');
    // Open the publishing options section and uncheck "Promoted..".
    $this->clickLink($this->callT('Publishing options'));
    $this->waitForInteraction('css', '#edit-options-promote');
    // Open the display settings section and uncheck 'Display author..'.
    $this->scrollWindowUp();
    $this->clickLink($this->callT('Display settings'));
    $this->waitForInteraction('css', '#edit-display-submitted');
    // Open the menu settings section and uncheck Main navigation menu.
    $this->scrollWindowUp();
    $this->clickLink($this->callT('Menu settings'));
    $this->waitForInteraction('css', '#edit-menu-options-main');
    $this->openMachineNameEdit();
    // Open submission form area and submit the form.
    $this->clickLink($this->callT('Submission form settings'));
    $this->waitForInteraction('css', '#edit-title-label', 'focus');
    $this->waitForInteraction('css', '#edit-save-continue', 'focus');
    $this->drupalPostForm(NULL, [
        'name' => $this->demoInput['recipe_type_name'],
        'type' => $recipe,
        'description' => $this->demoInput['recipe_type_description'],
        'title_label' => $this->demoInput['recipe_type_title_label'],
      ], $this->callT('Save and manage fields'));

    // Topic: structure-content-type-delete - Deleting a Content Type
    // Delete the Article content type.
    // Note: Navigation tested in previous topic.
    $this->drupalGet('admin/structure/types');
    // Verify some links for other topics as well here.
    $this->assertLink($this->callT('Delete'));
    $this->assertLink($this->callT('Manage fields'));
    $this->assertLink($this->callT('Manage form display'));
    $this->assertLink($this->callT('Manage display'));

    // Content types list on admin/structure/types, with operations dropdown
    // for Article content type expanded.
    $this->makeScreenShot('structure-content-type-delete-dropdown.png', 'jQuery("a[href*=\'article/delete\']").parents(\'.dropbutton-wrapper\').addClass(\'open\'); ' . $this->hideArea('#toolbar-administration') . $this->setWidth('.region-content', 950));

    $this->drupalGet('admin/structure/types/manage/article/delete');
    $this->assertText($this->callT('This action cannot be undone.'));
    // This test is problematic in non-English, due to entities or something.
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertRaw((string) $this->callT('Are you sure you want to delete the @entity-type %label?', TRUE, ['@entity-type' => $this->callT('content type'), '%label' => $this->callT('Article')]));
    }

    // Confirmation page for deleting Article content type.
    $this->makeScreenShot('structure-content-type-delete-confirmation.png', $this->hideArea('#toolbar-administration') . $this->setWidth('header, .page-content', 800) . $this->removeScrollbars());
    $this->drupalPostForm(NULL, [], $this->callT('Delete'));
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertRaw((string) $this->callT('The @entity-type %label has been deleted.', TRUE, ['@entity-type' => $this->callT('content type'), '%label' => $this->callT('Article')]));
    }

    // Confirmation message after deleting Article content type.
    $this->makeScreenShot('structure-content-type-delete-confirm.png', $this->showOnly('.messages') . $this->setWidth('.messages', 600) . $this->setBodyColor() . $this->removeScrollbars());

    // Topic: structure-fields - Adding basic fields to a content type.
    // Add Vendor URL field to Vendor content type.
    // Navigation to the Manage fields page has been tested in previous topics.
    $this->drupalGet('admin/structure/types/manage/' . $vendor . '/fields');
    $this->clickLink($this->callT('Add field'));
    $this->assertRaw((string) $this->callT('Add field'));
    $this->assertText($this->callT('Add a new field'));

    // Fill in the form in the screenshot: choose Link for field type and
    // type in Vendor URL for the Label, triggering the event to set
    // up the machine name.
    // Initial page for admin/structure/types/manage/vendor/fields/add-field.
    $this->drupalGet('admin/structure/types/manage/' . $vendor . '/fields/add-field');
    $this->makeScreenShot('structure-fields-add-field.png', 'jQuery(\'#edit-new-storage-type\').val(\'link\'); jQuery(\'#edit-label\').val("' . $this->demoInput['vendor_field_url_label'] . '"); jQuery(\'#edit-label\').trigger(\'formUpdated.machineName\'); jQuery(\'.machine-name-value\').html("field_' . $vendor_url . '"); jQuery(\'#edit-new-storage-wrapper, #edit-new-storage-wrapper input, #edit-new-storage-wrapper .field-suffix, #edit-new-storage-wrapper .field-suffix small\').show(); ' . $this->hideArea('#toolbar-administration') . $this->setWidth('header, .page-content'));

    // Reset form and submit.
    $this->drupalGet('admin/structure/types/manage/' . $vendor . '/fields/add-field');
    $this->setUpAddNewField('link');
    $this->assertText($this->callT('Label'));
    $this->drupalPostForm(NULL, [
        'new_storage_type' => 'link',
        'label' => $this->demoInput['vendor_field_url_label'],
        'field_name' => $vendor_url,
      ], $this->callT('Save and continue'));

    $this->assertRaw($this->demoInput['vendor_field_url_label']);
    $this->assertText($this->callT('Allowed number of values'));
    $this->drupalPostForm(NULL, [], $this->callT('Save field settings'));

    $this->assertRaw($this->demoInput['vendor_field_url_label']);
    $this->assertText($this->callT('Label'));
    $this->assertText($this->callT('Help text'));
    $this->assertText($this->callT('Required field'));
    $this->assertText($this->callT('Allowed link type'));
    $this->assertText($this->callT('Allow link text'));
    $this->drupalPostForm(NULL, [
        'settings[link_type]' => 16,
        'settings[title]' => 0,
      ], $this->callT('Save settings'));

    // To make the screen shot, go back to the edit form for this field.
    $this->drupalGet('admin/structure/types/manage/' . $vendor . '/fields/node.' . $vendor . '.field_' . $vendor_url);
    // Field settings page for adding vendor URL field.
    $this->makeScreenShot('structure-fields-vendor-url.png', 'window.scroll(0,100); ' . $this->hideArea('#toolbar-administration, #edit-actions') . $this->removeScrollbars());

    // Add Main Image field to Vendor content type.
    $this->drupalGet('admin/structure/types/manage/' . $vendor . '/fields/add-field');
    $this->setUpAddNewField('image');
    $this->drupalPostForm(NULL, [
        'new_storage_type' => 'image',
        'label' => $this->demoInput['vendor_field_image_label'],
        'field_name' => $main_image,
      ], $this->callT('Save and continue'));
    $this->drupalPostForm(NULL, [], $this->callT('Save field settings'));

    $this->assertRaw($this->demoInput['vendor_field_image_label']);
    $this->assertText($this->callT('Label'));
    $this->assertText($this->callT('Help text'));
    $this->assertText($this->callT('Required field'));
    $this->assertText($this->callT('Allowed file extensions'));
    $this->assertText($this->callT('File directory'));
    $this->assertText($this->callT('Minimum image resolution'));
    $this->assertText($this->callT('Maximum upload size'));
    $this->assertRaw((string) $this->callT('Enable <em>Alt</em> field'));
    $this->assertRaw((string) $this->callT('<em>Alt</em> field required'));
    $this->drupalPostForm(NULL, [
        'required' => 1,
        'settings[file_directory]' => $this->demoInput['vendor_field_image_directory'],
        'settings[min_resolution][x]' => 600,
        'settings[min_resolution][y]' => 600,
        'settings[max_filesize]' => '5 MB',
      ], $this->callT('Save settings'));
    // Manage fields page for Vendor, showing two new fields.
    $this->makeScreenShot('structure-fields-result.png', $this->hideArea('#toolbar-administration'));

    // To make the settings screen shot, go back to the edit form for this
    // field.
    $this->drupalGet('admin/structure/types/manage/' . $vendor . '/fields/node.' . $vendor . '.field_' . $main_image);
    // Field settings page for adding main image field.
    $this->makeScreenShot('structure-fields-main-img.png', 'window.scroll(0,100); ' . $this->hideArea('#toolbar-administration, #edit-actions') . $this->removeScrollbars());
    // Add the main image field to Recipe. No screenshots.
    $this->drupalGet('admin/structure/types/manage/' . $recipe . '/fields/add-field');
    $this->setUpAddExistingField('field_' . $main_image);
    $this->drupalPostForm(NULL, [
        'existing_storage_name' => 'field_' . $main_image,
        'existing_storage_label' => $this->demoInput['vendor_field_image_label'],
      ], $this->callT('Save and continue'));
    $this->drupalPostForm(NULL, [
        'required' => 1,
        'settings[file_directory]' => $this->demoInput['recipe_field_image_directory'],
        'settings[min_resolution][x]' => 600,
        'settings[min_resolution][y]' => 600,
        'settings[max_filesize]' => '5 MB',
      ], $this->callT('Save settings'));

    // Create two Vendor content items. No screenshots.
    $this->drupalGet('node/add/' . $vendor);
    // Fill in the body, summary, and file. Also open up the path edit area.
    $this->waitForInteraction('css', '#edit-path-0 summary');
    $this->fillInBody($this->demoInput['vendor_1_body']);
    $this->fillInSummary($this->demoInput['vendor_1_summary']);
    $this->getSession()->getPage()->attachFileToField('files[field_' . $main_image . '_0]', DRUPAL_ROOT . '/' . $this->assetsDirectory . 'farm.jpg');

    // Submit once.
    $this->drupalPostForm(NULL, [
        'title[0][value]' => $this->demoInput['vendor_1_title'],
        'path[0][alias]' => $this->demoInput['vendor_1_path'],
        'field_' . $vendor_url . '[0][uri]' => $this->demoInput['vendor_1_url'],
      ], $this->callT('Save'));
    // This will cause an error about missing alt text. Submit again with the
    // alt text defined.
    $this->drupalPostForm(NULL, [
        'field_' . $main_image . '[0][alt]' => $this->demoInput['vendor_1_title'],
      ], $this->callT('Save'));

    $this->drupalGet('node/add/' . $vendor);
    // Fill in the body text and image. Also open up the path edit area.
    $this->waitForInteraction('css', '#edit-path-0 summary');
    $this->fillInBody($this->demoInput['vendor_2_body']);
    $this->fillInSummary($this->demoInput['vendor_2_summary']);
    $this->getSession()->getPage()->attachFileToField('files[field_' . $main_image . '_0]', DRUPAL_ROOT . '/' . $this->assetsDirectory . 'honey_bee.jpg');
    $this->drupalPostForm(NULL, [
        'title[0][value]' => $this->demoInput['vendor_2_title'],
        'path[0][alias]' => $this->demoInput['vendor_2_path'],
        'field_' . $vendor_url . '[0][uri]' => $this->demoInput['vendor_2_url'],
      ], $this->callT('Save'));
    $this->drupalPostForm(NULL, [
        'field_' . $main_image . '[0][alt]' => $this->demoInput['vendor_2_title'],
      ], $this->callT('Save'));

    // The next topic with screenshots is structure-taxonomy, but the
    // screenshot is generated later.

    // Topic: structure-taxonomy-setup - Setting Up a Taxonomy.
    $this->drupalGet('<front>');
    $this->clickLink($this->callT('Structure'));
    // Here, you would ideally want to click the "Taxonomy" link.
    // However, the link text includes a span that says this, plus a div with
    // the description, so using clickLink is not really feasible. So, just
    // assert the text, and visit the URL. These can be problematic in
    // non-English languages...
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertText($this->callT('Taxonomy'));
    }
    $this->drupalGet('admin/structure/taxonomy');
    // Vocabulary names for built-in vocabularies should be English. See
    // https://www.drupal.org/project/user_guide/issues/2959852
    $this->assertText('Tags');

    // Taxonomy list page (admin/structure/taxonomy).
    $this->makeScreenShot('structure-taxonomy-setup-taxonomy-page.png', $this->hideArea('#toolbar-administration') . $this->setWidth('header, .layout-container', 800));

    // Add Ingredients taxonomy vocabulary.
    $this->clickLink($this->callT('Add vocabulary'));
    $this->assertText($this->callT('Name'));
    $this->assertText($this->callT('Description'));

    // Add Ingredients vocabulary from admin/structure/taxonomy/add.
    $this->makeScreenShot('structure-taxonomy-setup-add-vocabulary.png', 'jQuery(\'#edit-name\').val("' . $this->demoInput['recipe_field_ingredients_label'] . '");' . $this->hideArea('#toolbar-administration') . $this->setWidth('header, .page-content'));
    $this->openMachineNameEdit();
    $this->drupalPostForm(NULL, [
        'name' => $this->demoInput['recipe_field_ingredients_label'],
        'vid' => $ingredients,
      ], $this->callT('Save'));
    $this->assertRaw($this->demoInput['recipe_field_ingredients_label']);

    // Ingredients vocabulary page
    // (admin/structure/taxonomy/manage/ingredients/overview).
    $this->makeScreenShot('structure-taxonomy-setup-vocabulary-overview.png' , $this->hideArea('#toolbar-administration') . $this->setWidth('header, .layout-container', 800));
    // Add 3 sample terms.
    $this->clickLink($this->callT('Add term'));
    $this->assertText($this->callT('Name'));

    // Fill in the form in the screenshot, with the term name Butter.
    // Name portion of Add term page
    // (admin/structure/taxonomy/manage/ingredients/add).
    $this->makeScreenShot('structure-taxonomy-setup-add-term.png', 'jQuery(\'#edit-name-0-value\').val("' . $this->demoInput['recipe_field_ingredients_term_1'] . '");' . $this->hideArea('#toolbar-administration') . $this->removeScrollbars() . $this->setWidth('header, .layout-container', 800));

    // Add the rest of the terms, with no screenshots.
    $this->drupalPostForm(NULL, [
        'name[0][value]' => $this->demoInput['recipe_field_ingredients_term_1'],
      ], $this->callT('Save'));
    $this->drupalPostForm(NULL, [
        'name[0][value]' => $this->demoInput['recipe_field_ingredients_term_2'],
      ], $this->callT('Save'));
    $this->drupalPostForm(NULL, [
        'name[0][value]' => $this->demoInput['recipe_field_ingredients_term_3'],
      ], $this->callT('Save'));
    $this->drupalPostForm(NULL, [
        'name[0][value]' => $this->demoInput['recipe_field_ingredients_term_4'],
      ], $this->callT('Save'));

    // Add the Ingredients field to Recipe content type.
    // Skip navigation tests, as they have been tested on topics above.
    $this->drupalGet('admin/structure/types/manage/' . $recipe . '/fields/add-field');

    // Add field page to add Ingredients taxonomy reference field.
    $this->makeScreenShot('structure-taxonomy-setup-add-field.png', 'jQuery(\'#edit-new-storage-type\').val(\'field_ui:entity_reference:taxonomy_term\'); jQuery(\'#edit-label\').val("' . $this->demoInput['recipe_field_ingredients_label'] . '");  jQuery(\'#edit-label\').trigger(\'formUpdated.machineName\'); jQuery(\'.machine-name-value\').html("field_' . $ingredients . '"); jQuery(\'#edit-new-storage-wrapper, #edit-new-storage-wrapper .field-suffix, #edit-new-storage-wrapper .field-suffix small\').show(); ' . $this->hideArea('#toolbar-administration') . $this->setWidth('header, .page-content'));

    // Reset the form and submit.
    $this->drupalGet('admin/structure/types/manage/' . $recipe . '/fields/add-field');
    $this->setUpAddNewField('field_ui:entity_reference:taxonomy_term');
    $this->drupalPostForm(NULL, [
        'new_storage_type' => 'field_ui:entity_reference:taxonomy_term',
        'label' => $this->demoInput['recipe_field_ingredients_label'],
        'field_name' => $ingredients,
      ], $this->callT('Save and continue'));

    $this->assertText($this->callT('Type of item to reference'));
    $this->assertText($this->callT('Allowed number of values'));
    $this->drupalPostForm(NULL, [
        'cardinality' => '-1',
      ], $this->callT('Save field settings'));

    $this->assertText($this->callT('Help text'));
    $this->assertText($this->callT('Reference type'));
    $this->assertText($this->callT('Reference method'));
    $this->assertText($this->callT('Vocabulary'));
    $this->assertText($this->callT("Create referenced entities if they don't already exist"));

    $this->scrollWindowUp();
    // The checkboxes for vocabulary on this page are a bit weird in the test.
    // So check them outside of the form submit.
    $this->waitForInteraction('css', '.form-item-settings-handler-settings-target-bundles-' . $ingredients_hyphens . ' input');
    $this->drupalPostForm(NULL, [
        'description' => $this->demoInput['recipe_field_ingredients_help'],
        'settings[handler_settings][auto_create]' => 1,
      ], $this->callT('Save settings'));
    // Manage fields page showing Ingredients field on Recipe content type.
    $this->makeScreenShot('structure-taxonomy-setup-finished.png', $this->hideArea('#toolbar-administration'));

    // Go back and edit the field settings to make the next screenshot,
    // scrolling to the bottom.
    $this->drupalGet('admin/structure/types/manage/' . $recipe . '/fields/node.' . $recipe . '.field_' . $ingredients);
    // Reference type section of field settings page for Ingredients field.
    $this->makeScreenShot('structure-taxonomy-setup-field-settings-2.png', 'window.scroll(0,2000);' . $this->hideArea('#toolbar-administration, header, .region-breadcrumb') . 'jQuery(\'#edit-default-value-input\').removeAttr(\'open\');' . $this->removeScrollbars());

    // Make the other screenshot from the edit settings page.
    $this->drupalGet('admin/structure/types/manage/' . $recipe . '/fields/node.' . $recipe . '.field_' . $ingredients . '/storage');
    // Field storage settings page for Ingredients field.
    $this->makeScreenShot('structure-taxonomy-setup-field-settings.png', $this->hideArea('#toolbar-administration, header, .region-breadcrumb') . $this->setWidth('.page-content'));

    // Topic: structure-adding-reference - Adding a reference field.
    // Add the Submitted by field to Recipe content type.
    // Note: Navigation to this page has been tested in previous topics.
    $this->drupalGet('admin/structure/types/manage/' . $recipe . '/fields/add-field');

    // Add field page for adding a Submitted by field to Recipe.
    $this->makeScreenShot('structure-adding-reference-add-field.png', 'jQuery(\'#edit-new-storage-type\').val(\'field_ui:entity_reference:node\'); jQuery(\'#edit-label\').val("' . $this->demoInput['recipe_field_submitted_label'] . '"); jQuery(\'#edit-label\').trigger(\'formUpdated.machineName\'); jQuery(\'.machine-name-value\').html("field_' . $submitted_by . '");  jQuery(\'#edit-new-storage-wrapper, #edit-new-storage-wrapper .field-suffix, #edit-new-storage-wrapper .field-suffix small\').show(); ' . $this->hideArea('#toolbar-administration') . $this->setWidth('header, .page-content', 800));

    // Reset the form and submit.
    $this->drupalGet('admin/structure/types/manage/' . $recipe . '/fields/add-field');
    $this->setUpAddNewField('field_ui:entity_reference:node');
    $this->drupalPostForm(NULL, [
        'new_storage_type' => 'field_ui:entity_reference:node',
        'label' => $this->demoInput['recipe_field_submitted_label'],
        'field_name' => $submitted_by,
      ], $this->callT('Save and continue'));

    // Field storage settings page for Submitted by field.
    $this->makeScreenshot('structure-adding-reference-set-field-basic.png', $this->hideArea('#toolbar-administration') . $this->setWidth('header, .layout-container'));

    $this->drupalPostForm(NULL, [], $this->callT('Save field settings'));

    $this->assertText($this->callT('Label'));
    $this->assertText($this->callT('Help text'));
    $this->assertText($this->callT('Required field'));
    $this->assertText($this->callT('Reference method'));
    $this->assertText($this->callT('Content types'));
    $this->assertText($this->callT('Sort by'));
    $this->scrollWindowUp();
    // The checkbox for content type on this page is a bit
    // weird in the test. So do it outside of the form submit.
    $this->waitForInteraction('css', '.form-item-settings-handler-settings-target-bundles-' . $vendor . ' input');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->drupalPostForm(NULL, [
        'description' => $this->demoInput['recipe_field_submitted_help'],
        'required' => 1,
      ], $this->callT('Save settings'));

    // Manage fields page for content type Recipe.
    $this->makeScreenShot('structure-adding-reference-manage-fields.png', $this->hideArea('#toolbar-administration'));

    // Go back and edit the field settings to make the next screenshot,
    // scrolling to the bottom.
    $this->drupalGet('admin/structure/types/manage/' . $recipe . '/fields/node.' . $recipe . '.field_' . $submitted_by);
    // Field settings page for Submitted by field.
    $this->makeScreenShot('structure-adding-reference-field-settings.png', 'window.scroll(0,2000);' . $this->hideArea('#toolbar-administration') . $this->removeScrollbars());

    // The sort setting doesn't seem to work on the first try of editing the
    // field settings. So, set it here again.
    $this->getSession()->getPage()
      ->find('css', '#edit-settings-handler-settings-sort-field')
      ->selectOption('title');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertText($this->callT('Sort direction'));
    $this->drupalPostForm(NULL, [
      ], $this->callT('Save settings'));

    // Topic: structure-form-editing - Changing Content Entry Forms.
    // Note: Navigation has been tested on other topics.
    $this->drupalGet('admin/structure/types/manage/' . $recipe . '/form-display');

    // Manage form display page for Recipe, Ingredients field area, with
    // Widget drop-down outlined.
    // Note that ideally, the drop-down would be open, but this is not
    // apparently possible using JavaScript.
    $this->makeScreenShot('structure-form-editing-manage-form.png', 'window.scroll(0,200);' . $this->hideArea('#toolbar-administration, header, .region-breadcrumb, .help, .field-plugin-settings-edit-wrapper, .tabledrag-toggle-weight-wrapper') . 'jQuery(\'#edit-fields-field-' . $ingredients_hyphens . '-type\').val(\'entity_reference_autocomplete_tags\');' . $this->addBorder('#edit-fields-field-' . $ingredients . '-type') . $this->setWidth('#field-display-overview', 800) . $this->removeScrollbars());

    // Set the Ingredients field to use tag-style autocomplete.
    $this->drupalGet('admin/structure/types/manage/' . $recipe . '/form-display');
    $this->assertRaw((string) $this->callT('Autocomplete (Tags style)'));
    $this->drupalPostForm(NULL, [
        'fields[field_' . $ingredients . '][type]' => 'entity_reference_autocomplete_tags',
      ], $this->callT('Save'));

    $this->drupalGet('node/add/' . $recipe);
    // Create recipe page (node/add/recipe).
    $this->makeScreenShot('structure-form-editing-add-recipe.png', 'window.scroll(0,100);' . $this->hideArea('#toolbar-administration') . $this->removeScrollbars());

    // Create two Recipe content items. No screenshots.
    $this->drupalGet('node/add/' . $recipe);
    // Fill in the body and file fields. Also open up the path edit area.
    $this->waitForInteraction('css', '#edit-path-0 summary');
    $this->fillInBody($this->demoInput['recipe_1_body']);
    $this->getSession()->getPage()->attachFileToField('files[field_' . $main_image . '_0]', DRUPAL_ROOT . '/' . $this->assetsDirectory . 'salad.jpg');
    // Submit once.
    $this->scrollWindowUp();
    $this->drupalPostForm(NULL, [
        'title[0][value]' => $this->demoInput['recipe_1_title'],
        'path[0][alias]' => $this->demoInput['recipe_1_path'],
        'field_' . $ingredients . '[target_id]' => $this->demoInput['recipe_1_ingredients'],
        'field_' . $submitted_by . '[0][target_id]' => $this->demoInput['vendor_1_title'],
      ], $this->callT('Save'));
    // This will cause an error about missing alt text. Submit again with the
    // alt text defined.
    $this->drupalPostForm(NULL, [
        'field_' . $main_image . '[0][alt]' => $this->demoInput['recipe_1_title'],
      ], $this->callT('Save'));

    $this->drupalGet('node/add/' . $recipe);
    // Fill in the body and file fields. Also open up the path edit area.
    $this->waitForInteraction('css', '#edit-path-0 summary');
    $this->fillInBody($this->demoInput['recipe_2_body']);
    $this->getSession()->getPage()->attachFileToField('files[field_' . $main_image . '_0]', DRUPAL_ROOT . '/' . $this->assetsDirectory . 'carrots.jpg');
    $this->drupalPostForm(NULL, [
        'title[0][value]' => $this->demoInput['recipe_2_title'],
        'path[0][alias]' => $this->demoInput['recipe_2_path'],
        'field_' . $ingredients . '[target_id]' => $this->demoInput['recipe_2_ingredients'],
        'field_' . $submitted_by . '[0][target_id]' => $this->demoInput['vendor_1_title'],
      ], $this->callT('Save'));
    $this->drupalPostForm(NULL, [
        'field_' . $main_image . '[0][alt]' => $this->demoInput['recipe_2_title'],
      ], $this->callT('Save'));


    // Topic: (out of order) structure-taxonomy - Concept: Taxonomy.

    $this->drupalGet('taxonomy/term/4');
    // Carrots taxonomy page after adding Recipe content items.
    $this->makeScreenShot('structure-taxonomy_listingPage_carrots.png', $this->hideArea('#toolbar-administration, header#header, nav.tabs, footer, .feed-icons, .region-sidebar-first, .region-breadcrumb') . $this->setWidth('.block-system-main-block') . $this->removeScrollbars() . $this->setBodyColor());


    // Topic: structure-content-display - Changing Content Display.

    // Note: Navigation has been tested on topics above.
    $this->drupalGet('admin/structure/types');
    // Content types list on admin/structure/types, with operations dropdown
    // for Vendor content type expanded.
    $this->makeScreenShot('structure-content-display_manage_display.png', 'jQuery("a[href*=\'' . $vendor . '/delete\']").parents(\'.dropbutton-wrapper\').addClass(\'open\'); ' . $this->hideArea('#toolbar-administration') . $this->setWidth('.region-content', 950));

    // Note: Navigation has been tested on topics above.
    // Set the labels for main image and vendor URL to hidden.
    $this->drupalGet('admin/structure/types/manage/' . $vendor . '/display');
    $this->assertText($this->callT('Label'));
    $this->assertRaw((string) $this->callT('Hidden'));
    $this->drupalPostForm(NULL, [
        'fields[field_' . $main_image . '][label]' => 'hidden',
        'fields[field_' . $vendor_url . '][label]' => 'hidden',
      ], $this->callT('Save'));
    $this->drupalGet('admin/structure/types/manage/' . $vendor . '/display');

    // Manage display page for Vendor content type
    // (admin/structure/types/manage/vendor/display), with labels for Main
    // Image and Vendor URL hidden, and their select lists outlined in red.
    $this->makeScreenShot('structure-content-display_main_image_hidden.png', $this->hideArea('#toolbar-administration, header, .region-pre-content, .region-breadcrumb, .help, #edit-modes, #edit-actions') . $this->removeScrollbars() . $this->addBorder('#edit-fields-field-' . $main_image_hyphens . '-label, #edit-fields-field-' . $vendor_url_hyphens . '-label'));

    $this->drupalGet('admin/structure/types/manage/' . $vendor . '/display');
    // Use Ajax to open the Edit area for the Vendor URL field.
    $this->waitForInteraction('css', '#edit-fields-field-' . $vendor_url_hyphens . '-settings-edit');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // These text tests can be problematic in non-English languages due to
    // entities etc.
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertText($this->callT('Trim link text length'));
      $this->assertRaw((string) $this->callT('Open link in new window'));
      $this->assertRaw((string) $this->callT('Update'));
    }

    // Vendor URL settings form, with trim length cleared, and open link in
    // new window checked.
    $this->makeScreenShot('structure-content-display_trim_length.png', $this->removeScrollbars() . $this->showOnly('.field-plugin-settings-edit-form') . $this->setWidth('table', 400) . 'jQuery(\'.form-item-fields-field-' . $vendor_url_hyphens . '-settings-edit-form-settings-trim-length input\').val(\'\'); jQuery(\'.form-item-fields-field-' . $vendor_url_hyphens . '-settings-edit-form-settings-target input\').attr(\'checked\', \'checked\'); ');

    // Reset the page.
    $this->drupalGet('admin/structure/types/manage/' . $vendor . '/display');
    $this->waitForInteraction('css', '#edit-fields-field-' . $vendor_url_hyphens . '-settings-edit');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Set the trim length to zero and set links to open in a new window.
    $this->drupalPostForm(NULL, [
        'fields[field_' . $vendor_url . '][settings_edit_form][settings][trim_length]' => '',
        'fields[field_' . $vendor_url . '][settings_edit_form][settings][target]' => '_blank',
      ], $this->callT('Save'));

    $this->drupalGet('admin/structure/types/manage/' . $vendor . '/display');
    // Manage display page for Vendor content type, with order changed.
    $this->makeScreenShot('structure-content-display_change_order.png', $this->hideArea('#toolbar-administration, header, .region-pre-content, .region-breadcrumb, .help, .tabledrag-toggle-weight-wrapper, #edit-modes, #edit-actions') . 'jQuery(\'table\').before(\'<div style="display: block; " class="tabledrag-changed-warning messages messages--warning" role="alert"><abbr class="warning tabledrag-changed">*</abbr>' . $this->callT('You have unsaved changes.') . '</div>\');' . 'var img = jQuery(\'table tbody tr#field-' . $main_image_hyphens . '\').detach(); var bod = jQuery(\'table tbody tr#body\').detach(); var vurl = jQuery(\'table tbody tr#field-' . $vendor_url_hyphens . '\').detach(); jQuery(\'table tbody\').prepend(vurl).prepend(bod).prepend(img); jQuery(\'table tbody tr:first\').toggleClass(\'drag-previous\');');

    // Submit the changed order in the form.
    $this->drupalGet('admin/structure/types/manage/' . $vendor . '/display');
    $this->waitForInteraction('css', '.tabledrag-toggle-weight');
    $this->waitForInteraction('css', '#edit-fields-field-' . $main_image_hyphens . '-weight', 'focus');
    $this->drupalPostForm(NULL, [
        'fields[field_' . $main_image . '][weight]' => 10,
        'fields[body][weight]' => 20,
        'fields[field_' . $vendor_url . '][weight]' => 30,
        'fields[links][weight]' => 40,
      ], $this->callT('Save'));

    // Make similar changes for the Recipe content type. No screenshots.
    $this->drupalGet('admin/structure/types/manage/' . $recipe . '/display');
    // Show weights should still be toggled. Just in case, use jQuery.
    $this->getSession()->getDriver()->executeScript("jQuery('.tabledrag-hide').show();");
    $this->waitForInteraction('css', '#edit-fields-field-' . $main_image_hyphens . '-weight', 'focus');
    $this->drupalPostForm(NULL, [
        'fields[field_' . $main_image . '][weight]' => 10,
        'fields[field_' . $main_image . '][label]' => 'hidden',
        'fields[body][weight]' => 20,
        'fields[field_' . $ingredients . '][weight]' => 30,
        'fields[field_' . $submitted_by . '][weight]' => 40,
        'fields[field_' . $submitted_by . '][label]' => 'inline',
        'fields[links][weight]' => 50,
      ], $this->callT('Save'));

    // Topic: structure-image-style-create - Setting Up an Image Style.

    // Create the image style.
    // Topic: config-basic - Editing basic site information.
    $this->drupalGet('<front>');
    $this->clickLink($this->callT('Configuration'));
    $this->assertText($this->callT('Media'));
    // Here, you would ideally want to click the "Image styles" link.
    // However, the link text includes a span that says this, plus a div with
    // the description, so using clickLink is not really feasible. So, just
    // assert the text, and visit the URL. These can be problematic in
    // non-English languages...
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertText($this->callT('Image styles'));
    }
    $this->drupalGet('admin/config/media/image-styles');

    $this->clickLink($this->callT('Add image style'));
    $this->openMachineNameEdit('#edit-label');
    $this->drupalPostForm(NULL, [
        'label' => $this->demoInput['image_style_label'],
        'name' => $this->demoInput['image_style_machine_name'],
      ], $this->callT('Create new style'));


    $this->assertText($this->callT('Effect'));
    $this->assertRaw((string) $this->callT('Scale and crop'));
    $this->drupalPostForm(NULL, [
        'new' => 'image_scale_and_crop',
      ], $this->callT('Add'));

    $this->drupalPostForm(NULL, [
        'data[width]' => 300,
        'data[height]' => 200,
      ], $this->callT('Add effect'));
    // Image style editing page, with effects added.
    $this->makeScreenShot('structure-image-style-create-add-style.png', $this->removeScrollbars() . $this->hideArea('#toolbar-administration, .tabledrag-hide, .tabledrag-toggle-weight') . 'jQuery(".tabledrag-handle").show();' . $this->setWidth('.layout-container', 800) . $this->setWidth('header', 830));

    // Use the image style in Manage Display for the Vendor.
    // Navigation has already been tested for this page.
    $this->drupalGet('admin/structure/types/manage/' . $vendor . '/display');
    // Use Ajax to open the Edit area for the Main Image field.
    $this->waitForInteraction('css', '#edit-fields-field-' . $main_image_hyphens . '-settings-edit');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // These text tests can be problematic in non-English languages due to
    // entities etc.
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertText($this->callT('Image style'));
      $this->assertRaw((string) $this->callT('Link image to'));
      $this->assertRaw((string) $this->callT('Nothing'));
      $this->assertRaw((string) $this->callT('Update'));
    }

    // Main image settings area of Vendor content type.
    $this->makeScreenShot('structure-image-style-create-manage-display.png', $this->removeScrollbars() . $this->showOnly('.field-plugin-settings-edit-form') . $this->setWidth('table', 400) . 'jQuery(\'.form-item-fields-field-' . $main_image_hyphens . '-settings-edit-form-settings-image-style select\').val(\'' . $this->demoInput['image_style_machine_name'] . '\');');

    // Reset the form.
    $this->drupalGet('admin/structure/types/manage/' . $vendor . '/display');
    $this->waitForInteraction('css', '#edit-fields-field-' . $main_image_hyphens . '-settings-edit');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->drupalPostForm(NULL, [
        'fields[field_' . $main_image . '][settings_edit_form][settings][image_style]' => $this->demoInput['image_style_machine_name'],
      ], $this->callT('Save'));

    // Repeat for Recipe content type, no screenshots.
    $this->drupalGet('admin/structure/types/manage/' . $recipe . '/display');
    $this->waitForInteraction('css', '#edit-fields-field-' . $main_image_hyphens . '-settings-edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->drupalPostForm(NULL, [
        'fields[field_' . $main_image . '][settings_edit_form][settings][image_style]' => $this->demoInput['image_style_machine_name'],
      ], $this->callT('Save'));


    // Topic: structure-text-format-config - Configuring Text Formats and
    // Editors.

    // Update the configuration for Basic HTML: add an HR tag.
    $this->drupalGet('<front>');
    $this->clickLink($this->callT('Configuration'));
    $this->assertText($this->callT('Content authoring'));
    // Here, you would ideally want to click the "Text formats" link.
    // However, the link text includes a span that says this, plus a div with
    // the description, so using clickLink is not really feasible. So, just
    // assert the text, and visit the URL. These can be problematic in
    // non-English languages...
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertText($this->callT('Text formats and editors'));
    }
    $this->drupalGet('admin/config/content/formats');

    // Hard to figure out which button to click, so assert the text and
    // then visit the URL.
    $this->assertLink($this->callT('Configure'));
    // Text format names are in English. See
    // https://www.drupal.org/project/user_guide/issues/2959852
    $this->assertText('Basic HTML');
    $this->drupalGet('admin/config/content/formats/manage/basic_html');
    $this->assertText('CKEditor');
    $this->assertText($this->callT('Text editor'));
    // The Tools and Active toolbar words are apparently problematic due to
    // JavaScript I think. Also Show group names and Available buttons.
    $this->assertText($this->callT('Filter processing order'));
    $this->assertText($this->callT('Allowed HTML tags'));

    // The button configuration for the editing toolbar uses drag-and-drop,
    // but has a text field behind the scenes. So, save the configuration and
    // then come back for the screenshot, after showing the text form.
    $this->getSession()->getDriver()->executeScript("jQuery('.form-item-editor-settings-toolbar-button-groups').show();");
    $this->drupalPostForm(NULL, [
        'editor[settings][toolbar][button_groups]' => '[[{"name":"' . $this->callT('Formatting') . '","items":["Bold","Italic"]},{"name":"' . $this->callT('Links') . '","items":["DrupalLink","DrupalUnlink"]},{"name":"' . $this->callT('Lists') . '","items":["BulletedList","NumberedList"]},{"name":"' . $this->callT('Media') . '","items":["Blockquote","DrupalImage"]},{"name":"' . $this->callT('Tools') . '","items":["Source", "HorizontalRule"]}]]',
        'filters[filter_html][settings][allowed_html]' => '<hr> <a hreflang href> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol type start> <li> <dl> <dt> <dd> <h2 id> <h3 id> <h4 id> <h5 id> <h6 id> <p> <br> <span> <img width height data-caption data-align data-entity-uuid data-entity-type alt src> <hr>',
      ], $this->callT('Save configuration'));

    // Confirmation message after updating text format.
    $this->makeScreenShot('structure-text-format-config-summary.png', $this->showOnly('.messages') . $this->setWidth('.messages', 500) . $this->setBodyColor() . $this->removeScrollbars());

    $this->drupalGet('admin/config/content/formats/manage/basic_html');
    // Button configuration area on text format edit page.
    $this->makeScreenShot('structure-text-format-config-editor-config.png', $this->hideArea('#toolbar-administration, .content-header, .region-breadcrumb, .help, .form-type-textfield, .form-type-machine-name, #edit-roles--wrapper, .form-type-select, #filters-status-wrapper, .form-type-table, .form-type-vertical-tabs, #edit-actions') . 'jQuery(\'.ckeditor-toolbar\').addClass(\'ckeditor-group-names-are-visible\');' . $this->removeScrollbars());
    $this->drupalGet('admin/config/content/formats/manage/basic_html');
    // Allowed HTML tags area on text format edit page.
    $this->makeScreenShot('structure-text-format-config-allowed-html.png', 'window.scroll(0,5000);' . $this->hideArea('#toolbar-administration, .content-header, .region-breadcrumb, .help, .form-item-name, .form-type-machine-name, fieldset, .form-type-select, #editor-settings-wrapper, #filters-status-wrapper, .form-type-table,  #edit-actions') . $this->setWidth('.form-type-vertical-tabs', 800) . $this->removeScrollbars());

  }

  /**
   * Makes screenshots for the User Accounts chapter.
   */
  protected function doUserAccounts() {
    $this->verifyTranslations();

    $vendor = $this->demoInput['vendor_type_machine_name'];
    $recipe = $this->demoInput['recipe_type_machine_name'];

    // Topic: user-new-role - Creating a role.
    // Create vendor role.
    $this->drupalGet('<front>');
    $this->clickLink($this->callT('People'));
    $this->clickLink($this->callT('Roles'));
    $this->assertText($this->callT('Anonymous user'));
    $this->assertText($this->callT('Authenticated user'));
    $this->assertText($this->callT('Administrator'));

    // Roles page (admin/people/roles).
    $this->makeScreenShot('user-new-role-roles-page.png', $this->hideArea('#toolbar-administration, .tabledrag-hide, .tabledrag-toggle-weight') . 'jQuery(".tabledrag-handle").show();' . $this->setWidth('header', 630) . $this->setWidth('.layout-container', 600) . $this->removeScrollbars());

    $this->clickLink($this->callT('Add role'));
    $this->assertText($this->callT('Role name'));

    // Add role page (admin/people/roles/add).
    $this->makeScreenShot('user-new-role-add-role.png', 'jQuery(\'#edit-label\').val("' . $this->demoInput['vendor_type_name'] . '"); jQuery(\'.form-item-label .field-suffix\').show(); jQuery(\'#edit-label\').trigger(\'formUpdated.machineName\'); jQuery(\'.machine-name-value\').html("' . $vendor . '"); ' . $this->setWidth('.layout-container, header') . $this->hideArea('#toolbar-administration'));
    $this->openMachineNameEdit('#edit-label');
    $this->drupalPostForm(NULL, [
        'label' => $this->demoInput['vendor_type_name'],
        'id' => $vendor,
      ], $this->callT('Save'));
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertRaw((string) $this->callT('Role %label has been added.', TRUE, ['%label' => $this->demoInput['vendor_type_name']]));
    }

    // Confirmation message after adding new role.
    $this->makeScreenShot('user-new-role-confirm.png', $this->showOnly('.messages') . $this->setWidth('.messages', 500) . $this->setBodyColor() . $this->removeScrollbars());


    // Topic: user-new-user - Creating a User Account.
    // Create a user account for Sweet Honey.
    $this->drupalGet('<front>');
    $this->clickLink($this->callT('People'));
    $this->clickLink($this->callT('Add user'));
    $this->assertText($this->callT('Email address'));
    $this->assertText($this->callT('Username'));
    $this->assertText($this->callT('Password'));
    $this->assertText($this->callT('Confirm password'));
    $this->assertText($this->callT('Status'));
    $this->assertText($this->callT('Roles'));
    $this->assertText($this->callT('Notify user of new account'));
    $this->assertText($this->callT('Picture'));
    $this->assertText($this->callT('Contact settings'));

    // Add new user form (/admin/people/create).
    $this->makeScreenShot('user-new-user_form.png', $this->hideArea('#toolbar-administration') . $this->setWidth('header', 830) . $this->setWidth('.layout-container', 800) . $this->removeScrollbars());
    $password = $this->randomString();
    // Fill in the file field.
    $this->getSession()->getPage()->attachFileToField('files[user_picture_0]', DRUPAL_ROOT . '/' . $this->assetsDirectory . 'honey_bee.jpg');
    $this->drupalPostForm(NULL, [
        'mail' => $this->demoInput['vendor_2_email'],
        'name' => $this->demoInput['vendor_2_title'],
        'pass[pass1]' => $password,
        'pass[pass2]' => $password,
        'roles[' . $vendor . ']' => $vendor,
        'notify' => TRUE,
      ], $this->callT('Create new account'));
    if ($this->demoInput['first_langcode'] == 'en') {
      // Looking for the whole string requires that we know the URL. So,
      // just look for the two parts separately. This will only work in
      // English.
      $this->assertRaw((string) $this->callT('A welcome message with further instructions has been emailed to the new user'));
      $this->assertRaw($this->demoInput['vendor_2_title']);
    }

    // Confirmation message after adding new user.
    $this->makeScreenShot('user-new-user-created.png', $this->showOnly('.messages--status') . $this->setWidth('.messages', 800) . $this->setBodyColor() . $this->removeScrollbars());

    // Create a second user account for Happy Farms, no screenshots.
    $this->drupalGet('admin/people/create');
    $password = $this->randomString();
    $this->getSession()->getPage()->attachFileToField('files[user_picture_0]', DRUPAL_ROOT . '/' . $this->assetsDirectory . 'farm.jpg');
    $this->drupalPostForm(NULL, [
        'mail' => $this->demoInput['vendor_1_email'],
        'name' => $this->demoInput['vendor_1_title'],
        'pass[pass1]' => $password,
        'pass[pass2]' => $password,
        'roles[' . $vendor . ']' => $vendor,
        'notify' => TRUE,
      ], $this->callT('Create new account'));

    // Topic: user-permissions - Assigning permissions to a role.

    // Update the permissions for the Vendor role.
    $this->drupalGet('<front>');
    $this->clickLink($this->callT('People'));
    $this->clickLink($this->callT('Roles'));
    // Figuring out how to navigate to the permissions page for the Vendor role
    // is difficult, so just check that the text/links are there and then go
    // directly.
    $this->assertLink($this->callT('Edit permissions'));
    $this->assertText($this->demoInput['vendor_type_name']);
    $this->drupalGet('admin/people/permissions/' . $vendor);
    $this->assertRaw((string) $this->callT('Edit role'));
    $this->assertText($this->callT('Post comments'));
    $this->assertText($this->callT('Administer blocks'));
    $this->assertText('Contact');
    $this->assertText($this->callT("Use users' personal contact forms"));
    $this->assertText('Filter');
    $this->assertText('Node');
    $this->assertText('Quick Edit');
    $this->assertText($this->callT('Access in-place editing'));
    // These strings are problematic to test in non-English languages.
    if ($this->demoInput['first_langcode'] == 'en') {
      // This full text string includes a URL, so just assert the pieces, in
      // English.
      $this->assertText('Use the');
      $this->assertText('Restricted HTML');
      $this->assertText('text format');
      $this->assertRaw((string) $this->callT('%type_name: Create new content', TRUE, ['%type_name' => $this->demoInput['recipe_type_name']]));
      $this->assertRaw((string) $this->callT('%type_name: Edit own content', TRUE, ['%type_name' => $this->demoInput['recipe_type_name']]));
      $this->assertRaw((string) $this->callT('%type_name: Delete own content', TRUE, ['%type_name' => $this->demoInput['recipe_type_name']]));
      $this->assertRaw((string) $this->callT('%type_name: Edit own content', TRUE, ['%type_name' => $this->demoInput['vendor_type_name']]));
    }

    $this->drupalPostForm(NULL, [
        $vendor . '[access user contact forms]' => 1,
        $vendor . '[use text format restricted_html]' => 1,
        $vendor . '[create ' . $recipe . ' content]' => 1,
        $vendor . '[edit own ' . $recipe . ' content]' => 1,
        $vendor . '[delete own ' . $recipe . ' content]' => 1,
        $vendor . '[edit own ' . $vendor . ' content]' => 1,
        $vendor . '[access in-place editing]' => 1,
      ], $this->callT('Save permissions'));
    $this->assertText($this->callT('The changes have been saved.'));

    // Confirmation message after updating permissions.
    $this->makeScreenShot('user-permissions-save-permissions.png', $this->showOnly('.messages--status') . $this->setWidth('.messages', 400) . $this->setBodyColor() . $this->removeScrollbars());

    // This screenshot doesn't work well in LTR languages unless the browser
    // window is resized.
    $this->getSession()->resizeWindow(950, 800);
    $this->drupalGet('admin/people/permissions/' . $vendor);
    // Permissions page for Vendor (admin/people/permissions/vendor).
    $this->makeScreenShot('user-permissions-check-permissions.png', $this->hideArea('#toolbar-administration') . 'window.scroll(0,3200);' . $this->removeScrollbars() . $this->setBodyColor());
    $this->getSession()->resizeWindow(1200, 800);


    // Topic: user-roles - Changing a User's Roles.

    // Update the user 1 account via single user edit.
    $this->drupalGet('admin/people');
    $this->assertLink($this->callT('Edit'));
    $this->assertText($this->callT('Name or email contains'));
    $this->assertRaw((string) $this->callT('Filter'));

    // People page (admin/people), with user 1's Edit button outlined.
    $this->makeScreenShot('user-roles_people-list.png', $this->addBorder('a[href*="user/1/edit"]') . $this->hideArea('#toolbar-administration') . $this->removeScrollbars());

    $this->drupalGet('user/1/edit');
    $this->assertText($this->callT('Roles'));
    $this->assertText($this->callT('Administrator'));

    // Roles area on user editing page.
    $this->makeScreenShot('user-roles_person-edit.png', 'window.scroll(0,6000);' . $this->showOnly('#edit-roles--wrapper') . 'jQuery(\'#edit-roles-administrator\').attr(\'checked\', 1);' . $this->removeScrollbars() . $this->setBodyColor());

    // Reload the page and submit form.
    $this->drupalGet('user/1/edit');
    $this->drupalPostForm(NULL, [
        'roles[administrator]' => 1,
      ], $this->callT('Save'));
    $this->assertText($this->callT('The changes have been saved.'));

    // Confirmation message after updating user.
    $this->makeScreenShot('user-roles_message.png', $this->showOnly('.messages--status') . $this->setWidth('.messages', 500) . $this->setBodyColor());

    // Update two accounts using bulk edit.
    $this->drupalGet('admin/people');
    $this->assertRaw((string) $this->callT('Action'));
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertRaw((string) $this->callT('Add the @label role to the selected user(s)', TRUE, ['@label' => $this->demoInput['vendor_type_name']]));
    }

    // Bulk editing form on People page (admin/people).
    $this->makeScreenShot('user-roles_bulk.png', $this->hideArea('#toolbar-administration, header, .region-breadcrumb, #block-seven-local-actions, .view-filters') . 'jQuery(\'#edit-user-bulk-form-0, #edit-user-bulk-form-1\').attr(\'checked\', 1).parents(\'tr\').addClass(\'selected\');' . 'jQuery(\'#edit-action\').val(\'user_add_role_action.' . $vendor . '\');' . $this->removeScrollbars() . $this->setBodyColor());

    $this->drupalGet('admin/people');
    $this->drupalPostForm(NULL, [
        'user_bulk_form[0]' => 1,
        'user_bulk_form[1]' => 1,
        'action' => 'user_add_role_action.' . $vendor,
      ], $this->callT('Apply to selected items'));
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertRaw((string) $this->callT('%action was applied to @count items.', TRUE, ['@count' => 2, '%action' => $this->callT('Add the @label role to the selected user(s)', TRUE, ['@label' => $this->demoInput['vendor_type_name']])]));
    }

    // Confirmation message after bulk user update.
    $this->makeScreenShot('user-roles_message_bulk.png', $this->showOnly('.messages--status') . $this->setWidth('.messages') . $this->setBodyColor());


    // Topic: user-content - Assigning Authors to Content.

    // Assign first vendor node to the corresponding vendor user.
    // Navigation has been tested on other topics.
    $this->drupalGet('node/3/edit');
    $this->assertText($this->callT('Authoring information'));
    $this->waitForInteraction('css', '#edit-author summary');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertText($this->callT('Authored by'));

    $this->drupalPostForm(NULL, [
        'uid[0][target_id]' => $this->demoInput['vendor_1_title'],
      ], $this->callT('Save'));
    if ($this->demoInput['first_langcode'] == 'en') {
      // The confirm message has a URL in it, so just look for the pieces of
      // the message.
      $this->assertText($this->demoInput['vendor_type_name']);
      $this->assertText($this->demoInput['vendor_1_title']);
      $this->assertText('has been updated');
    }

    // Confirmation message after content update.
    $this->makeScreenShot('user-content_updated.png', $this->showOnly('.messages--status') . $this->setWidth('.messages') . $this->setBodyColor() . $this->removeScrollbars());
    // Go back and take the screenshot of the authoring information.
    $this->drupalGet('node/3/edit');
    // Authoring information section of content edit page.
    $this->makeScreenShot('user-content.png', $this->hideArea('#toolbar-administration, .content-header, .region-breadcrumb, .help, .layout-region-node-main, .layout-region-node-footer') . $this->setBodyColor() . 'jQuery(\'#edit-author\').attr(\'open\', \'open\'); ' . 'jQuery(\'#edit-path-0\').removeAttr(\'open\'); ' . $this->removeScrollbars());

    // Assign second vendor node to the corresponding vendor user, without
    // screenshots.
    $this->drupalGet('node/4/edit');
    $this->waitForInteraction('css', '#edit-author summary');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->drupalPostForm(NULL, [
        'uid[0][target_id]' => $this->demoInput['vendor_2_title'],
      ], $this->callT('Save'));
  }

  /**
   * Makes screenshots for the Blocks chapter.
   */
  protected function doBlocks() {
    $this->verifyTranslations();

    // Some UI tests from the block-concept topic.
    $this->drupalGet('admin/structure/block');
    $this->assertRaw((string) $this->callT('Block layout'));
    $this->drupalGet('admin/structure/block/library/bartik');
    // We should test the "Who's online" block title, but due to the ' being
    // sometimes an entity, this is problematic. So only test in English and
    // skip the '
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertText('Who');
      $this->assertText('online');
    }

    // Topic: block-create-custom - Creating a Custom Block.
    // Create a block for hours and location.
    $this->drupalGet('<front>');
    $this->clickLink($this->callT('Structure'));
    // Here, you would ideally want to click the "Block layout" link.
    // However, the link text includes a span that says this, plus a div with
    // the description, so using clickLink is not really feasible. So, just
    // assert the text, and visit the URL. These can be problematic in
    // non-English languages...
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertText($this->callT('Block layout'));
    }
    $this->drupalGet('admin/structure/block');
    $this->clickLink($this->callT('Custom block library'));
    $this->clickLink($this->callT('Add custom block'));
    $this->assertRaw((string) $this->callT('Add custom block'));
    $this->assertText($this->callT('Block description'));
    $this->assertRaw((string) $this->callT('Body'));

    // Now navigate directly to the page, without the destination set.
    // Without the destination set, saving a custom block takes you to the
    // configure page, which we need to be on for the next topic. Otherwise,
    // getting to that page involves some kind of obscured URL and is very
    // difficult to manage.
    $this->drupalGet('block/add');
    $this->fillInBody($this->demoInput['hours_block_body']);

    // Block add page (block/add).
    $this->makeScreenShot('block-create-custom-add-custom-block.png', 'jQuery(\'#edit-info-0-value\').val("' . $this->demoInput['hours_block_description'] . '");' . $this->hideArea('#toolbar-administration') . $this->setWidth('.content-header, .layout-container', 800) . $this->removeScrollbars());

    $this->drupalPostForm(NULL, [
        'info[0][value]' => $this->demoInput['hours_block_description'],
      ], $this->callT('Save'));

    // Topic: block-place - Placing a Block in a Region.
    // Configuration page for placing a custom block in the sidebar.
    $this->assertRaw((string) $this->callT('Configure block'));
    $this->assertText($this->callT('Title'));
    $this->assertText($this->callT('Display title'));
    $this->assertText($this->callT('Region'));

    $this->makeScreenShot('block-place-configure-block.png', 'jQuery(\'#edit-settings-label\').val("' . $this->demoInput['hours_block_title'] . '"); jQuery(\'.machine-name-value\').html(\'' . $this->demoInput['hours_block_title_machine_name'] . '\');' . 'jQuery(\'#edit-region\').val(\'sidebar_second\');' . $this->hideArea('#toolbar-administration') . $this->setWidth('.content-header, .layout-container', 800) . $this->removeScrollbars());

    // Place the block in Bartik, sidebar second.
    $this->waitForInteraction('css', '#edit-settings-label-machine-name-suffix button');
    $this->drupalPostForm(NULL, [
        'settings[label]' => $this->demoInput['hours_block_title'],
        'id' => $this->demoInput['hours_block_title_machine_name'],
        'region' => 'sidebar_second',
      ], $this->callT('Save block'));
    $this->drupalGet('node/2');
    // About page with placed sidebar block.
    $this->makeScreenShot('block-place-sidebar.png', $this->hideArea('#toolbar-administration, footer') . $this->removeScrollbars());

    // Verify some UI text on several block pages, without checking navigation.
    $this->drupalGet('admin/structure/block');
    $this->assertRaw('Bartik');
    // Block and menu names are shown in English for built-in blocks. See
    // https://www.drupal.org/project/user_guide/issues/2959852
    $this->assertText('Powered by Drupal');
    $this->assertText($this->callT('Footer fifth'));
    $this->assertText('Tools');
    $this->assertText($this->callT('Sidebar first'));
    $this->assertText($this->callT('Sidebar second'));
    $this->assertText($this->callT('Operations'));
    $this->assertRaw((string) $this->callT('Disable'));
    $this->assertRaw((string) $this->callT('Remove'));
    // The Place block link on this page has some other hidden text in it. So,
    // only test in English.
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertText('Place block');
    }

    $this->drupalGet('admin/structure/block/library/bartik');
    $this->assertRaw((string) $this->callT('Place block'));
    $this->assertRaw((string) $this->callT('User login'));

    $this->drupalGet('admin/structure/block/block-content');
    $this->assertRaw((string) $this->callT('Edit'));
  }

  /**
   * Makes screenshots for the Views chapter.
   */
  protected function doViews() {
    $this->verifyTranslations();

    $vendor = $this->demoInput['vendor_type_machine_name'];
    $recipe = $this->demoInput['recipe_type_machine_name'];
    $main_image = $this->demoInput['vendor_field_image_machine_name'];
    $ingredients = $this->demoInput['recipe_field_ingredients_machine_name'];
    $vendors_view = $this->demoInput['vendors_view_machine_name'];
    $recipes_view = $this->demoInput['recipes_view_machine_name'];

    // Topic: views-create: Creating a Content List View.
    // Create a Vendors view.
    $this->drupalGet('<front>');
    $this->clickLink($this->callT('Structure'));
    // Here, you would ideally want to click the "Views" link.
    // However, the link text includes a span that says this, plus a div with
    // the description, so using clickLink is not really feasible. So, just
    // assert the text, and visit the URL. These can be problematic in
    // non-English languages...
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertText($this->callT('Views'));
    }
    $this->drupalGet('admin/structure/views');
    $this->clickLink($this->callT('Add view'));
    $this->assertText($this->callT('View name'));
    $this->assertText($this->callT('Show'));
    $this->assertRaw((string) $this->callT('Content'));
    $this->assertText($this->callT('of type'));
    $this->assertText($this->callT('sorted by'));
    $this->assertText($this->callT('Create a page'));
    $this->waitForInteraction('css', '#edit-page-create');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertText($this->callT('Page title'));
    $this->assertText($this->callT('Path'));
    $this->assertText($this->callT('Display format'));
    $this->assertRaw((string) $this->callT('Table'));
    $this->assertText($this->callT('Items to display'));
    $this->assertText($this->callT('Use a pager'));
    $this->assertText($this->callT('Create a menu link'));
    $this->waitForInteraction('css', '#edit-page-link');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertText($this->callT('Menu'));
    $this->assertRaw((string) $this->callT('Main navigation'));
    $this->assertText($this->callT('Link text'));

    // Add view wizard.
    $this->makeScreenShot('views-create-wizard.png', 'jQuery(\'#edit-label\').val("' . $this->demoInput['vendors_view_title'] . '"); jQuery(\'#edit-label-machine-name-suffix\').show(); jQuery(\'#edit-label\').trigger(\'formUpdated.machineName\'); jQuery(\'.machine-name-value\').html(\'' . $this->demoInput['vendors_view_machine_name'] . '\').parent().show(); jQuery(\'#edit-show-type\').val(\'' . $vendor . '\'); jQuery(\'#edit-show-sort\').val(\'node_field_data-title:ASC\'); jQuery(\'#edit-page-create\').attr(\'checked\', \'checked\'); jQuery(\'#edit-page--2\').show(); jQuery(\'#edit-page-title\').val("' . $this->demoInput['vendors_view_title'] . '"); jQuery(\'#edit-page-path\').val(\'' . $this->demoInput['vendors_view_path'] . '\'); jQuery(\'.form-item-page-style-style-plugin select\').val(\'table\'); jQuery(\'#edit-page-link\').attr(\'checked\', \'checked\'); jQuery(\'.form-item-page-link-properties-menu-name select\').val(\'main\');  jQuery(\'.form-item-page-link-properties-title select\').val("' . $this->demoInput['vendors_view_title'] . '"); window.scroll(0,0);' . $this->hideArea('#toolbar-administration, .messages') . $this->removeScrollbars(), '', TRUE);

    // Refresh page and submit. Note that several things have to be selected
    // or checked outside of the form submit, as they trigger Ajax events.
    $this->drupalGet('admin/structure/views/add');
    $this->getSession()->getPage()
      ->find('css', '.form-item-show-type select')
      ->selectOption($vendor);
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->getSession()->getPage()
      ->find('css', '.form-item-show-sort select')
      ->selectOption('node_field_data-title:ASC');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->waitForInteraction('css', '#edit-page-create');
    $this->getSession()->getPage()
      ->find('css', '.form-item-page-style-style-plugin select')
      ->selectOption('table');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->waitForInteraction('css', '#edit-page-link');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->openMachineNameEdit('#edit-label');
    $this->drupalPostForm(NULL, [
        'label' => $this->demoInput['vendors_view_title'],
        'id' => $vendors_view,
        'page[create]' => TRUE,
        'page[title]' => $this->demoInput['vendors_view_title'],
        'page[path]' => $this->demoInput['vendors_view_path'],
        'page[link]' => TRUE,
        'page[link_properties][menu_name]' => 'main',
        'page[link_properties][title]' => $this->demoInput['vendors_view_title'],
      ], $this->callT('Save and edit'));
    // The next statements add parts to the view, using no-JS options for the
    // test.
    // Add the main image field.
    $this->clickLinkContainingUrl('add-handler');
    $this->drupalPostForm(NULL, [
        'name[node__field_' . $main_image . '.field_' . $main_image . ']' => 'node__field_' . $main_image . '.field_' . $main_image,
      ], $this->callT('Add and configure @types', TRUE, ['@types' => $this->callT('fields')]));
    $this->assertText($this->callT('Create a label'));
    $this->assertText($this->callT('Image style'));
    $this->assertText($this->callT('Link image to'));
    $this->assertRaw((string) $this->callT('Content'));
    $this->drupalPostForm(NULL, [
        'options[custom_label]' => FALSE,
        'options[settings][image_style]' => 'medium',
        'options[settings][image_link]' => 'content',
      ], $this->callT('Apply'));

    // Add the body field.
    $this->clickLinkContainingUrl('add-handler');
    $this->drupalPostForm(NULL, [
        'name[node__body.body]' => 'node__body.body',
      ], $this->callT('Add and configure @types', TRUE, ['@types' => $this->callT('fields')]));
    $this->assertText($this->callT('Create a label'));
    $this->assertText($this->callT('Formatter'));
    $this->assertRaw((string) $this->callT('Summary or trimmed'));
    $this->drupalPostForm(NULL, [
        'options[custom_label]' => FALSE,
        'options[type]' => 'text_summary_or_trimmed',
      ], $this->callT('Apply'));

    // Fix the configuration for the Title field: remove the label.
    $this->clickLinkContainingUrl('field/title');
    $this->assertText($this->callT('Create a label'));
    $this->drupalPostForm(NULL, [
        'options[custom_label]' => FALSE,
      ], $this->callT('Apply'));

    // Fix the configuration for the Body field: change the trim length.
    $this->clickLinkContainingUrl('field/body');
    $this->drupalPostForm(NULL, [
        'options[settings][trim_length]' => 120,
      ], $this->callT('Apply'));

    // Reorder the fields.
    $this->clickLinkContainingUrl('rearrange');
    // Show weight fields.
    $this->getSession()->getDriver()->executeScript("jQuery('.tabledrag-hide').show();");
    $this->drupalPostForm(NULL, [
        'fields[title][weight]' => 3,
        'fields[body][weight]' => 4,
      ], $this->callT('Apply'));

    // Fix the menu weight.
    $this->clickLinkContainingUrl('menu');
    $this->drupalPostForm(NULL, [
        'menu[weight]' => 20,
      ], $this->callT('Apply'));
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertRaw((string) $this->callT('Update preview'));
    }

    // Save the view.
    $this->drupalPostForm(NULL, [], $this->callT('Save'));

    // Completed vendors view administration page.
    $this->makeScreenShot('views-create-view.png', $this->hideArea('#toolbar-administration, #views-preview-wrapper, .messages') . $this->removeScrollbars());
    // View the output.
    $this->drupalGet($this->demoInput['vendors_view_path']);
    // Completed vendors view output.
    $this->makeScreenShot('views-create-view-output.png', $this->hideArea('#toolbar-administration, .site-footer') . $this->removeScrollbars() . $this->setBodyColor());


    // Topic: views-duplicate - Duplicating a View.
    // Duplicate the Vendors view.
    $this->drupalGet('admin/structure/views');
    $this->assertRaw((string) $this->callT('Duplicate'));

    // Views page (admin/structure/views), with operations dropdown
    // for Vendor view open.
    $this->makeScreenShot('views-duplicate_duplicate.png', 'jQuery("a[href*=\'views/view/' . $vendors_view . '\']").parents(\'.dropbutton-wrapper\').addClass(\'open\'); ' . $this->hideArea('#toolbar-administration, .disabled') . 'jQuery("a[href*=\'views/view/content\'], a[href*=\'views/view/block_content\'], a[href*=\'views/view/files\'], a[href*=\'views/view/frontpage\'], a[href*=\'views/view/user_admin_people\'], a[href*=\'views/view/comments_recent\']").parents(\'tr\').hide();' . $this->removeScrollbars());

    // Start over after screenshot.
    $this->drupalGet('admin/structure/views');
    $this->clickLinkContainingUrl('views/view/' . $vendors_view . '/duplicate');
    $this->openMachineNameEdit('#edit-label');
    $this->drupalPostForm(NULL, [
        'label' => $this->demoInput['recipes_view_title'],
        'id' => $recipes_view,
      ], $this->callT('Duplicate'));

    // Modify various aspects of the view, and make screenshots of some of
    // the configuration forms.

    // Page title.
    $this->assertText($this->callT('Title'));
    $this->clickLinkContainingUrl('page_1/title');
    $this->assertRaw((string) $this->callT('The title of this view'));
    $this->drupalPostForm(NULL, [
        'title' => $this->demoInput['recipes_view_title'],
      ], $this->callT('Apply'));

    $this->clickLinkContainingUrl('page_1/title');
    // View title configuration screen.
    $this->makeScreenShot('views-duplicate_title.png', $this->hideArea('#toolbar-administration, .content-header, .breadcrumb') . $this->setWidth('layout-container'));
    $this->drupalPostForm(NULL, [], $this->callT('Apply'));

    // Grid style.
    $this->assertText($this->callT('Format'));
    $this->clickLinkContainingUrl('page_1/style');
    $this->assertRaw((string) $this->callT('How should this view be styled'));
    $this->assertRaw((string) $this->callT('Grid'));
    $this->drupalPostForm(NULL, [
        'style[type]' => 'grid',
      ], $this->callT('Apply'));
    $this->assertRaw((string) $this->callT('Style options'));
    $this->drupalPostForm(NULL, [], $this->callT('Apply'));

    // Remove body field.
    $this->clickLinkContainingUrl('page_1/field/body');
    $this->drupalPostForm(NULL, [], $this->callT('Remove'));

    // Filter on Recipe content type.
    $this->assertRaw((string) $this->callT('Filter criteria'));
    $this->clickLinkContainingUrl('page_1/filter/type');
    $this->assertRaw((string) $this->callT('filter criterion'));
    $this->drupalPostForm(NULL, [
        'options[value][' . $vendor . ']' => FALSE,
        'options[value][' . $recipe . ']' => $recipe,
      ], $this->callT('Apply'));

    // Add exposed filter for Ingredients.
    $this->clickLinkContainingUrl('add-handler/' . $recipes_view . '/page_1/filter');
    $this->drupalPostForm(NULL, [
        'name[node__field_' . $ingredients . '.field_' . $ingredients . '_target_id]' => 'node__field_' . $ingredients . '.field_' . $ingredients . '_target_id',
      ], $this->callT('Add and configure @types', TRUE, ['@types' => $this->callT('filter criteria')]));
    $this->drupalPostForm(NULL, [], $this->callT('Apply'));
    $this->assertText($this->callT('Expose this filter to visitors, to allow them to change it'));

    $this->drupalPostForm(NULL, [
        'options[expose_button][checkbox][checkbox]' => 1,
      ], $this->callT('Expose filter'));
    $this->assertText($this->callT('Required'));
    $this->assertText($this->callT('Label'));
    $this->drupalPostForm(NULL, [
        'options[expose][label]' => $this->demoInput['recipes_view_ingredients_label'],
      ], $this->callT('Apply'));
    $this->clickLinkContainingUrl('/page_1/filter/field_');
    // Ingredients field exposed filter configuration.
    $this->makeScreenShot('views-duplicate_expose.png', $this->hideArea('#toolbar-administration, .content-header, .breadcrumb, .exposed-description, #edit-options-expose-button-button, .grouped-description, #edit-options-group-button-button, #edit-options-operator--wrapper, .form-item-options-value, .form-item-options-expose-use-operator,  .form-item-options-expose-operator-id,  .form-item-options-expose-multiple,  .form-item-options-expose-remember, #edit-options-expose-remember-roles--wrapper,  .form-item-options-expose-identifier,  .form-item-options-error-message,  .form-item-options-reduce-duplicates, #edit-options-admin-label, #edit-actions') . $this->setWidth('layout-container', 800) . $this->removeScrollbars(), "jQuery('#edit-actions').show();");
    $this->drupalPostForm(NULL, [], $this->callT('Apply'));

    // Path and menu link title.
    $this->assertText($this->callT('Page settings'));
    $this->clickLinkContainingUrl('page_1/path');
    $this->drupalPostForm(NULL, [
        'path' => $this->demoInput['recipes_view_path'],
      ], $this->callT('Apply'));
    $this->clickLinkContainingUrl('page_1/menu');
    $this->drupalPostForm(NULL, [
        'menu[title]' => $this->demoInput['recipes_view_title'],
      ], $this->callT('Apply'));

    // Use Ajax.
    $this->assertRaw((string) $this->callT('Advanced'));
    // Open up the Advanced section.
    $this->waitForInteraction('css', '.third summary');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertText($this->callT('Other'));
    $this->assertText($this->callT('Use AJAX'));
    $this->clickLinkContainingUrl('page_1/use_ajax');
    $this->drupalPostForm(NULL, [
        'use_ajax' => 1,
      ], $this->callT('Apply'));

    // Save the view and view the output.
    $this->drupalPostForm(NULL, [], $this->callT('Save'));
    $this->drupalGet($this->demoInput['recipes_view_path']);
    // Completed recipes view output.
    $this->makeScreenShot('views-duplicate_final.png', $this->hideArea('#toolbar-administration, .site-footer') . $this->removeScrollbars() . $this->setBodyColor());

    // Topic: views-block - Adding a Block Display to a View.
    // Add a block to the Recipes view.
    $this->drupalGet('admin/structure/views/view/' . $recipes_view);
    // Click Add and wait for it to finish.
    $this->waitForInteraction('css', '#views-display-menu-tabs li.add a');
    $this->assertSession()->assertWaitOnAjaxRequest();
    // Add display button on Recipes view edit page, with Block highlighted
    // (admin/structure/views/view/recipes).
    $this->makeScreenShot('views-block_add-block.png', $this->hideArea('#toolbar-administration, .content-header, .region-breadcrumb, .region-highlighted, #views-display-extra-actions, #edit-display-settings, #edit-actions, .views-preview-wrapper, #views-preview-wrapper, .dropbutton-wrapper, .messages') . $this->setWidth('.region-content') . $this->removeScrollbars());

    $this->drupalGet('admin/structure/views/view/' . $recipes_view);
    // Click the Add > Block button.
    $this->waitForInteraction('css', '#views-display-menu-tabs li.add a');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->waitForInteraction('css', '#edit-displays-top-add-display-block');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Update various settings for the block display.

    // Display title.
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertRaw((string) $this->callT('Display name'));
    }
    $this->clickLinkContainingUrl('block_1/display_title');
    $this->assertRaw((string) $this->callT('The name and the description of this display'));
    $this->assertRaw((string) $this->callT('Administrative name'));
    $this->drupalPostForm(NULL, [
        'display_title' => $this->demoInput['recipes_view_block_display_name'],
      ], $this->callT('Apply'));

    // Block title.
    $this->clickLinkContainingUrl('block_1/title');
    // Configuring the block title for this display only.
    $this->makeScreenShot('views-block_title.png', $this->hideArea('#toolbar-administration, .region-breadcrumbs, .region-highlighted') . 'jQuery(\'#edit-override-dropdown\').val(\'block_1\'); jQuery(\'#edit-title\').val("' . $this->demoInput['recipes_view_block_title'] . '");' . $this->setWidth('.content-header, .layout-container'));
    $this->assertRaw((string) $this->callT('This @display_type (override)', TRUE, ['@display_type' => 'block']));

    $this->drupalPostForm(NULL, [
        'override[dropdown]' => 'block_1',
        'title' => $this->demoInput['recipes_view_block_title'],
      ], $this->callT('Apply'));

    // Style - unformatted list.
    $this->clickLinkContainingUrl('block_1/style');
    $this->assertText($this->callT('Unformatted list'));
    $this->drupalPostForm(NULL, [
        'override[dropdown]' => 'block_1',
        'style[type]' => 'default',
      ], $this->callT('Apply'));
    $this->drupalPostForm(NULL, [], $this->callT('Apply'));

    // Image field.
    $this->clickLinkContainingUrl('block_1/field/field_' . $main_image);
    // Configuring the image field for this display only.
    $this->makeScreenShot('views-block_image.png', $this->hideArea('#toolbar-administration, .region_breadcrumbs, .region-highlighted') . 'jQuery(\'#edit-override-dropdown\').val(\'block_1\'); jQuery(\'#edit-options-settings-image-style\').val(\'thumbnail\');' . $this->addBorder('#edit-override-dropdown, #edit-options-settings-image-style') . $this->setWidth('.content-header, .layout-container') . $this->removeScrollbars());
    $this->drupalPostForm(NULL, [
        'override[dropdown]' => 'block_1',
        'options[settings][image_style]' => 'thumbnail',
      ], $this->callT('Apply'));


    // Remove ingredients filter.
    $this->clickLinkContainingUrl('block_1/filter/field_');
    $this->drupalPostForm(NULL, [
        'override[dropdown]' => 'block_1',
      ], $this->callT('Remove'));

    // Add sort by authored date.
    $this->assertRaw((string) $this->callT('Sort criteria'));
    $this->clickLinkContainingUrl('add-handler/' . $recipes_view . '/block_1/sort');
    $this->assertRaw((string) $this->callT('Authored on'));
    $this->drupalPostForm(NULL, [
        'override[dropdown]' => 'block_1',
        'name[node_field_data.created]' => 'node_field_data.created',
      ], $this->callT('Add and configure @types', TRUE, ['@types' => $this->callT('sort criteria')]));
    $this->drupalPostForm(NULL, [
        'override[dropdown]' => 'block_1',
        'options[order]' => 'DESC',
      ], $this->callT('Apply'));

    // Instead of pager, display 5 recipes.
    $this->assertText($this->callT('Pager'));
    $this->assertRaw((string) $this->callT('Mini'));
    $this->clickLinkContainingUrl('block_1/pager');
    $this->assertRaw((string) $this->callT('Display a specified number of items'));
    $this->drupalPostForm(NULL, [
        'override[dropdown]' => 'block_1',
        'pager[type]' => 'some',
      ], $this->callT('Apply'));
    $this->assertRaw((string) $this->callT('Pager options'));
    $this->assertText($this->callT('Items to display'));
    $this->drupalPostForm(NULL, [
        'pager_options[items_per_page]' => 5,
      ], $this->callT('Apply'));

    // Save the view.
    $this->drupalPostForm(NULL, [], $this->callT('Save'));
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertText('The view');
      $this->assertText('has been saved.');
    }
    // View saved confirmation message.
    $this->makeScreenShot('views-block_recipes.png', $this->showOnly('.messages--status') . $this->setWidth('.messages', 600) . $this->setBodyColor() . $this->removeScrollbars());

    // Place the block on the sidebar.
    $this->placeBlock('views_block:' . $recipes_view . '-block_1', [
        'region' => 'sidebar_second',
        'theme' => 'bartik',
        'label' => $this->demoInput['recipes_view_block_title'],
      ]);
    $this->drupalGet('<front>');
    // Home page with recipes sidebar visible.
    $this->makeScreenShot('views-block_sidebar.png', $this->hideArea('#toolbar-administration, footer') . $this->removeScrollbars());

  }

  /**
   * Makes screenshots for the Multilingual chapter, first topic only.
   *
   * The rest of the chapter is in the doTranslating() method. It was split
   * because the first topic is very time-consuming due to needing to
   * import the translations.
   */
  protected function doMultilingualSetup() {
    $this->verifyTranslations();

    // Topic: language-add - Adding a Language.

    // Enable the 4 multilingual modules.
    // For non-English versions, locale and language will already be enabled;
    // for English, not yet. In both cases, we need config/content translation
    // though.
    $this->drupalGet('admin/modules');
    // Note that module names are not translated.
    $this->assertText('Language');
    $this->assertText('Interface Translation');
    $this->assertText('Content Translation');
    $this->assertText('Configuration Translation');

    $values = [
      'modules[content_translation][enable]' => TRUE,
    ];
    if ($this->demoInput['first_langcode'] == 'en') {
      // In other languages, these other three modules are already enabled.
      $values += [
        'modules[language][enable]' => TRUE,
        'modules[locale][enable]' => TRUE,
        'modules[config_translation][enable]' => TRUE,
      ];
    }
    $this->drupalPostForm(NULL, $values, $this->callT('Install'));

    // Due to a core bug, installing a module corrupts translations. So,
    // import the saved translations.
    $this->importTranslations($this->demoInput['first_langcode']);
    $this->verifyTranslations();

    // Add the second language.
    $this->drupalGet('<front>');
    $this->clickLink($this->callT('Configuration'));
    $this->assertText($this->callT('Regional and language'));
    // Here, you would ideally want to click the "Languages" link.
    // However, the link text includes a span that says this, plus a div with
    // the description, so using clickLink is not really feasible. So, just
    // assert the text, and visit the URL. These can be problematic in
    // non-English languages...
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertText($this->callT('Languages'));
      // Also test the navigation text for the next topics.
      $this->assertText($this->callT('Content language and translation'));
    }
    $this->fixTranslationSettings();
    $this->drupalGet('admin/config/regional/language');
    $this->clickLink($this->callT('Add language'));
    $this->assertText($this->callT('Language name'));
    $this->drupalPostForm(NULL, [
        'predefined_langcode' => $this->demoInput['second_langcode'],
      ], $this->callT('Add language'));
    // Confirmation and language list after adding second language.
    $this->makeScreenShot('language-add-list.png', $this->hideArea('#toolbar-administration,  .tabledrag-hide, .tabledrag-toggle-weight') . 'jQuery(".tabledrag-handle").show();' . $this->removeScrollbars());
    $this->importTranslations($this->demoInput['second_langcode']);
    $this->verifyTranslations();
    $this->verifyTranslations(FALSE);

    // Place the Language Switcher block in sidebar second (no screenshots).
    $this->drupalGet('admin/structure/block/library/bartik');
    $this->assertRaw((string) $this->callT('Language switcher'));
    $this->placeBlock('language_block:language_interface', [
        'region' => 'sidebar_second',
        'theme' => 'bartik',
        'label' => $this->callT('Language'),
      ]);
  }

  /**
   * Makes screenshots for the Multilingual chapter, except first topic.
   *
   * The first topic is in the doMultilingualSetup() method.
   */
  protected function doTranslating() {
    $this->verifyTranslations();
    $this->verifyTranslations(FALSE);

    $recipes_view = $this->demoInput['recipes_view_machine_name'];
    $ingredients = $this->demoInput['recipe_field_ingredients_machine_name'];
    $ingredients_hyphens = str_replace('_', '-', $ingredients);

    // Topic: language-content-config - Configuring Content Translation

    // Top section of Content language settings page
    // (admin/config/regional/content-language).
    $this->drupalGet('admin/config/regional/content-language');
    $this->waitForInteraction('css', '#edit-entity-types-node');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->waitForInteraction('css', '#edit-entity-types-block-content');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->waitForInteraction('css', '#edit-entity-types-menu-link-content');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->makeScreenShot('language-content-config_custom.png', $this->showOnly('#edit-entity-types--wrapper') . $this->removeScrollbars());

    // Reset page and start over.
    // Set up content translation for Basic page nodes, Custom blocks, and
    // Custom menu links.
    // Navigation for this page is tested in the language-content-config topic.
    $this->drupalGet('admin/config/regional/content-language');
    $this->assertText($this->callT('Custom language settings'));
    $this->assertText($this->callT('Content'));
    $this->assertText($this->callT('Custom block'));
    $this->assertText($this->callT('Custom menu link'));
    $this->waitForInteraction('css', '#edit-entity-types-node');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->waitForInteraction('css', '#edit-entity-types-block-content');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->waitForInteraction('css', '#edit-entity-types-menu-link-content');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertText($this->callT('Basic page'));
    $this->assertText($this->callT('Basic block'));
    $this->assertText($this->callT('Default language'));
    $this->assertText($this->callT('Show language selector on create and edit pages'));
    $this->waitForInteraction('css', '#edit-settings-node-page-translatable');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->waitForInteraction('css', '#edit-settings-block-content-basic-translatable');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->waitForInteraction('css', '#edit-settings-menu-link-content-menu-link-content-translatable');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertText($this->callT('Title'));
    $this->assertText($this->callT('Authored by'));
    $this->assertText($this->callT('Published'));
    $this->assertText($this->callT('Authored on'));
    $this->assertText($this->callT('Changed'));
    $this->assertText($this->callT('Body'));
    if ($this->demoInput['first_langcode'] == 'en') {
      // These strings had trouble in French due to accents and apostrophes.
      $this->assertText($this->callT('Promoted to front page'));
      $this->assertText($this->callT('Sticky at top of lists'));
      $this->assertText($this->callT('URL alias'));
    }

    $this->drupalPostForm(NULL, [
        'entity_types[node]' => 'node',
        'settings[node][page][translatable]' => TRUE,
        'settings[node][page][settings][language][language_alterable]' => TRUE,
        'settings[node][page][fields][title]' => TRUE,
        'settings[node][page][fields][uid]' => FALSE,
        'settings[node][page][fields][status]' => TRUE,
        'settings[node][page][fields][created]' => FALSE,
        'settings[node][page][fields][changed]' => FALSE,
        'settings[node][page][fields][promote]' => FALSE,
        'settings[node][page][fields][sticky]' => FALSE,
        'settings[node][page][fields][path]' => TRUE,
        'settings[node][page][fields][body]' => TRUE,

        'entity_types[block_content]' => 'block_content',
        'settings[block_content][basic][translatable]' => TRUE,
        'settings[block_content][basic][settings][language][language_alterable]' => TRUE,
        'settings[block_content][basic][fields][info]' => TRUE,
        'settings[block_content][basic][fields][changed]' => FALSE,
        'settings[block_content][basic][fields][body]' => TRUE,

        'entity_types[menu_link_content]' => 'menu_link_content',
        'settings[menu_link_content][menu_link_content][translatable]' => TRUE,
        'settings[menu_link_content][menu_link_content][settings][language][language_alterable]' => TRUE,
        'settings[menu_link_content][menu_link_content][translatable]' => TRUE,
        'settings[menu_link_content][menu_link_content][fields][title]' => TRUE,
        'settings[menu_link_content][menu_link_content][fields][description]' => TRUE,
        'settings[menu_link_content][menu_link_content][fields][changed]' => FALSE,
      ], $this->callT('Save configuration'));
    // Main settings area for Custom Block translations.
    $this->makeScreenShot('language-content-config_content.png', $this->showOnly('#edit-settings-block-content tr.bundle-settings') . $this->setWidth('#edit-settings-block-content', 600) . 'jQuery(\'tr\').css(\'border-bottom\', \'none\');' . $this->removeScrollbars());
    // Field settings area for Basic page translations.
    $this->makeScreenShot('language-content-config_basic_page.png', $this->hideArea('*') . 'jQuery(\'#edit-settings-node tr.field-settings\').has(\'input[name*="settings[node][page]"]\').show().parents().show(); jQuery(\'#edit-settings-node tr.field-settings\').has(\'input[name*="settings[node][page]"]\').find(\'*\').show();'  . $this->setWidth('#edit-settings-node', 400) . $this->setWidth('.language-content-settings-form .field', 350) . $this->setWidth('.language-content-settings-form .operations', 0) . $this->removeScrollbars());

    // Topic: language-content-translate - Translating Content.

    // Add a translation of the Home page.
    $this->drupalGet('<front>');
    $this->clickLink($this->callT('Content'));
    $this->assertLink($this->callT('Translate'));
    // It is too complicated at this point to figure out which Translate link
    // to click on, so jump to the node/1 translations page.
    $this->drupalGet('node/1/translations');
    $this->assertLink($this->callT('Add'));
    // Screen shot of the translations page for the Home page content item.
    $this->makeScreenShot('language-content-translate-add.png', $this->hideArea('#toolbar-administration'));

    // The UI is in Spanish if you use the link, and the instructions in the
    // User guide say to alter the URL... so go ahead and get the right page.
    $this->drupalGet('node/1/translations/add/' . $this->demoInput['first_langcode'] . '/' . $this->demoInput['second_langcode']);
    $this->assertText($this->callT('Title'));
    $this->assertText($this->callT('Body'));
    $this->assertText($this->callT('URL alias'));

    $this->fillInBody($this->demoInput['home_body_translated']);
    $this->drupalPostForm(NULL, [
        'title[0][value]' => $this->demoInput['home_title_translated'],
        'path[0][alias]' => $this->demoInput['home_path_translated'],
        // This looks strange, but that is how the button text is translated.
      ], $this->callT('Save') . ' ' . $this->callT('(this translation)'));

    // Topic: language-config-translate - Translating Configuration.

    // Translate the Recipes view.
    // First test the navigation.
    $this->drupalGet('admin/structure/views');
    $this->assertLink($this->callT('Translate'));
    $this->drupalGet('admin/structure/views/view/' . $recipes_view . '/translate');
    $this->clickLink($this->callT('Add'));

    // Now jump to the actual page we want.
    $this->drupalGet('admin/structure/views/view/' . $recipes_view . '/translate/' . $this->demoInput['second_langcode'] . '/add');
    $this->assertText($this->callT('Displays'));
    if ($this->demoInput['first_langcode'] == 'en') {
      // String had trouble in French due to accents/quotes.
      $this->assertText('Display settings');
    }

    // Open up a bunch of the fieldsets.
    $this->waitForInteraction('css', '#edit-default summary');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->waitForInteraction('css', '#edit-display-options summary');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->waitForInteraction('css', '#edit-exposed-form summary');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->waitForInteraction('css', '#edit-options summary');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->waitForInteraction('css', '#edit-filters summary');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->waitForInteraction('css', '#edit-field-' . $ingredients_hyphens . '-target-id summary');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->waitForInteraction('css', '#edit-expose--3 summary');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->waitForInteraction('css', '#edit-block-1 summary');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->waitForInteraction('css', '#edit-page-1 summary');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertText($this->callT('Display title'));
    $this->assertText($this->callT('Exposed form'));
    $this->assertText($this->callT('Reset'));
    $this->assertText($this->callT('Options'));
    $this->assertText($this->callT('Submit button text'));
    $this->assertText($this->callT('Apply'));
    $this->assertText($this->callT('Filters'));
    $this->assertText($this->callT('Expose'));
    $this->assertText($this->callT('Label'));

    $this->scrollWindowUp();
    // Exposed form options for Recipes view.
    $this->makeScreenShot('language-config-translate-recipes-view.png', $this->hideArea('#toolbar-administration') . $this->removeScrollbars(), "jQuery('body').css('overflow', 'scroll');");
    $this->drupalPostForm(NULL, [
        'translation[config_names][views.view.' . $recipes_view . '][display][default][display_options][title]' => $this->demoInput['recipes_view_title_translated'],
        'translation[config_names][views.view.' . $recipes_view . '][display][default][display_options][exposed_form][options][submit_button]' => $this->demoInput['recipes_view_submit_button_translated'],
        'translation[config_names][views.view.' . $recipes_view . '][display][default][display_options][filters][field_' . $ingredients . '_target_id][expose][label]' => $this->demoInput['recipes_view_ingredients_label_translated'],
      ], $this->callT('Save translation'));

  }

  /**
   * Makes screenshots for the Extending chapter.
   */
  protected function doExtending() {
    $this->verifyTranslations();
    $this->verifyTranslations(FALSE);

    $vendors_view = $this->demoInput['vendors_view_machine_name'];

    // Topic: extend-maintenance: Enabling and Disabling Maintenance Mode.
    $this->drupalGet('<front>');
    $this->clickLink($this->callT('Configuration'));
    $this->assertText($this->callT('Development'));
    // Here, you would ideally want to click the "Maintenance mode" link.
    // However, the link text includes a span that says this, plus a div with
    // the description, so using clickLink is not really feasible. So, just
    // assert the text, and visit the URL. These can be problematic in
    // non-English languages...
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertText($this->callT('Maintenance mode'));
    }

    $this->drupalGet('admin/config/development/maintenance');
    $this->assertText($this->callT('Put site into maintenance mode'));
    $this->assertText($this->callT('Message to display when in maintenance mode'));

    $this->drupalPostForm(NULL, [
        'maintenance_mode' => 1,
      ], $this->callT('Save configuration'));
    $this->clearCache();
    $this->drupalLogout();
    $this->drupalGet('<front>');
    // Site in maintenance mode.
    $this->makeScreenShot('extend-maintenance-mode-enabled.png', "document.documentElement.style.overflow = 'hidden';");
    $this->drupalLogin($this->rootUser);
    $this->drupalPostForm('admin/config/development/maintenance', [
        'maintenance_mode' => FALSE,
      ], $this->callT('Save configuration'));
    $this->clearCache();
    $this->drupalLogout();
    $this->drupalGet('<front>');
    // Site no longer in maintenance mode.
    $this->makeScreenShot('extend-maintenance-mode-disabled.png', $this->removeScrollbars());
    $this->drupalLogin($this->rootUser);

    // Topic: extend-module-find - Finding Modules. Manual screenshots only.

    // Topic: extend-module-install - Downloading and Installing a Module from
    // drupal.org.

    // Test navigation to install page.
    $this->drupalGet('<front>');
    $this->clickLink($this->callT('Extend'));
    $this->clickLink($this->callT('Install new module'));
    $this->assertText($this->callT('Install from a URL'));
    $this->assertRaw((string) $this->callT('Install'));

    // Install new module page (admin/modules/install).
    $this->makeScreenShot('extend-module-install-admin-toolbar-do.png', $this->hideArea('#toolbar-administration') . $this->setWidth('.content-header, .layout-container', 600));

    // Topic: extend-theme-find - Finding Themes. Manual screenshots only.

    // Topic: extend-theme-install - Downloading and Installing a Theme from
    // drupal.org.

    // Test navigation to install page.
    $this->drupalGet('<front>');
    $this->clickLink($this->callT('Appearance'));
    $this->clickLink($this->callT('Install new theme'));
    $this->assertText($this->callT('Install from a URL'));
    $this->assertRaw((string) $this->callT('Install'));

    $this->drupalGet('admin/theme/install');
    // Install new theme page (admin/theme/install).
    $this->makeScreenShot('extend-theme-install-page.png', $this->hideArea('#toolbar-administration') . $this->setWidth('.content-header, .layout-container', 600));

    $this->drupalGet('admin/appearance');
    // The text 'Uninstalled themes' is translated through a formatPlural call,
    // so only test in English.
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertText('Uninstalled themes');
    }
    $this->assertLink($this->callT('Install and set as default'));

    // Mayo theme on the Appearance page.
    $this->makeScreenShot('extend-theme-install-appearance-page.png', 'window.scroll(0,6000);' . $this->showOnly('.system-themes-list-uninstalled .theme-selector:contains("Mayo")') . 'jQuery(\'.system-themes-list-uninstalled\').css(\'border\', \'none\');');

    // Topic: extend-manual-install - Manually Downloading Module or Theme
    // Files. Manual screenshots only.

    // Topic: extend-deploy - Deploying New Site Features.

    // Test navigation.
    $this->drupalGet('<front>');
    $this->clickLink($this->callT('Configuration'));
    $this->assertText($this->callT('Development'));
    // Here, you would ideally want to click the "Configuration
    // synchronization" link. However, the link text includes a span that says
    // this, plus a div with the description, so using clickLink is not really
    // feasible. So, just assert the text, and visit the URL. These can be
    // problematic in non-English languages...
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertText($this->callT('Configuration synchronization'));
    }
    $this->drupalGet('admin/config/development/configuration');
    $this->clickLink($this->callT('Export'));
    $this->assertText($this->callT('Full archive'));
    $this->clickLink($this->callT('Single item'));
    $this->assertText($this->callT('Configuration type'));
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertText('View');
    }
    $this->drupalGet('admin/config/development/configuration');
    $this->clickLink($this->callT('Import'));
    $this->assertText($this->callT('Full archive'));
    $this->clickLink($this->callT('Single item'));
    $this->assertText($this->callT('Configuration type'));
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertText('View');
    }

    // Export the Vendors view configuration. In the UI, you can get the
    // export via Ajax, but Ajax post did not work in the test. Luckily,
    // it also has a direct URL.
    $this->drupalGet('admin/config/development/configuration/single/export/view/' . $vendors_view);
    // Single configuration export of the Vendors view from
    // admin/config/development/configuration/single/export.
    $this->makeScreenShot('extend-deploy-export-single.png', $this->hideArea('#toolbar-administration, .breadcrumb ol li:gt(4)') . $this->setWidth('.content-header, .layout-container', 600) . $this->removeScrollbars());

  }

  /**
   * Makes screenshots for the Preventing and Fixing Problems chapter.
   */
  protected function doPreventing() {
    $this->verifyTranslations();
    $this->verifyTranslations(FALSE);

    // Topic: prevent-cache-clear - Clearing the cache.
    // No screenshots, just UI text tests.
    $this->drupalGet('<front>');
    $this->clickLink($this->callT('Configuration'));
    $this->assertText($this->callT('Development'));
    // Here, you would ideally want to click the "Performance" link.
    // However, the link text includes a span that says this, plus a div with
    // the description, so using clickLink is not really feasible. So, just
    // assert the text, and visit the URL. These can be problematic in
    // non-English languages...
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertText($this->callT('Performance'));
    }
    $this->drupalGet('admin/config/development/performance');
    $this->assertRaw((string) $this->callT('Clear all caches'));

    // Topic: prevent-log - Concept: Log.
    // Test navigation for this and the next few topics.
    $this->drupalGet('<front>');
    $this->clickLink($this->callT('Reports'));
    // Here, you would ideally want to click the "Recent log messages" link.
    // However, the link text includes a span that says this, plus a div with
    // the description, so using clickLink is not really feasible. So, just
    // assert the text, and visit the URL. These can be problematic in
    // non-English languages...
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertText($this->callT('Recent log messages'));
      $this->assertText($this->callT('Status report'));
    }

    $this->drupalGet('admin/reports/dblog');
    // Recent log messages report (admin/reports/dblog).
    $this->makeScreenShot('prevent-log.png', $this->hideArea('#toolbar-administration') . $this->removeScrollbars());

    // Topic: prevent-status - Concept: Status Report.

    $this->drupalGet('admin/reports/status');
    // Status report (admin/reports/status).
    $this->makeScreenShot('prevent-status.png', $this->hideArea('#toolbar-administration') . $this->removeScrollbars());
  }

  /**
   * Makes screenshots for the Security chapter.
   */
  protected function doSecurity() {
    $this->verifyTranslations();
    $this->verifyTranslations(FALSE);
    $this->fixTranslationSettings();

    // Topic: security-cron - Configuring Cron Maintenance Tasks.

    // Test navigation.
    $this->drupalGet('<front>');
    $this->clickLink($this->callT('Configuration'));
    $this->assertText($this->callT('System'));
    // Here, you would ideally want to click the "Cron" link.
    // However, the link text includes a span that says this, plus a div with
    // the description, so using clickLink is not really feasible. So, just
    // assert the text, and visit the URL. These can be problematic in
    // non-English languages...
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertText($this->callT('Cron'));
    }
    $this->drupalGet('admin/config/system/cron');
    $this->assertText($this->callT('Cron settings'));
    $this->assertRaw((string) $this->callT('Save configuration'));

    // Cron configuration page (admin/config/system/cron).
    $this->makeScreenShot('security-cron.png', $this->hideArea('#toolbar-administration') . $this->setWidth('.content-header, .layout-container', 600) . $this->removeScrollbars() . $this->replaceUrl() . 'window.scroll(0,0)');

    // Topic: security-update-module - Updating a Module.

    // Install an old version of the Admin Toolbar module, and visit the
    // Updates page.
    $this->drupalPostForm('admin/modules', [
        'modules[admin_toolbar][enable]' => TRUE,
      ], $this->callT('Install'));
    update_storage_clear();

    // Due to a core bug, installing a module corrupts translations. So,
    // import translations again.
    $this->importTranslations($this->demoInput['first_langcode']);
    $this->importTranslations($this->demoInput['second_langcode']);
    $this->verifyTranslations();
    $this->verifyTranslations(FALSE);

    $this->drupalGet('<front>');
    $this->clickLink($this->callT('Reports'));
    // Here, you would ideally want to click the "Available updates" link.
    // However, the link text includes a span that says this, plus a div with
    // the description, so using clickLink is not really feasible. So, just
    // assert the text, and visit the URL. These can be problematic in
    // non-English languages...
    if ($this->demoInput['first_langcode'] == 'en') {
      $this->assertText($this->callT('Available updates'));
    }
    $this->drupalGet('admin/reports/updates');
    // This link text is in an earlier topic on security notifications.
    $this->assertLink($this->callT('Settings'));
    $this->clickLink($this->callT('Update'));
    $this->assertRaw((string) $this->callT('Download these updates'));

    $this->drupalGet('admin/reports/updates/update');
    // Update page for module (admin/reports/updates/update).
    $this->makeScreenShot('security-update-module-updates.png', $this->hideArea('#toolbar-administration') . $this->setWidth('.content-header, .layout-container', 800) . $this->removeScrollbars());
    // Uninstall the module.
    $this->drupalPostForm('admin/modules/uninstall', [
        'uninstall[admin_toolbar]' => 1,
      ], $this->callT('Uninstall'));
    $this->drupalPostForm(NULL, [], $this->callT('Uninstall'));

    // Topic: security-update-theme - Updating a Theme.

    // Install an old version of the Mayo theme, and visit the Updates page.
    $this->drupalGet('admin/appearance');
    $this->clickLinkContainingUrl('theme=mayo');
    update_storage_clear();

    $this->drupalGet('admin/reports/updates/update');
    $this->assertRaw('Mayo');
    // Update page for theme (admin/reports/updates/update).
    $this->makeScreenShot('security-update-theme-updates.png', $this->hideArea('#toolbar-administration') . $this->setWidth('.content-header, .layout-container', 800) . $this->removeScrollbars());
    // As this is the last screenshot, do not bother to uninstall the theme.
  }

  /**
   * Clears the Drupal cache using the user interface page.
   */
  protected function clearCache() {
    $this->drupalPostForm('admin/config/development/performance', [], $this->callT('Clear all caches'));
  }

  /**
   * Calls t() in the user interface, with the site's first language.
   *
   * For some unknown reason, when running this in non-English languages, the
   * form submits etc. are not working because it is not looking for the
   * correct (translated) button text when you make a call like
   * @code
   * $this->drupalPostForm('url/here', [], t('Button name'));
   * @endcode
   * So this method wraps t() by passing in the language code to translate
   * to, which is easier than trying to figure out what the real problem is.
   *
   * @param string $text
   *   Text to pass into t(). Must have been defined by another module or it
   *   will not be in the translation database.
   * @param bool $first
   *   (optional) TRUE (default) to translate to the first language in the
   *   demoInput member variable; FALSE to use the second language.
   * @param array $args
   *   (optional) Arguments to substitute in for @vars etc. in the string.
   *
   * @return string
   *   Original string, translated string, or a wrapper object that can be used
   *   like a string.
   */
  protected function callT($text, $first = TRUE, $args = []) {
    if ($first) {
      $langcode = $this->demoInput['first_langcode'];
    }
    else {
      $langcode = $this->demoInput['second_langcode'];
    }

    if ($langcode == 'en') {
      return new FormattableMarkup($text, $args);
    }

    return new TranslatableMarkup($text, $args, ['langcode' => $langcode]);
  }

  /**
   * Makes a screenshot, and adds a note afterwards.
   *
   * The screen shot is of the current page. The image will be cropped down
   * to eliminate whitespace at the edges, so make sure to use the
   * $script_before parameter to white out everything outside the area that
   * you want to be in the screenshot.
   *
   * @param string $file
   *   Name of the screen shot file.
   * @param string $script_before
   *   (optional) JavaScript to execute before the screenshot.
   * @param string $script_after
   *   (optional) JavaScript to execute after the screenshot, to put things
   *   back to usable. If the next statement is a drupalGet(), this is not
   *   necessary.
   * @param bool $wait
   *   (optional) If set to TRUE (default is FALSE), wait after executing the
   *   JavaScript for Ajax to finish.
   *
   * @see ScreenshotTestBase::showOnly()
   * @see ScreenshotTestBase::hideArea()
   * @see ScreenshotTestBase::setWidth()
   * @see ScreenshotTestBase::setBodyColor()
   * @see ScreenshotTestBase::removeScrollbars()
   * @see ScreenshotTestBase::addBorder()
   * @see UserGuideDemoTestBase::replaceUrl()
   */
  protected function makeScreenShot($file, $script_before = '', $script_after = '', $wait = FALSE) {
    if ($script_before) {
      $this->getSession()->executeScript($script_before);
      if ($wait) {
        $this->assertSession()->assertWaitOnAjaxRequest();
      }
    }

    $image = imagecreatefromstring($this->getSession()->getScreenshot());
    if ($this->doCrop) {
      // Crop the image down to remove the whitespace at the edges. This does
      // not work on DrupalCI, but can be used locally.
      $image = imagecropauto($image, IMG_CROP_SIDES);
      $image = imagecropauto($image, IMG_CROP_WHITE);
    }
    $this->logAndSaveImage($image, $file);

    if ($script_after) {
      $this->getSession()->executeScript($script_after);
      if ($wait) {
        $this->assertSession()->assertWaitOnAjaxRequest();
      }
    }
  }

  /**
   * Returns JavaScript code to replace the site URL in the page.
   *
   * Replaces the test environment URL with example.com.
   */
  protected function replaceUrl() {
    $front_url = rtrim(Url::fromRoute('<front>')->setAbsolute()->toString(), '/');
    return "orig = jQuery('body').html(); exp = new RegExp('" . $front_url . "', 'g'); jQuery('body').html(orig.replace(exp, 'https://example.com'));";
  }

  /**
   * Prepares site settings and services before installation.
   *
   * Overrides WebTestBase::prepareSettings() so that we can store public
   * files in a directory that will not get removed until the verbose output
   * is gone.
   */
  protected function prepareSettings() {
    parent::prepareSettings();
    $this->initBrowserOutputFile();

    $this->publicFilesDirectory = 'sites/simpletest/browser_output/' . $this->databasePrefix . '/public_files' ;
    $settings['settings']['file_public_path'] = (object) [
      'value' => $this->publicFilesDirectory,
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
    Settings::initialize(DRUPAL_ROOT, $this->siteDirectory, $this->classLoader);
  }

  /**
   * Finds a link whose link contains the given URL substring, and clicks it.
   */
  protected function clickLinkContainingUrl($url, $index = 0) {
    $urls = $this->xpath('//a[contains(@href, :url)]', [':url' => $url]);
    if (isset($urls[$index])) {
      $url_target = $this->getAbsoluteUrl($urls[$index]->getAttribute('href'));
      $this->drupalGet($url_target);
    }
    else {
      $this->fail('Could not find link matching ' . $url);
    }
  }

  /**
   * Makes a backup of uploads and database, and stores it in a directory.
   *
   * @param string $directory
   *   Directory to store the backup in.
   *
   * @see UserGuideDemoTestBase::restoreBackup()
   */
  protected function makeBackup($directory) {
    drupal_flush_all_caches();
    $this->ensureDirectoryWriteable($directory, 'backup');
    $db_manager = $this->backupDBManager($directory);
    $db_manager->backup('database1', 'directory1');
    $file_manager = $this->backupFileManager($directory);
    $file_manager->backup('public1', 'directory1');
    $this->logTestMessage('BACKUP MADE TO: ' . $directory);
  }

  /**
   * Restores a backup of uploads and database.
   *
   * @param string $directory
   *   Directory the backup was saved in.
   *
   * @see UserGuideDemoTestBase::makeBackup()
   */
  protected function restoreBackup($directory) {
    // The User 1 account created in this session will not match the one in
    // the database we are restoring, so take care of this problem.
    $pass_raw = $this->rootUser->pass_raw;
    $this->drupalLogout();

    // Actually update the database and files.
    $db_manager = $this->backupDBManager($directory);
    $db_manager->restore('database1', 'directory1', 'database.mysql.gz');
    $file_manager = $this->backupFileManager($directory);
    $file_manager->restore('public1', 'directory1', 'public_files.tar.gz');
    $this->logTestMessage('BACKUP RESTORED FROM: ' . $directory);

    // Fix the configuration for temp files directory.
    \Drupal::configFactory()->getEditable('system.file')
      ->set('path.temporary', $this->tempFilesDirectory)
      ->save();
    $this->flushAll();

    // Update the root user, log in, and clear the cache again.
    $this->rootUser = User::load(1);
    // This line is needed for $this->drupalLogin().
    $this->rootUser->pass_raw = $pass_raw;
    // This line is needed for User::save().
    $this->rootUser->pass = $pass_raw;
    $this->rootUser->save();
    $this->drupalLogin($this->rootUser);
    $this->flushAll();
  }

  /**
   * Sets up and returns a database backup manager.
   *
   * @param string $directory
   *   Directory for the backup. The backup file name should be
   *   database.mysql.gz within this directory.
   *
   * @return BackupMigrate
   *   The database backup manager object.
   */
  protected function backupDBManager($directory) {
    // Figure out which tables to exclude: anything lacking the current
    // test prefix. Also do not save data for any table containing 'cache_'.
    $db_info = Database::getConnectionInfo()['default'];
    $prefix = $this->databasePrefix;
    $exclude = [];
    $no_data = [];
    $all_tables = Database::getConnection()->query('SHOW TABLES')->fetchCol();
    foreach ($all_tables as $table) {
      if (strpos($table, $prefix) !== 0) {
        $exclude[] = $table;
      }
      elseif ((strpos($table, 'cache_') !== FALSE)) {
        $no_data[] = $table;
      }
    }

    // Set up the backup manager object.
    $config = new Config([
        'database1' => $db_info,
        'directory1' => [
          'directory' => $directory,
        ],
        'compressor' => [
          'compression' => 'gzip',
        ],
        'namer' => [
          'filename' => 'database',
          'timestamp' => FALSE,
        ],
        'excluder' => [
          'exclude_tables' => $exclude,
          'nodata_tables' => $no_data,
        ],
        'renamer' => [
          'source_prefix' => $prefix,
          'destination_prefix' => 'generic_simpletest_prefix_',
        ],
      ]);

    $manager = new BackupMigrate();
    $manager->services()->add('ArchiveReader', new TarArchiveReader());
    $manager->services()->add('ArchiveWriter', new TarArchiveWriter());
    $manager->services()->add('TempFileAdapter', new TempFileAdapter($this->tempFilesDirectory));
    $manager->services()->add('TempFileManager', new TempFileManager($manager->services()->get('TempFileAdapter')));

    $db_source = new MySQLiSource();
    $manager->services()->addClient($db_source);
    $manager->sources()->add('database1', $db_source);
    $manager->sources()->setConfig($config);

    $dir_dest = new DirectoryDestination();
    $manager->services()->addClient($dir_dest);
    $manager->destinations()->add('directory1', $dir_dest);
    $manager->destinations()->setConfig($config);

    $manager->plugins()->add('excluder', new DBExcludeFilter());
    $manager->plugins()->add('renamer', new DBTableRenameFilter());
    $manager->plugins()->add('compressor', new CompressionFilter());
    $manager->plugins()->add('namer', new FileNamer());
    $manager->plugins()->setConfig($config);

    return $manager;
  }

  /**
   * Sets up and returns a file backup manager.
   *
   * @param string $directory
   *   Directory for the backup. The backup file name should be
   *   public_files.tar within this directory.
   *
   * @return BackupMigrate
   *   The file backup manager object.
   */
  protected function backupFileManager($directory) {
    // Set up the backup manager object.
    $files_source = new FileDirectorySource();
    $config = new Config([
        'public1' => [
          'directory' => drupal_realpath('public://'),
        ],
        'directory1' => [
          'directory' => $directory,
        ],
        'compressor' => [
          'compression' => 'gzip',
        ],
        'namer' => [
          'filename' => 'public_files',
          'timestamp' => FALSE,
        ],
        'excluder' => [
          'source' => $files_source,
          'exclude_filepaths' => [
            '.htaccess',
            'php',
          ],
        ],
      ]);

    $manager = new BackupMigrate();
    $manager->services()->add('ArchiveReader', new TarArchiveReader());
    $manager->services()->add('ArchiveWriter', new TarArchiveWriter());
    $manager->services()->add('TempFileAdapter', new TempFileAdapter($this->tempFilesDirectory));
    $manager->services()->add('TempFileManager', new TempFileManager($manager->services()->get('TempFileAdapter')));

    $manager->services()->addClient($files_source);
    $manager->sources()->add('public1', $files_source);
    $manager->sources()->setConfig($config);

    $dir_dest = new DirectoryDestination();
    $manager->services()->addClient($dir_dest);
    $manager->destinations()->add('directory1', $dir_dest);
    $manager->destinations()->setConfig($config);

    $manager->plugins()->add('excluder', new FileExcludeFilter());
    $manager->plugins()->add('namer', new FileNamer());
    $manager->plugins()->add('compressor', new CompressionFilter());
    $manager->plugins()->setConfig($config);

    return $manager;
  }

  /**
   * Overrides drupalLogin() so it will work in our multingual setup.
   *
   * Also skips some of the checks and logging out existing user.
   */
  protected function drupalLogin(AccountInterface $account) {
    $this->drupalGet('user/login');
    $this->drupalPostForm(NULL, [
        'name' => $account->getUserName(),
        'pass' => $account->pass_raw,
      ], $this->callT('Log in'));
    if (isset($this->sessionId)) {
      $account->session_id = $this->sessionId;
    }
    $this->loggedInUser = $account;
    $this->container->get('current_user')->setAccount($account);
  }

  /**
   * Imports translations from all existing .po files in translation directory.
   *
   * Translations are read from the translations/LANGCODE directory. Also,
   * configuration is refreshed, to get around using the locale batch.
   *
   * @param string $langcode
   *   Language code to import the translations for. Skips if it is English.
   *
   * @see https://www.drupal.org/project/drupal/issues/2806009
   */
  protected function importTranslations($langcode) {
    if ($langcode != 'en') {
      $this->fixTranslationSettings();

      // Find all the translation files to import.
      $pattern = '|[a-zA-Z0-9_\-\.]+\.po$|';
      $options = ['recurse' => FALSE];
      $result = [];
      $directory = drupal_realpath(drupal_get_path('module', 'user_guide_tests') . '/translations/' . $langcode);
      if (is_dir($directory)) {
        $this->logTestMessage('CHECKING FOR INITIAL TRANSLATIONS IN: ' . $directory);
        $result = file_scan_directory($directory, $pattern, $options);
      }

      foreach ($result as $file) {
        $file->langcode = $langcode;
        $this->readPoFile($file->uri, $langcode);
        $this->logTestMessage('TRANSLATIONS READ FROM: ' . $file->uri);
      }
    }

    // Emulate the batch that we turned off in the test module,
    // that was coming from locale_form_language_admin_add_form_alter() and
    // locale_modules_installed() and needs to run whenever a new language is
    // added (even English), or a module is installed, if the locale module
    // is enabled.
    if (\Drupal::hasService('locale.config_manager')) {
      $locale_config = \Drupal::service('locale.config_manager');
      $names = $locale_config->getComponentNames([]);
      $locale_config->updateConfigTranslations($names, [$langcode]);
    }

    $this->flushAll();
  }

  /**
   * Fixes the settings for translation.
   *
   * Makes sure the translation directory exists, and sets up to only use local
   * translation files.
   */
  protected function fixTranslationSettings() {
    // Alter the core.extension config to put the test module last.
    $config = \Drupal::configFactory()->getEditable('core.extension');
    $modules = $config->get('module');
    $modules['user_guide_tests'] = 500;
    $config->set('module', $modules)->save();

    // Alter the translation path, and set up to not import.
    $this->ensureDirectoryWriteable(file_directory_temp(), 'temp');
    \Drupal::configFactory()->getEditable('locale.settings')
      ->set('translation.import_enabled', FALSE)
      ->set('translation.path', file_directory_temp())
      ->save();
    drupal_flush_all_caches();
    $this->refreshVariables();
    $translations_directory = \Drupal::service('file_system')->realpath('translations://');
    $this->ensureDirectoryWriteable($translations_directory, 'translations');
  }

  /**
   * Replicates what Gettext::fileToDatabase() does, but simpler.
   *
   * @param string $file_uri
   *   URI of file to read.
   * @param string $langcode
   *   Language code to save the strings for.
   */
  protected function readPoFile($file_uri, $langcode) {
    $reader = new PoStreamReader();
    $reader->setLangcode($langcode);
    $reader->setURI($file_uri);
    $reader->open();

    $header = $reader->getHeader();
    if (!$header) {
      throw new \Exception('Missing or malformed header.');
    }

    $writer = new PoDatabaseWriter();
    // We need to overwrite existing translations when we read this in,
    // because the reason we have this method is that translations are being
    // corrupted (overwritten with English) when modules are enabled.
    $writer->setOptions([
      'overwrite_options' => [
        'not_customized' => TRUE,
        'customized' => TRUE,
      ],
    ]);
    $writer->setLangcode($langcode);
    $writer->setHeader($header);
    $writer->writeItems($reader);
  }

  /**
   * Verifies translations to something other than English do not match English.
   *
   * @param bool $first
   *   (optional) TRUE (default) to translate to the first language in the
   *   demoInput member variable; FALSE to use the second language.
   */
  protected function verifyTranslations($first = TRUE) {
    // Only test if we're testing a non-English language.
    if (($this->demoInput['first_langcode'] == 'en' && $first) ||
      ($this->demoInput['second_langcode'] == 'en' && !$first)) {
      return;
    }

    // These strings are examples of ones found in English in some previous
    // tests that should have been translated.
    $to_test = [
      'Author',
      'Basic page',
      'Body',
      'Content type',
      'Comments',
      'Filter',
      'Language',
      'Main navigation',
      'Preview',
      'Published status',
      'Published',
      'Site section links',
      'Title',
    ];

    foreach ($to_test as $string) {
      $this->assertNotEqual($string, (string) $this->callT($string, $first));
    }

    // If we're looking at the site's main language (it is not English if we
    // get to this point in the method), also test that some config is not
    // English when we load it, and UI text when we view it. We have also just
    // verified that the translation of this config and UI text was correct
    // above.
    if ($first) {
      $config = \Drupal::config('system.menu.main');
      $this->assertNotEqual('Main navigation', $config->get('label'));
      $this->assertNotEqual('Site section links', $config->get('description'));

      // Menu names and descriptions on this page are in English, even if site
      // language is not English, so only test UI text here.
      $this->drupalGet('admin/structure/menu');
      $this->assertText($this->callT('Title'));
      $this->assertText($this->callT('Description'));
    }
  }

  /**
   * Flushes all caches and rebuilds container and routing.
   */
  protected function flushAll() {
    $this->rebuildContainer();
    $this->rebuildAll();
    $this->container->get('router.builder')->rebuild();
    drupal_flush_all_caches();
    $this->refreshVariables();
  }

  /**
   * Waits for a UI element to be present and ready to interact.
   *
   * The desired interaction is also performed. If it doesn't work out, the
   * method generates a test assertion fail.
   *
   * @param string $type
   *   Type of selector: 'css' or 'xpath'.
   * @param string $selector
   *   Selector for element to wait for.
   * @param string $interaction
   *   The interaction to check for and perform. One of:
   *   - 'click' (default)
   *   - 'focus'
   *   - 'none': Just wait for the element to be visible.
   */
  protected function waitForInteraction($type, $selector, $interaction = 'click') {
    $page = $this->getSession()->getPage();
    $reason = '';
    $result = $page->waitFor(10,
      function() use ($type, $selector, $page, $interaction, &$reason) {
        $item = $page->find($type, $selector);
        if (!$item) {
          $reason = "$type $selector not found on page";
          return FALSE;
        }
        try {
          switch ($interaction) {
            case 'click':
              $item->click();
              return TRUE;
            case 'focus':
              $item->focus();
              return TRUE;
            case 'none':
              return TRUE;
          }
        }
        catch (UnknownError $exception) {
          if (strstr($exception->getMessage(), 'not clickable') === FALSE &&
            strstr($exception->getMessage(), 'not interactable') === FALSE) {
            // Rethrow any unexpected exception.
            throw $exception;
          }
          $reason = "$type $selector found but not ready\n" .
            $exception->getmessage();
          return FALSE;
        }
      });

    if (!$result) {
      $this->fail($reason);
    }
  }

  /**
   * Fills in the body in a CKEditor field.
   *
   * @param string $text
   *   Text to put in the body in the CKEditor field.
   * @param string $prefix
   *   (optional) CSS selector prefix for the body field to fill in.
   */
  protected function fillInBody($text, $prefix = '#edit-body-wrapper') {
    $this->waitForInteraction('css', $prefix . ' .cke_button__source');
    $this->waitForInteraction('css', $prefix . ' .cke_contents textarea', 'focus');
    $this->getSession()->getPage()
      ->find('css', $prefix . ' .cke_contents textarea')
      ->setValue($text);
    $this->getSession()->getPage()
      ->find('css', $prefix . ' .cke_button__source')->click();
  }

  /**
   * Fills in the summary in a CKEditor field.
   *
   * @param string $text
   *   Text to put in the summary in the CKEditor field.
   * @param string $prefix
   *   (optional) CSS selector prefix for the body field to fill in the summary
   *   for.
   */
  protected function fillInSummary($text, $prefix = '#edit-body-wrapper') {
    $this->waitForInteraction('css', $prefix . ' label .field-edit-link button');
    $this->assertText($this->callT('Summary'));
    $this->getSession()->getPage()
      ->find('css', $prefix . ' .text-summary-wrapper textarea')
      ->setValue($text);
  }

  /**
   * Opens up the machine name edit area on a page.
   *
   * @param string $name_selector
   *   (optional) CSS selector for the human-readable name field. Defaults to
   *   '#edit-name'.
   */
  protected function openMachineNameEdit($name_selector = '#edit-name') {
    $this->getSession()->getDriver()->executeScript("window.scroll(0,0); jQuery('" . $name_selector . "').val('foo'); jQuery('.field-suffix').show(); jQuery('" . $name_selector . "-machine-name-suffix').show();");
    $this->waitForInteraction('css', $name_selector . '-machine-name-suffix button');
    $this->assertSession()->assertWaitOnAjaxRequest();
  }

  /**
   * Sets up an add field page that you are on for a new field edit.
   *
   * @param string $type
   *   Type of field to add.
   */
  protected function setUpAddNewField($type) {
    $this->getSession()->getPage()
      ->find('css', '#edit-new-storage-type')
      ->selectOption($type);
    $this->getSession()->getDriver()
      ->executeScript("jQuery('#edit-new-storage-wrapper').show();");
    $this->openMachineNameEdit('#edit-label');
  }

  /**
   * Sets up an add field page that you are on for an existing field edit.
   *
   * @param string $name
   *   Name of existing field to add.
   */
  protected function setUpAddExistingField($name) {
    $this->getSession()->getPage()
      ->find('css', '#edit-existing-storage-name')
      ->selectOption($name);
    $this->getSession()->getDriver()
      ->executeScript("jQuery('.form-item-existing-storage-label').show();");
  }

  /**
   * Overrides drupalPostForm so that it always scrolls the window up first.
   */
  protected function drupalPostForm($path, $edit, $submit, array $options = [], $form_html_id = NULL) {
    $this->scrollWindowUp();
    parent::drupalPostForm($path, $edit, $submit, $options, $form_html_id);
  }

}
