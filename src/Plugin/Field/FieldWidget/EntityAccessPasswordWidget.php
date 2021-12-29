<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Random;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Password\PasswordInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_access_password\Form\SettingsForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the 'entity_access_password_password' field widget.
 *
 * @FieldWidget(
 *   id = "entity_access_password_password",
 *   label = @Translation("Password Protection"),
 *   field_types = {"entity_access_password_password"},
 * )
 */
class EntityAccessPasswordWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  /**
   * The password hashing service object.
   *
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected PasswordInterface $password;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) : self {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->password = $container->get('password');
    $instance->configFactory = $container->get('config.factory');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() : array {
    return [
      'open' => FALSE,
      'show_entity_title' => 'optional',
      'show_hint' => 'optional',
      'allow_random_password' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) : array {
    $element = parent::settingsForm($form, $form_state);

    $element['open'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show widget details as opened by default'),
      '#description' => $this->t('If checked, the fieldset that wraps the Entity Access Password field will initially be displayed expanded.'),
      '#default_value' => $this->getSetting('open'),
    ];

    $element['show_entity_title'] = [
      '#type' => 'radios',
      '#title' => $this->t('Show entity title'),
      '#default_value' => $this->getSetting('show_entity_title'),
      '#options' => $this->getShowTitleOptions(),
      'never' => [
        '#description' => $this->t('No option available on the entity form and never display the title.'),
      ],
      'optional' => [
        '#description' => $this->t('Possible to choose on the entity form to display the title or not.'),
      ],
      'always' => [
        '#description' => $this->t('No option available on the entity form and always display the title.'),
      ],
    ];

    $element['show_hint'] = [
      '#type' => 'radios',
      '#title' => $this->t('Show hint'),
      '#default_value' => $this->getSetting('show_hint'),
      '#options' => $this->getShowHintOptions(),
      'never' => [
        '#description' => $this->t('No option available on the entity form and clear existing values.'),
      ],
      'optional' => [
        '#description' => $this->t('Possible to choose on the entity form to enter a hint or not.'),
      ],
      'always' => [
        '#description' => $this->t('Make the hint required on the entity form.'),
      ],
    ];

    $element['allow_random_password'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow to generate a random password'),
      '#description' => $this->t('If checked, a checkbox will be displayed on the entity form to allow to generate a random password.'),
      '#default_value' => $this->getSetting('allow_random_password'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() : array {
    $summary = [];
    $summary[] = $this->getSetting('open') ? $this->t('Opened by default') : $this->t('Closed by default');
    $summary[] = $this->t('Show entity title: @value', [
      '@value' => $this->getShowTitleOptions()[$this->getSetting('show_entity_title')],
    ]);
    $summary[] = $this->t('Show hint: @value', [
      '@value' => $this->getShowHintOptions()[$this->getSetting('show_hint')],
    ]);
    $summary[] = $this->getSetting('allow_random_password') ? $this->t('Random password allowed') : $this->t('Random password not allowed');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) : array {
    /** @var \Drupal\entity_access_password\Plugin\Field\FieldType\EntityAccessPasswordItem $item */
    $item = $items[$delta];

    $element['is_protected'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable password protection'),
      '#default_value' => isset($items[$delta]->is_protected) ? $items[$delta]->is_protected : $element['#required'] ?? FALSE,
      '#required' => $element['#required'],
    ];

    // Allows password confirm states to depend only on the random password
    // checkbox.
    $element['password_wrapper'] = [
      '#type' => 'container',
      '#states' => [
        'invisible' => [
          ':input[name="' . $this->fieldDefinition->getName() . '[0][is_protected]"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];

    if ($this->getSetting('allow_random_password')) {
      $element['password_wrapper']['random_password'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Generate random password'),
        '#default_value' => FALSE,
      ];
    }

    // Need to wrap password confirm for #states to work.
    // @see https://www.drupal.org/project/drupal/issues/1427838.
    $element['password_wrapper']['password_confirm_wrapper'] = [
      '#type' => 'container',
      '#states' => [
        'invisible' => [
          ':input[name="' . $this->fieldDefinition->getName() . '[0][password_wrapper][random_password]"]' => [
            'checked' => TRUE,
          ],
        ],
      ],
    ];
    $element['password_wrapper']['password_confirm_wrapper']['password'] = [
      '#type' => 'password_confirm',
      '#description' => $this->t('If left empty will not overwrite current password (if any).'),
    ];

    $show_entity_title_setting = $this->getSetting('show_entity_title');
    switch ($show_entity_title_setting) {
      case 'never':
        $element['show_title'] = [
          '#type' => 'hidden',
          '#value' => 0,
        ];
        break;

      case 'optional':
        $element['show_title'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Show entity title'),
          '#default_value' => isset($items[$delta]->show_title) ? $items[$delta]->show_title : FALSE,
          '#states' => [
            'invisible' => [
              ':input[name="' . $this->fieldDefinition->getName() . '[0][is_protected]"]' => [
                'checked' => FALSE,
              ],
            ],
          ],
        ];
        break;

      case 'always':
        $element['show_title'] = [
          '#type' => 'hidden',
          '#value' => 1,
        ];
        break;
    }

    $show_hint_setting = $this->getSetting('show_hint');
    if ($show_hint_setting == 'never') {
      $element['hint'] = [
        '#type' => 'hidden',
        '#value' => '',
      ];
    }
    else {
      $element['hint'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Password hint'),
        '#default_value' => isset($items[$delta]->hint) ? $items[$delta]->hint : '',
        '#required' => ($element['#required'] && $show_hint_setting === 'always'),
        '#states' => [
          'invisible' => [
            ':input[name="' . $this->fieldDefinition->getName() . '[0][is_protected]"]' => [
              'checked' => FALSE,
            ],
          ],
        ],
      ];
    }

    $element += [
      '#type' => 'details',
      '#open' => $this->getSetting('open'),
    ];
    // Put the form element into the form's "advanced" group if on a node.
    if ($item->getFieldDefinition()->getTargetEntityTypeId() == 'node') {
      $element += [
        '#group' => 'advanced',
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) : array {
    foreach ($values as &$value) {
      $password = $value['password_wrapper']['password_confirm_wrapper']['password'];

      // Random password.
      if ($value['password_wrapper']['random_password']) {
        $global_settings = $this->configFactory->get(SettingsForm::CONFIG_NAME);
        /** @var int $random_password_length */
        $random_password_length = $global_settings->get('random_password_length');
        $random = new Random();
        $random_password = $random->string($random_password_length);
        $value['password'] = $this->password->hash($random_password);

        // This method is called during form validation and form submission.
        // Only display the random password for the submission.
        if (isset($form['#validated']) && $form['#validated']) {
          $this->messenger()->addWarning($this->t('Please note the randomly generated password as it will not be possible to show it again: @password', [
            '@password' => $random_password,
          ]));
        }
      }
      elseif (!empty($password)) {
        $value['password'] = $this->password->hash($password);
      }

      // Cleanup.
      unset($value['password_wrapper']);
    }
    return $values;
  }

  /**
   * Intermediate method in case options will differ.
   *
   * @return array
   *   The setting options.
   */
  protected function getShowTitleOptions() : array {
    return $this->getShowOptions();
  }

  /**
   * Intermediate method in case options will differ.
   *
   * @return array
   *   The setting options.
   */
  protected function getShowHintOptions() : array {
    return $this->getShowOptions();
  }

  /**
   * Get some settings options.
   *
   * Can not use a constant due to translatable label.
   *
   * @return array
   *   The settings options.
   */
  protected function getShowOptions() : array {
    return [
      'never' => $this->t('Never'),
      'optional' => $this->t('Optional'),
      'always' => $this->t('Always'),
    ];
  }

}
