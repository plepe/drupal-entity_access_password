ENTITY ACCESS PASSWORD MIGRATION EXAMPLES LESSON
------------------------------------------------

USAGE
-----

The Entity Access Password Migration Examples Lesson module allows migrating D7
Protected Node Password entries to a D9 Entity Access Password custom field.

The Entity Access Password Migration Examples Lesson module needs some adaptions
to each custom environment (in our example we migrated the node type "lesson"):

* src/Plugin/migrate/process/ProtectedNode.php

```php
  // Replace bundle by node type protected with protected node.
  $query->condition('node.type', 'bundle', '=');
```

* config/install/migrate_plus.migration.protected_node.yml

  * replace node_lesson by your custom node migration name
  * replace field_password_protect by the field name used in customers
    environment.
    The password field has to be created and configured manually at the target
    entity type before running the entity_access_password migration.
  * replace migration_db_key by your custom migration DB key
  * replace lesson by your custom node type
  * replace langcode by your custom langcode

Enable the module.

Run Migrate Protected Node entries migration after your node migrations with the
following Drush command:

```bash
drush mim protected_node --update
```
