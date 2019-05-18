<?php
/**
 * @file
 * Contains \Drupal\author_pane\Plugin\Block\AuthorPane.
 */

namespace Drupal\author_pane\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\author_pane\Entity\AuthorPane;

/**
 * Provides a Author Pane block.
 *
 * @Block(
 *   id = "author_pane_block",
 *   admin_label = @Translation("Author Information"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node", required = FALSE)
 *   }
 * )
 */
class AuthorPaneBlock extends BlockBase {

  /**
   * Instance of authorPaneManager service.
   */
  protected $authorPaneManager;

  /**
   * @var \Drupal\author_pane\Entity\AuthorPane
   */
  protected $authorPane;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->authorPaneManager = \Drupal::service('authorpane.manager');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    // @TODO: Replace this with list of author panes.
    $options = array('authorpane' => 'Author Pane');

    $config = $this->getConfiguration();

    $form['author_pane'] = array(
      '#type' => 'select',
      '#title' => $this->t('Which author pane to display?'),
      '#default_value' => $config['author_pane'],
      '#options' => $options,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['author_pane'] = $form_state->getValue('author_pane');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = array();

    // Try to determine the author from context.
    $author = $this->findAuthor();

    // If we have no author, we can't build the pane.
    if (!is_null($author)) {
      $config = $this->getConfiguration();
      $this->authorPane = $this->authorPaneManager->load($config['author_pane']);
      $this->authorPane->setAuthor($author);
      $content = $this->authorPane->display();

      // @TODO: More advanced theming on the block?
      $block = array('#markup' => $content);
    }

    return $block;
  }

  /**
   * Attempts to find the author for the current page.
   *
   * @return \Drupal\user\UserInterface
   *   The author user entity.
   */
  private function findAuthor() {
    // Check if we are on a profile page.
    $author = \Drupal::request()->attributes->get('user');
    if (!is_null($author)) {
      return $author;
    }

    // Check if there is a node context we can pull the author from.
    $node = $this->getContextValue('node');
    if (!is_null($node)) {
      return $node->getOwner();
    }

    // @TODO: Figure out how to make this work in other situations.

    // No source found for the author.
    return NULL;
  }

}

