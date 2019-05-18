<?php

namespace Drupal\blocktabs\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\blocktabs\TabManager;
use Drupal\blocktabs\BlocktabsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an add form for tab.
 */
class TabAddForm extends TabFormBase {

  /**
   * The tab manager.
   *
   * @var \Drupal\blocktabs\TabManager
   */
  protected $tabManager;

  /**
   * Constructs a new TabAddForm.
   *
   * @param \Drupal\blocktabs\TabManager $tab_manager
   *   The tab manager.
   */
  public function __construct(TabManager $tab_manager) {
    $this->tabManager = $tab_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.blocktabs.tab')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, BlocktabsInterface $blocktabs = NULL, $tab = NULL) {
    $form = parent::buildForm($form, $form_state, $blocktabs, $tab);
    // drupal_set_message('term_id:' . var_export($tab));
    $form['#title'] = $this->t('Add %label tab', ['%label' => $this->tab->label()]);
    $form['actions']['submit']['#value'] = $this->t('Add tab');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareTab($tab) {
    $tab = $this->tabManager->createInstance($tab);
    // Set the initial weight so this tab comes last.
    $tab->setWeight(count($this->blocktabs->getTabs()));
    return $tab;
  }

}
