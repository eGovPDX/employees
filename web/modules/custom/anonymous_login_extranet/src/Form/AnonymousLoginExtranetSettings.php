<?php

namespace Drupal\anonymous_login_extranet\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AnonymousLoginExtranetSettings.
 */
class AnonymousLoginExtranetSettings extends ConfigFormBase {

  /**
   * The path validator service.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'anonymous_login_extranet.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'anonymous_login_extranet_settings';
  }

  /**
   * Constructs a AnonymousLoginExtranetSettings object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PathValidatorInterface $path_validator) {
    parent::__construct($config_factory);
    $this->pathValidator = $path_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('path.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('anonymous_login_extranet.settings');
    $form['paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Page paths'),
      '#default_value' => $config->get('paths'),
      '#description' => $this->t('Enter a list of page paths that will force anonymous users to login before viewing. After logging in, they will be redirected back to the requested page. Enter each path on a different line. Wildcards (*) can be used. Prefix a path with ~ (tilde) to exclude it from being redirected.'),
    ];
    $form['ip_exceptions'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Internal IP Addresses'),
      '#default_value' => $config->get('ip_exceptions'),
      '#description' => $this->t('Enter a list of internal IP addresses. Anonymous users from these IP addresses will not be forced to login. Enter each IP address on a different line. IP address ranges can also be entered using the format \'192.168.0.0 - 192.168.255.255\'.'),
    ];
    $form['login_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login page path'),
      '#default_value' => ($config->get('login_path')) ? $config->get('login_path') : '/user/login',
      '#required' => TRUE,
      '#description' => $this->t('Enter the user login page path of your site.'),
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Login message'),
      '#default_value' => $config->get('message'),
      '#description' => $this->t('Optionally provide a message that will be shown to users when they are redirected to login.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // IP address range validation.
    $ip_exceptions = $form_state->getValue('ip_exceptions');
    if (strlen($ip_exceptions)) {
      $ip_exceptions = trim($ip_exceptions);
      $form_state->setValue('ip_exceptions', $ip_exceptions);
      
      // Split by each newline.
      $setting = explode(PHP_EOL, $ip_exceptions);
  
      // Iterate each line and check for valid IP address or range.
      foreach ($setting as $ip_range) {
        if (preg_match('/^((2[0-4]\d|25[0-5]|1?\d?\d)(?:\.(?2)){3})\s*-\s*((?1))\r?$/', $ip_range)) {
          // Valid IP address range
          continue;
        }
        elseif (preg_match('/^(2[0-4]\d|25[0-5]|1?\d?\d)(?:\.(?1)){3}\r?$/', $ip_range)) {
          // Valid single IP address
          continue;
        }
        else {
          // Invalid address/range
          $form_state->setErrorByName('ip_exceptions', $this->t('Invalid IP address or range: ') . $ip_range);
        }
      }
    }
    
    // Login page path validation.
    $path = $this->pathValidator
      ->getUrlIfValidWithoutAccessCheck($form_state->getValue('login_path'));
    if (!$path) {
      $form_state->setErrorByName('login_path', $this->t('Login page path is invalid. Check it please.'));
    }
    else {
      // Set path without language prefix.
      $form_state->setValue('login_path', '/' . $path->getInternalPath());
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('anonymous_login_extranet.settings')
      ->set('paths', $form_state->getValue('paths'))
      ->set('ip_exceptions', $form_state->getValue('ip_exceptions'))
      ->set('login_path', $form_state->getValue('login_path'))
      ->set('message', $form_state->getValue('message'))
      ->save();
  }

}
