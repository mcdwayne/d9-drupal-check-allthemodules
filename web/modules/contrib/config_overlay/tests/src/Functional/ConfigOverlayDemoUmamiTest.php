<?php

namespace Drupal\Tests\config_overlay\Functional;

use Drupal\Core\Config\StorageInterface;

/**
 * Tests installation of the Umami profile with Configuration Overlay.
 *
 * @group config_overlay
 */
class ConfigOverlayDemoUmamiTest extends ConfigOverlayTestingTest {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'demo_umami';

  /**
   * {@inheritdoc}
   */
  protected $collections = [StorageInterface::DEFAULT_COLLECTION, 'language.es'];

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    parent::prepareEnvironment();
    // Place a custom local translation file in the translations directory to
    // avoid the installer downloading translations from localize.drupal.org.
    $po = <<<PO
msgid ""
msgstr ""

msgid "Breadcrumbs"
msgstr "Enlaces de ayuda a la navegación"
PO;

    mkdir($this->root . '/' . $this->siteDirectory . '/files/translations', 0777, TRUE);
    file_put_contents($this->root . '/' . $this->siteDirectory . '/files/translations/drupal-8.0.0.es.po', $po);
  }

  /**
   * {@inheritdoc}
   */
  protected function getOverriddenConfig() {
    $overridden_config = parent::getOverriddenConfig();

    // Add any modules that are installed by Umami, but not by Testing.
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['core.extension']['module'] += [
      'automated_cron' => 0,
      'big_pipe' => 0,
      'block' => 0,
      'block_content' => 0,
      'breakpoint' => 0,
      'ckeditor' => 0,
      'color' => 0,
      'config' => 0,
      'config_translation' => 0,
      'contact' => 0,
      'content_moderation' => 0,
      'contextual' => 0,
      'datetime' => 0,
      'dblog' => 0,
      'demo_umami_content' => 0,
      'demo_umami_tour' => 0,
      'editor' => 0,
      'field' => 0,
      'field_ui' => 0,
      'file' => 0,
      'filter' => 0,
      'help' => 0,
      'history' => 0,
      'image' => 0,
      'language' => 0,
      'layout_builder' => 0,
      'layout_discovery' => 0,
      'link' => 0,
      'locale' => 0,
      'media' => 0,
      'menu_ui' => 0,
      'node' => 0,
      'options' => 0,
      'path' => 0,
      'quickedit' => 0,
      'rdf' => 0,
      'responsive_image' => 0,
      'search' => 0,
      'shortcut' => 0,
      'taxonomy' => 0,
      'text' => 0,
      'toolbar' => 0,
      'tour' => 0,
      'views_ui' => 0,
      'workflows' => 0,
      'menu_link_content' => 1,
      'content_translation' => 10,
      'views' => 10,
    ];
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['core.extension']['module'] = module_config_sort($overridden_config[StorageInterface::DEFAULT_COLLECTION]['core.extension']['module']);

    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['core.extension']['theme'] = [
      'stable' => 0,
      'classy' => 0,
      'seven' => 0,
      'umami' => 0,
    ];

    // The system site configuration is overridden by the test, so make it match
    // the values given in Standard's version of the file.
    /* @see \Drupal\Tests\config_overlay\Functional\ConfigOverlayTestingTest::getOverriddenConfig() */
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['system.site']['page']['front'] = '/node';

    // Add text formats. Because the shipped configuration includes a 'roles'
    // property which is not persisted, they will always be considered
    // overridden.
    /* @see \Drupal\Tests\config_overlay\Functional\ConfigOverlayDemoUmamiTest::getExpectedConfig() */
    foreach (['basic_html', 'full_html', 'restricted_html'] as $format_id) {
      $overridden_config[StorageInterface::DEFAULT_COLLECTION]["filter.format.$format_id"] = [];
    }

    /* @see demo_umami_form_install_configure_submit() */
    $overridden_config[StorageInterface::DEFAULT_COLLECTION]['contact.form.feedback'] = [
      'recipients' => ['simpletest@example.com'],
    ];

    // Add Spanish configuration overrides.
    // @todo Extract this into a dedicated multilingual test.
    /* @see \Drupal\Tests\config_overlay\Functional\ConfigOverlayDemoUmamiTest::prepareEnvironment() */
    $overridden_config['language.es']['block.block.breadcrumbs'] = [
      'settings' => ['label' => 'Enlaces de ayuda a la navegación'],
    ];
    $overridden_config['language.es']['block.block.seven_breadcrumbs'] = [
      'settings' => ['label' => 'Enlaces de ayuda a la navegación'],
    ];

    return $overridden_config;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedConfig() {
    $expected_config = parent::getExpectedConfig();

    foreach (['basic_html', 'full_html', 'restricted_html'] as $format_id) {
      unset($expected_config[StorageInterface::DEFAULT_COLLECTION]["filter.format.$format_id"]['roles']);
    }

    return $expected_config;
  }

}
