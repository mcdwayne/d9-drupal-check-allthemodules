# Airship Web Notifications

## How to use

1. Install module and visit the configuration page at `admin/config/services/urban-airship-web-push-notifications`.

2. In the *Credentials* tab, set the **App Key** and the **App Master Secret** form items. Credentials can be found in your Airship dashboard.

3. In the *SDK Bundle* tab, upload the SDK zip file from your Airship account. For more information about the contents of the SDK bundle, please see the [Airship documentation](https://docs.urbanairship.com/platform/web/#getting-started).

4. In the *Opt-in Prompt* tab, configure the behavior and appearance of your opt-in prompt. For additional details on creating or using a custom opt-in, please see the [Airship documentation](https://docs.urbanairship.com/platform/web/#registration-ui).

5. Enable notifications on the desired content types by visiting that content types edit page. Select the *Airship Web Push Notifications* tab and check the **Enable notifications** checkbox. You may also set the default notification text to be used by all nodes of that content type.

6. Once web notifications are enabled for a specific content type, add a new node or edit an existing node.

7. Select the *Airship Web Push Notifications* tab. From here, you can set the notification title, icon, text and action URL for the notification you wish to send. If the title, icon, or action URL fields are left blank, the defaults from your configuration file will be used for these fields. You may also select the “Require Interaction” option if you wish, which requires the end user to interact with the notification in order to remove it from their computer screen. Finally, you may select the **On content save** option to instantly send a web push notification upon saving your node or select the **Schedule notification** option to schedule your notification for a date and time in the future. 

## Important

Mixed HTTPS sites will need to ensure that both of these paths are available on the secure site:
* `/push-worker.js`
* `/web-push-secure-bridge.html`

Make sure to clear Drupal cache after you switch `Prompt for Notifications` mode in order to see the changes.

In addition, these sites will need to have a registration page that is served over HTTPS for users to opt in to notifications. For more information, see the [Airship documentation on secure integration](https://docs.urbanairship.com/platform/secure-integration/).

## API

You can use `hook_urban_airship_web_push_notifications_alter()` in your custom module to programmatically alter the notification title, body, icon and action url as needed. You may also use it to programmatically set Require Interaction on your notification. This hook has access to the current node object, so it is possible to make alterations to the notification based on things like a taxonomy field value or a date associated with that node.

## Theming

The notification template can be found at `templates/urban-airship.html.twig` in the root directory of the module. 
Further alterations to the notifications and template suggestions can be created by overriding `THEME_preprocess_urban_airship()` in your own theme.

## Settings.php overrides

If the unzip option is not available on the SDK Bundle page it can be configured in your settings.php. 

// Contents of `push-worker.js` file
`$config['urban_airship_web_push_notifications.configuration']['push-worker']['js'] = "";`

// Contents of `snippet.html` file
`$config['urban_airship_web_push_notifications.configuration']['snippet']['html'] = "";`

// Contents of `secure-bridge.html` file
`$config['urban_airship_web_push_notifications.configuration']['secure-bridge']['html'] = "";`

## Troubleshooting

Using `fast_404` module could generate 404 error on `/push-worker.js` and `/web-push-secure-bridge.html` paths. You will have to exclude those paths in the configuration.

`$settings['fast404_exts'] = '/^(?!\/push-worker\.js)';`