<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\file\FileInterface;

/**
 * Allows to alter the list of entities checked to access a private file.
 */
class FileUsageEntityListEvent extends Event {

  /**
   * The file being downloaded.
   *
   * @var \Drupal\file\FileInterface
   */
  protected FileInterface $file;

  /**
   * The list of entities the access will be checked.
   *
   * @var array|\Drupal\Core\Entity\EntityInterface[]
   */
  protected array $entities;

  /**
   * Constructor.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file being downloaded.
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   The list of entities the access will be checked.
   */
  public function __construct(FileInterface $file, array $entities) {
    $this->file = $file;
    $this->entities = $entities;
  }

  /**
   * Returns the file entity.
   *
   * @return \Drupal\file\FileInterface
   *   The file being manipulated.
   */
  public function getFile(): FileInterface {
    return $this->file;
  }

  /**
   * Returns the list of entities to check.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The list of entities the access will be checked.
   */
  public function getEntities(): array {
    return $this->entities;
  }

  /**
   * Set the new list of entities to check for.
   *
   * @param array $entities
   *   The list of entities to check.
   */
  public function setEntities(array $entities): void {
    $this->entities = $entities;
  }

}
