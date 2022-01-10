<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password_user_data_backend\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\entity_access_password_user_data_backend\HookHandler\EntityTypeInfo;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides local task definitions for entity bundles.
 */
class UserDataBackendEntityLocalTask extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) : self {
    return new self(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) : array {
    $this->derivatives = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      $has_eap_edit_user_data_path = $entity_type->hasLinkTemplate(EntityTypeInfo::ENTITY_LINK_TEMPLATE);
      $has_canonical_path = $entity_type->hasLinkTemplate('canonical');
      $has_edit_path = $entity_type->hasLinkTemplate('edit');

      if ($has_eap_edit_user_data_path && ($has_canonical_path || $has_edit_path)) {
        $this->derivatives["$entity_type_id." . EntityTypeInfo::ENTITY_OPERATION] = [
          'title' => $this->t('Edit password access user data'),
          'route_name' => "entity.$entity_type_id." . EntityTypeInfo::ENTITY_OPERATION,
          'base_route' => "entity.$entity_type_id." . ($has_canonical_path ? "canonical" : "edit_form"),
          'weight' => 50,
        ];
      }
    }

    foreach ($this->derivatives as &$entry) {
      $entry += $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
