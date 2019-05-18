Set the uid 1 (super user account) username and password to random strings each
time cron runs. This stops people logging in and performing tasks as that user
and as bonus, it prevents any attackers from brute-forcing the login details.

In case of emergency, you should be able to login with uid 1 using a login url
generated via `drush uli`.

Do not install this module if you cannot use drush.
