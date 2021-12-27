<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Service;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines interface for storing password access to entities.
 */
interface AccessStorageInterface {

  /**
   * Stores that the user has access to this entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity the access is stored.
   */
  public function storeEntityAccess(ContentEntityInterface $entity) : void;

  /**
   * Stores that the user has access to the entity's bundle.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity the access is stored for the bundle.
   */
  public function storeEntityBundleAccess(ContentEntityInterface $entity) : void;

  /**
   * Stores that the user has a global access.
   */
  public function storeGlobalAccess() : void;

}
