ENTITY ACCESS PASSWORD
----------------------

* Introduction
* Requirements
* Similar modules
* Installation
* Configuration
* Maintainers


INTRODUCTION
------------

The Entity Access Password module allows to restrict access to fieldable
entities by requiring to enter a password.

The module provides a new field type: Password protection.

The administrator can choose per field instance the behavior (among other
settings):
* the password level (can be cumulative):
  * entity password
  * bundle password
  * global password
* the protected view modes

When a protected content entity is displayed in a protected view mode, if the
user does not have access then it is the "Password protected" view mode that is
displayed instead. That way, the administrator have a very flexible way to
control how the password form is displayed, with the field formatter and view
mode granularity.

Warning! As the module only switches the displayed view mode, a user can still
access the content if exposed through web services for example.

The modules does not implement hook_entity_access() to allow to still see
password protected entities in listings otherwise the user would not be able to
access the form to unlock the entity with a direct link.


REQUIREMENTS
------------

There is no special requirement for this module.


SIMILAR MODULES
---------------

 * Protected Pages (https://www.drupal.org/project/protected_pages):
   Protected Pages uses the path to protect your page so you can password
   protect Views pages for example. With Entity Access Password, content
   creators can add the password as they create content, they don't have to go
   to another configuration page.


INSTALLATION
------------

* Install as you would normally install a contributed Drupal module. See:
  https://www.drupal.org/docs/extending-drupal/installing-modules
  for further information.
* You need to enable at least one module providing an access backend otherwise
  the module loses its interest. By default there are two sub-modules providing
  an access backend:
  * Entity Access Password Session Backend
    (entity_access_password_session_backend): Allows to store granted access in
    the session.
  * Entity Access Password User Data Backend
    (entity_access_password_user_data_backend): Allows to store granted access
    in the user data backend for more persistent access storage. Does nothing
    for anonymous users.


CONFIGURATION
-------------

This is a straight forward configuration example, please read the documentation
pages (https://www.drupal.org/docs/contributed-modules/entity-access-password)
for detailed instructions:
* Enable the Entity Access Password module on your site.
* You can configure a global password on the configuration page
  (/admin/config/content/entity_access_password/settings).
* Add a "Password protection" field to a content type.
* Configure the "Password protected" view mode to display the password field.
* Select the behavior on this field instance:
  * entity password and/or bundle password and/or global password
  * which view mode will be protected
* Go to one content edit form where the field is added and enable
  password protection.
* View your entity as an anonymous user, the content should be rendered in the
  "Password protected" view mode and so the password field should be displayed.


MAINTAINERS
-----------

Current maintainers:
* Florent Torregrosa (Grimreaper) - https://www.drupal.org/user/2388214

This project has been sponsored by:
* Smile - https://www.drupal.org/smile
  Sponsored initial port from Protected Node D7.
* Ludwig-Maximilians-Universität München - https://www.lmu.de
  Sponsored initial port from Protected Node D7.
