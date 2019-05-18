Enables modules to use AWS SNS API, and integrates with SMS Framework.

# Installation

 1. Download [SMS Framework][sms-framework] and its dependencies.
 2. Install module as per [standard procedure][drupal-module-install].
 3. This module has composer dependencies, see [instructions][composer-dependencies]
    for information on how to resolve these dependencies.

# Configure Amazon AWS SNS.

 1. Check the [supported countries documentation][sms-supported-countries] to check what region is supporting SMS services.
 2. IMPORTANT. By default, the spend limit is set to 1.00 USD. If you want to raise the limit, submit an SNS Text 
    Messaging case with AWS Support. Check the [SMS preference documentation][aws-sms-preferences]
 3. Read the [AWS configuration information][aws-sms-sns-readme] for information on how to setup correct permissions
    and other settings.

# Configuration

 1. Create a SMS gateway plugin at _/admin/config/smsframework/gateways_.
 2. Fill out the form, click 'Save' button.
 3. The page will reload, fill out the _Authorization token_ field. Click 
    'Save'.
    
# Testing

If you need to test, you should take advantage of the _SMS Devel_ module
bundled with _SMS Framework_. It is accessible at _Configuration » Development »
Test SMS_.

[aws-sms-sns-readme]: https://docs.aws.amazon.com/sns/latest/dg/SMSMessages.html
[sms-supported-countries]: https://docs.aws.amazon.com/sns/latest/dg/sms_supported-countries.html
[aws-sms-preferences]: https://docs.aws.amazon.com/sns/latest/dg/sms_preferences.html
[aws]: https://aws.amazon.com
[sms-framework]: https://drupal.org/project/smsframework
[drupal-module-install]: https://www.drupal.org/docs/8/extending-drupal/installing-contributed-modules "Installing Contributed Modules"
[composer-dependencies]: https://www.drupal.org/docs/8/extending-drupal/installing-modules-composer-dependencies "Installing modules' Composer dependencies"
