General Usage:

Redirect system generated email to your configured email domain or single email address.

[NOTE - This module will handle redirecting all email messages created and sent with drupal_mail(). Email messages sent using functions other than drupal_mail() will not be affected. For example, a contributed module directly calling the drupal_mail_system()->mail() or PHP mail() function will not be affected. All core modules use drupal_mail() for messaging, it is best practice but not mandatory in contributed modules.]

usage:
- in your test site's settings.php set:
  
  $config['mail_redirect.settings']['mail_redirect_opt'] = 'domain';
  $config['mail_redirect.settings']['mail_redirect_domain'] = 'mydomain.com';
  
OR
  
  $config['mail_redirect.settings']['mail_redirect_opt'] = 'address';
  $config['mail_redirect.settings']['mail_redirect_domain'] = 'myaddress@mydomain.com'

result:
- input $to: john_smith@about.com
- output 
  
  $to: john_smith@mydomain.com

OR

  $to: myaddress@mydomain.com

This module was developed for a multi-developer test environment where ongoing development work runs in parallel with the operation of the production site. The developers regularly sync their test site's db to that of the production server. Our general development environment provides numerous sites folders for a mutli-site setup so that each developer has their own local and server based sandboxes for testing and development. As an example:

3 developers: tom, joe, hank

site folders as:
- www.oursite.com (production site)
- oursite.joe (joe's local)
- oursite.tom
- outsite.hank
- joe.oursite.com (joe's server sandbox)
- hank.oursite.com
- tom.oursite.com

Set up subdomains on a shared host account (we use Dreamhost.com) which provides unlimited subdomains and catch-all email accounts.

e.g. mail domains:
- joe.somedomain.com
- hank.somedomain.com

Set each of these up with catch-all mail accounts.

For Joe's local development system (oursite.joe):
- in sites/oursite.joe/settings.php
- defined $config['mail_redirect.settings']['mail_redirect_domain'] = "joe.somedomain.com";

Now, when mail_redirect module is enabled all the site email will redirect to that domain. E.g.:

Janet_Smith@gmail.com -> Janet_Smith@joe.somedomain.com

All mail will be sent to one catch-all account and it is possible to see what email the system has sent out and who they have been sent to.




