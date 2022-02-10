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
   *
   * @var \Drupal\Core\Utility\Token
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
    // Test random password message.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/node/add/eap_all');
    $this->submitForm([
      'title[0][value]' => $this->randomString(),
      $this->fieldName . '[0][is_protected]' => TRUE,
      $this->fieldName . '[0][protected_wrapper][change_existing_wrapper][random_password]' => TRUE,
    ], $this->t('Save'));
    $this->assertSession()->pageTextContains('Please note the randomly generated password as it will not be possible to show it again:');

    // Test that a password with bypass permission can access the content
    // directly, so no password form, hence no help and hint texts.
    $this->drupalLogin($this->bypassPasswordUser);
    foreach ($this->protectedNodes as $key => $node) {
      $this->drupalGet($node->toUrl());
      $this->passwordFormIsNotDisplayed($key);
    }

    $this->drupalLogin($this->user);

    // Test that hint and help texts are displayed on the password form.
    foreach ($this->protectedNodes as $key => $node) {
      $this->drupalGet($node->toUrl());
      $this->passwordFormIsDisplayed($key);
    }

    // Test that it is possible to protect view modes other than the full one.
    // The password form of all the nodes should be displayed in this teaser
    // list.
    $this->drupalGet(self::TEST_CONTROLLER_PATH);
    foreach (\array_keys($this->protectedNodes) as $key) {
      $this->passwordFormIsDisplayed($key);
    }

    // Test the hide title feature.
    // Check a protected content showing the title.
    $node = $this->protectedNodes['all'];
    /** @var string $node_title */
    $node_title = $node->label();
    $this->drupalGet($node->toUrl());
    // Page title.
    $this->assertSession()->titleEquals("{$node_title} | Drupal");
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

}
