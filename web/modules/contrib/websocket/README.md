Real time Drupal powered by Ratchet.

INSTALLATION

Preferred way to download a module with composer.
Please run 'composer require drupal/websocket'
from the webroot directory (neither inside the core directory nor
inside websocket module directory).

USAGE

Module adds drush command 'start-websocket-server' to start websocket
server powered by Ratchet library. This should work on local machine.
More information about Ratchet:
http://socketo.me/

Module adds 'Chat' custom block. Place block to some region
to enable websocket chat integration.
 
For production deployment please refer to http://socketo.me/docs/deploy.
