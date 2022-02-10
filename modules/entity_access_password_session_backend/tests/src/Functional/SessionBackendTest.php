<?php

declare(strict_types = 1);

namespace Drupal\Tests\entity_access_password_session_backend\Functional;

use Drupal\Tests\entity_access_password\Functional\BackendTestBase;
use Drupal\user\UserInterface;

/**
 * Session backend tests.
 *
 * @group entity_access_password
 * @group entity_access_password_session_backend
 */
class SessionBackendTest extends BackendTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_access_password_session_backend',
  ];

  /**
   * {@inheritdoc}
   */
  protected function resetAllAccesses(UserInterface $user): void {
    $this->drupalLogout();
    $this->drupalLogin($user);
  }

}
