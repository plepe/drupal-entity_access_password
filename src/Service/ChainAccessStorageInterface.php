<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Service;

/**
 * Defines interface for a chained service that handle access storage.
 */
interface ChainAccessStorageInterface extends AccessStorageInterface {

  /**
   * Adds another access storage.
   *
   * @param \Drupal\entity_access_password\Service\AccessStorageInterface $accessStorage
   *   The access storage to add.
   * @param int $priority
   *   Priority of the access storage.
   */
  public function addStorage(AccessStorageInterface $accessStorage, $priority): void;

}
