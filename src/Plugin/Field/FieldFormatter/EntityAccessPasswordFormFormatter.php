<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_access_password\Service\PasswordFormBuilder;

/**
 * Plugin implementation of the 'entity_access_password_form' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_access_password_form",
 *   label = @Translation("Password form"),
 *   field_types = {
 *     "entity_access_password_password"
 *   }
 * )
 */
class EntityAccessPasswordFormFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() : array {
    return [
      'help_text' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) : array {
    $form = parent::settingsForm($form, $form_state);

    $form['help_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Help text'),
      '#description' => $this->t('The help text that will be displayed with the password form. HTML is accepted.'),
      '#default_value' => $this->getSetting('help_text'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() : array {
    $summary = [];
    $summary[] = empty($this->getSetting('help_text')) ? $this->t('No help text') : $this->t('With help text');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) : array {
    $elements = [];
    if ($items->count() < 1) {
      return $elements;
    }

    /** @var \Drupal\entity_access_password\Plugin\Field\FieldType\EntityAccessPasswordItem $itemsData */
    $itemsData = $items->get(0);
    /** @var array $values */
    $values = $itemsData->getValue();

    // Not protected.
    if (!$values['is_protected']) {
      return $elements;
    }

    /** @var string $help_text */
    $help_text = $this->getSetting('help_text');

    /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    $entity = $itemsData->getEntity();

    $elements[] = [
      '#lazy_builder' => [PasswordFormBuilder::SERVICE_ID . ':build',
        [
          $help_text,
          $values['hint'],
          $entity->id(),
          $entity->getEntityTypeId(),
          $items->getName(),
        ],
      ],
      '#create_placeholder' => TRUE,
    ];

    return $elements;
  }

}
