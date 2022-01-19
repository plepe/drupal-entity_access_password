<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\HookHandler;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_access_password\Event\FileUsageEntityListEvent;
use Drupal\entity_access_password\Service\PasswordAccessManagerInterface;
use Drupal\file\FileUsage\FileUsageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Access control for private files.
 */
class FileDownload implements ContainerInjectionInterface {

  /**
   * Return value expected by hook_file_download when denying access.
   */
  public const ACCESS_DENIED_RETURN = -1;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The file usage service.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected FileUsageInterface $fileUsage;

  /**
   * The password access manager.
   *
   * @var \Drupal\entity_access_password\Service\PasswordAccessManagerInterface
   */
  protected PasswordAccessManagerInterface $passwordAccessManager;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected EventDispatcherInterface $eventDispatcher;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\file\FileUsage\FileUsageInterface $fileUsage
   *   The file usage service.
   * @param \Drupal\entity_access_password\Service\PasswordAccessManagerInterface $passwordAccessManager
   *   The password access manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    FileUsageInterface $fileUsage,
    PasswordAccessManagerInterface $passwordAccessManager,
    EventDispatcherInterface $eventDispatcher
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->fileUsage = $fileUsage;
    $this->passwordAccessManager = $passwordAccessManager;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('entity_type.manager'),
      $container->get('file.usage'),
      $container->get('entity_access_password.password_access_manager'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Check if the file is attached to a password protected entity.
   *
   * @param string $uri
   *   The URI of the file.
   *
   * @return int|null
   *   If the user does not have permission to access the file, return -1. NULL
   *   Otherwise.
   *
   * @see \hook_file_download()
   */
  public function fileDownload(string $uri) {
    /** @var \Drupal\file\FileInterface[] $files */
    $files = $this->entityTypeManager->getStorage('file')->loadByProperties([
      'uri' => $uri,
    ]);

    // There should be only one file per URI.
    foreach ($files as $file) {
      $usages = $this->fileUsage->listUsage($file);
      $user_has_access = NULL;

      foreach ($usages as $entity_list) {
        // It is not possible without custom development or modules like
        // Entity Browser to re-use a file in multiple entities so there should
        // be most of the time only one entity.
        foreach ($entity_list as $entity_type_id => $entity_ids) {
          $entity_storage = $this->entityTypeManager->getStorage($entity_type_id);
          $entities = $entity_storage->loadMultiple(\array_keys($entity_ids));

          // Allows to alter the list of entities.
          $file_usage_entity_list_event = new FileUsageEntityListEvent($file, $entities);
          $this->eventDispatcher->dispatch($file_usage_entity_list_event);

          foreach ($file_usage_entity_list_event->getEntities() as $entity) {
            $user_has_access = $this->passwordAccessManager->hasUserAccessToEntity($entity);
            // If the user has access to at least one entity using the file do
            // nothing.
            if ($user_has_access) {
              return NULL;
            }
          }
        }
      }

      // It means that only password protected entities had been encountered
      // and that the user has access to none.
      if ($user_has_access === FALSE) {
        return self::ACCESS_DENIED_RETURN;
      }
    }

    return NULL;
  }

}
