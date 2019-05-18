## Domain Access Sitemap

Domain Access Sitemap module generates sitemaps for active domains.
 It requires Domain Access and Simple Sitemap modules.

### Steps to generate map:

- Click save on form
- Set generator
- Set generate form
- Start generation
  - Set batch info
  - Foreach domain generate:
    - Add "generateCustomUrls" operation
    - Add "generateBundleUrls" operations for each entity bundle
