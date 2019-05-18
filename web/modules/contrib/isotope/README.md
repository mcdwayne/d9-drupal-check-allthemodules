## Should I use a local library copy, or hotlink from a CDN?

That's entirely your choice. You should consider:
* Performance implications. There is a great explanation here http://stackoverflow.com/a/21099939.
* Security implications. Only use trusted CDNs.
* Access implications. If your site won't have access to the CDN sometimes (e.g. an intranet).

## Roadmap:

* Libraries API module for D8 is not yet stable. We are using some deprecated functions that we'll tidy up once we get a clear best practice method of using shared third-party libraries in D8.
* Context module for D8 is not yet stable. For now, you'll need to define a presentation each time you output a grid. Once a best-practice emerges for handling per-page contexts, we'll expose the presentation selection as a reaction.
