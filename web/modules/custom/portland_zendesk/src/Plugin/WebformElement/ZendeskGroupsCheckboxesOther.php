<?php

namespace Drupal\portland_zendesk\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\Checkboxes;
use Drupal\webform\Plugin\WebformElementOtherInterface;
use Drupal\webform\WebformSubmissionConditionsValidator;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'zendesk_groups_checkboxes_other' element.
 *
 * @WebformElement(
 *   id = "zendesk_groups_checkboxes_other",
 *   label = @Translation("Zendesk Groups Checkboxes Other"),
 *   description = @Translation("Provides a form element for a set of checkboxes, with the ability to enter a custom value."),
 *   category = @Translation("Portland elements"),
 * )
 */
class ZendeskGroupsCheckboxesOther extends Checkboxes implements WebformElementOtherInterface {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    $properties = parent::defineDefaultProperties();
    // Remove 'All of the above' options.
    unset(
      $properties['options_all'],
      $properties['options_all_value'],
      $properties['options_all_text']
    );
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $element, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($element, $form_state);

    // Add "Other options settings".
    $form['other'] = [
      '#type' => 'details',
      '#title' => $this->t('Other options settings'),
      '#open' => TRUE,
    ];
    $form['other']['#element_validate'][] = [get_class($this), 'validateOtherOptions'];

    $form['other']['other__title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other option title'),
      '#default_value' => $element['#other__title'] ?? '',
      '#description' => $this->t('The title for the "Other" option.'),
    ];

    $form['other']['other__value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other option value'),
      '#default_value' => $element['#other__value'] ?? '',
      '#description' => $this->t('The value for the "Other" option.'),
    ];

    $form['other']['other__description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other option description'),
      '#default_value' => $element['#other__description'] ?? '',
      '#description' => $this->t('The description for the "Other" option.'),
    ];

    $form['other']['other__placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other option placeholder'),
      '#default_value' => $element['#other__placeholder'] ?? '',
      '#description' => $this->t('The placeholder for the "Other" option.'),
    ];

    $form['other']['other__required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Other option required'),
      '#default_value' => $element['#other__required'] ?? '',
      '#description' => $this->t('Whether the "Other" option is required.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorOptions(array $element) {
    $title = $this->getAdminLabel($element);
    $name = $element['#webform_key'];

    $selectors = [];
    foreach ($element['#options'] as $input_name => $input_title) {
      $selectors[":input[name=\"{$name}[checkboxes][{$input_name}]\"]"] = $input_title . ' [' . $this->t('Checkboxes') . ']';
    }
    $selectors[":input[name=\"{$name}[other]\"]"] = $title . ' [' . $this->t('Textfield') . ']';
    return [$title => $selectors];
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorInputValue($selector, $trigger, array $element, WebformSubmissionInterface $webform_submission) {
    $input_name = WebformSubmissionConditionsValidator::getSelectorInputName($selector);
    $other_type = WebformSubmissionConditionsValidator::getInputNameAsArray($input_name, 1);
    $value = $this->getRawValue($element, $webform_submission) ?: [];
    if ($other_type === 'other') {
      $other_value = array_diff($value, array_keys($element['#options']));
      return ($other_value) ? implode(', ', $other_value) : NULL;
    }
    else {
      $option_value = WebformSubmissionConditionsValidator::getInputNameAsArray($input_name, 2);
      if (in_array($option_value, $value, TRUE)) {
        return (in_array($trigger, ['checked', 'unchecked'])) ? TRUE : $value;
      }
      else {
        return (in_array($trigger, ['checked', 'unchecked'])) ? FALSE : NULL;
      }
    }
  }

  /**
   * Form element validation handler for other options.
   */
  public static function validateOtherOptions(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // Custom validation logic for other options.
  }

}