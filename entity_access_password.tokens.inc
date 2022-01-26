<?php

/**
 * @file
 * Token callbacks for the entity_access_password module.
 */

declare(strict_types = 1);

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_access_password\Cache\Context\EntityIsProtectedCacheContext;

/**
 * Implements hook_token_info().
 */
function entity_access_password_token_info(): array {
  $info = [];

  $entities = \Drupal::entityTypeManager()->getDefinitions();
  foreach ($entities as $entity_info) {
    // Do not generate tokens if the entity doesn't define a token type or is
    // not a content entity.
    if (!$entity_info->get('token_type') || (!$entity_info instanceof ContentEntityTypeInterface)) {
      continue;
    }

    $token_type = $entity_info->get('token_type');

    $entity_type_label = $entity_info->getLabel();
    if ($entity_type_label instanceof TranslatableMarkup) {
      $entity_type_label = $entity_type_label->__toString();
    }
    $entity_type_label = mb_strtolower($entity_type_label);

    // Add [entity:protected-label] tokens.
    $info['tokens'][$token_type]['protected-label'] = [
      'name' => t('Protected label'),
      'description' => t('The label of the @entity if the user have access.', ['@entity' => $entity_type_label]),
      'module' => 'entity_access_password',
    ];
  }

  return $info;
}

/**
 * Implements hook_tokens().
 */
function entity_access_password_tokens(string $type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
  $replacements = [];

  if ($type == 'entity') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'protected-label':
          /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
          $entity = $data['entity'];
          $replacements[$original] = $entity->label();

          /** @var \Drupal\entity_access_password\Service\PasswordAccessManagerInterface $password_access_manager */
          $password_access_manager = \Drupal::service('entity_access_password.password_access_manager');
          if ($password_access_manager->isEntityLabelProtected($entity)) {
            $bubbleable_metadata->addCacheContexts([EntityIsProtectedCacheContext::CONTEXT_ID . ':' . $entity->getEntityTypeId() . '||' . $entity->id()]);

            if (!$password_access_manager->hasUserAccessToEntity($entity)) {
              $replacements[$original] = t('Protected entity');
            }
          }
          break;

      }
    }
  }

  return $replacements;
}
