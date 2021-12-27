<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Service;

/**
 * Defines interface for a chained service that handle access check.
 */
interface ChainAccessCheckerInterface extends AccessCheckerInterface {

  /**
   * Adds another access checker.
   *
   * @param \Drupal\entity_access_password\Service\AccessCheckerInterface $accessChecker
   *   The access checker to add.
   * @param int $priority
   *   Priority of the access checker.
   */
  public function addChecker(AccessCheckerInterface $accessChecker, $priority) : void;

}
