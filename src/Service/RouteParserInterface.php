<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Service;

/**
 * Route parser interface methods.
 */
interface RouteParserInterface {

  /**
   * The searched parameter type to determine the entity.
   */
  public const SEARCHED_TYPE = 'entity:';

  /**
   * Get an entity from the current route.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface|null
   *   The entity if present. NULL otherwise.
   */
  public function getEntityFromCurrentRoute();

}
