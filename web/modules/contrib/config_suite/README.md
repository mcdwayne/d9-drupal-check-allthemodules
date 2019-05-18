Config Suite is a set of small changes to the core Config system that eases the experience of working with configuration in a version controlled workflow. 

Currently, Config Suite is a small modification that allows you to reuse configuration created in one site in a new site with a different UUID.  This change allows developers working on different copies of the same site without any workarounds. This makes configuration reusable between sites because it removes the error message:

"Site UUID in source storage does not match the target storage."

This project goal is to make configuration easier to reuse, edit, and deploy without additional steps, Drush commands, or workarounds. Feel free to send Pull Requests.
