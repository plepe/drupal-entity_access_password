<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\BooleanFormatter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_access_password_boolean' formatter.
 *
 * @FieldFormatter(
 *     id = "entity_access_password_boolean",
 *     label = @Translation("Boolean"),
 *     field_types = {
 *         "entity_access_password_password"
 *     }
 * )
 */
class EntityAccessPasswordBooleanFormatter extends BooleanFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'format' => 'yes-no',
      'condition_property' => 'is_protected',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);

    // Remove the default option because an Entity Access Password field does
    // not have boolean settings.
    unset($form['format']['#options']['default']);

    $form['condition_property'] = [
      '#type' => 'select',
      '#title' => $this->t('Condition property'),
      '#description' => $this->t('Select which field property be evaluated.'),
      '#required' => TRUE,
      '#default_value' => $this->getSetting('condition_property'),
      '#options' => [
        'is_protected' => $this->t('Entity is protected'),
        'show_title' => $this->t('Show title'),
        'hint' => $this->t('Hint'),
        'password' => $this->t('Password'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = parent::settingsSummary();
    $summary[] = $this->t('Condition property: @condition_property', [
      '@condition_property' => $this->getSetting('condition_property'),
    ]);
    // @phpstan-ignore-next-line
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode): array {
    /** @var \Drupal\entity_access_password\Plugin\Field\FieldType\EntityAccessPasswordItem[] $items */
    $elements = [];

    $formats = $this->getOutputFormats();
    $condition_property = $this->getSetting('condition_property');

    foreach ($items as $delta => $item) {
      /** @var array $values */
      $values = $item->getValue();

      $format = $this->getSetting('format');

      if ($format == 'custom') {
        $elements[$delta] = ['#markup' => $values[$condition_property] ? $this->getSetting('format_custom_true') : $this->getSetting('format_custom_false')];
      }
      else {
        $elements[$delta] = ['#markup' => $values[$condition_property] ? $formats[$format][0] : $formats[$format][1]];
      }
    }

    return $elements;
  }

}
