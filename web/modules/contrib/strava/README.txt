 ____    __
/\  _`\ /\ \__                                                        __
\ \,\L\_\ \ ,_\  _ __    __     __  __     __           __     _____ /\_\
 \/_\__ \\ \ \/ /\`'__\/'__`\  /\ \/\ \  /'__`\       /'__`\  /\ '__`\/\ \
   /\ \L\ \ \ \_\ \ \//\ \L\.\_\ \ \_/ |/\ \L\.\_    /\ \L\.\_\ \ \L\ \ \ \
   \ `\____\ \__\\ \_\\ \__/.\_\\ \___/ \ \__/.\_\   \ \__/.\_\\ \ ,__/\ \_\
    \/_____/\/__/ \/_/ \/__/\/_/ \/__/   \/__/\/_/    \/__/\/_/ \ \ \/  \/_/
                                                                 \ \_\
                                                                  \/_/

---------------------------
 Installation instructions
---------------------------

1. Place the module code in a eligible module directory and enable the module.
2. Run composer install from the drupal root to install vendor libraries.
3. Setup your Strava account so you have a Client ID and Client Secret
   (https://www.strava.com/settings/api)
4. Setup your Strava account so it knows your website and auth callback domain
   (https://www.strava.com/settings/api)
5. Configure this module to use the Client ID, Client secret and API scope the
   module configuration page. (/admin/config/development/strava)
6. Authorize a specific user via the admin/strava page or Strava login block.


Code example
------------
To perform specific api requests first do the installation steps above. The try
something like this:

<?php
// Initiate a Strava object.
$strava = new Strava();

// Perform the authentication steps.
$strava->authenticate();

// Retrieve an API client for the authenticated user.
$client = $strava->getApiClient();

// Do an API request.
$athlete = $client->getAthlete();
?>
