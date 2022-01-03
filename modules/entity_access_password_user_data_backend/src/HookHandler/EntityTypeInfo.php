<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password_user_data_backend\HookHandler;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manipulates entity type information.
 */
class EntityTypeInfo implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The access permission.
   */
  public const ACCESS_PERMISSION = 'entity_access_password_user_data_backend_access_entity_form';

  /**
   * The entity link template to edit user data.
   */
  public const ENTITY_LINK_TEMPLATE = 'entity-access-password-user-data-edit';

  /**
   * The entity operation. Also used for the dynamic route and task link.
   */
  public const ENTITY_OPERATION = 'entity_access_password_user_data_edit';

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * EntityTypeInfo constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   */
  public function __construct(
    AccountProxyInterface $currentUser,
    EntityFieldManagerInterface $entityFieldManager
  ) {
    $this->currentUser = $currentUser;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) : self {
    return new self(
      $container->get('current_user'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Adds links to appropriate entity types.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface[] $entity_types
   *   The entity type list to alter.
   */
  public function entityTypeAlter(array &$entity_types) : void {
    foreach ($entity_types as $entity_type_id => $entity_type) {
      if (!$entity_type instanceof ContentEntityTypeInterface) {
        continue;
      }

      $entity_fields = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
      foreach ($entity_fields as $entity_field) {
        if ($entity_field->getType() == 'entity_access_password_password') {
          if (
            ($entity_type->getFormClass('default') || $entity_type->getFormClass('edit')) && $entity_type->hasLinkTemplate('edit-form') ||
            $entity_type->hasLinkTemplate('canonical')
          ) {
            $entity_type->setLinkTemplate(self::ENTITY_LINK_TEMPLATE, "/entity_access_password_user_data_backend/$entity_type_id/{{$entity_type_id}}");
          }

          break;
        }
      }
    }
  }

  /**
   * Adds operations on entity that supports it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity on which to define an operation.
   *
   * @return array
   *   An array of operation definitions.
   */
  public function entityOperation(EntityInterface $entity) : array {
    $operations = [];
    if (
      $entity->hasLinkTemplate(self::ENTITY_LINK_TEMPLATE) &&
      $this->currentUser->hasPermission(self::ACCESS_PERMISSION) &&
      $entity->access('edit')
    ) {
      $operations[self::ENTITY_OPERATION] = [
        'title' => $this->t('Edit password access user data'),
        'url' => $entity->toUrl(self::ENTITY_LINK_TEMPLATE),
      ];
    }
    return $operations;
  }

}
