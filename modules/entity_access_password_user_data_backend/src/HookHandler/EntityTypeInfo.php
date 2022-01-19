<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password_user_data_backend\HookHandler;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\entity_access_password_user_data_backend\Routing\EntityFormRoutes;
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
   * The entity operation. Also used for the dynamic route and task link.
   */
  public const ENTITY_OPERATION = 'entity_access_password_user_data_edit';

  /**
   * The entity operation weight.
   */
  public const ENTITY_OPERATION_WEIGHT = 50;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(
    AccountProxyInterface $currentUser
  ) {
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('current_user')
    );
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
  public function entityOperation(EntityInterface $entity): array {
    $operations = [];
    if (
      $entity instanceof FieldableEntityInterface
      && $this->currentUser->hasPermission(self::ACCESS_PERMISSION)
      && $entity->access('update')
    ) {
      $fields = $entity->getFields();
      foreach ($fields as $field) {
        $field_definition = $field->getFieldDefinition();
        if ($field_definition->getType() == 'entity_access_password_password') {
          $entity_type_id = $entity->getEntityTypeId();
          $bundle_id = $entity->bundle();
          $route_name = \sprintf(EntityFormRoutes::ROUTE_NAME, $entity_type_id, $bundle_id);
          $operations[self::ENTITY_OPERATION] = [
            'title' => $this->t('Password access user data'),
            'url' => URL::fromRoute($route_name, [
              $entity_type_id => $entity->id(),
            ]),
            'weight' => self::ENTITY_OPERATION_WEIGHT,
          ];
          break;
        }
      }
    }
    return $operations;
  }

}
