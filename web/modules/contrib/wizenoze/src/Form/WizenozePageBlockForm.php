<?php

namespace Drupal\wizenoze\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\wizenoze\Entity\Wizenoze;

/**
 * Builds the search form for the wizenoze page block.
 */
class WizenozePageBlockForm extends FormBase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new WizenozePageBlockForm.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   */
  public function __construct(LanguageManagerInterface $language_manager, RendererInterface $renderer) {
    $this->languageManager = $language_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('language_manager'), $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wizenoze_page_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $args = []) {
    /* @var $wizenoze_page \Drupal\wizenoze\WizenozePageInterface */
    $wizenoze_page = Wizenoze::load($args['wizenoze_page']);

    $langcode = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();

    $form['wizenoze_page'] = [
      '#type' => 'value',
      '#value' => $wizenoze_page->id(),
    ];

    $default_value = '';
    if (isset($args['keys'])) {
      $default_value = $args['keys'];
    }
    elseif ($search_value = $this->getRequest()->get('keys')) {
      $default_value = $search_value;
    }

    $form['keys'] = [
      '#type' => 'search',
      '#title' => $this->t('Search', [], ['langcode' => $langcode]),
      '#title_display' => 'invisible',
      '#size' => 15,
      '#default_value' => $default_value,
      '#attributes' => [
        'title' => $this->t('Enter the terms you wish to search for.', [], [
          'langcode' => $langcode,
        ]
        ),
      ],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search', [], ['langcode' => $langcode]),
    ];

    if (!$wizenoze_page->getCleanUrl()) {
      $route = 'wizenoze_page.' . $langcode . '.' . $wizenoze_page->id();
      $form['#action'] = $this->getUrlGenerator()->generateFromRoute($route);
      $form['#method'] = 'get';
      $form['actions']['submit']['#name'] = '';
    }

    // Dependency on wizenoze config entity.
    $this->renderer->addCacheableDependency($form, $wizenoze_page->getConfigDependencyName());
    $this->renderer->addCacheableDependency($form, $langcode);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This form submits to the search page, so processing happens there.
    $keys = $form_state->getValue('keys');
    $langcode = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
    $form_state->setRedirectUrl(Url::fromRoute('wizenoze_page.' . $langcode . '.' . $form_state->getValue('wizenoze_page'), ['keys' => $keys]));
  }

}
