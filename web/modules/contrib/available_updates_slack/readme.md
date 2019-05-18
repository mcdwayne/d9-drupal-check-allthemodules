# Slack Update Notifier

### Descriptions

An easy to use module that lets you get update notification of your Drupal 8 Project. It uses the plugin system to create Notification Types and based on the notifications are going to be based on that type. 

Currently we support only `Security Updates` notifications, please feel free to post other notification types.

### Requirements

- Slack Webhook URL to be inserted in the following endpoint: `/admin/config/system/available-updates-slack/settings`
- On the `settings.php` add the following line:
```
$settings['slack_notification'] = true;
```
