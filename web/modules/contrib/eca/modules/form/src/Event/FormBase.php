<?php

namespace Drupal\eca_form\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\BaseFormIdInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eca\Event\ConditionalApplianceInterface;
use Drupal\eca\Event\EntityApplianceTrait;
use Drupal\eca\Event\FormEventInterface;

/**
 * Abstract base class for form events.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 *
 * @package Drupal\eca_form\Event
 */
abstract class FormBase extends Event implements ConditionalApplianceInterface, FormEventInterface {

  use EntityApplianceTrait;

  /**
   * The form array.
   *
   * This may be the complete form, or a sub-form, or a specific form element.
   *
   * @var array
   */
  protected array $form;

  /**
   * The form state.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected FormStateInterface $formState;

  /**
   * Constructs a FormBase instance.
   *
   * @param array &$form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function __construct(array &$form, FormStateInterface $form_state) {
    $this->form = &$form;
    $this->formState = $form_state;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    [$w_form_ids, $w_entity_type_ids, $w_bundles, $w_operations] = explode(':', $wildcard);
    $form_object = $this->getFormState()->getFormObject();

    if ($w_form_ids !== '*') {
      $form_ids = [$form_object->getFormId()];
      if ($form_object instanceof BaseFormIdInterface) {
        $form_ids[] = $form_object->getBaseFormId();
      }

      if (empty(array_intersect($form_ids, explode(',', $w_form_ids)))) {
        return FALSE;
      }
    }

    /** @var \Drupal\Core\Entity\EntityFormInterface $form_object */
    $is_entity_form = ($form_object instanceof EntityFormInterface);

    if ($w_entity_type_ids !== '*') {
      if (!$is_entity_form) {
        return FALSE;
      }
      if (!in_array($form_object->getEntity()->getEntityTypeId(), explode(',', $w_entity_type_ids), TRUE)) {
        return FALSE;
      }
    }

    if ($w_bundles !== '*') {
      if (!$is_entity_form) {
        return FALSE;
      }
      if (!in_array($form_object->getEntity()->bundle(), explode(',', $w_bundles), TRUE)) {
        return FALSE;
      }
    }

    if ($w_operations !== '*') {
      if (!$is_entity_form) {
        return FALSE;
      }
      if (!in_array($form_object->getOperation(), explode(',', $w_operations), TRUE)) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function applies(string $id, array $arguments): bool {
    $form_object = $this->getFormState()->getFormObject();

    if (!empty($arguments['form_id']) && $arguments['form_id'] !== '*') {
      $form_ids = [$form_object->getFormId()];
      if ($form_object instanceof BaseFormIdInterface) {
        $form_ids[] = $form_object->getBaseFormId();
      }

      $contains_form_id = FALSE;
      foreach (explode(',', $arguments['form_id']) as $c_form_id) {
        $c_form_id = strtolower(trim(str_replace('-', '_', $c_form_id)));
        if ($contains_form_id = in_array($c_form_id, $form_ids, TRUE)) {
          break;
        }
      }
      if (!$contains_form_id) {
        return FALSE;
      }
    }

    /** @var \Drupal\Core\Entity\EntityFormInterface $form_object */
    $is_entity_form = ($form_object instanceof EntityFormInterface);

    if (!$is_entity_form && !empty($arguments['entity_type_id']) && $arguments['entity_type_id'] !== '*') {
      return FALSE;
    }

    if (!$is_entity_form && !empty($arguments['bundle']) && $arguments['bundle'] !== '*') {
      return FALSE;
    }

    if ($is_entity_form && !$this->appliesForEntityTypeOrBundle($form_object->getEntity(), $arguments)) {
      return FALSE;
    }

    if (!empty($arguments['operation']) && $arguments['operation'] !== '*') {
      if (!$is_entity_form) {
        return FALSE;
      }

      $contains_operation = FALSE;
      foreach (explode(',', $arguments['operation']) as $c_operation) {
        $c_operation = trim($c_operation);
        if ($contains_operation = ($c_operation === $form_object->getOperation())) {
          break;
        }
      }
      if (!$contains_operation) {
        return FALSE;
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function &getForm(): array {
    return $this->form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormState(): FormStateInterface {
    return $this->formState;
  }

}
