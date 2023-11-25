<?php

namespace Drupal\eca_queue\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\eca\Event\ConditionalApplianceInterface;
use Drupal\eca_queue\Task;

/**
 * Dispatches when a queued ECA task is being processed.
 *
 * @internal
 *   This class is not meant to be used as a public API. It is subject for name
 *   change or may be removed completely, also on minor version updates.
 */
class ProcessingTaskEvent extends Event implements ConditionalApplianceInterface {

  /**
   * The task that is being processed.
   *
   * @var \Drupal\eca_queue\Task
   */
  protected Task $task;

  /**
   * The ProcessingTaskEvent constructor.
   *
   * @param \Drupal\eca_queue\Task $task
   *   The task that is being processed.
   */
  public function __construct(Task $task) {
    $this->task = $task;
  }

  /**
   * Get the task that is being processed.
   *
   * @return \Drupal\eca_queue\Task
   *   The task that is being processed.
   */
  public function getTask(): Task {
    return $this->task;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesForLazyLoadingWildcard(string $wildcard): bool {
    $task_name = mb_strtolower(trim((string) $this->task->getTaskName()));
    $task_value = mb_strtolower(trim((string) $this->task->getTaskValue()));
    return in_array($wildcard, [
      '*',
      $task_name,
      $task_name . '::' . $task_value,
    ], TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function applies(string $id, array $arguments): bool {
    $task_name = $this->task->getTaskName();
    $task_value = $this->task->getTaskValue();
    $argument_task_name = isset($arguments['task_name']) ? mb_strtolower(trim($arguments['task_name'])) : '';
    $argument_task_value = isset($arguments['task_value']) ? mb_strtolower(trim($arguments['task_value'])) : '';
    if ($argument_task_name !== '' && $argument_task_name !== mb_strtolower(trim($task_name))) {
      return FALSE;
    }
    if ($argument_task_value !== '' && (!isset($task_value) || ($argument_task_value !== mb_strtolower(trim((string) $task_value))))) {
      return FALSE;
    }
    return TRUE;
  }

}
