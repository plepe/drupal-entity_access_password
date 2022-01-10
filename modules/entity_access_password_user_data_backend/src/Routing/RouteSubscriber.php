<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password_user_data_backend\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\entity_access_password_user_data_backend\HookHandler\EntityTypeInfo;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Entity Access Password routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager) {
    $this->entityTypeManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', 100];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) : void {
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      $route = $this->getEntityEditUserDataRoute($entity_type);
      if ($route) {
        $collection->add("entity.$entity_type_id." . EntityTypeInfo::ENTITY_OPERATION, $route);
      }
    }
  }

  /**
   * Gets the entity route to edit user data.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getEntityEditUserDataRoute(EntityTypeInterface $entity_type) {
    /** @var string $entity_template */
    $entity_template = $entity_type->getLinkTemplate(EntityTypeInfo::ENTITY_LINK_TEMPLATE);
    if ($entity_template) {
      $entity_type_id = $entity_type->id();
      $route = new Route($entity_template);
      $route
        ->addDefaults([
          '_title' => 'Entity password user data',
          '_form' => '\Drupal\entity_access_password_user_data_backend\Form\EntityUserDataEditForm',
        ])
        ->addRequirements([
          '_permission' => EntityTypeInfo::ACCESS_PERMISSION,
        ])
        ->setOption('_admin_route', TRUE)
        ->setOption('_eapudb_entity_type_id', $entity_type_id)
        ->setOption('parameters', [
          $entity_type_id => ['type' => 'entity:' . $entity_type_id],
        ]);

      return $route;
    }

    return NULL;
  }

}
