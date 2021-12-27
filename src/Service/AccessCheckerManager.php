<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Service;

/**
 * Provides a password access checker manager.
 */
class AccessCheckerManager implements ChainAccessCheckerInterface {

  /**
   * Holds arrays of checkers, keyed by priority.
   *
   * @var array
   */
  protected $checkers = [];

  /**
   * Holds the array of checkers sorted by priority.
   *
   * Set to NULL if the array needs to be re-calculated.
   *
   * @var \Drupal\entity_access_password\Service\AccessCheckerInterface[]|null
   */
  protected $sortedCheckers;

  /**
   * {@inheritdoc}
   */
  public function addChecker(AccessCheckerInterface $checker, $priority) : void {
    $this->checkers[$priority][] = $checker;
    // Force the checkers to be re-sorted.
    $this->sortedCheckers = NULL;
  }

  /**
   * {@inheritdoc}
   */
//  public function storeEntityAccess(ContentEntityInterface $entity) : void {
//    foreach ($this->getSortedCheckers() as $checker) {
//      $checker->storeEntityAccess($entity);
//    }
//  }

  /**
   * Returns the sorted array of checkers.
   *
   * @return \Drupal\entity_access_password\Service\AccessCheckerInterface[]
   *   An array of checker objects.
   */
  protected function getSortedCheckers() {
    if (!isset($this->sortedCheckers)) {
      // Sort the checkers according to priority.
      krsort($this->checkers);
      // Merge nested checkers from $this->checkers into $this->sortedCheckers.
      $this->sortedCheckers = [];
      foreach ($this->checkers as $checkers) {
        $this->sortedCheckers = array_merge($this->sortedCheckers, $checkers);
      }
    }
    return $this->sortedCheckers;
  }

}
