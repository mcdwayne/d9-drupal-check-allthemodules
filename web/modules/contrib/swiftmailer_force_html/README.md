**ABOUT**

This Drupal 8 module creates a very simply Swift Mailer mail plugin which checks each mail body for HTML tags, and 
forces that email to be in HTML format if any is found.

**DEPENDENCIES**

- [Swift Mailer](https://www.drupal.org/project/swiftmailer)
- [Mail System](https://www.drupal.org/project/mailsystem)

**INSTALLATION**

Enable the module and visit /admin/config/system/mailsystem to select "Swift Mailer (Force HTML)" as your mail plugin.

**USAGE**

Once configured, any mail body with HTML tags in it should render as HTML.

**CONTACT**

Current maintainers:
* [bmcclure](https://www.drupal.org/user/278485)
