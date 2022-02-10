<?php

declare(strict_types = 1);

namespace Drupal\Tests\entity_access_password\Functional;

use Drupal\user\UserInterface;

/**
 * Base class for backend tests.
 */
abstract class BackendTestBase extends EntityAccessPasswordFunctionalTestBase {

  /**
   * Test backend.
   */
  public function testBackend(): void {
    $this->drupalLogin($this->user);

    // Test global, bundle and entity levels separately.
    $this->enterNodePassword('global');
    $this->enterNodePassword('bundle');
    $this->enterNodePassword('entity');

    $this->resetAllAccesses($this->user);

    // Test combinations of access levels.
    $node_all_1 = $this->protectedNodes['all'];
    $node_all_2 = $this->protectedNodes['all_title_hidden'];
    // Entity level.
    $this->drupalGet($node_all_1->toUrl());
    $this->passwordFormIsDisplayed('all');
    $this->enterNodePassword('all_title_hidden');
    // Password form is still displayed because the entity password had unlocked
    // only one entity.
    $this->drupalGet($node_all_1->toUrl());
    $this->passwordFormIsDisplayed('all');

    // Bundle level.
    $node_bundle_1 = $this->protectedNodes['bundle'];
    $this->drupalGet($node_bundle_1->toUrl());
    $this->passwordFormIsDisplayed('bundle');
    $this->enterNodePassword('bundle_2');
    // Password form is no more displayed because the bundle password had
    // unlocked access to all the entities of this bundle.
    $this->drupalGet($node_bundle_1->toUrl());
    $this->passwordFormIsNotDisplayed('bundle');

    // Reset before global access checks.
    $this->resetAllAccesses($this->user);

    // Global level.
    $this->drupalGet($node_all_1->toUrl());
    $this->passwordFormIsDisplayed('all');
    $this->drupalGet($node_all_2->toUrl());
    $this->passwordFormIsDisplayed('all_title_hidden');
    $this->enterNodePassword('global');

    // Password form is no more displayed because the global password had
    // unlocked access to all the entities using the global password.
    $this->drupalGet($node_all_1->toUrl());
    $this->passwordFormIsNotDisplayed('all');
    $this->drupalGet($node_all_2->toUrl());
    $this->passwordFormIsNotDisplayed('all_title_hidden');
  }

  /**
   * Reset all accesses.
   */
  abstract protected function resetAllAccesses(UserInterface $user): void;

}
