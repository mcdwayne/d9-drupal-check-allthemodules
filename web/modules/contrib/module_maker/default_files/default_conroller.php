[[phtag]]
namespace Drupal\[[module_name]]\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Defines [[controller_name]] class.
 */
class [[controller_name]] extends ControllerBase {

  /**
   * Display the markup.
   *
   * @return array
   *   Return markup array.
   */
  public function content() {
    return [
      '#type' => 'markup',
      '#markup' => $this->t('Hello, World!'),
    ];
  }

}