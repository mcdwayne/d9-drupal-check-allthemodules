<?php

namespace Drupal\entity_gallery\Tests\Views;

use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\views\Plugin\views\PluginBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\views\Views;

/**
 * Tests entity gallery language fields, filters, and sorting.
 *
 * @group entity_gallery
 */
class EntityGalleryLanguageTest extends EntityGalleryTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('language', 'entity_gallery_test_views');

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_frontpage', 'test_language');

  /**
   * List of entity gallery titles by language.
   *
   * @var array
   */
  public $entityGalleryTitles = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp(FALSE);

    // Create Page gallery type.
    if ($this->profile != 'standard') {
      $this->drupalCreateGalleryType(array('type' => 'page', 'name' => 'Basic page'));
      ViewTestData::createTestViews(get_class($this), array('entity_gallery_test_views'));
    }

    // Add two new languages.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('es')->save();

    // Set up entity gallery titles. They should not include the words "French",
    // "English", or "Spanish", as there is a language field in the view
    // that prints out those words.
    $this->entityGalleryTitles = array(
      LanguageInterface::LANGCODE_NOT_SPECIFIED => array(
        'First entity gallery und',
      ),
      'es' => array(
        'Primero entity gallery es',
        'Segundo entity gallery es',
        'Tercera entity gallery es',
      ),
      'en' => array(
        'First entity gallery en',
        'Second entity gallery en',
      ),
      'fr' => array(
        'Premier entity gallery fr',
      )
    );

    // Create entity galleries with translations.
    foreach ($this->entityGalleryTitles['es'] as $index => $title) {
      $entity_gallery = $this->drupalCreateEntityGallery(array('title' => $title, 'langcode' => 'es', 'type' => 'page', 'promote' => 1));
      foreach (array('en', 'fr') as $langcode) {
        if (isset($this->entityGalleryTitles[$langcode][$index])) {
          $translation = $entity_gallery->addTranslation($langcode, array('title' => $this->entityGalleryTitles[$langcode][$index]));
        }
      }
      $entity_gallery->save();
    }
    // Create non-translatable entity galleries.
    foreach ($this->entityGalleryTitles[LanguageInterface::LANGCODE_NOT_SPECIFIED] as $index => $title) {
      $entity_gallery = $this->drupalCreateEntityGallery(array('title' => $title, 'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED, 'type' => 'page', 'promote' => 1));
      $entity_gallery->save();
    }

    $this->container->get('router.builder')->rebuild();

    $user = $this->drupalCreateUser(array('access entity gallery overview', 'access entity galleries'));
    $this->drupalLogin($user);
  }

  /**
   * Tests translation language filter, field, and sort.
   */
  public function testLanguages() {
    // Test the page with no arguments. It is filtered to Spanish and French.
    // The page shows entity gallery titles and languages.
    $this->drupalGet('test-language');
    $message = 'French/Spanish page';

    // Test that the correct entity galleries are shown.
    foreach ($this->entityGalleryTitles as $langcode => $list) {
      foreach ($list as $title) {
        if ($langcode == 'en') {
          $this->assertNoText($title, $title . ' does not appear on ' . $message);
        }
        else {
          $this->assertText($title, $title . ' does appear on ' . $message);
        }
      }
    }

    // Test that the language field value is shown.
    $this->assertNoText('English', 'English language is not shown on ' . $message);
    $this->assertText('French', 'French language is shown on ' . $message);
    $this->assertText('Spanish', 'Spanish language is shown on ' . $message);

    // Test page sorting, which is by language code, ascending. So the
    // Spanish entity galleries should appear before the French entity
    // galleries.
    $page = $this->getTextContent();
    $pos_es_max = 0;
    $pos_fr_min = 10000;
    foreach ($this->entityGalleryTitles['es'] as $title) {
      $pos_es_max = max($pos_es_max, strpos($page, $title));
    }
    foreach ($this->entityGalleryTitles['fr'] as $title) {
      $pos_fr_min = min($pos_fr_min, strpos($page, $title));
    }
    $this->assertTrue($pos_es_max < $pos_fr_min, 'Spanish translations appear before French on ' . $message);

    // Test the argument -- filter to just Spanish.
    $this->drupalGet('test-language/es');
    // This time, test just the language field.
    $message = 'Spanish argument page';
    $this->assertNoText('English', 'English language is not shown on ' . $message);
    $this->assertNoText('French', 'French language is not shown on ' . $message);
    $this->assertText('Spanish', 'Spanish language is shown on ' . $message);

    // Test the front page view filter. Only entity gallery titles in the
    // current language should be displayed on the front page by default.
    foreach ($this->entityGalleryTitles as $langcode => $titles) {
      // The frontpage view does not display content without a language.
      if ($langcode == LanguageInterface::LANGCODE_NOT_SPECIFIED) {
        continue;
      }
      $this->drupalGet(($langcode == 'en' ? '' : "$langcode/") . 'entity_gallery');
      foreach ($titles as $title) {
        $this->assertText($title);
      }
      foreach ($this->entityGalleryTitles as $control_langcode => $control_titles) {
        if ($langcode != $control_langcode) {
          foreach ($control_titles as $title) {
            $this->assertNoText($title);
          }
        }
      }
    }

    // Test the entity gallery admin view filter. By default all translations
    // should show.
    $this->drupalGet('admin/content/gallery');
    foreach ($this->entityGalleryTitles as $titles) {
      foreach ($titles as $title) {
        $this->assertText($title);
      }
    }
    // When filtered, only the specific languages should show.
    foreach ($this->entityGalleryTitles as $langcode => $titles) {
      $this->drupalGet('admin/content/gallery', array('query' => array('langcode' => $langcode)));
      foreach ($titles as $title) {
        $this->assertText($title);
      }
      foreach ($this->entityGalleryTitles as $control_langcode => $control_titles) {
        if ($langcode != $control_langcode) {
          foreach ($control_titles as $title) {
            $this->assertNoText($title);
          }
        }
      }
    }

    // Override the config for the front page view, so that the language
    // filter is set to the site default language instead. This should just
    // show the English entity galleries, no matter what the content language
    // is.
    $config = $this->config('views.view.test_frontpage');
    $config->set('display.default.display_options.filters.langcode.value', array(PluginBase::VIEWS_QUERY_LANGUAGE_SITE_DEFAULT => PluginBase::VIEWS_QUERY_LANGUAGE_SITE_DEFAULT));
    $config->save();
    foreach ($this->entityGalleryTitles as $langcode => $titles) {
      if ($langcode == LanguageInterface::LANGCODE_NOT_SPECIFIED) {
        continue;
      }
      $this->drupalGet(($langcode == 'en' ? '' : "$langcode/") . 'entity_gallery');
      foreach ($this->entityGalleryTitles as $control_langcode => $control_titles) {
        foreach ($control_titles as $title) {
          if ($control_langcode == 'en') {
            $this->assertText($title, 'English title is shown when filtering is site default');
          }
          else {
            $this->assertNoText($title, 'Non-English title is not shown when filtering is site default');
          }
        }
      }
    }

    // Override the config so that the language filter is set to the UI
    // language, and make that have a fixed value of 'es'.
    //
    // IMPORTANT: Make sure this part of the test is last -- it is changing
    // language configuration!
    $config->set('display.default.display_options.filters.langcode.value', array('***LANGUAGE_language_interface***' => '***LANGUAGE_language_interface***'));
    $config->save();
    $language_config = $this->config('language.types');
    $language_config->set('negotiation.language_interface.enabled', array('language-selected' => 1));
    $language_config->save();
    $language_config = $this->config('language.negotiation');
    $language_config->set('selected_langcode', 'es');
    $language_config->save();

    // With a fixed language selected, there is no language-based URL.
    $this->drupalGet('entity_gallery');
    foreach ($this->entityGalleryTitles as $control_langcode => $control_titles) {
      foreach ($control_titles as $title) {
        if ($control_langcode == 'es') {
          $this->assertText($title, 'Spanish title is shown when filtering is fixed UI language');
        }
        else {
          $this->assertNoText($title, 'Non-Spanish title is not shown when filtering is fixed UI language');
        }
      }
    }
  }

  /**
   * Tests native name display in language field.
   */
  public function testNativeLanguageField() {
    $this->assertLanguageNames();

    // Modify test view to display native language names and set translations.
    $config = $this->config('views.view.test_language');
    $config->set('display.default.display_options.fields.langcode.settings.native_language', TRUE);
    $config->save();
    \Drupal::languageManager()->getLanguageConfigOverride('fr', 'language.entity.fr')->set('label', 'Français')->save();
    \Drupal::languageManager()->getLanguageConfigOverride('es', 'language.entity.es')->set('label', 'Español')->save();
    $this->assertLanguageNames(TRUE);

    // Modify test view to use the views built-in language field and test that.
    \Drupal::state()->set('entity_gallery_test_views.use_basic_handler', TRUE);
    Views::viewsData()->clear();
    $config = $this->config('views.view.test_language');
    $config->set('display.default.display_options.fields.langcode.native_language', FALSE);
    $config->clear('display.default.display_options.fields.langcode.settings');
    $config->clear('display.default.display_options.fields.langcode.type');
    $config->set('display.default.display_options.fields.langcode.plugin_id', 'language');
    $config->save();
    $this->assertLanguageNames();
    $config->set('display.default.display_options.fields.langcode.native_language', TRUE)->save();
    $this->assertLanguageNames(TRUE);
  }

  /**
   * Asserts the presence of language names in their English or native forms.
   *
   * @param bool $native
   *   (optional) Whether to assert the language name in its native form.
   */
  protected function assertLanguageNames($native = FALSE) {
    $this->drupalGet('test-language');
    if ($native) {
      $this->assertText('Français', 'French language shown in native form.');
      $this->assertText('Español', 'Spanish language shown in native form.');
      $this->assertNoText('French', 'French language not shown in English.');
      $this->assertNoText('Spanish', 'Spanish language not shown in English.');
    }
    else {
      $this->assertNoText('Français', 'French language not shown in native form.');
      $this->assertNoText('Español', 'Spanish language not shown in native form.');
      $this->assertText('French', 'French language shown in English.');
      $this->assertText('Spanish', 'Spanish language shown in English.');
    }
  }

}
