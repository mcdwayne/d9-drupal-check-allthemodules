<?php

namespace Drupal\available_updates_slack\Plugin\slack_notification\type;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a Security Update Notification Type
 *
 * @SlackNotificationType(
 *    id="security_updates",
 *    enabled=true,
 *    label=@Translation("Security Updates"),
 *    description=@Translation("Security Updates Notification Type Plugin Object")
 * )
 */
class SecurityUpdatesType extends TypeBase {

     /**
     * {@inheritdoc}
     */
    public function getType() {
        return $this->getPluginId();
    }

    /**
     * {@inheritdoc}
     */
    public function filterUpdates(array $modules){
        $availables = [];
        foreach ($modules as $module) {
            if (array_key_exists('security updates', $module)) {
                $availables[] = $module['info']['name'];
            }
        }

        return $availables;
    }

    /**
     * {@inheritdoc}
     */
    protected function getMessageHeading() {
        return $this->t('Some Modules have Security Updates')->render();
    }

  /**
   * {@inheritdoc}
   */
  protected function definedColor() {
    return 'danger';
  }
}
