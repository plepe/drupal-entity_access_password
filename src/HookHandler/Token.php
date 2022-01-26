<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\HookHandler;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\entity_access_password\Cache\Context\EntityIsProtectedCacheContext;
use Drupal\entity_access_password\Service\PasswordAccessManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Declare a new token for entities.
 */
class Token implements ContainerInjectionInterface {
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The password access manager.
   *
   * @var \Drupal\entity_access_password\Service\PasswordAccessManagerInterface
   */
  protected PasswordAccessManagerInterface $passwordAccessManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\entity_access_password\Service\PasswordAccessManagerInterface $passwordAccessManager
   *   The password access manager.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    PasswordAccessManagerInterface $passwordAccessManager
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->passwordAccessManager = $passwordAccessManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('entity_type.manager'),
      $container->get('entity_access_password.password_access_manager')
    );
  }

  /**
   * Declare a new token for entities.
   *
   * @return array
   *   A token array declaration.
   *
   * @see \hook_token_info()
   */
  public function tokenInfo(): array {
    $info = [];

    $entities = $this->entityTypeManager->getDefinitions();
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
      $entity_type_label = \mb_strtolower($entity_type_label);

      // Add [entity:protected-label] tokens.
      $info['tokens'][$token_type]['protected-label'] = [
        'name' => $this->t('Protected label'),
        'description' => $this->t('The label of the @entity if the user has access.', [
          '@entity' => $entity_type_label,
        ]),
        'module' => 'entity_access_password',
      ];
    }

    return $info;
  }

  /**
   * Provide replacement values for placeholder tokens.
   *
   * @param string $type
   *   The machine-readable name of the type (group) of token being replaced.
   * @param array $tokens
   *   An array of tokens to be replaced..
   * @param array $data
   *   An associative array of data objects.
   * @param array $options
   *   An associative array of options for token replacement.
   * @param \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata
   *   The bubbleable metadata.
   *
   * @return array
   *   Token replacements.
   *
   * @see \hook_tokens()
   */
  public function tokens(string $type, array $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata): array {
    $replacements = [];

    if ($type == 'entity') {
      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'protected-label':
            /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
            $entity = $data['entity'];
            $replacements[$original] = $entity->label();

            if ($this->passwordAccessManager->isEntityLabelProtected($entity)) {
              $bubbleable_metadata->addCacheContexts([EntityIsProtectedCacheContext::CONTEXT_ID . ':' . $entity->getEntityTypeId() . '||' . $entity->id()]);

              if (!$this->passwordAccessManager->hasUserAccessToEntity($entity)) {
                $replacements[$original] = $this->t('Protected entity');
              }
            }
            break;
        }
      }
    }

    return $replacements;
  }

}
