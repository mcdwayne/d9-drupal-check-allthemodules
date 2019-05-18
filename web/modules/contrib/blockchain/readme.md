____Notes about blockchain implementation.____

Lets imagine, that each D8 installation can be blockchain node, that
can sync with each other freely.
What if blockchain databases can be any count for each blockchain node,
and each can be configured separately.

Module uses rich amount of services, so almost all functionality
can be altered in 'Symfony way'.

All functionality is unique and enclosed (fex. API and CRON), with no
dependencies, to make it easy to setup any node to make it 
compatible with each other.

This module attempts to be basis for any kind of blockchain system -
whether it is closed (single) or open, that relies on heavy POF.

Also this has auth plugin manager, so any kind of auth can be set up 
between nodes.

Connectivity requirements:
  - blockchain entity type id should match;
  - each node should have UUID blockchain node id in system (self param);
  - auth plugin should be compatible with each node;
  - 

Collisions convention:
  - generic block should match;
  - longer valid chain prevails;
  - own blocks in invalid chain should be re-added
   to blockchain by author;
  
Each blockchain has settings:
  - blockchain id;
  - blockchain node id;
  - single/multiple;
  - block pool managing:
    - immediate (batch);
    - CRON (queue);
  - announce managing:
    - queue (CRON);
    - immediate;
  - interval CRON announce;
  - interval CRON block pool;
  - pow_position;
  - pow_expression;
  - use auth;
  - whitelist/blacklist filtering;
  - allow not secure connection;
  - search blocks interval;
  - pull blocks batch size;
  
Test coverage looks big but surely not covers all for now.
