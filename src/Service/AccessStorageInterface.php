<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Service;

use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Defines interface for storing password access to entities.
 */
interface AccessStorageInterface {

  /**
   * Stores that the user has access to this entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity the access is stored.
   */
  public function storeEntityAccess(FieldableEntityInterface $entity) : void;

  /**
   * Stores that the user has access to the entity's bundle.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity the access is stored for the bundle.
   */
  public function storeEntityBundleAccess(FieldableEntityInterface $entity) : void;

  /**
   * Stores that the user has a global access.
   */
  public function storeGlobalAccess() : void;

}
