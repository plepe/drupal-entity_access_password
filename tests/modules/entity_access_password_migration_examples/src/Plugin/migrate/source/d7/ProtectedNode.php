<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password_migration_examples\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\node\Plugin\migrate\source\d7\Node;

/**
 * Exemple source plugin for Protected Node custom table.
 *
 * @MigrateSource(
 *     id = "d7_entity_access_password_protected_node",
 *     source_module = "protected_node",
 * )
 */
class ProtectedNode extends Node {

  /**
   * {@inheritdoc}
   */
  public function fields(): array {
    $fields = parent::fields();
    $fields['protected_node'] = $this->t('Protected node infos');
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row): bool {
    $nid = $row->getSourceProperty('nid');

    $protected_nodes = $this->select('protected_nodes', 'pn')
      ->condition('pn.nid', $nid)
      ->fields('pn', [
        'protected_node_is_protected',
        'protected_node_passwd',
        'protected_node_show_title',
        'protected_node_hint',
      ])
      ->execute()
      ->fetchAll();

    if (!empty($protected_nodes)) {
      $protected_node = \array_shift($protected_nodes);
      $row->setSourceProperty('protected_node', [
        'is_protected' => $protected_node['protected_node_is_protected'],
        'show_title' => $protected_node['protected_node_show_title'],
        'hint' => $protected_node['protected_node_hint'],
        'password' => $protected_node['protected_node_passwd'],
      ]);
    }
    else {
      $row->setSourceProperty('protected_node', []);
    }

    return parent::prepareRow($row);
  }

}
