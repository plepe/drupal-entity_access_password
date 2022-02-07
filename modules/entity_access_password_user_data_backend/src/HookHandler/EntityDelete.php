<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password_user_data_backend\HookHandler;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\entity_access_password_user_data_backend\Service\UserDataBackendInterface;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Purge user data on entity deletion.
 */
class EntityDelete implements ContainerInjectionInterface {

  /**
   * The user data.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected UserDataInterface $userData;

  /**
   * The user data backend.
   *
   * @var \Drupal\entity_access_password_user_data_backend\Service\UserDataBackendInterface
   */
  protected UserDataBackendInterface $userDataBackend;

  /**
   * Constructor.
   *
   * @param \Drupal\user\UserDataInterface $userData
   *   The user data.
   * @param \Drupal\entity_access_password_user_data_backend\Service\UserDataBackendInterface $userDataBackend
   *   The user data backend.
   */
  public function __construct(
    UserDataInterface $userData,
    UserDataBackendInterface $userDataBackend
  ) {
    $this->userData = $userData;
    $this->userDataBackend = $userDataBackend;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('user.data'),
      $container->get('entity_access_password_user_data_backend.user_data_backend')
    );
  }

  /**
   * Purge user data on entity deletion.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity on which to purge user data.
   */
  public function entityDelete(EntityInterface $entity): void {
    if (!$entity instanceof FieldableEntityInterface) {
      return;
    }

    $this->userData->delete(UserDataBackendInterface::MODULE_NAME, NULL, $this->userDataBackend->getEntityName($entity));
  }

}
