<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password_user_data_backend\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_access_password\Service\EntityTypePasswordBundleInfoInterface;
use Drupal\entity_access_password_user_data_backend\Routing\BundleFormRoutes;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides menu links definitions for bundles.
 */
class UserDataBackendBundleMenuLink extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

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
  public static function create(ContainerInterface $container, $base_plugin_id) : self {
    return new self(
      $container->get('entity_access_password.entity_type_password_bundle_info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) : array {
    $this->derivatives = [];

    $password_infos = $this->entityTypePasswordBundleInfo->getAllPasswordBundleInfo();
    foreach ($password_infos as $entity_type_id => $entity_infos) {
      /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_type */
      $entity_type = $entity_infos['entity_type'];
      foreach ($entity_infos['bundles'] as $bundle_id => $bundle_infos) {
        $route_name = sprintf(BundleFormRoutes::ROUTE_NAME, $entity_type_id, $bundle_id);
        $this->derivatives[$route_name] = [
          'title' => $this->t('@entity_type: @bundle', [
            '@entity_type' => $entity_type->getLabel(),
            '@bundle' => $bundle_infos['label'],
          ]),
          'description' => $this->t('Access form to purge @entity_type "@bundle" password access user data.', [
            '@entity_type' => $entity_type->getLabel(),
            '@bundle' => $bundle_infos['label'],
          ]),
          'route_name' => $route_name,
          'parent' => 'entity_access_password_user_data_backend.admin_config_page',
          'menu_name' => 'admin',
        ];
        break;
      }
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
