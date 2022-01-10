<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password_user_data_backend\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_access_password\Service\EntityTypePasswordBundleInfoInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides common code for deriver.
 */
abstract class UserDataBackendDeriverBase extends DeriverBase implements ContainerDeriverInterface {

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
    // @phpstan-ignore-next-line
    return new static(
      $container->get('entity_access_password.entity_type_password_bundle_info')
    );
  }

}
