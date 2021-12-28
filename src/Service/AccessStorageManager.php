<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Service;

use Drupal\Core\Entity\FieldableEntityInterface;

/**
 * Provides a password access storage manager.
 */
class AccessStorageManager implements ChainAccessStorageInterface {

  /**
   * Holds arrays of storages, keyed by priority.
   *
   * @var array
   */
  protected array $storages = [];

  /**
   * Holds the array of storages sorted by priority.
   *
   * Set to NULL if the array needs to be re-calculated.
   *
   * @var \Drupal\entity_access_password\Service\AccessStorageInterface[]|null
   */
  protected $sortedStorages;

  /**
   * {@inheritdoc}
   */
  public function addStorage(AccessStorageInterface $storage, $priority) : void {
    $this->storages[$priority][] = $storage;
    // Force the storages to be re-sorted.
    $this->sortedStorages = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function storeEntityAccess(FieldableEntityInterface $entity) : void {
    foreach ($this->getSortedStorages() as $storage) {
      $storage->storeEntityAccess($entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function storeEntityBundleAccess(FieldableEntityInterface $entity) : void {
    foreach ($this->getSortedStorages() as $storage) {
      $storage->storeEntityBundleAccess($entity);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function storeGlobalAccess() : void {
    foreach ($this->getSortedStorages() as $storage) {
      $storage->storeGlobalAccess();
    }
  }

  /**
   * Returns the sorted array of storages.
   *
   * @return \Drupal\entity_access_password\Service\AccessStorageInterface[]
   *   An array of storage objects.
   */
  protected function getSortedStorages() {
    if (!isset($this->sortedStorages)) {
      // Sort the storages according to priority.
      krsort($this->storages);
      // Merge nested storages from $this->storages into $this->sortedStorages.
      $this->sortedStorages = [];
      foreach ($this->storages as $storages) {
        $this->sortedStorages = array_merge($this->sortedStorages, $storages);
      }
    }
    return $this->sortedStorages;
  }

}
