<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password_migrate\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process Protected Node.
 *
 * @MigrateProcessPlugin(
 *   id = "process_protected_node"
 * )
 *
 * @code
 * field_password_protect:
 *   plugin: process_protected_node
 *   source: nid
 *   migration_db_key: <migration db key>
 *   bundle: <bundle>
 *   langcode: <langcode>
 * @endcode
 */
class ProcessProtectedNode extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    /** @var string $d7_nid */
    $d7_nid = $value;

    if ($d7_nid && is_numeric($d7_nid)) {
      // Get D7 Protected Node Entry.
      Database::setActiveConnection($this->configuration['migration_db_key']);
      $database = Database::getConnection();
      $query = $database->select('protected_nodes', 'pn');
      $query->fields('pn');
      $query->condition('pn.nid', $d7_nid, '=');
      /** @var \Drupal\Core\Database\StatementInterface $query */
      $query->execute();

      if ($query == NULL) {
        return [];
      }

      $protected_node_row = $query->fetch();

      if ($protected_node_row->protected_node_is_protected) {
        // Set D8 Password field values.
        $destination = $row->getDestination();
        $field_password_protect = [];
        $field_password_protect[] = [
          'bundle' => $this->configuration['bundle'],
          'deleted' => 0,
          'entity_id' => $destination['nid'],
          'langcode' => $this->configuration['langcode'],
          'is_protected' => 1,
          'show_title' => $protected_node_row->protected_node_show_title,
          'hint' => $protected_node_row->protected_node_hint,
          'password' => $protected_node_row->protected_node_passwd,
        ];
        return $field_password_protect;
      }
    }

    return [];
  }

}
