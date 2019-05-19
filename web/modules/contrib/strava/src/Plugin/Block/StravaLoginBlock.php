<?php

namespace Drupal\strava\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\strava\Api\Strava;
use Drupal\strava_athletes\Entity\Athlete;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'StravaLoginBlock' block.
 *
 * @Block(
 *  id = "strava_login_block",
 *  admin_label = @Translation("Strava Login"),
 * )
 */
class StravaLoginBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use MessengerTrait;

  /**
   * @var Strava
   */
  protected $strava;

  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param Strava $strava
   */

  public function __construct(array $configuration, $plugin_id, $plugin_definition, Strava $strava) {
    // Call parent construct method.
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->strava = $strava;
  }

  /**
   * @param ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('strava.api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Disable caching for this block.
    $build['#cache']['max-age'] = 0;

    $config = \Drupal::config('strava_configuration.settings');
    if (!empty($config->get('client_id')) && !empty($config->get('client_secret'))) {

      // Check for an access token in the user's session.
      if ($this->strava->checkAccessToken()) {
        /** @var \Drupal\strava_athletes\Manager\AthleteManager $athlete_manager */
        $athlete_manager = \Drupal::service('strava.athlete_manager');
        $athlete = $athlete_manager->loadAthleteByProperty('uid', \Drupal::currentUser()
          ->id());

        if ($athlete instanceof Athlete) {
          // Try to get Strava athlete details from the database.
          $athlete_id = $athlete->getId();
          $athlete_name = $athlete->label();
          $athlete_image = $athlete->getProfile();
        }
        else {
          // Do an api request to get Strava athlete details if we didn't store
          // it in the database.
          $athlete = $this->strava->getApiClient()->getAthlete();
          $athlete_id = $athlete['id'];
          $athlete_name = $athlete['firstname'] . ' ' . $athlete['lastname'];
          $athlete_image = $athlete['profile_medium'];
        }

        // Build a simple markup render array with athlete info.
        $athlete_url = 'https://www.strava.com/athletes/' . $athlete_id;
        $build['strava'] = [
          '#markup' => '<p><a href="' . $athlete_url . '"><img src="' . $athlete_image . '" alt="profile"></a><br/>' . t('Authenticated as @name', [
              '@name' => Link::fromTextAndUrl($athlete_name, Url::fromUri($athlete_url))
                ->toString(),
            ]) . '</p>',
        ];
      }
      // Display a link to authorize the application.
      else {
        $auth_url = $this->strava->getAuthorizationUrl();
        $build['strava'] = [
          '#type' => 'link',
          '#title' => t('Connect with Strava'),
          '#url' => Url::fromUri($auth_url),
          '#attributes' => [
            'class' => [
              'strava-auth',
            ],
          ],
        ];
      }

      return $build;
    }
    else {
      $this->messenger()
        ->addError(t('No Strava credentials were found, fill in the <a href="@config">Strava configuration form</a>.', [
          '@config' => Url::fromRoute('strava.configuration')
            ->toString(),
        ]));
    }
  }

}
