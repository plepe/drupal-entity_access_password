<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Service;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Check if the user has permission to bypass password protection.
 */
class BypassPermissionAccessChecker implements AccessCheckerInterface {

  /**
   * Bypass permission machine name.
   */
  public const BYPASS_PERMISSION = 'bypass_password_protection';

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(AccountProxyInterface $currentUser) {
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public function hasUserAccessToEntity(FieldableEntityInterface $entity): bool {
    return $this->bypassPermissionCheck();
  }

  /**
   * {@inheritdoc}
   */
  public function hasUserAccessToBundle(FieldableEntityInterface $entity): bool {
    return $this->bypassPermissionCheck();
  }

  /**
   * {@inheritdoc}
   */
  public function hasUserGlobalAccess(): bool {
    return $this->bypassPermissionCheck();
  }

  /**
   * Check if the user has the bypass permission.
   *
   * @return bool
   *   TRUE if the user has the bypass permission. FALSE otherwise.
   */
  protected function bypassPermissionCheck(): bool {
    if ($this->currentUser->hasPermission(self::BYPASS_PERMISSION)) {
      return TRUE;
    }

    return FALSE;
  }

}
