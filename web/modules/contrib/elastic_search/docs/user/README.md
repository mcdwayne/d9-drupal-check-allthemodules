# Overview


Although elastic can decide on field types when you simply 'throw data at it' to get the best quality results you need to spend some time setting it up.
Although the elastic search module aims to deal with the complicated issues of index management in the background it expects you to spend some time configuring the mappings for your data correctly.
However once this is done you will not need to reguarly interact with the module and it will perform CRUD actions on elastic documents without any manual interaction.

A general setup involves the following steps:

1. [Server Configuration](./server.md)
2. [Fieldable Entity Map Configuration](./fem.md)
3. [Index Generation](./indices.md)
4. [Mapping Push](./indices.md)
5. [Document Indexing](./document_indexing.md)
