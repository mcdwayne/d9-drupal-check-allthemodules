<?php

/**
 * @file
 * Contains \Drupal\noughts_and_crosses\Form\Board\BoardFormBase.
 */

namespace Drupal\noughts_and_crosses\Form\Board;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\SessionManagerInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class BoardFormBase extends FormBase {

  /**
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * @var \Drupal\Core\Session\SessionManagerInterface
   */
  private $sessionManager;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $currentUser;

  /**
   * @var \Drupal\user\PrivateTempStore
   */
  protected $store;

  /**
   * Constructs a \Drupal\noughts_and_crosses\Form\Board\BoardFormBase.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   * @param \Drupal\Core\Session\SessionManagerInterface $session_manager
   * @param \Drupal\Core\Session\AccountInterface $current_user
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, SessionManagerInterface $session_manager, AccountInterface $current_user) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->sessionManager = $session_manager;
    $this->currentUser = $current_user;

    $this->store = $this->tempStoreFactory->get('board_data');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('session_manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Start a manual session for anonymous users.
    if (!isset($_SESSION['board_form_holds_session'])) {
      if ($this->currentUser->isAnonymous()) {
        $_SESSION['board_form_holds_session'] = true;
        $this->sessionManager->start();
      }      
    }

    $form = array();
    
    $form['#attributes']['class'][] = 'noughts-and-crosses-board';
    $form['#attached']['library'][] = 'noughts_and_crosses/noughts-and-crosses-styling';
    $form['game_instructions'] = array(
      '#prefix' => '<div class="game-rules">' . $this->t("Instructions") . '<div class="game-instructions">',
      '#markup' => $this->noughts_and_crosses_instructions(),
      '#suffix' => '</div></div>',
      '#weight' => 0,
    );
    $form['game-message'] = array(
      '#prefix' => '<div id="game-messages">',
      '#suffix' => '</div>',
    );
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',      
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
      '#weight' => 10,
    );

    return $form;
  }

  /**
   * Dispalys a basic instructions to end user(s) about the game.
   */
  protected function noughts_and_crosses_instructions() {    
    // Show the instructions before playing.
    $markup = $this->t("Rules of the Game :") . "<br />";
    $markup .= "-----------------------";
    $markup .= "<ol>";
      $markup .= "<li>" . $this->t("The game is to be played between -");
        $markup .= "<ul>";
          $markup .= "<li>" . $this->t("Two people i.e. Player 1 v/s Player 2") . "<em> <strong>" . $this->t("OR") . "</strong></em>" . "</li>";
          $markup .= "<li>" . $this->t("COMPUTER (Player 1) v/s HUMAN (Player 2)") . "</li>";
        $markup .= "</ul>";
      $markup .= "</li>";
      $markup .= "<li>" . $this->t("One of the player chooses 'X' and the other 'O' to mark their respective cells.") . "</li>";
      $markup .= "<li>" . $this->t("In our case, Player 1 will have 'X' assigned by default.") . "</li>";
      $markup .= "<li>" . $this->t("The game starts with Player 1 and the game ends when one of the players has one whole row / column / diagonal filled with his/her respective character ('X' or 'O').") . "</li>";
      $markup .= "<li>" . $this->t("First player completing the whole row / column / diagonal will be declared as winner.") . "</li>";
      $markup .= "<li>" . $this->t("If no one wins, then the game is said to be draw.") . "</li>";
    $markup .= "</ol>";
    return $markup;
  }

  /**
   * Saves the data from the Board form.
   */
  protected function saveData() {
    // Logic for saving data goes here...
    $this->deleteStore();
    drupal_set_message($this->t('Thank you for playing the game.  Have a good time ahead !'));
  }

  /**
   * Helper method that removes all the keys from the store collection used for
   * the Board form.
   */
  protected function deleteStore() {
    $keys = ['play_type', 'first_move'];
    foreach ($keys as $key) {
      $this->store->delete($key);
    }
  }
}
