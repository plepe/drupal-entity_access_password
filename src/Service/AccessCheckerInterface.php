<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Service;

use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Defines interface for access check against entities.
 */
interface AccessCheckerInterface {

  /**
   * Check if the current user has access to the entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to check access for.
   *
   * @return bool
   *   TRUE if the user has access. FALSE otherwise.
   */
  public function hasUserAccessToEntity(FieldableEntityInterface $entity): bool;

  /**
   * Check if the current user has access to the entity's bundle.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to check bundle access for.
   *
   * @return bool
   *   TRUE if the user has access. FALSE otherwise.
   */
  public function hasUserAccessToBundle(FieldableEntityInterface $entity): bool;

  /**
   * Check if the current user has global access.
   *
   * @return bool
   *   TRUE if the user has access. FALSE otherwise.
   */
  public function hasUserGlobalAccess(): bool;

}
