#Stop Forum Spam Client

##Description
Stop Forum Spam Client is a module for Drupal 8.x that makes use of the [stopforumspam.com](https://stopforumspam.com/) 
services for blocking spam, spammers and spambots.  
The module aims at preventing spammers from registering and blocks known spammers that try to post "content".

##Features
* Prevents known spammers from registering as user
* Detects and disables registered users that are known spammers
* Blocks known spammers from posting content
* Blocks known spammers from posting comments
* Blocks known spammers from sending contact mail
* Maintainer can report individual content and comments as spam
* Maintainer can report individual account and contributions by that account
* Whitelisting of usernames, e-mail addresses and IP addresses
* Can caches api calls to stopforumspam.com
* Can delay form submissions by known spammers

##Installation
Install as usual, see [Installing Drupal 8 Modules](https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules) 
under [Extending Drupal 8](https://www.drupal.org/docs/8/extending-drupal-8) for further instructions.

##Configuration
After installing and enabling the module it only prevents user registration by known spammers by default.

Go to menu Configuration >> System >> Stop Forum Spam Client.


####Check activities
You can choose for which activities SFS Client needs to block known spammers.

####Flood protection
You can select whether to use caching for api calls to stopforumspam.com, and 
whether to delay form submissions by known spammers. Caching is adviced.
 
####Spam block criteria
You can select for which items (name, e-mail address, ip address) to check 
whether a user is known by stopforumspam.com and how many times the item needs 
to occur in their database before you consider it to be spam. The defaults 
(that exclude the username check) are usually efficient in correct 
identification of a spammer.
 
####Whitelists
You can whitelist usernames, e-mail addresses and ip addresses to exclude them 
from spam analysis and prevent them from being send to stopforumspam.com for 
checking.
 
####Scan user accounts
You can choose to scan existing user account for being a known spammer. It is a 
functionality that needs to be used for a limited time, unless spammers manage 
to register at your site at a regular basis. In that case you will need to reset 
the "Continue scanning after this user id" value regularly.
 
####Block message
Text is being shown to a user in the interface when the form submission is being 
blocked.

####stopforumspam.com API Key
You need an api key for registering spam to stopforumspam.com. You do not need 
the api key for checking spammers at stopforumspam.com.

#### Use secure protocol (https)
Https is the preferred protocol to use, but stopforumspam.com also allows the 
use of http.

###More information
[Project page](https://www.drupal.org/project/sfs)
