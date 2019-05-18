The Matrix API module provides a library for interacting with a Matrix server using the Client API, and may eventually
support the Application Service API.

Matrix is "an open standard for decentralized persistent communication". This module is being developed to connect
Drupal sites to Matrix chat rooms. It is in its very early stages, and does not yet provide any direct UI for using
Matrix, but is meant to act as an SDK for integrating Drupal with Matrix.

Current Use Cases
=================

1. The initial use case is to post arbitrary messages into a Matrix room. For example, whenever a new node, comment,
or user is added to a Drupal site, you can post a message into a Matrix room. To support this, the initial Matrix
integration involves logging in/getting an access_token, listing Matrix rooms the user account has access to, joining
a room by alias or roomId, and posting a message.


Planned Use Cases
=================

Future scenarios we want to support:

1. Set a state in a room -- update room topic, set arbitrary state (requires sufficient power in the room to update
state)

2. Rules integration -- Send a Matrix message via Rules

3. Embedded chat -- Embed a Matrix room in a Drupal page or block

4. Application service -- Add content in Drupal based upon events in Matrix (This is essentially creating a
privileged Server API that receives Matrix events)


How to get started
==================

1. Create a Matrix user account, either on your own Matrix Home Server or on http://matrix.org.

2. Install and enable this module.

3. Go to admin/config/matrix_api/matrixapisettings (under Admin -> Config -> Web Services), and set a Homeserver URL,
 and provide either an access_token OR a user and password. If you supply a user and password, the module will
 attempt to log into the homeserver and retrieve an access_token.

4. In a custom module, load the matrix_api.matrixclient service, and call the methods on the object you get.

For example:

$matrixClient = \Drupal::service('matrix_api.matrixclient');

$roomId = $matrixClient->join('#myroom:matrix.example.com');

$matrixClient->sendMessage($roomId, 'This is a post from Drupal');
