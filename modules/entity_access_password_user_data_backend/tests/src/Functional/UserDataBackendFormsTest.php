<?php

declare(strict_types = 1);

namespace Drupal\Tests\entity_access_password_user_data_backend\Functional;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountSwitcherInterface;
use Drupal\Core\Url;
use Drupal\entity_access_password_user_data_backend\Routing\BundleFormRoutes;
use Drupal\entity_access_password_user_data_backend\Routing\EntityFormRoutes;
use Drupal\entity_access_password_user_data_backend\Service\UserDataBackendInterface;
use Drupal\Tests\entity_access_password\Functional\EntityAccessPasswordFunctionalTestBase;
use Drupal\user\UserDataInterface;
use Drupal\user\UserInterface;

/**
 * Test user data backend forms.
 *
 * @group entity_access_password
 * @group entity_access_password_user_data_backend
 */
class UserDataBackendFormsTest extends EntityAccessPasswordFunctionalTestBase {

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
   * The account switcher.
   *
   * @var \Drupal\Core\Session\AccountSwitcherInterface
   */
  protected AccountSwitcherInterface $accountSwitcher;

  /**
   * Another test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $user2;

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
    $this->accountSwitcher = $this->container->get('account_switcher');

    $this->user2 = $this->drupalCreateUser($this->getUserPermissions());
  }

  /**
   * {@inheritdoc}
   */
  public function testForms(): void {
    // To have the new routes found.
    drupal_flush_all_caches();

    $this->drupalLogin($this->adminUser);

    // Entity form.
    $entity_password_node = $this->protectedNodes['entity'];
    $route_name = \sprintf(EntityFormRoutes::ROUTE_NAME, $entity_password_node->getEntityTypeId(), $entity_password_node->bundle());
    $this->drupalGet(Url::fromRoute($route_name, ['node' => $entity_password_node->id()]));
    $this->checkAccessLevelForm('entity', $entity_password_node);

    // Bundle form.
    $bundle_password_node = $this->protectedNodes['bundle'];
    $route_name = \sprintf(BundleFormRoutes::ROUTE_NAME, $bundle_password_node->getEntityTypeId(), $bundle_password_node->bundle());
    $this->drupalGet(Url::fromRoute($route_name));
    $this->checkAccessLevelForm('bundle', $bundle_password_node);

    // Global form.
    $this->drupalGet(Url::fromRoute('entity_access_password_user_data_backend.user_data_form.global'));
    $this->checkAccessLevelForm('global');

    // User form.
  }

  /**
   * Test an access level form.
   *
   * @param string $accessLevel
   *   The level of access to check.
   * @param \Drupal\Core\Entity\FieldableEntityInterface|null $entity
   *   The entity to check access against. NULL if global access.
   */
  protected function checkAccessLevelForm(string $accessLevel, ?FieldableEntityInterface $entity = NULL): void {
    // Test non-existing user.
    $this->submitForm(
      ['grant_area' => 'non_existing_user'],
      $this->t('Submit')
    );
    $this->assertSession()->pageTextContains('No user found for the username or email address: non_existing_user.');

    // Grant access.
    $this->userDoesNotHaveAccess($accessLevel, $this->user, $entity);
    $this->submitForm(
      ['grant_area' => $this->user->getAccountName()],
      $this->t('Submit')
    );
    $this->userHasAccess($accessLevel, $this->user, $entity);

    // Revoke access.
    $this->submitForm(
      ['users[' . $this->user->id() . ']' => TRUE],
      $this->t('Submit')
    );
    $this->userDoesNotHaveAccess($accessLevel, $this->user, $entity);

    // Grant multiple accesses.
    $this->submitForm(
      ['grant_area' => $this->user->getAccountName() . ',' . $this->user2->getAccountName()],
      $this->t('Submit')
    );
    $this->userHasAccess($accessLevel, $this->user, $entity);
    $this->userHasAccess($accessLevel, $this->user2, $entity);

    // Revoke all.
    $this->submitForm(
      ['revoke_all' => TRUE],
      $this->t('Submit')
    );
    $this->userDoesNotHaveAccess($accessLevel, $this->user, $entity);
    $this->userDoesNotHaveAccess($accessLevel, $this->user2, $entity);
  }

  /**
   * Check that the user has access.
   *
   * @param string $accessLevel
   *   The level of access to check.
   * @param \Drupal\user\UserInterface $user,
   *   The user to check access for.
   * @param \Drupal\Core\Entity\FieldableEntityInterface|null $entity
   *   The entity to check access against. NULL if global access.
   */
  protected function userHasAccess(string $accessLevel, UserInterface $user, ?FieldableEntityInterface $entity = NULL): void {
    $this->assertSession()->pageTextContains($user->getDisplayName());
    $this->assertSession()->pageTextContains($user->getEmail());
    $this->accountSwitcher->switchTo($user);
    switch ($accessLevel) {
      case 'entity':
        $this->assertTrue($this->userDataBackend->hasUserAccessToEntity($entity));
        break;

      case 'bundle':
        $this->assertTrue($this->userDataBackend->hasUserAccessToBundle($entity));
        break;

      case 'global':
        $this->assertTrue($this->userDataBackend->hasUserGlobalAccess());
        break;
    }
    $this->accountSwitcher->switchBack();
  }

  /**
   * Check that the user does not have access.
   *
   * @param string $accessType
   *   The type of access to check.
   * @param \Drupal\user\UserInterface $user,
   *   The user to check access for.
   * @param \Drupal\Core\Entity\FieldableEntityInterface|null $entity
   *   The entity to check access against.
   */
  protected function userDoesNotHaveAccess(string $accessType, UserInterface $user, ?FieldableEntityInterface $entity): void {
    $this->assertSession()->pageTextNotContains($user->getDisplayName());
    $this->assertSession()->pageTextNotContains($user->getEmail());
    $this->accountSwitcher->switchTo($user);
    switch ($accessType) {
      case 'entity':
        $this->assertFalse($this->userDataBackend->hasUserAccessToEntity($entity));
        break;

      case 'bundle':
        $this->assertFalse($this->userDataBackend->hasUserAccessToBundle($entity));
        break;

      case 'global':
        $this->assertFalse($this->userDataBackend->hasUserGlobalAccess());
        break;
    }
    $this->accountSwitcher->switchBack();
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdminUserPermissions(): array {
    return [
      'entity_access_password_user_data_backend_access_entity_form',
      'entity_access_password_user_data_backend_access_bundle_form',
      'entity_access_password_user_data_backend_access_global_form',
      'entity_access_password_user_data_backend_access_user_form',
      'edit any eap_global content',
      'edit any eap_bundle content',
      'edit any eap_entity content',
      'edit any eap_all content',
    ] + parent::getAdminUserPermissions();
  }

}
