<?php

declare(strict_types = 1);

namespace Drupal\entity_access_password\Form;

use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Form\BaseFormIdInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_access_password\Service\PasswordValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an Entity Access Password form.
 */
class PasswordForm extends FormBase implements BaseFormIdInterface, PasswordFormInterface {

  /**
   * Flood config name.
   */
  public const FLOOD_CONFIG_NAME = 'user.flood';

  /**
   * Flood event fot IP max attempts.
   */
  public const FLOOD_EVENT_IP = 'entity_access_password.failed_password_ip';

  /**
   * Flood event for user max attempts.
   */
  public const FLOOD_EVENT_USER = 'entity_access_password.failed_password_user';

  /**
   * The flood service.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected FloodInterface $flood;

  /**
   * The password validator.
   *
   * @var \Drupal\entity_access_password\Service\PasswordValidatorInterface
   */
  protected PasswordValidatorInterface $passwordValidator;

  /**
   * Prevent form cache problem of displaying the same form multiple times.
   *
   * @var string
   */
  protected string $formIdSuffix = '';

  /**
   * {@inheritdoc}
   */
  public function __construct(
    FloodInterface $flood,
    PasswordValidatorInterface $passwordValidator
  ) {
    $this->flood = $flood;
    $this->passwordValidator = $passwordValidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) : self {
    return new self(
      $container->get('flood'),
      $container->get('entity_access_password.password_validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() : string {
    return 'entity_access_password_password';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return 'entity_access_password_password_' . $this->formIdSuffix;
  }

  /**
   * Prevent form cache problem of displaying the same form multiple times.
   *
   * @param string $suffix
   *   The form ID suffix.
   */
  public function setFormIdSuffix(string $suffix) : void {
    $this->formIdSuffix = $suffix;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) : array {
    // Not possible to know for which entity the form is built against.
    if (empty($form_state->getBuildInfo()['args'])) {
      return [];
    }

    $form['form_password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#required' => TRUE,
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    // Validation in two steps to properly handle flood and allow modules to
    // alter the form to add validation steps in between if needed.
    $form['#validate'][] = '::validatePassword';
    $form['#validate'][] = '::validateFinal';

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\user\Form\UserLoginForm::validateAuthentication()
   */
  public function validatePassword(array &$form, FormStateInterface $form_state) : void {
    $flood_config = $this->config(self::FLOOD_CONFIG_NAME);
    /** @var int $ip_limit */
    $ip_limit = $flood_config->get('ip_limit');
    /** @var int $ip_window */
    $ip_window = $flood_config->get('ip_window');
    /** @var int $user_limit */
    $user_limit = $flood_config->get('user_limit');
    /** @var int $user_window */
    $user_window = $flood_config->get('user_window');

    if (!$this->flood->isAllowed(self::FLOOD_EVENT_IP, $ip_limit, $ip_window)) {
      $form_state->set('flood_control_triggered', 'ip');
      return;
    }

    $account = $this->currentUser();
    if ($account->isAuthenticated()) {
      if ($flood_config->get('uid_only')) {
        $identifier = (string) $account->id();
      }
      else {
        $identifier = $account->id() . '-' . $this->getRequest()->getClientIP();
      }
      $form_state->set('flood_control_user_identifier', $identifier);

      if (!$this->flood->isAllowed(self::FLOOD_EVENT_USER, $user_limit, $user_window, $identifier)) {
        $form_state->set('flood_control_triggered', 'user');
        return;
      }
    }

    /** @var string $password */
    $password = $form_state->getValue('form_password');
    $form_state->set('password_is_valid', $this->passwordValidator->validatePassword($password, $form_state->getBuildInfo()['args'][0]));
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\user\Form\UserLoginForm::validateFinal()
   */
  public function validateFinal(array &$form, FormStateInterface $form_state) : void {
    $flood_config = $this->config(self::FLOOD_CONFIG_NAME);
    /** @var int $ip_window */
    $ip_window = $flood_config->get('ip_window');
    /** @var int $user_limit */
    $user_limit = $flood_config->get('user_limit');
    /** @var int $user_window */
    $user_window = $flood_config->get('user_window');

    $flood_control_triggered = $form_state->get('flood_control_triggered');
    /** @var string $flood_control_user_identifier */
    $flood_control_user_identifier = $form_state->get('flood_control_user_identifier');

    // Invalid flood.
    if ($flood_control_triggered) {
      $message = $this->t('Too many failed attempts from your IP address. This IP address is temporarily blocked. Try again later.');

      if ($flood_control_triggered == 'user') {
        $message = $this->formatPlural($user_limit, 'There has been more than one failed attempt for this account. It is temporarily blocked. Try again later.', 'There have been more than @count failed attempts for this account. It is temporarily blocked. Try again later.');
      }

      $form_state->setError($form['form_password'], $message);
    }

    $password_is_valid = $form_state->get('password_is_valid');
    if (!$password_is_valid) {
      $form_state->setError($form['form_password'], $this->t('Incorrect password!'));

      // Register flood.
      $this->flood->register(self::FLOOD_EVENT_IP, $ip_window);
      if ($flood_control_user_identifier) {
        $this->flood->register(self::FLOOD_EVENT_USER, $user_window, $flood_control_user_identifier);
      }
    }
    elseif ($flood_control_user_identifier) {
      // Clear in case of successful access.
      $this->flood->clear(self::FLOOD_EVENT_USER, $flood_control_user_identifier);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) : void {
    // The access storage is managed in the password validator service because
    // it has the knowledge of the access perimeter (entity, bundle, global).
  }

}
