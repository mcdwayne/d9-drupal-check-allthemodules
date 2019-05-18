<?php

namespace Drupal\desk_net\Collection;

class NoticesCollection {

  /**
   * Getting notice message.
   *
   * @param int $code
   *   The code message.
   *
   * @return string
   *   The message text.
   */
  public static function getNotice($code) {
    // Generate custom links.
    $DN_credentials_link = \Drupal\Core\Link::fromTextAndUrl(t('Desk-Net Credentials'), \Drupal\Core\Url::fromUri("internal:/admin/config/desk-net/desk-net-credentials"))->toString();
    $support_link = \Drupal\Core\Link::fromTextAndUrl('support@desk-net.com', \Drupal\Core\Url::fromUri("mailto:support@desk-net.com"))->toString();

    $message_list = array(
      1 => t('Story update successfully sent to Desk-Net'),
      2 => t('Cannot update story in Desk-Net. The Thunder module could not find a
     corresponding story ID in Desk-Net. Code: 01'),
      3 => t('Cannot update story in Desk-Net. There is no corresponding story in
     Desk-Net. Code: 02'),
      4 => t("Cannot update the story in Desk-Net. Reason unknown. Please contact
     Desk-Net support at @link. Code: 03", array('@link' => $support_link)),
      5 => t("The Desk-Net API login credentials are not valid or have not been 
     entered. Please check the settings on the page @link in the Desk-Net plugin. Code: 04", array('@link' => $DN_credentials_link)),
      6 => t('Cannot create story in Desk-Net. Reason unknown. Code: 05'),
      7 => t('Connection successfully established.'),
      8 => t('Connection could not be established.'),
      9 => t('There is no connection from Thunder to Desk-Net. Code: 07'),
      10 => t('There is no connection from Desk-Net to Thunder. Code: 08'),
      11 => t("Cannot create story in Desk-Net. Publication date doesn't match platform schedule."),
      12 => t("Cannot update story in Desk-Net. Publication date doesn't match platform schedule."),
      13 => t('The configuration options have been saved.'),
    );

    return $message_list[$code];
  }
}