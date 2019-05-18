Altering Entity Routes
The Altering Entity Routes is based on Entity Construction Kit (ECK) . It can alter routes of entities created by eck.
As we know,the Entity Construction Kit (ECK) builds upon the entity system to create a flexible and extensible data modeling system both with a UI for site builders, and with useful abstractions (classes, plugins, etc) to help developers use entities with ease.
The entities` paths are like "admin/structure/eck/entity/$eck_type->id /{$eck_type->id }" when we create one entity type use eck e.g. admin/structure/eck/entity/book/1("book" entity type is created by eck). And this path will use admin themes,that is not what we want.
Now this Altering Entity Routes module could alter eck`s path to "/$eck_type->id /{$eck_type->id }" automatically.

Dependencies:
eck(>= 8.x)