<?php

namespace Drupal\snippet_metatag\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\metatag\MetatagManagerInterface;
use Drupal\metatag\MetatagTagPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Snippet metatag form.
 *
 * @property \Drupal\snippet_manager\SnippetInterface $entity
 */
class MetatagForm extends EntityForm {

  /**
   * The metatag manager.
   *
   * @var \Drupal\metatag\MetatagManagerInterface
   */
  protected $metatagManager;

  /**
   * The metatag plugin manager.
   *
   * @var \Drupal\metatag\MetatagTagPluginManager
   */
  protected $tagPluginManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a snippet form object.
   *
   * @param \Drupal\metatag\MetatagManagerInterface $metatag_manager
   *   The config factory.
   * @param \Drupal\metatag\MetatagTagPluginManager $tag_plugin_manager
   *   The snippet library builder.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(MetatagManagerInterface $metatag_manager, MetatagTagPluginManager $tag_plugin_manager, MessengerInterface $messenger) {
    $this->metatagManager = $metatag_manager;
    $this->tagPluginManager = $tag_plugin_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('metatag.manager'),
      $container->get('plugin.manager.metatag.tag'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);

    if (!$this->entity->get('page')['status']) {
      $this->messenger->addWarning($this->t('These metatags will only apply when snippet page is enabled.'));
    }
    $metatags = $this->entity->getThirdPartySetting('snippet_metatag', 'metatags');
    $form['metatags'] = $this->metatagManager->form($metatags ?: [], $form, ['snippet']);
    $form['metatags']['#type'] = 'container';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actionsElement(array $form, FormStateInterface $form_state) {
    $element = parent::actionsElement($form, $form_state);
    $element['delete']['#access'] = FALSE;

    $element['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset'),
      '#submit' => ['::reset'] + $element['submit']['#submit'],
      '#weight' => $element['submit']['#weight'] + 1,
    ];

    return $element;
  }

  /**
   * Form submission handler for the 'reset' action.
   */
  public function reset(array &$form, FormStateInterface $form_state) {
    $form_state->set('reset', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    if ($form_state->get('reset')) {
      $this->entity->unsetThirdPartySetting('snippet_metatag', 'metatags');
    }
    else {
      $tag_values = [];
      $metatags = $form_state->cleanValues()->getValues();
      foreach ($metatags as $tag_id => $tag_value) {
        // Some plugins need to process form input before storing it.
        // Hence, we set it and then get it.
        $plugin = $this->tagPluginManager->createInstance($tag_id);
        $plugin->setValue($tag_value);
        $tag_value = $plugin->value();
        if (!empty($tag_value)) {
          $tag_values[$tag_id] = $tag_value;
        }
      }
      $this->entity->setThirdPartySetting('snippet_metatag', 'metatags', $tag_values);
    }

    parent::save($form, $form_state);
    $this->messenger->addStatus($this->t('Snippet %label has been updated.', ['%label' => $this->entity->label()]));
  }

}
