<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Url;

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
      'password_entity' => FALSE,
      'password_bundle' => FALSE,
      'password_global' => FALSE,
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

    $element['password_entity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable per entity password access check'),
      '#default_value' => $settings['password_entity'],
    ];

    $element['password_bundle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable bundle password access check'),
      '#default_value' => $settings['password_bundle'],
    ];

    // Hidden element to store already saved password if not changed.
    $element['password'] = [
      '#type' => 'hidden',
      '#value' => $settings['password'],
    ];

    // Need to wrap password confirm for #states to work.
    // @see https://www.drupal.org/project/drupal/issues/1427838.
    $element['password_wrapper'] = [
      '#type' => 'container',
      '#states' => [
        'invisible' => [
          ':input[name="settings[password_bundle]"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];

    $element['password_wrapper']['password'] = [
      '#type' => 'password_confirm',
      '#title' => $this->t('Bundle password'),
      '#title_display' => 'hidden',
      '#description' => $this->t('To act as a per-bundle password. If left empty will not overwrite current password (if any).'),
      '#size' => 25,
    ];

    $element['password_global'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable global password access check'),
      '#description' => $this->t('Please ensure that a global password is set on the <a href=":url">configuration</a> page.', [
        ':url' => Url::fromRoute('entity_access_password.settings_form')->toString(),
      ]),
      '#default_value' => $settings['password_global'],
      '#element_validate' => [[static::class, 'massagePassword']],
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
   * Element validate function for password field.
   */
  public static function massagePassword(array $element, FormStateInterface $form_state) : void {
    /** @var string $password */
    $password = $form_state->getValue([
      'settings',
      'password_wrapper',
      'password',
    ]);
    if (!empty($password)) {
      $form_state->setValue(['settings', 'password'], \Drupal::service('password')->hash($password));
    }
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
  public function preSave() : void {
    parent::preSave();

    /** @var array $current_value */
    $current_value = $this->getValue();

    // If new password, save it.
    if (!empty($current_value['password'])) {
      return;
    }

    // If no new password, re-inject saved password if existing.
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();
    /** @var \Drupal\Core\Entity\ContentEntityInterface $original */
    // @phpstan-ignore-next-line
    $original = $entity->original;
    $field_name = $this->getFieldDefinition()->getName();

    /** @var array $original_value */
    $original_value = $original->get($field_name)->getValue();
    if (isset($original_value[0]['password']) && !empty($original_value[0]['password'])) {
      $current_value['password'] = $original_value[0]['password'];
      $this->setValue($current_value);
    }
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
  public static function mainPropertyName() {
    return 'is_protected';
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
