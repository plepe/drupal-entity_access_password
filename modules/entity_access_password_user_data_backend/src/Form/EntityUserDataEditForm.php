<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password_user_data_backend\Form;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\entity_access_password_user_data_backend\Service\UserDataBackend;

/**
 * Provides form to remove access (stored in user data) to the entity.
 */
class EntityUserDataEditForm extends UserDataEditFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getUserDataName() : string {
    $entity = $this->getEntityFromRouteMatch($this->getRouteMatch());

    // Not possible to know for which entity the form is built against.
    if (!$entity instanceof FieldableEntityInterface) {
      return '';
    }
    return sprintf(UserDataBackend::ENTITY_NAME_KEY, $entity->getEntityTypeId(), $entity->uuid());
  }

  /**
   * Retrieves entity from route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity object as determined from the passed-in route match.
   */
  protected function getEntityFromRouteMatch(RouteMatchInterface $route_match) {
    $route = $route_match->getRouteObject();

    if ($route == NULL) {
      return NULL;
    }
    $parameter_name = $route->getOption('_eapudb_entity_type_id');
    // @phpstan-ignore-next-line
    return $route_match->getParameter($parameter_name);
  }

}
