<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password_user_data_backend\Service;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\UserDataInterface;

/**
 * Handle access data in user data.
 */
class UserDataBackend implements UserDataBackendInterface {

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
  public function storeEntityAccess(FieldableEntityInterface $entity): void {
    // Do not store for anonymous user.
    if ($this->currentUser->id() == 0) {
      return;
    }

    $this->userData->set(self::MODULE_NAME, $this->currentUser->id(), $this->getEntityName($entity), TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function storeEntityBundleAccess(FieldableEntityInterface $entity): void {
    // Do not store for anonymous user.
    if ($this->currentUser->id() == 0) {
      return;
    }

    $this->userData->set(self::MODULE_NAME, $this->currentUser->id(), $this->getBundleNameFromEntity($entity), TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function storeGlobalAccess(): void {
    // Do not store for anonymous user.
    if ($this->currentUser->id() == 0) {
      return;
    }

    $this->userData->set(self::MODULE_NAME, $this->currentUser->id(), $this->getGlobalName(), TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function hasUserAccessToEntity(FieldableEntityInterface $entity): bool {
    // Do nothing for anonymous user.
    if ($this->currentUser->id() == 0) {
      return FALSE;
    }

    $has_entity_access = $this->userData->get(self::MODULE_NAME, $this->currentUser->id(), $this->getEntityName($entity));
    if ($has_entity_access) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasUserAccessToBundle(FieldableEntityInterface $entity): bool {
    // Do nothing for anonymous user.
    if ($this->currentUser->id() == 0) {
      return FALSE;
    }

    $has_bundle_access = $this->userData->get(self::MODULE_NAME, $this->currentUser->id(), $this->getBundleNameFromEntity($entity));
    if ($has_bundle_access) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasUserGlobalAccess(): bool {
    // Do nothing for anonymous user.
    if ($this->currentUser->id() == 0) {
      return FALSE;
    }

    $has_global_access = $this->userData->get(self::MODULE_NAME, $this->currentUser->id(), $this->getGlobalName());
    if ($has_global_access) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityName(FieldableEntityInterface $entity): string {
    return \sprintf(self::ENTITY_NAME_KEY, $entity->getEntityTypeId(), $entity->uuid());
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleName(string $entityTypeId, string $bundleId): string {
    return \sprintf(self::BUNDLE_NAME_KEY, $entityTypeId, $bundleId);
  }

  /**
   * {@inheritdoc}
   */
  public function getBundleNameFromEntity(FieldableEntityInterface $entity): string {
    return \sprintf(self::BUNDLE_NAME_KEY, $entity->getEntityTypeId(), $entity->bundle());
  }

  /**
   * {@inheritdoc}
   */
  public function getGlobalName(): string {
    return self::GLOBAL_NAME_KEY;
  }

}
