<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password_user_data_backend\Service;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\entity_access_password\Service\AccessCheckerInterface;
use Drupal\entity_access_password\Service\AccessStorageInterface;

/**
 * Defines interface for user data backend services.
 */
interface UserDataBackendInterface extends AccessCheckerInterface, AccessStorageInterface {

  /**
   * The module name for user data storage.
   */
  public const MODULE_NAME = 'entity_access_password_user_data_backend';

  /**
   * Name key for entity access (entity_type_id||entity_uuid).
   */
  public const ENTITY_NAME_KEY = '%s||%s';

  /**
   * Name key for entity bundle access (entity_type_id||entity_bundle).
   */
  public const BUNDLE_NAME_KEY = '%s||%s';

  /**
   * Name key for global access.
   */
  public const GLOBAL_NAME_KEY = 'global';

  /**
   * Get the user data name for an entity level access.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to get entity level access name.
   *
   * @return string
   *   The user data name.
   */
  public function getEntityName(FieldableEntityInterface $entity): string;

  /**
   * Get the user data name for a bundle level access.
   *
   * @param string $entityTypeId
   *   The entity type ID.
   * @param string $bundleId
   *   The bundle ID.
   *
   * @return string
   *   The user data name.
   */
  public function getBundleName(string $entityTypeId, string $bundleId): string;

  /**
   * Get the user data name for a bundle level access.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to get bundle level access name.
   *
   * @return string
   *   The user data name.
   */
  public function getBundleNameFromEntity(FieldableEntityInterface $entity): string;

  /**
   * Get the user data name for the global level access.
   *
   * @return string
   *   The user data name.
   */
  public function getGlobalName(): string;

}
