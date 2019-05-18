# Advertising Entity: Release notes

Like this module and want to help us making it stable?
We appreciate any contribution - have a look at the ROADMAP.md
to see the current blockers for the first release candidate.

8.x-1.0-beta32:
- DFP: Added Amp rtc-config options for the AMP view handler, removed rtc-config tag if empty.

8.x-1.0-beta31:
- DFP: Added Amp rtc-config options for the AMP view handler.

8.x-1.0-beta30:
- DFP: Added personalization options by consent for the AMP view handler.
- DFP: Issue #3020997 by Cecilina, mxh: Add an optional setting to define the
  changeCorrelator option for fetching ads.

8.x-1.0-beta29:
- New feature: Added consent awareness option for OIL.js framework.

8.x-1.0-beta28:
- Performance: The viewready.js library now includes only view handler
  libraries, which are being used by existing Advertising entities.
- DFP: External GPT.js is now only when being used by existing entities.
- API: Added a service, providing information about which type or view
  plugins are being used by existing entities.
- New feature: Added a submodule for managing generic ads.
- Bugfix: Advertising entity form might collide when view and type plugin
  have the same machine name.

8.x-1.0-beta27:
- #2994073: Increased #maxlength for targeting input.
- New feature: Added the option to initialize ads with inline JS.

8.x-1.0-beta26:
- Bugfix: Switch to array format instead of JSON for AdTech page targeting.

8.x-1.0-beta25:
- Issue #2994073 by osopolar: Can't get token to work in ad-entity input
  field "Default page targeting".
- ad_entity_adtech: Convert page targeting from JSON to array inside the
  global configuration.
Export your configuration after running database updates.

8.x-1.0-beta24:
- Bugfix: Make sure page targeting is being added before ads are initialized.

8.x-1.0-beta23:
Some bug fixes for:
- #2993184: Blocks for display configurations didn't appear right after
  new display configurations have been created.
- #2993421: Warning: Invalid argument supplied for foreach() in
  Drupal\ad_entity\TargetingCollection->collectFromCollection() (line 188 of
  modules/ad_entity/src/TargetingCollection.php).
- #2991197: When a module with new implementations for Advertising types and
  views has been installed, its plugins would not show up without a
  cache clear before.

8.x-1.0-beta22:
- Bugfix: Properly check whether the field item is empty.

8.x-1.0-beta21:
- TargetingContext::getJsonEncode now includes filter processing.
  The encoding method must equal the encoding result of collection objects.
  This change directly affects the (deprecated) frontend appliance mode.

8.x-1.0-beta20:
- #2985402: "Make the targeting system more flexible, e.g. for Tokens support".
  NOTE: This change affects the logic of displaying targeting information.
  HTML tags are now being stripped out by default, with the possibility
  of using a filter format instead. Filter formats enable you to apply
  arbitrary filter processing on the targeting information.

8.x-1.0-beta19:
- Changed: No more force-including of external libraries on admin pages.
- New feature: Added a performance tweak option for enabling script preloading.

8.x-1.0-beta18:
- Added further cookie operators (contains and exists) for consent awareness.
- Implemented test coverage for TargetingCollection class,
  ad_entity types and view builders.

8.x-1.0-beta17:
1. This release contains a lot of refactored code.
   Contains API changes, which might affect your extensions or modifications.
   Take care of it when updating your codebase. In case you have custom code
   which makes use of any ad_entity API, either JS or PHP, make sure your
   code still works. This is an extraordinary API change for a beta state,
   but it's required to improve the module's frontend efficiency.
2. Frontend appliance mode for Advertising contexts is now deprecated.
   The frontend appliance mode used to be the recommended mode in favor of
   saving resources on the server side. It has been shown though that this
   would not become true unless your appliance process is very complex.
   The global settings offer a tweak option to enforce backend appliance mode,
   so that the module does not include any context.js files anymore.
- #2974510 by arthur_lorenz, mxh: Get rid of jQuery dependency.
  NOTE: Any adEntity event is not being triggered with jQuery anymore.
  It's now a CustomEvent object, holding any parameter at event.detail.
- New feature: Added a view controller for showing ad display configurations.
  The controller is able to switch to a certain theme. With this controller,
  ad display configs can now be embedded as a generic iFrame. You can also
  create block configurations for generic ad display iFrames.
- #13 (GitHub issue): Make Theme Breakpoints JS an optional dependency.
- Bugfix (regression from 1.0-beta16): Missing Theme Breakpoints JS dependency.
  This library would now be included only when the module is available.
- Added an option to enforce backend appliance mode (see 2. above).

8.x-1.0-beta16:
- New feature: Configure to use personalized ads with consent awareness.
  Can be optionally combined with the EU Cookie Compliance module.
- Fixed #2969296 by Cecilina: JS set fixed targeting
  value for slotname and onPageLoad
NOTE: Contains API changes, which might affect your extensions or modifications.
      Take care of it when updating your codebase.

8.x-1.0-beta15:
- Performance: Added viewready.js handler for being able to load
  ads before document is ready on first page load.
- Fixed #2957766 by ashutoshsngh: Update hook running into infinite
  loop if module is enabled and there are no Ads.

8.x-1.0-beta14:
- DFP: Support native ads (#22)

8.x-1.0-beta13:
- Fixed #2953257 by Insasse: Notice undefined index AdEntity.php

8.x-1.0-beta12:
- Bugfix by @milkovsky: Fix DFP display.
- DFP: Added CSS class "empty" for empty slots.
- DFP: Added support for Roadblocks.

8.x-1.0-beta11:
- New feature: Site wide context
- Refactored DFP Javascript implementation
NOTE: Contains API changes, which might affect your extensions or modifications.
Take care of it when updating your codebase. Export your configuration after
running database updates.

8.x-1.0-beta10:
- Fixed #2949411 by Cecilina: Js Missing Out of Page Ads define
- Fixed #2947876 - Processing of targeting currently requires
  at least one DOM query on each container element. This has been solved
  by moving targeting handling into the Advertising container.
- Optimization: Added in-memory caching of context plugins.
- Third party providers are now able to define context data.
NOTE: Contains API changes, which might affect your extensions or modifications.
Take care of it when updating your codebase. Export your configuration after
running database updates.

8.x-1.0-beta9:
- Ensure proper access to context data.
NOTE: This is a security release. All sites are advised to update.

8.x-1.0-beta8:
- Bugfix: "context_object.remove is not a function"
- Changed JSON formatted output to unescaped unicode and slashes.

8.x-1.0-beta7:
- Minor bugfix: Check on exisiting id of Ad container with typeof instructor.

8.x-1.0-beta6:
- Bugfix: Make sure containers are being collected only once.
  This bug caused multiple loading of ad slots in case the provider
  doesn't sufficiently care whether the slots already have been loaded before.
- Removed Javascript behavior which created a new id for duplicate containers.
  This behavior is obsolete and should be avoided since Advertising containers
  must have a unique id provided by the backend system.

8.x-1.0-beta5:
- Javascript implementations have been refactored to improve load performance
  and to be more accessible for extending or manipulating behaviors.
- Added a sub-module which provides the ability to load fallback Advertisement.
NOTE: Code changes might affect your extensions or modifications.
Take care of it when updating your codebase.

8.x-1.0-beta4:
- Omit cache records for Advertising entities, see
  https://github.com/BurdaMagazinOrg/module-ad_entity/issues/7
  https://www.drupal.org/project/ad_entity/issues/2937615

8.x-1.0-beta3:
- Bugfix: Context fields are not included on server-side collections
  when their item list is empty.

8.x-1.0-beta2:
- Bugfix: Block caching might break when ads have been turned off before.

8.x-1.0-beta1:
- First beta release (same as 1.0-alpha29)

8.x-1.0-alpha29:
- Added in-memory caching for context data reset.

This release is a candidate for the first beta release.

8.x-1.0-alpha28:
- Added fallback for resetting context of multiple entities.
  Resetting context data is still a problem in this version, see
  https://github.com/BurdaMagazinOrg/module-ad_entity/issues/12.

This release is a candidate for the first beta release.

8.x-1.0-alpha27:
- Prevent double-caching when a block already holds the display config.

This release is a candidate for the first beta release.

8.x-1.0-alpha26:
- AdBlocks have been refactored to AdDisplay configuration entities.
  This change includes a new configuration schema and permissions.
  You'll need to export your config after running the updates.
- Added theme hook suggestions for Advertising entities and Display configs.
- Prevent a double-reset when viewing entities via their main routes.
- Created the service collection class AdEntityServices which offers
  any single service provided by the ad_entity module.
This release is a candidate for the first beta release.

8.x-1.0-alpha25:
- Replaced Xss::filter with Html::escape to avoid a possibly broken
  HTML structure by given user input.
- Added GPLv2 license.

8.x-1.0-alpha24:
- ad_entity_adtech: Switched to asynchronous loading of Advertisement.
- Added defensive checks for existing field items.
  Issue: https://github.com/BurdaMagazinOrg/module-ad_entity/issues/8
