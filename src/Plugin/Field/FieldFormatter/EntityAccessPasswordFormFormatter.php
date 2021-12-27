<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Plugin\Field\FieldFormatter;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_access_password\Form\PasswordForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class EntityAccessPasswordFormFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected FormBuilderInterface $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) : self {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->formBuilder = $container->get('form_builder');
    return $instance;
  }

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

    /** @var \Drupal\Core\TypedData\TypedDataInterface $itemsData */
    $itemsData = $items->get(0);
    /** @var array $values */
    $values = $itemsData->getValue();

    // Not protected.
    if (!$values['is_protected']) {
      return $elements;
    }

    /** @var string $help_text */
    $help_text = $this->getSetting('help_text');

    $elements[] = [
      '#theme' => 'entity_access_password_form',
      '#help_text' => new FormattableMarkup(Xss::filterAdmin($help_text), []),
      '#hint' => XSS::filter($values['hint']),
      // @phpstan-ignore-next-line
      '#form' => $this->formBuilder->getForm(PasswordForm::class, ['field' => $itemsData]),
    ];

    return $elements;
  }

}
