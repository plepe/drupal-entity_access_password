<?php

declare(strict_types = 1);

namespace Drupal\Tests\entity_access_password_user_data_backend\Functional;

use Drupal\entity_access_password_user_data_backend\Service\UserDataBackendInterface;
use Drupal\Tests\entity_access_password\Functional\BackendTestBase;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;

/**
 * User data backend tests.
 *
 * @group entity_access_password
 * @group entity_access_password_user_data_backend
 */
class UserDataBackendTest extends BackendTestBase {

  /**
   * The user data.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected UserDataInterface $userData;

  /**
   * The user data backend.
   *
   * @var \Drupal\entity_access_password_user_data_backend\Service\UserDataBackendInterface
   */
  protected UserDataBackendInterface $userDataBackend;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'entity_access_password_user_data_backend',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->userData = $this->container->get('user.data');
    $this->userDataBackend = $this->container->get('entity_access_password_user_data_backend.user_data_backend');
  }

  /**
   * {@inheritdoc}
   */
  protected function resetAllAccesses(UserInterface $user): void {
    /** @var int $user_id */
    $user_id = $user->id();
    // Global.
    $this->userData->delete(UserDataBackendInterface::MODULE_NAME, $user_id, $this->userDataBackend->getGlobalName());

    foreach ($this->protectedNodes as $node) {
      // Bundle.
      $bundle_user_data_name = $this->userDataBackend->getBundleName('node', $node->bundle());
      $this->userData->delete(UserDataBackendInterface::MODULE_NAME, $user_id, $bundle_user_data_name);

      // Entity.
      $entity_user_data_name = $this->userDataBackend->getEntityName($node);
      $this->userData->delete(UserDataBackendInterface::MODULE_NAME, $user_id, $entity_user_data_name);
    }
  }

}
