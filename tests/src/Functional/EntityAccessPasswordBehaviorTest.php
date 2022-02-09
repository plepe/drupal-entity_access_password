<?php

declare(strict_types = 1);

namespace Drupal\Tests\entity_access_password\Functional;

/**
 * Backend independent tests.
 *
 * @group entity_access_password
 */
class EntityAccessPasswordBehaviorTest extends EntityAccessPasswordFunctionalTestBase {

  /**
   * Test that hint and help texts are displayed on the password form.
   */
  public function testHelpHintTexts(): void {
    $this->drupalLogin($this->user);
    foreach ($this->protectedNodes as $key => $node) {
      $this->drupalGet($node->toUrl());
      $this->assertSession()->pageTextContains('Help text: ' . $this->protectedNodesStructure[$key]['type']);
      $this->assertSession()->pageTextContains($this->protectedNodesStructure[$key]['hint']);
    }
  }

  /**
   * Test that a user with the bypass permission can access content directly.
   */
//  public function testBypassPermission(): void {
//
//  }

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

}
