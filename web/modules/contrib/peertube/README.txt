# PeerTube

This module allows you to add PeerTube videos as Remote videos from the media module in drupal core.

<h3>Why do I need this module?</h3>

PeerTube supports oEmbed and Remote videos from Drupal core are based on this protocol. That's cool because that means that Drupal core supports PeerTube natively! But tere are two problems and that's why this module exists:
- By default, the media module accepts only a few websites defined by this URL: https://oembed.com/providers.json
This URL is inside the setting oembed_providers_url but the core does not allows to modify it yet: See  <a href="https://www.drupal.org/project/drupal/issues/2999018">#2999018</a>
- The second one is more relative to PeerTube itself. PeerTube is a decentralized network of video platforms, consequently the videos can have URLs with a lots of different domain names. But the core is based on a fixed list stored inside a JSON file pointed by the oembed_providers_url setting. 

<h3>What does this module do?</h3>

This module will override the oembed_providers_url setting with a local controller that will keep the default list and add PeerTube instances to that list.
In order to do that, a settings form gives you the choice to set the default oembed providers url used by the custom controller. By default, the module will keep the same as Drupal core. And then you can also add different domain names corresponding to the PeerTube instances you want to allow.
 An issue has been opened in order to avoid to change the oembed_providers_url setting and just use a hook in order to alter the list of the providers. See <a href="https://www.drupal.org/project/drupal/issues/3008119">#3008119</a>

<h3>Is it ready?</h3>

Yes. But it supports only one PeerTube instance at a time. The drupal core doesn't allow mutiple websites for a same provider. An issue has been created in order to fix that: <a href="https://www.drupal.org/project/drupal/issues/3008712">#3008712 oEmbed implementation has a centralized approach</a>


