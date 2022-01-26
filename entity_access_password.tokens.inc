<?php

/**
 * @file
 * Token callbacks for the entity_access_password module.
 */

declare(strict_types = 1);

use Drupal\Core\Render\BubbleableMetadata;
use Drupal\entity_access_password\HookHandler\Token;

/**
 * Implements hook_token_info().
 */
function entity_access_password_token_info(): array {
  /** @var \Drupal\entity_access_password\HookHandler\Token $instance */
  $instance = \Drupal::service('class_resolver')
    ->getInstanceFromDefinition(Token::class);
  return $instance->tokenInfo();
}

/**
 * Implements hook_tokens().
 */
function entity_access_password_tokens(string $type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
  /** @var \Drupal\entity_access_password\HookHandler\Token $instance */
  $instance = \Drupal::service('class_resolver')
    ->getInstanceFromDefinition(Token::class);
  return $instance->tokens($type, $tokens, $data, $options, $bubbleable_metadata);
}
