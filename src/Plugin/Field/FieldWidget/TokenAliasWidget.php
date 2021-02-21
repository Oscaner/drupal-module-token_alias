<?php

namespace Drupal\token_alias\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\pathauto\PathautoGenerator;
use Drupal\pathauto\PathautoWidget;
use Drupal\token_alias\TokenAliasFormHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TokenAliasWidget.
 *
 * @package Drupal\token_alias
 */
class TokenAliasWidget extends PathautoWidget {

  /**
   * Current state.
   *
   * @var bool
   */
  protected $update = FALSE;

  /**
   * The temporary alias.
   *
   * @var string[]
   */
  protected $temporaryAliases = [];

  /**
   * The pathauto generator.
   *
   * @var \Drupal\pathauto\PathautoGenerator
   */
  protected $pathautoGenerator;

  /**
   * The pathauto pro form helper.
   *
   * @var \Drupal\token_alias\TokenAliasFormHelperInterface
   */
  protected $tokenAliasFormHelper;

  /**
   * Constructs a WidgetBase object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   */
  public function __construct(
    $plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings,
    PathautoGenerator $pathauto_generator,
    TokenAliasFormHelperInterface $token_alias_form_helper
  ) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->pathautoGenerator = $pathauto_generator;
    $this->tokenAliasFormHelper = $token_alias_form_helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('pathauto.generator'),
      $container->get('token_alias.form_helper')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $entity = $items->getEntity();
    $pattern = $this->pathautoGenerator->getPatternByEntity($entity);

    // Set state (Is update or not).
    $this->update = (
      (bool) $form_state->getValue(['path', $delta, 'pid']) ||
      (bool) $form_state->getValue(['entity', 'path', $delta, 'pid']) ||
      !$entity->isNew()
    );

    // Checks, if not have pattern for this entity.
    if (!$pattern) {
      return $element;
    }

    // Format pathauto description.
    $pathauto_description = $element['pathauto']['#description'] ?? new TranslatableMarkup('');
    $element['pathauto']['#description'] = $this->t(
      $pathauto_description->getUntranslatedString() . '<br />Pattern: <code>@pattern</code>',
      array_merge(['@pattern' => $pattern->getPattern()], $pathauto_description->getArguments())
    );

    // Show the token help relevant to this alias.
    $element['token_help'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => $pattern->getAliasType()->getTokenTypes(),
    ];

    // Pathauto ajax.
    $element['pathauto']['#ajax'] = [
      'callback' => [$this, 'pathautoAjaxChange'],
      'disable-refocus' => FALSE,
      'wrapper' => 'wrapper-pathauto-pro-alias-' . $delta,
      'event' => 'change',
    ];

    $element['alias']['#prefix'] = "<div id=\"wrapper-pathauto-pro-{$delta}-alias\">";
    $element['alias']['#suffix'] = '</div>';

    // Input value.
    if ($input_alias = $form_state->getValue(['path', $delta, 'alias'])) {
      $this->temporaryAliases[$delta] = $input_alias;
    }
    elseif ($input_alias = $form_state->getValue(['entity', 'path', $delta, 'alias'])) {
      $this->temporaryAliases[$delta] = $input_alias;
    }
    // Force override temporary aliases if current state is update.
    if ($this->update) {
      $this->temporaryAliases[$delta] = $input_alias ?: '';
    }

    // Set pattern as default alias if needed.
    if (isset($this->temporaryAliases[$delta]) && $this->temporaryAliases[$delta]) {
      $element['alias']['#default_value'] = $element['alias']['#default_value'] ?: $this->temporaryAliases[$delta];
    }
    elseif (isset($this->temporaryAliases[$delta]) && $this->update) {
      $element['alias']['#default_value'] = $element['alias']['#default_value'] ?: $this->temporaryAliases[$delta];
    }
    else {
      $element['alias']['#default_value'] = $element['alias']['#default_value'] ?: $pattern->getPattern();
    }

    return $element;
  }

  /**
   * Ajax change function.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The value.
   */
  public function pathautoAjaxChange(array &$form, FormStateInterface $form_state) {
    $trigger_element = $form_state->getTriggeringElement();

    // Get alias element.
    $element_parents = $trigger_element['#array_parents'];
    $element_parents[count($element_parents) - 1] = 'alias';
    $alias_element = NestedArray::getValue($form, $element_parents);

    // Entity.
    $entity = $this->tokenAliasFormHelper->getEntityFromFormState($form_state);
    $pattern = $entity ? $this->pathautoGenerator->getPatternByEntity($entity) : NULL;
    $pathauto_status = (bool) $trigger_element['#value'];

    // Alter alias value.
    if ($pathauto_status && $pattern) {
      if (preg_match("/wrapper-pathauto-pro-(\d+)-alias/", ($alias_element['#prefix'] ?? ''), $matches) && isset($matches[1])) {
        $alias_element['#value'] = $this->temporaryAliases[$matches[1]] ?? $pattern->getPattern();
      }
      else {
        $alias_element['#value'] = $pattern->getPattern();
      }
    }

    NestedArray::setValue($form, $element_parents, $alias_element, TRUE);

    return NestedArray::getValue($form, $element_parents);
  }

}
