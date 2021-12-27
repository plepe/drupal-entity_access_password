<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Service;

/**
 * Default password access manager service.
 */
class PasswordAccessManager implements PasswordAccessManagerInterface {

  /**
   * Machine name of the view mode to use when the entity should be protected.
   */
  public const PROTECTED_VIEW_MODE = 'password_protected';

}
