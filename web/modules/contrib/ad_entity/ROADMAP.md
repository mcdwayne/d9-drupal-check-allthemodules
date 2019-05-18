# Advertising Entity: Goals and achieved milestones

Goals for 8.x-2.x:
- Implement a better method of collecting and delivering Advertising contexts.
- Drop the deprecated frontend appliance mode of 8.x-1.x.
- Major test coverage.

8.x-1.0-beta [current]:
- Implement unit and web tests. [incomplete]
- Performance optimizations:
    - Get rid of jQuery (see #2974510) [done]
    - Add viewready.js which fires as soon as possible [done]
    - Provide the ability to initialize ads via inline JS. [done]
- Fix all known major problems.
  List of known major problems:
    - https://github.com/BurdaMagazinOrg/module-ad_entity/issues/7 [fixed]
    - https://github.com/BurdaMagazinOrg/module-ad_entity/issues/12 [done]
    - https://github.com/BurdaMagazinOrg/module-ad_entity/issues/17 [done]

8.x-1.0-alpha [completed]:

- Provide a consolidated way for managing advertisement [done].
- Ensure global reusability of defined Advertisement [done].
- Provide the flexibility to add, remove and change existing ads [done].
- Provide a unified way to define targeting, turning off ads
  and other contextual tasks [done].
- Freeze the featureset [done].
- Finalize the schema, data structure and APIs [done].
