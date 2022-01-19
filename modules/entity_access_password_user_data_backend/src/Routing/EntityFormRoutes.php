<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password_user_data_backend\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\entity_access_password\Service\EntityTypePasswordBundleInfoInterface;
use Drupal\entity_access_password_user_data_backend\HookHandler\EntityTypeInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines entity form routes.
 */
class EntityFormRoutes implements ContainerInjectionInterface {

  /**
   * The route name.
   *
   * "entity_access_password_user_data_backend.user_data_form.entity.$entity_type_id.$bundle_id".
   */
  public const ROUTE_NAME = 'entity_access_password_user_data_backend.user_data_form.entity.%s.%s';

  /**
   * The entity type password bundle info.
   *
   * @var \Drupal\entity_access_password\Service\EntityTypePasswordBundleInfoInterface
   */
  protected EntityTypePasswordBundleInfoInterface $entityTypePasswordBundleInfo;

  /**
   * Constructor.
   *
   * @param \Drupal\entity_access_password\Service\EntityTypePasswordBundleInfoInterface $entityTypePasswordBundleInfo
   *   The entity type password bundle info.
   */
  public function __construct(
    EntityTypePasswordBundleInfoInterface $entityTypePasswordBundleInfo
  ) {
    $this->entityTypePasswordBundleInfo = $entityTypePasswordBundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('entity_access_password.entity_type_password_bundle_info')
    );
  }

  /**
   * Returns an array of route objects.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  public function routes(): array {
    $routes = [];

    $password_infos = $this->entityTypePasswordBundleInfo->getAllPasswordBundleInfo();
    foreach ($password_infos as $entity_type_id => $entity_infos) {
      foreach (\array_keys($entity_infos['bundles']) as $bundle_id) {
        $machine_name = \sprintf(self::ROUTE_NAME, $entity_type_id, $bundle_id);
        $route = new Route("/entity_access_password_user_data_backend/{$entity_type_id}/{$bundle_id}/{{$entity_type_id}}");
        $route
          ->addDefaults([
            // @todo check if this is translatable and if possible to inject
            // variables.
            '_title' => 'Entity password user data',
            '_form' => '\Drupal\entity_access_password_user_data_backend\Form\EntityUserDataEditForm',
          ])
          ->addRequirements([
            '_permission' => EntityTypeInfo::ACCESS_PERMISSION,
            '_entity_access' => $entity_type_id . '.update',
            '_entity_bundles' => $entity_type_id . ':' . $bundle_id,
          ])
          ->setOption('_admin_route', TRUE)
          ->setOption('_eapudb_entity_type_id', $entity_type_id)
          ->setOption('parameters', [
            $entity_type_id => ['type' => 'entity:' . $entity_type_id],
          ]);
        $routes[$machine_name] = $route;
      }
    }

    return $routes;
  }

}
