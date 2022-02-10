<?php

declare(strict_types = 1);

namespace Drupal\Tests\entity_access_password\Functional;

use Drupal\Core\Utility\Token;
use Drupal\user\UserInterface;

/**
 * Backend independent tests.
 *
 * @group entity_access_password
 */
class GlobalBehaviorsTest extends EntityAccessPasswordFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'token',
  ];

  /**
   * The token service.
   */
  protected Token $token;

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
    $this->token = $this->container->get('token');
    $this->bypassPasswordUser = $this->drupalCreateUser($this->bypassPasswordUserPermissions);
  }

  /**
   * Test that hint and help texts are displayed on the password form.
   */
  public function testGlobalBehaviors(): void {
    // Test that a password with bypass permission can access the content
    // directly, so no password form, hence no help and hint texts.
    $this->drupalLogin($this->bypassPasswordUser);
    foreach ($this->protectedNodes as $key => $node) {
      $this->drupalGet($node->toUrl());
      $this->assertSession()->pageTextNotContains('Help text: ' . $this->protectedNodesStructure[$key]['type']);
      $this->assertSession()->pageTextNotContains($this->protectedNodesStructure[$key]['hint']);
    }

    $this->drupalLogin($this->user);

    // Test that hint and help texts are displayed on the password form.
    foreach ($this->protectedNodes as $key => $node) {
      $this->drupalGet($node->toUrl());
      $this->assertSession()->pageTextContains('Help text: ' . $this->protectedNodesStructure[$key]['type']);
      $this->assertSession()->pageTextContains($this->protectedNodesStructure[$key]['hint']);
    }

    // Test that it is possible to protect view modes other than the full one.
    // The password form of all the nodes should be displayed in this teaser
    // list.
    $this->drupalGet(self::TEST_CONTROLLER_PATH);
    foreach (array_keys($this->protectedNodes) as $key) {
      $this->assertSession()->pageTextContains('Help text: ' . $this->protectedNodesStructure[$key]['type']);
      $this->assertSession()->pageTextContains($this->protectedNodesStructure[$key]['hint']);
    }

    // Test the hide title feature.
    // Check a protected content showing the title.
    $node = $this->protectedNodes['all'];
    $node_title = $node->label();
    $this->drupalGet($node->toUrl());
    // Page title.
    $this->assertSession()->titleEquals("$node_title | Drupal");
    // Block page title.
    $this->assertSession()->elementContains('css', 'h1', $node_title);
    // Label in entity template.
    $this->assertSession()->elementContains('css', 'article h2 span', $node_title);
    // Token.
    $token_value = $this->token->replace('[node:protected-label]', [
      'node' => $node,
    ]);
    $this->assertEquals($node_title, $token_value);

    // Check a protected content not showing the title.
    $node = $this->protectedNodes['all_title_hidden'];
    $this->drupalGet($node->toUrl());
    // Page title.
    $this->assertSession()->titleEquals('Protected entity | Drupal');
    // Block page title.
    $this->assertSession()->elementContains('css', 'h1', 'Protected entity');
    // Label in entity template.
    $this->assertSession()->elementContains('css', 'article h2 span', 'Protected entity');
    // Token.
    $token_value = $this->token->replace('[node:protected-label]', [
      'node' => $node,
    ]);
    $this->assertEquals('Protected entity', $token_value);
  }

  /**
   * Test random password message.
   */
//  public function testRandomPassword(): void {
//
//  }

}
