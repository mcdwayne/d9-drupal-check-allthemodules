<?php
namespace Drupal\Tests\linkback\Kernel;

use Drupal\simpletest\UserCreationTrait;
use Drupal\simpletest\NodeCreationTrait;
use Drupal\node\NodeInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Path\AliasManager;
use Drupal\Core\Path\AliasStorage;
use Drupal\Core\Database\Database;
use Drupal\system\Tests\Path\UrlAliasFixtures;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Provides a base class for Commerce kernel tests.
 *
 * @group linkback
 */
class LinkbackServiceTest extends LinkbackKernelTestBase
{
    use NodeCreationTrait;
  
    /**
   * {@inheritdoc}
   */
    protected function setUp() 
    {
        parent::setUp();
        $this->linkbackService = $this->container->get('linkback.default');
        $this->fixtures = new UrlAliasFixtures();
        $this->adminUser = $this->createUser(array('administer nodes', 'create article content','post comments'));


        // Add another language to site
        $langcode = 'xx';
        $name = $this->randomMachineName(16);
        $prefix = $langcode;
        $edit = [
        'langcode' => $langcode,
        'label' => $name,
        'id' => $langcode
        ];
        $new_language = new ConfigurableLanguage($edit, 'configurable_language');
        $new_language->save();

        $langcode = 'yy';
        $name = $this->randomMachineName(16);
        $prefix = $langcode;
        $edit = [
        'langcode' => $langcode,
        'label' => $name,
        'id' => $langcode
        ];
        $new_language = new ConfigurableLanguage($edit, 'configurable_language');
        $new_language->save();

        // Set article type as translatable
        $article_lang_settings = \Drupal\language\Entity\ContentLanguageSettings::loadByEntityTypeBundle('node', 'article')
        ->setLanguageAlterable(true)
        ->setDefaultLangcode($langcode)
        ->save();

        /*  //  @todo add localized assertion, mark content as translatable, and
        //  translated.
        // ENABLE language_url negotiation
        //$this->container->get('language_negotiator')->updateConfiguration('');
        //language_negotiation_url_prefixes_update();
        //$this->container->get('language_negotiator')->saveConfiguration(\Drupal\Core\Language\LanguageInterface::TYPE_CONTENT, ['language-url'=>0]);
        //print_r($this->container->get('language_negotiator')->getPrimaryNegotiationMethod(\Drupal\Core\Language\LanguageInterface::TYPE_CONTENT));

        // Setting body field_storage translation
        $field_storage = \Drupal\field\Entity\FieldStorageConfig::loadByName('node', 'body');
        print_r($field_storage->isTranslatable());
        $field_storage->setTranslatable(TRUE);
        $field_storage->save();
        print_r($field_storage);

        // Setting body field definition as translatable
        $definitions = $this->container->get('entity.manager')->getFieldDefinitions('node','article');
        $definitions['body']->setTranslatable(TRUE);
        print_r($definitions['body']);
        */

        // Add node

        $settings = array(
        'type' => 'article',
        'title' => 'Title test',
        'body' => [[
          'value' => "Body test",
          'format' => filter_default_format(),
        ],
        ],
        'langcode' => 'en',
        );
        $this->node = $this->createNode($settings);
        $this->node->addTranslation('xx', ['title' => 'Title in xx', 'body' => [ 'value' => "Body test in xx", 'format' => filter_default_format() ]])->save();
        $this->container->get('router.builder')->rebuild();
    }

    /**
   * covers ::getLocalUrl
   */
    function testGetLocalUrl()
    {
        $node_id =  $this->node->id();

        // Prepare database table.
        $connection = Database::getConnection();
        $this->fixtures->createTables($connection);
        // Create AliasManager and Path object.

        $aliasManager = $this->container->get('path.alias_manager');
        $aliasStorage = new AliasStorage($connection, $this->container->get('module_handler'));

        $path = [
        'source' => "/node/1",
        'alias' => '/mutual_aid',
        ];

        $aliasStorage->save($path['source'], $path['alias']);

        $this->assertEquals($this->linkbackService->getLocalUrl($node_id), 'http://localhost/node/1', "This local url without translations is not expected");
        $this->assertContains('http://localhost/node/1', $this->linkbackService->getLocalUrl($node_id, true), "Expected url not found");
        $this->assertContains('/node/1', $this->linkbackService->getLocalUrl($node_id, true), "Expected url not found");
        $this->assertContains('/mutual_aid', $this->linkbackService->getLocalUrl($node_id, true), "Expected url not found");
        //  @todo add localized assertion, mark content as translatable, and
        //  translated.
        // $this->assertContains('/xx/mutual_aid', $this->linkbackService->getLocalUrl($node_id, true), "Expected localized url not found");
    }

    /**
   * covers ::getTitleExcerpt
   */
    function testGetTitleExcerpt()
    {
        $text="<title>Mutual Aid</title><p>The mutual-aid tendency in man has so remote an <a href='http://localhost/node/1'>origin</a>, and is so deeply interwoven with all the past evolution of the human race, that is has been maintained by mankind up to the present time, notwithstanding all vicissitudes of history.";
        $linkback_service = $this->linkbackService;
        $theme_render = function () use ($linkback_service, $text) {
            return $linkback_service->getTitleExcerpt("1", $text);
        };
        /**
 * @var \Drupal\Core\Render\RendererInterface $renderer 
*/
        $renderer = \Drupal::service('renderer');
        $context = new RenderContext();
        $output = $renderer->executeInRenderContext($context, $theme_render);
        $this->assertEquals("Mutual Aid", $output[0], "Title cannot be obtained as it should");
        $this->assertEquals("The mutual-aid tendency in man has so remote an <strong>origin</strong>, and is so deeply interwoven with all the past …", $output[1]->__toString(), "Excerpt cannot be obtained as it should");
    }
}
