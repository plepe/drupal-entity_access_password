<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password_user_data_backend\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\entity_access_password\Service\EntityTypePasswordBundleInfoInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines bundle form routes.
 */
class BundleFormRoutes implements ContainerInjectionInterface {

  /**
   * The route path.
   *
   * "entity_access_password_user_data_backend.user_data_form.bundle.$entity_type_id.$bundle_id".
   */
  public const ROUTE_NAME = 'entity_access_password_user_data_backend.user_data_form.bundle.%s.%s';

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
  public static function create(ContainerInterface $container) : self {
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
  public function routes() : array {
    $routes = [];

    $password_infos = $this->entityTypePasswordBundleInfo->getAllPasswordBundleInfo();
    foreach ($password_infos as $entity_type_id => $entity_infos) {
      foreach (array_keys($entity_infos['bundles']) as $bundle_id) {
        $machine_name = sprintf(self::ROUTE_NAME, $entity_type_id, $bundle_id);
        $route = new Route("/admin/config/content/entity_access_password/user_data/$entity_type_id/$bundle_id");
        $route
          ->addDefaults([
            // @todo check if this is translatable and if possible to inject
            // variables.
            '_title' => 'Bundle password user data',
            '_form' => '\Drupal\entity_access_password_user_data_backend\Form\BundleUserDataEditForm',
          ])
          ->addRequirements([
            '_permission' => 'entity_access_password_user_data_backend_access_bundle_form',
          ])
          ->setOption('_eapudb_entity_type_id', $entity_type_id)
          ->setOption('_eapudb_bundle_id', $bundle_id);
        $routes[$machine_name] = $route;
        break;
      }
    }

    return $routes;
  }

}
