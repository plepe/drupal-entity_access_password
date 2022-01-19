<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Service;

/**
 * Entity type password bundle info interface methods.
 */
interface EntityTypePasswordBundleInfoInterface {

  /**
   * Get all bundles with a password field.
   *
   * @return array
   *   The list of bundles infos with a password field keyed by bundle ID and
   *   in a first level keyed by entity type ID.
   */
  public function getAllPasswordBundleInfo(): array;

}
