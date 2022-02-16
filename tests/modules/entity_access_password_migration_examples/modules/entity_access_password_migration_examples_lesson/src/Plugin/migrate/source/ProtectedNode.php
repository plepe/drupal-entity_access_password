<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password_migration_examples_lesson\Plugin\migrate\source;

use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Source plugin for protected_node migration.
 *
 * @MigrateSource(
 *     id = "protected_node"
 * )
 */
class ProtectedNode extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Query d7 db table node.
    $query = $this->select('node', 'node');
    $query->fields('node');

    // Replace lesson by your custom protected node node type.
    $query->condition('node.type', 'lesson', '=');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    // Only used for Migration UI.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return $this->query()->__toString();
  }

}
