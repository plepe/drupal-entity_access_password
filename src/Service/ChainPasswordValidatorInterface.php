<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Service;

/**
 * Defines interface for a chained service that handle password validation.
 */
interface ChainPasswordValidatorInterface extends PasswordValidatorInterface {

  /**
   * Adds another access validator.
   *
   * @param \Drupal\entity_access_password\Service\PasswordValidatorInterface $accessValidator
   *   The access validator to add.
   * @param int $priority
   *   Priority of the access validator.
   */
  public function addValidator(PasswordValidatorInterface $accessValidator, $priority) : void;

}
