<?php

declare(strict_types = 1);

namespace Drupal\Tests\entity_access_password\Functional;

use Drupal\user\UserInterface;

/**
 * Backend independent tests.
 *
 * @group entity_access_password
 */
class GlobalBehaviorTest extends EntityAccessPasswordFunctionalTestBase {

  /**
   * The list of user permissions.
   *
   * @var array
   */
  protected array $bypassPasswordUserPermissions = [
    'bypass_password_protection',
    'access content',
  ];

  /**
   * The bypass password test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $bypassPasswordUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->bypassPasswordUser = $this->drupalCreateUser($this->bypassPasswordUserPermissions);
  }

  /**
   * Test that hint and help texts are displayed on the password form.
   */
  public function testGlobalBehavior(): void {
    // Test that hint and help texts are displayed on the password form.
    $this->drupalLogin($this->user);
    foreach ($this->protectedNodes as $key => $node) {
      $this->drupalGet($node->toUrl());
      $this->assertSession()->pageTextContains('Help text: ' . $this->protectedNodesStructure[$key]['type']);
      $this->assertSession()->pageTextContains($this->protectedNodesStructure[$key]['hint']);
    }

    // Test that a password with bypass permission can access the content
    // directly, so no password form, hence no help and hint texts.
    $this->drupalLogin($this->bypassPasswordUser);
    foreach ($this->protectedNodes as $key => $node) {
      $this->drupalGet($node->toUrl());
      $this->assertSession()->pageTextNotContains('Help text: ' . $this->protectedNodesStructure[$key]['type']);
      $this->assertSession()->pageTextNotContains($this->protectedNodesStructure[$key]['hint']);
    }
  }

  /**
   * Test that it is possible to protect view modes other than the full one.
   */
//  public function testProtectedViewModes(): void {
//
//  }

  /**
   * Test the hide title feature.
   */
//  public function testHideTitle(): void {
//
//  }

  /**
   * Test random password message.
   */
//  public function testRandomPassword(): void {
//
//  }

}
