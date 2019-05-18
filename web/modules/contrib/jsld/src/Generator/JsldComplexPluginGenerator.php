<?php

namespace Drupal\jsld\Generator;

use Drupal\Core\Entity\ContentEntityType;
use DrupalCodeGenerator\Command\BaseGenerator;
use DrupalCodeGenerator\Utils;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Class JsldComplexPluginGenerator.
 *
 * @package Drupal\jsld\Generators
 */
class JsldComplexPluginGenerator extends BaseGenerator {

  /**
   * {@inheritdoc}
   */
  protected $name = 'plugin-jsld';

  /**
   * {@inheritdoc}
   */
  protected $alias = 'jsld';

  /**
   * {@inheritdoc}
   */
  protected $description = 'Generates JSON-LD structured data for common use cases.';

  /**
   * {@inheritdoc}
   */
  protected $templatePath = __DIR__ . '/templates';

  /**
   * {@inheritdoc}
   */
  protected function interact(InputInterface $input, OutputInterface $output) {
    $questions = Utils::defaultPluginQuestions();

    $this->askForStructuredDataType($questions);
    $this->askForPluginType($questions);

    $vars = &$this->collectVars($input, $output, $questions);
    $vars['name'] = Utils::camelize($vars['plugin_id']);
    // For extends.
    $vars['base_plugin_twig'] = 'jsld-' . $vars['jsld_plugin_type'] . '-plugin.html.twig';

    // Additional questions.
    $questions = [];
    // Special questions for Path plugin type.
    if ($vars['jsld_plugin_type'] == 'path') {
      $questions['match_type'] = new ChoiceQuestion('Match type for path', [
        'listed' => 'Add markup on listed pages',
        'unlisted' => 'Add on all, except listed',
      ]);
    }

    // Special questions for Entity plugin type.
    if ($vars['jsld_plugin_type'] == 'entity') {
      $entity_types = [];
      foreach (\Drupal::entityTypeManager()
                 ->getDefinitions() as $entity_type_id => $entity_type) {
        if ($entity_type instanceof ContentEntityType) {
          $entity_types[$entity_type_id] = $entity_type->getLabel();
        }
      }
      $questions['entity_type'] = new ChoiceQuestion('Entity type', $entity_types);
    }

    $vars = &$this->collectVars($input, $output, $questions, $vars);

    $this->addFile()
      ->path('src/Plugin/jsld/{jsld_plugin_type}/{name}.php')
      ->template($vars['structured_data_type'] . '.html.twig');
  }

  /**
   * Ask for structured type.
   */
  protected function askForStructuredDataType(&$questions) {
    $questions['structured_data_type'] = new ChoiceQuestion(
      'What structured data do you want to describe',
      $this->getStructuredDataType(),
      'default'
    );

    $questions['structured_data_type']->setValidator([
      Utils::class,
      'validateRequired',
    ]);
  }

  /**
   * Return array with available structured types.
   */
  protected function getStructuredDataType() {
    return [
      'default' => 'Default value for custom markup.',
      'article' => 'Article. Best for “How-to (task) topics, step-by-step, procedural troubleshooting, specifications, etc.”',
      'blog-post' => 'Blog Post.',
      'book' => 'Book. For regular books and eBooks.',
      'breadcrumb' => 'Breadcrumb.',
      'event' => 'Event.',
      'job-posting' => 'Job Posting.',
      'local-business' => 'Local Business.',
      'news-article' => 'News Article.',
      'organization' => 'Organization.',
      'person' => 'Person.',
      'product' => 'Product.',
      'recipe' => 'Recipe.',
      'social-profile' => 'Social Network Profiles.',
      'video' => 'Video.',
      'review' => 'Review.',
    ];
  }

  /**
   * Asks for preferred plugin type.
   */
  protected function askForPluginType(&$questions) {
    $jsld_plugin_types = [
      'path' => 'JSON-LD path plugin',
      'entity' => 'JSON-LD entity plugin',
    ];

    $questions['jsld_plugin_type'] = new ChoiceQuestion('What JSLD plugin type you want to use.', $jsld_plugin_types, [
      $this,
      'recommendationForType',
    ]);
  }

  /**
   * Set recommended type as default value.
   */
  public function recommendationForType($variables) {
    $recommended_type = NULL;
    switch ($variables['structured_data_type']) {
      case 'article':
      case 'blog-post':
      case 'book':
      case 'event':
      case 'job-posting':
      case 'news-article':
      case 'product':
      case 'recipe':
      case 'video':
      case 'review':
        $recommended_type = 'entity';
        break;

      case 'breadcrumb':
      case 'local-business':
      case 'organization':
      case 'person':
      case 'social-profile':
        $recommended_type = 'path';
        break;
    }
    return $recommended_type;
  }

}
