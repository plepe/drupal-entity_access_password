<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'entity_access_password_password' field type.
 *
 * @FieldType(
 *   id = "entity_access_password_password",
 *   label = @Translation("Password Protection"),
 *   category = @Translation("Access"),
 *   default_formatter = "entity_access_password_form",
 *   default_widget = "entity_access_password_password",
 *   cardinality = 1,
 * )
 */
class EntityAccessPasswordItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() : array {
    return [
      'password' => '',
      'view_modes' => [],
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) : array {
    $settings = $this->getSettings();
    $element = [];

    $element['password'] = [
      '#type' => 'password_confirm',
      '#title' => $this->t('Password'),
      '#description' => $this->t('To act as a per-bundle password. If left empty will not overwrite current password (if any).'),
      '#size' => 25,
    ];

    $element['view_modes'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('View modes'),
      '#description' => $this->t('Check the view modes on which access control should be enforced. None if left empty.'),
      '#default_value' => $settings['view_modes'],
      '#options' => \Drupal::service('entity_display.repository')->getViewModeOptions($this->getEntity()->getEntityTypeId()),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() : bool {
    $is_protected = $this->get('is_protected')->getValue();
    $password = $this->get('password')->getValue();
    $hint = $this->get('hint')->getValue();
    if ($is_protected === TRUE) {
      return FALSE;
    }
    elseif (!empty($password)) {
      return FALSE;
    }
    elseif (!empty($hint)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) : array {
    $properties = [];
    $properties['is_protected'] = DataDefinition::create('boolean')
      ->setLabel(t('Entity is protected'));
    $properties['show_title'] = DataDefinition::create('boolean')
      ->setLabel(t('Show title'));
    $properties['hint'] = DataDefinition::create('string')
      ->setLabel(t('Hint'));
    $properties['password'] = DataDefinition::create('string')
      ->setLabel(t('Password'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) : array {
    $columns = [
      'is_protected' => [
        'type' => 'int',
        'size' => 'tiny',
      ],
      'show_title' => [
        'type' => 'int',
        'size' => 'tiny',
      ],
      'hint' => [
        'type' => 'text',
        'size' => 'big',
      ],
      'password' => [
        'type' => 'varchar',
        'length' => 255,
      ],
    ];

    return [
      'columns' => $columns,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) : array {
    $random = new Random();
    $values = [];
    $values['is_protected'] = (bool) mt_rand(0, 1);
    $values['show_title'] = (bool) mt_rand(0, 1);
    $values['hint'] = $random->paragraphs(1);
    $values['password'] = $random->word(mt_rand(1, 255));

    return $values;
  }

}
