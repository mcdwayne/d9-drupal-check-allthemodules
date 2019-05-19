<?php
/**
 * Created by PhpStorm.
 * User: darius
 * Date: 10/20/17
 * Time: 14:29
 */

namespace Drupal\views_entity_translations_links\Plugin\views\field;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Url;
use Drupal\views\Entity\Render\EntityTranslationRenderTrait;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders all translations links for an entity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("entity_translations")
 */
class EntityTranslations extends FieldPluginBase {

  use EntityTranslationRenderTrait;
  use RedirectDestinationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *    The entity manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, Renderer $renderer) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityManager = $entity_manager;
    $this->languageManager = $language_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function usesGroupBy() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['destination'] = [
      'default' => TRUE,
    ];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['destination'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include destination'),
      '#description' => $this->t('Include a <code>destination</code> parameter in the link to return the user to the original view upon completing the link action.'),
      '#default_value' => $this->options['destination'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {

    $langcodes = array_keys($this->languageManager->getLanguages());
    $entity = $values->_entity;

    foreach ($langcodes as $langcode) {

      if ($entity->hasTranslation($langcode)) {

        $build['translation_link'][$langcode] = [
          '#title' => 'Edit ' . $langcode . ' translation',
          '#type' => 'link',
          '#url' => $entity->getTranslation($langcode)->toUrl('edit-form'),
          '#attributes' => ['class' => [$langcode . '-has-translation' , 'language-has-translation'], 'title' => 'Edit ' . $langcode . ' translation']
        ];
      }
      else {
        $build['translation_link'][$langcode] = [
          '#title' => 'Add ' . $langcode . ' translation',
          '#type' => 'link',
          '#url' => Url::fromRoute('entity.' . $entity->getEntityTypeId() . '.content_translation_add', ['source' => $entity->language()->getId(), 'target' => $langcode, $entity->getEntityTypeId() => $entity->id()]),
          '#attributes' => ['class' => ['language-add-translation'], 'title' => 'Add ' . $langcode . ' translation']
        ];
      }
    }

    // Attaches the library.
    $build['translation_link']['#attached']['library'][] = 'views_entity_translations_links/views.entity.translations.links';


    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // We purposefully do not call parent::query() because we do not want the
    // default query behavior for Views fields. Instead, let the entity
    // translation renderer provide the correct query behavior.8798876

    if ($this->languageManager->isMultilingual()) {
      $this->getEntityTranslationRenderer()->query($this->query, $this->relationship);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->getEntityType();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityManager() {
    return $this->entityManager;
  }

  /**v
   * {@inheritdoc}
   */
  protected function getLanguageManager() {
    return $this->languageManager;
  }
  /**
   * {@inheritdoc}
   */
  protected function getView() {
    return $this->view;
  }

  /**
   * {@inheritdoc}
   */
  public function clickSortable() {
    return FALSE;
  }

  public function label() {
    return 'Translate links (This will be overwritten in frontend)';
  }

}
