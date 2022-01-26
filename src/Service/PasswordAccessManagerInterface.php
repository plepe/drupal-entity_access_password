<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Service;

use Drupal\Core\Entity\EntityInterface;

/**
 * Password access manager interface methods.
 */
interface PasswordAccessManagerInterface {

  /**
   * Machine name of the view mode to use when the entity should be protected.
   */
  public const PROTECTED_VIEW_MODE = 'password_protected';

  /**
   * Check if the view mode of an entity is protected.
   *
   * @param string $view_mode
   *   The view mode to check for.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check access for.
   *
   * @return bool
   *   TRUE if protected. FALSE otherwise.
   */
  public function isEntityViewModeProtected(string $view_mode, EntityInterface $entity): bool;

  /**
   * Check if an entity label is protected.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check access for.
   *
   * @return bool
   *   TRUE if protected. FALSE otherwise.
   */
  public function isEntityLabelProtected(EntityInterface $entity): bool;

  /**
   * Check if the current user has access to the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check access for.
   *
   * @return bool
   *   TRUE if the user has access. FALSE otherwise.
   */
  public function hasUserAccessToEntity(EntityInterface $entity): bool;

}
