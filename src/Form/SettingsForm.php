<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Password\PasswordInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Entity Access Password settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Machine name of the config.
   */
  public const CONFIG_NAME = 'entity_access_password.settings';

  /**
   * Minimum possible length of the random password.
   */
  public const RANDOM_PASSWORD_LENGTH_MIN = 8;

  /**
   * Maximum possible length of the random password.
   */
  public const RANDOM_PASSWORD_LENGTH_MAX = 50;

  /**
   * The password hashing service.
   *
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected PasswordInterface $password;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    $instance = parent::create($container);
    $instance->password = $container->get('password');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_access_password_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config(self::CONFIG_NAME);

    $form['global_password'] = [
      '#type' => 'password_confirm',
      '#title' => $this->t('Global password'),
      '#description' => $this->t('If left empty will not overwrite current password (if any).'),
      '#size' => (int) 25,
    ];
    $form['random_password_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Random password length'),
      '#description' => $this->t('The length of the randomly generated passwords.'),
      '#default_value' => $config->get('random_password_length'),
      '#required' => TRUE,
      '#min' => self::RANDOM_PASSWORD_LENGTH_MIN,
      '#max' => self::RANDOM_PASSWORD_LENGTH_MAX,
      '#step' => 1,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var string $password */
    $password = $form_state->getValue('global_password');

    $config = $this->config(self::CONFIG_NAME);
    if ($password) {
      $config->set('global_password', $this->password->hash($password));
    }
    $config->set('random_password_length', $form_state->getValue('random_password_length'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
