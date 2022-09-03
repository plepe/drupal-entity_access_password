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
  protected static $modules = [
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
  public function testBackend(): void {
    parent::testBackend();

    $node_keys = [
      'global',
      'bundle',
      'entity',
    ];

    $this->resetAllAccesses($this->user);
    // Test that the access is persisted when changing the session.
    $this->drupalLogin($this->user);
    foreach ($node_keys as $key) {
      $this->enterNodePassword($key);
    }
    $this->drupalLogout();
    $this->drupalLogin($this->user);

    foreach ($node_keys as $key) {
      $node = $this->protectedNodes[$key];
      $this->drupalGet($node->toUrl());
      $this->passwordFormIsNotDisplayed($key);
    }

    // Test that the user data backend does not store access for anonymous
    // users. The password form should still be displayed even after entering
    // the correct password.
    $this->drupalLogout();
    foreach ($node_keys as $key) {
      $this->enterNodePassword($key, [TRUE, TRUE]);
    }
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
