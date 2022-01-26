<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Service;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\StackedRouteMatchInterface;

/**
 * The default route parser.
 */
class RouteParser implements RouteParserInterface {

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * The entity if present. NULL otherwise.
   *
   * @var \Drupal\Core\Entity\FieldableEntityInterface|null
   */
  protected $foundEntity;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Routing\StackedRouteMatchInterface $routeMatch
   *   The current route match.
   */
  public function __construct(
    StackedRouteMatchInterface $routeMatch
  ) {
    $this->routeMatch = $routeMatch->getCurrentRouteMatch();
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromCurrentRoute() {
    if (isset($this->foundEntity)) {
      return $this->foundEntity;
    }

    $route = $this->routeMatch->getRouteObject();
    if ($route == NULL) {
      $this->foundEntity = NULL;
      return $this->foundEntity;
    }

    $options = $route->getOptions();

    $entity_parameter_key = '';
    if (isset($options['parameters'])) {
      foreach ($options['parameters'] as $parameter_key => $parameter) {
        if (isset($parameter['type']) && \substr($parameter['type'], 0, \strlen(self::SEARCHED_TYPE)) === self::SEARCHED_TYPE) {
          $entity_parameter_key = $parameter_key;
          // Stop on the first one found.
          break;
        }
      }
    }

    if (empty($entity_parameter_key)) {
      $this->foundEntity = NULL;
      return $this->foundEntity;
    }

    $parameters = $this->routeMatch->getParameters();
    $entity = $parameters->get($entity_parameter_key);

    if (!$entity instanceof FieldableEntityInterface) {
      $this->foundEntity = NULL;
      return $this->foundEntity;
    }

    $this->foundEntity = $entity;
    return $this->foundEntity;
  }

}
