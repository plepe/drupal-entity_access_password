<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Service;

use Drupal\entity_access_password\Plugin\Field\FieldType\EntityAccessPasswordItem;

/**
 * Password validator interface methods.
 */
interface PasswordValidatorInterface {

  /**
   * Validate a password against a field instance of an entity.
   *
   * So the following infos are accessible:
   *   - the password value for this entity field,
   *   - the field config for per bundle password and password hierarchy
   *     management,
   *   - the global password with config factory.
   *
   * @param string $password
   *   The password to validate.
   * @param \Drupal\entity_access_password\Plugin\Field\FieldType\EntityAccessPasswordItem
   *   The field item.
   *
   * @return bool
   *   TRUE if access is granted. FALSE otherwise.
   */
  public function validatePassword(string $password, EntityAccessPasswordItem $fieldItem) : bool;

}
