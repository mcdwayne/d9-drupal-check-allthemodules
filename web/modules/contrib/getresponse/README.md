# GetResponse Drupal 8 Integration

Invite your visitors and commenters to join your contact list and stay in touch with updates and the latest news from you.

Create highly targeted lists captured from among visitors to your Drupal site and blog. Simply create a web form matched to your brand and install it on your site in a few simple steps. With the new GetResponse-Drupal plug-in, it’s automatic. 

The plug-in enables your visitors to subscribe via a sign-up form as well as via comment. All you need to do is add an invitation line that will be displayed in the comment form, and their email address will be added to your GetResponse list automatically.

## Getting started

1. Download the GetResponse plugin archive from releases section or [Drupal directory](https://www.drupal.org/project/getresponse). Make sure you select the plugin version that matches the version of your Drupal installation.
2. Log into your Drupal account using administrative rights.
3. Use the Drupal user interface to upload the plugin.
4. Don’t forget to activate the GetResponse plugin.
5. Navigate to Manage > Extend and find GetResponse on the list. You’ll find the extension in the Core section. Make sure the GetResponse module is selected.

## Usage

### How do I connect my GetResponse account with Drupal?

1. Log into your GetResponse account.
2. Go to My account >> Account details >> API & OAuth.
3. Copy the API key.
4. Log into your Drupal administrative panel and go to the GetResponse settings page. Click Configuration in the top menu. When the Configuration page opens, find the Web services section. Click GetResponse to display the module configuration.
5. Enter your GetResponse API key and click Connect. When the connection is successful, the plugin downloads your account data and other important information (for example, the list of forms and campaigns).
6. Click Save subscription settings.

When you connect Drupal and GetResponse, you can access your GetResponse campaigns, forms, and newsletters without leaving Drupal.

### Can visitors subscribe using a Drupal registration form?

Yes, you can allow visitors to subscribe to your newsletter when they create an account on your site. Enable this feature in GetResponse module configuration (disabled by default).

1. Log into Drupal as an administrator.
2. Go to GetResponse module settings.
3. Find the section Subscribe via registration form.
4. Select Allow subscriptions when visitors register.
5. Choose a campaign for new subscribers to receive.
6. Optionally, you can edit the message that appears as the caption of the subscribe checkbox.
7. Click Save subscription settings to confirm the changes.

That’s it. From now on, your site registration form will include a checkbox encouraging users to subscribe.

### How do I allow visitors to subscribe using a specific web form?

You can display any form you create in GetResponse. Just enable the option in the GetResponse module configuration and select which form to display.

#### Enable GetResponse forms

1. Log into Drupal as an administrator.
2. Go to GetResponse module settings.
3. Find the section Subscribe via a form.
4. Select Allow subscriptions via forms.
5. Select the form. Our Drupal integration automatically downloads all available forms and presents their names in a list.
6. Click Save subscription settings to confirm the changes.

#### Configure content blocks

Important. Configure the content blocks so your GetResponse form displays correctly and doesn’t break the site layout.

1. In the top menu, click Structure.
2. On the Structure page, navigate to Blocks.
3. Adjust the order and region of the GetResponse form. Configure the GetResponse block just like you configure any other Drupal block. If it needs tweaking to conform to your Drupal theme, click Configure and decide how the form should behave.
4. Click Save blocks to publish the revised form on your site.

That’s it. Your GetResponse form is now on your Drupal site.

### How do I allow readers to subscribe using the comment field?

You can display a special checkbox that readers can select when they comment on posts on your Drupal blog. Enable this in our module configuration (disabled by default).

1. Log into Drupal as an administrator.
2. Go to GetResponse module settings.
3. Find the section Subscribe via comments​.
4. Select Allow subscriptions when visitors comment.
5. Select a campaign for new subscribers to receive.
6. Optionally, you can edit the message that appears as the caption of the 7. subscribe checkbox.
Click Save subscription settings to confirm the changes.

That’s it. From now on, your site comments section will include a checkbox encouraging users to subscribe.

## Support

If you have any trouble installing or working the plugin feel free to contact us by [phone, email or chat](https://support.getresponse.com/). You can also get more info on [GetResponse App Center](https://connect.getresponse.com/) page.

## Contributing

We love feature requests! Bug reports are very welcome as well. You can participate in this project by [reporting an issue](https://github.com/GetResponse/drupal/issues) or creating a [pull request](https://github.com/GetResponse/drupal/pulls). Please remind that all features and fixes must stick to platform coding standards.
