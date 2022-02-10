<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password_tests\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * For easier testing.
 */
final class TestController extends ControllerBase {

  /**
   * List the contents.
   *
   * @return array
   *   The render array that list the contents.
   */
  public function list(): array {
    $node_storage = $this->entityTypeManager()->getStorage('node');
    $node_view_builder = $this->entityTypeManager()->getViewBuilder('node');

    $nodes = $node_storage->loadMultiple();

    return $node_view_builder->viewMultiple($nodes, 'teaser');
  }

}
