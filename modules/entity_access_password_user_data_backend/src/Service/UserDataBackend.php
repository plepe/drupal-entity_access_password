<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password_user_data_backend\Service;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\entity_access_password\Service\AccessCheckerInterface;
use Drupal\entity_access_password\Service\AccessStorageInterface;
use Drupal\user\UserDataInterface;

/**
 * Handle access data in user data.
 */
class UserDataBackend implements AccessStorageInterface, AccessCheckerInterface {

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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The user data.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected UserDataInterface $userData;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\user\UserDataInterface $userData
   *   The user data.
   */
  public function __construct(
    AccountProxyInterface $currentUser,
    UserDataInterface $userData
  ) {
    $this->currentUser = $currentUser;
    $this->userData = $userData;
  }

  /**
   * {@inheritdoc}
   */
  public function storeEntityAccess(FieldableEntityInterface $entity) : void {
    // Do not store for anonymous user.
    if ($this->currentUser->id() == 0) {
      return;
    }

    $name = sprintf(self::ENTITY_NAME_KEY, $entity->getEntityTypeId(), $entity->uuid());
    $this->userData->set(self::MODULE_NAME, $this->currentUser->id(), $name, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function storeEntityBundleAccess(FieldableEntityInterface $entity) : void {
    // Do not store for anonymous user.
    if ($this->currentUser->id() == 0) {
      return;
    }

    $name = sprintf(self::BUNDLE_NAME_KEY, $entity->getEntityTypeId(), $entity->bundle());
    $this->userData->set(self::MODULE_NAME, $this->currentUser->id(), $name, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function storeGlobalAccess() : void {
    // Do not store for anonymous user.
    if ($this->currentUser->id() == 0) {
      return;
    }

    $this->userData->set(self::MODULE_NAME, $this->currentUser->id(), self::GLOBAL_NAME_KEY, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function hasUserAccessToEntity(FieldableEntityInterface $entity) : bool {
    // Do nothing for anonymous user.
    if ($this->currentUser->id() == 0) {
      return FALSE;
    }

    $name = sprintf(self::ENTITY_NAME_KEY, $entity->getEntityTypeId(), $entity->uuid());
    $has_entity_access = $this->userData->get(self::MODULE_NAME, $this->currentUser->id(), $name);
    if ($has_entity_access) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasUserAccessToBundle(FieldableEntityInterface $entity) : bool {
    // Do nothing for anonymous user.
    if ($this->currentUser->id() == 0) {
      return FALSE;
    }

    $name = sprintf(self::BUNDLE_NAME_KEY, $entity->getEntityTypeId(), $entity->bundle());
    $has_bundle_access = $this->userData->get(self::MODULE_NAME, $this->currentUser->id(), $name);
    if ($has_bundle_access) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasUserGlobalAccess() : bool {
    // Do nothing for anonymous user.
    if ($this->currentUser->id() == 0) {
      return FALSE;
    }

    $has_global_access = $this->userData->get(self::MODULE_NAME, $this->currentUser->id(), self::GLOBAL_NAME_KEY);
    if ($has_global_access) {
      return TRUE;
    }

    return FALSE;
  }

}
