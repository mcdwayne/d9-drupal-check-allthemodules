INTRODUCTION
============
IPFS -- A module for the stores the hash value of the node being inserted or
updated. This module created an custom entity which will store the node id and
hash value for the nodes. It also create a menu link "ipfs" which will show the
nodes and their hash value with the value retrieved from hash value from IPFS
server.
https://www.ipfs.io

INSTALLATION
============
1.Extract the zip file in modules folder.
2.Go to admin > extend. Enable the module.
3.Make sure IPFS Client is running at your server.
Refer https://ipfs.io/docs/install/.

Run the following commands after succesfully installing the IPFS Client.
1) ipfs init
2) ipfs daemon

Replace the following lines from add function in
vendor/cloutier/php-ipfs-api/src/IPFS.php :-
	$req = json_decode($req, TRUE);
	return $req['Hash'];
with :-
	return $req;

USAGE
============

1.After installing module , Just create or update any node.
2.View list of hash values: admin > structure > IPFS Hash list.

MAINTAINER
============

Anmol Goel (https://drupal.org/u/anmolgoyal74)
Gaurav Kapoor(https://drupal.org/u/gauravkapoor)
