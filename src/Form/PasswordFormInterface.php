<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Form;

/**
 * Defines interface for password form.
 */
interface PasswordFormInterface {

  /**
   * Prevent form cache problem of displaying the same form multiple times.
   *
   * @param string $suffix
   *   The form ID suffix.
   */
  public function setFormIdSuffix(string $suffix) : void;

}
