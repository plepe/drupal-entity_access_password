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
   * The password hashing service.
   *
   * @var \Drupal\Core\Password\PasswordInterface
   */
  protected PasswordInterface $password;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
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
  public function buildForm(array $form, FormStateInterface $form_state) : array {
    $config = $this->config('entity_access_password.settings');

    $form['global_password'] = [
      '#type' => 'details',
      '#title' => $this->t('Global password'),
      '#open' => TRUE,
    ];
    // @todo check if this boolean is still needed.
    $form['global_password']['allow'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow global password'),
      '#default_value' => $config->get('global_password.allow'),
    ];
    $form['global_password']['password'] = [
      '#type' => 'password_confirm',
      '#description' => $this->t('If left empty will not overwrite current password (if any).'),
      '#size' => 25,
    ];
    $form['random_password_length'] = [
      '#type' => 'number',
      '#title' => $this->t('Random password length'),
      '#description' => $this->t('The length of the randomly generated passwords.'),
      '#default_value' => $config->get('random_password_length'),
      '#required' => TRUE,
      '#min' => 8,
      '#max' => 50,
      '#step' => 1,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) : void {
    $password = $form_state->getValue('password');

    $config = $this->config('entity_access_password.settings');
    $config->set('global_password.allow', $form_state->getValue('allow'));
    if ($password) {
      $config->set('global_password.password', $this->password->hash($password));
    }
    $config->set('random_password_length', $form_state->getValue('random_password_length'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
