<?php

namespace Drupal\eca_form\Token;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Entity\ContentEntityFormInterface;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\BaseFormIdInterface;
use Drupal\eca\EventSubscriber\EcaExecutionFormSubscriber;
use Drupal\eca\Plugin\DataType\DataTransferObject;
use Drupal\eca\Token\DataProviderInterface;

/**
 * Provides data of the current form.
 */
class CurrentFormDataProvider implements DataProviderInterface {

  /**
   * The ECA form event subscriber.
   *
   * @var \Drupal\eca\EventSubscriber\EcaExecutionFormSubscriber|null
   */
  protected ?EcaExecutionFormSubscriber $subscriber = NULL;

  /**
   * In-memory cache of instantiated data.
   *
   * @var array
   */
  protected static array $cached = [];

  /**
   * {@inheritdoc}
   */
  public function getData(string $key) {
    if (!($events = $this->subscriber()->getStackedFormEvents())) {
      return NULL;
    }

    $form_state = reset($events)->getFormState();

    switch ($key) {

      case 'form':
      case 'current_form':
        $form_object = $form_state->getFormObject();
        $dto_values = [
          'id' => $form_object->getFormId(),
          'base-id' => $form_object instanceof BaseFormIdInterface ? $form_object->getBaseFormId() : NULL,
          'operation' => $form_object instanceof EntityFormInterface ? $form_object->getOperation() : NULL,
          'mode' => $form_object instanceof ContentEntityFormInterface ? $form_object->getFormDisplay($form_state)->id() : NULL,
          'dangerous_raw_values' => array_merge($form_state->getUserInput(), $form_state->getValues()),
          'triggered' => NULL,
        ];
        if ($triggering_element = $form_state->getTriggeringElement()) {
          if ($triggering_element['#name'] === 'op' && !empty($triggering_element['#array_parents'])) {
            $dto_values['triggered'] = end($triggering_element['#array_parents']);
          }
          else {
            $dto_values['triggered'] = $triggering_element['#name'];
          }
        }
        if (isset(static::$cached['form_dto_values'], static::$cached['form_dto']) && (static::$cached['form_dto_values'] === $dto_values)) {
          $dto = static::$cached['form_dto'];
        }
        else {
          $dto = DataTransferObject::create([
            'id' => $dto_values['id'],
            'base-id' => $dto_values['base-id'],
            'operation' => $dto_values['operation'],
            'triggered' => $dto_values['triggered'],
          ]);
          if (!empty($dto_values['dangerous_raw_values'])) {
            $values_sanitized = $dto_values['dangerous_raw_values'];
            array_walk_recursive($values_sanitized, static function (&$value) {
              if (!$value) {
                return;
              }
              if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString' ))) {
                $value = trim(Xss::filter(strip_tags((string) $value)));
              }
            });
            $dto->set('values', $values_sanitized);
          }
        }
        $dto->set('num-errors', count($form_state->getErrors()));
        static::$cached['form_dto_values'] = $dto_values;
        static::$cached['form_dto'] = $dto;
        return $dto;

      default:
        return NULL;

    }
  }

  /**
   * {@inheritdoc}
   */
  public function hasData(string $key): bool {
    return $this->getData($key) !== NULL;
  }

  /**
   * Get the ECA form event subscriber.
   *
   * This service can not be obtained through dependency injection, because
   * this may lead to a circular reference.
   * @see https://www.drupal.org/project/eca/issues/3318655
   *
   * @return \Drupal\eca\EventSubscriber\EcaExecutionFormSubscriber
   *   The subscriber.
   */
  protected function subscriber(): EcaExecutionFormSubscriber {
    if (!isset($this->subscriber)) {
      $this->subscriber = EcaExecutionFormSubscriber::get();
    }
    return $this->subscriber;
  }

}
