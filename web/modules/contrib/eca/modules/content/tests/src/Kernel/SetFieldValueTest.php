<?php

namespace Drupal\Tests\eca_content\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\user\Entity\User;

/**
 * Kernel tests for the "eca_set_field_value" action plugin.
 *
 * @group eca
 * @group eca_content
 */
class SetFieldValueTest extends KernelTestBase {

  /**
   * The modules.
   *
   * @var string[]
   *   The modules.
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'filter',
    'text',
    'node',
    'eca',
    'eca_content',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(static::$modules);
    User::create(['uid' => 1, 'name' => 'admin'])->save();
  }

  /**
   * Tests setting field values on a node body field.
   */
  public function testNodeBody() {
    // Create the Article content type with a standard body field.
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = NodeType::create(['type' => 'article', 'name' => 'Article']);
    $node_type->save();
    node_add_body_field($node_type);

    /** @var \Drupal\Core\Action\ActionManager $action_manager */
    $action_manager = \Drupal::service('plugin.manager.action');
    /** @var \Drupal\eca\Token\TokenInterface $token_services */
    $token_services = \Drupal::service('eca.token_services');
    /** @var \Drupal\Core\Session\AccountSwitcherInterface $account_switcher */
    $account_switcher = \Drupal::service('account_switcher');

    $body = $this->randomMachineName(32);
    $summary = $this->randomMachineName(16);
    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::create([
      'type' => 'article',
      'uid' => 0,
      'title' => '123',
      'body' => [
        [
          'value' => $body,
          'summary' => $summary,
          'format' => 'plain_text',
        ],
      ],
    ]);
    $node->save();

    // Create an action that sets the body value of the node.
    /** @var \Drupal\eca_content\Plugin\Action\SetFieldValue $action */
    $defaults = [
      'strip_tags' => FALSE,
      'trim' => FALSE,
      'save_entity' => FALSE,
    ];
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'body',
      'field_value' => '123',
    ] + $defaults);
    $this->assertFalse($action->access($node), 'User without permissions must not have access to change the field.');
    // Same as above, but using the "value" column explicitly.
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'body.value',
      'field_value' => '456',
    ] + $defaults);
    $this->assertFalse($action->access($node), 'User without permissions must not have access to change the field.');

    // Now switching to priviledged user.
    $account_switcher->switchTo(User::load(1));
    // Create an action that sets the body value of the node.
    /** @var \Drupal\eca_content\Plugin\Action\SetFieldValue $action */
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'body',
      'field_value' => '123',
    ] + $defaults);
    $this->assertTrue($action->access($node), 'User with permissions must have access to change the field.');
    $this->assertEquals($body, $node->body->value, 'Original body value before action execution must remain the same.');
    $action->execute($node);
    $this->assertEquals('123', $node->body->value, 'After action execution, the body value must have been changed.');

    // Same as above, but using the "value" column explicitly.
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'body.value',
      'field_value' => '456',
    ] + $defaults);
    $this->assertTrue($action->access($node), 'User with permissions must have access to change the field.');
    $action->execute($node);
    $this->assertEquals('456', $node->body->value, 'After action execution, the body value must have been changed.');

    // Using set:empty method which should not change the value because it was
    // set before.
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:empty',
      'field_name' => 'body.value',
      'field_value' => '555',
    ] + $defaults);
    $action->execute($node);
    $this->assertEquals('456', $node->body->value, 'After action execution, the body value must not have been changed because it was set before.');

    $token_services->addTokenData('node', $node);
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'remove',
      'field_name' => 'body.value',
      'field_value' => '[node:body:value]',
    ] + $defaults);
    $action->execute($node);
    $this->assertEquals('', $node->body->value, 'Body value got removed and therefore must be empty.');

    // Using set:empty method now which should change the value because the
    // body value is currently empty.
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:empty',
      'field_name' => 'body.value',
      'field_value' => '555',
    ] + $defaults);
    $action->execute($node);
    $this->assertEquals('555', $node->body->value, 'The body value must have been changed because it was empty.');

    // Now setting the summary value.
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'body.summary',
      'field_value' => '8888',
    ] + $defaults);
    $action->execute($node);
    $this->assertEquals('555', $node->body->value, 'The body value must not have been changed.');
    $this->assertEquals('8888', $node->body->summary, 'The body summary must have been changed.');
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:empty',
      'field_name' => 'body.summary',
      'field_value' => '9',
    ] + $defaults);
    $action->execute($node);
    $this->assertEquals('555', $node->body->value, 'The body value must not have been changed.');
    $this->assertEquals('8888', $node->body->summary, 'The body summary must not have been changed.');

    // Use an explicit delta.
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'body.0.value',
      'field_value' => '1000',
    ] + $defaults);
    $action->execute($node);
    $this->assertEquals('1000', $node->body->value, 'The body value must have been changed.');
    $this->assertEquals('8888', $node->body->summary, 'The body summary must not have been changed.');
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'body.0.summary',
      'field_value' => '111111',
    ] + $defaults);
    $action->execute($node);
    $this->assertEquals('1000', $node->body->value, 'The body value must not have been changed.');
    $this->assertEquals('111111', $node->body->summary, 'The body summary must not have been changed.');

    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'body.0',
      'field_value' => '33333',
    ] + $defaults);
    $action->execute($node);
    $this->assertEquals('33333', $node->body->value, 'The body value must have been changed.');
    $this->assertEquals('111111', $node->body->summary, 'The body summary must not have been changed.');

    // Trying to set an invalid delta must throw an exception.
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'body.2.value',
      'field_value' => '7777777',
    ] + $defaults);
    $exception = NULL;
    try {
      $action->execute($node);
    }
    catch (\Exception $thrown) {
      $exception = $thrown;
    }
    finally {
      $this->assertTrue($exception instanceof \InvalidArgumentException, 'Trying to set an invalid delta must throw an exception.');
    }
    $this->assertEquals('33333', $node->body->value, 'The body value must not have been changed.');
    $this->assertEquals('111111', $node->body->summary, 'The body summary must not have been changed.');

    $body = $this->randomMachineName(32);
    $summary = $this->randomMachineName(16);
    $another_node = Node::create([
      'type' => 'article',
      'uid' => 0,
      'title' => '456',
      'body' => [
        [
          'value' => $body,
          'summary' => $summary,
          'format' => 'plain_text',
        ],
      ],
    ]);
    $another_node->save();
    $token_services->addTokenData('another', $another_node);

    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'body',
      'field_value' => '[another:body]',
    ] + $defaults);
    $action->execute($node);
    $this->assertEquals($body, $node->body->value, 'The body value must have been changed to the value of another node.');
    $this->assertEquals($summary, $node->body->summary, 'The body summary must have been changed to the summary of another node.');

    $another_node->body->value = '222111';
    $node->body->summary = '000000';
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'body:value',
      'field_value' => '[another:body]',
    ] + $defaults);
    $action->execute($node);
    $this->assertEquals('222111', $node->body->value, 'The body value must have been changed to the value of another node.');
    $this->assertEquals('000000', $node->body->summary, 'The body summary must remain unchanged.');

    $body = $this->randomMachineName(32);
    $another_node->body->value = $body;
    $summary = $this->randomMachineName(16);
    $another_node->body->summary = $summary;
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => '[body:summary]',
      'field_value' => '[another:body:summary]',
    ] + $defaults);
    $action->execute($node);
    $this->assertEquals('222111', $node->body->value, 'The body value must remain unchanged.');
    $this->assertEquals($summary, $node->body->summary, 'The body summary must have been changed to the value of another node.');
    $this->assertEquals($body, $another_node->body->value, 'The body value of another node must remain unchanged.');

    // Removing a value by using the clear method.
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'body.value',
      'field_value' => '',
    ] + $defaults);
    $action->execute($node);
    $this->assertEquals('', $node->body->value, 'The body value must be empty.');
    $this->assertEquals($summary, $node->body->summary, 'The body summary must not have been changed.');
    $node->body->value = $this->randomMachineName(32);
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'body',
      'field_value' => '',
    ] + $defaults);
    $action->execute($node);
    $this->assertNull($node->body->value, 'The body value must be unset.');
    $this->assertNull($node->body->summary, 'The summary must be unset.');

    $account_switcher->switchBack();
  }

  /**
   * Tests setting a multi-value string and multi-value text-with-summary field.
   */
  public function testNodeStringMultiple() {
    // Create the Article content type.
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = NodeType::create(['type' => 'article', 'name' => 'Article']);
    $node_type->save();
    // Create the multi-value string field, using cardinality 3.
    $field_definition = FieldStorageConfig::create([
      'field_name' => 'field_string_multi',
      'type' => 'string',
      'entity_type' => 'node',
      'cardinality' => 3,
    ]);
    $field_definition->save();
    $instance = FieldConfig::create([
      'field_name' => 'field_string_multi',
      'label' => 'A string field having multiple values.',
      'entity_type' => 'node',
      'bundle' => 'article',
    ]);
    $instance->save();
    // Create the multi-value text-with-summary field, unlimited cardinality.
    $field_definition = FieldStorageConfig::create([
      'field_name' => 'field_text_multi',
      'type' => 'text_with_summary',
      'entity_type' => 'node',
      'cardinality' => FieldStorageConfig::CARDINALITY_UNLIMITED,
    ]);
    $field_definition->save();
    $instance = FieldConfig::create([
      'field_name' => 'field_text_multi',
      'label' => 'A text with summary field having multiple values.',
      'entity_type' => 'node',
      'bundle' => 'article',
    ]);
    $instance->save();

    /** @var \Drupal\Core\Action\ActionManager $action_manager */
    $action_manager = \Drupal::service('plugin.manager.action');
    /** @var \Drupal\eca\Token\TokenInterface $token_services */
    $token_services = \Drupal::service('eca.token_services');
    /** @var \Drupal\Core\Session\AccountSwitcherInterface $account_switcher */
    $account_switcher = \Drupal::service('account_switcher');

    $string = $this->randomMachineName(32);
    $text = $this->randomMachineName(32);
    $summary = $this->randomMachineName(16);
    /** @var \Drupal\node\NodeInterface $node */
    $node = Node::create([
      'type' => 'article',
      'uid' => 0,
      'title' => '123',
      'field_string_multi' => [$string, $string . '2', $string . '3'],
      'field_text_multi' => [
        [
          'value' => $text,
          'summary' => $summary,
          'format' => 'plain_text',
        ],
        [
          'value' => $text . '2',
          'summary' => $summary . '2',
          'format' => 'plain_text',
        ],
        [
          'value' => $text . '3',
          'summary' => $summary . '3',
          'format' => 'plain_text',
        ],
      ],
    ]);
    $node->save();

    // Create an action that sets a string value of the node.
    /** @var \Drupal\eca_content\Plugin\Action\SetFieldValue $action */
    $defaults = [
      'strip_tags' => FALSE,
      'trim' => FALSE,
      'save_entity' => FALSE,
    ];
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'field_string_multi',
      'field_value' => '123',
    ] + $defaults);
    $this->assertFalse($action->access($node), 'User without permissions must not have access to change the field.');
    // Same as above, but using the "value" column explicitly.
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'field_string_multi.value',
      'field_value' => '456',
    ] + $defaults);
    $this->assertFalse($action->access($node), 'User without permissions must not have access to change the field.');

    // Now switching to priviledged user.
    $account_switcher->switchTo(User::load(1));
    // Create an action that sets the body value of the node.
    /** @var \Drupal\eca_content\Plugin\Action\SetFieldValue $action */
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'field_string_multi',
      'field_value' => '123',
    ] + $defaults);
    $this->assertTrue($action->access($node), 'User with permissions must have access to change the field.');
    $this->assertEquals($string, $node->field_string_multi[0]->value, 'Original field_string_multi[0] value before action execution must remain the same.');
    $this->assertEquals($string . '2', $node->field_string_multi[1]->value, 'Original field_string_multi[1] value before action execution must remain the same.');
    $this->assertEquals($string . '3', $node->field_string_multi[2]->value, 'Original field_string_multi[2] value before action execution must remain the same.');
    $action->execute($node);
    $this->assertTrue(isset($node->field_string_multi[0]), 'First value must be set.');
    $this->assertTrue(!isset($node->field_string_multi[1]), 'Second value must not be set anymore.');
    $this->assertTrue(!isset($node->field_string_multi[2]), 'Third value must not be set anymore.');
    $this->assertEquals('123', $node->field_string_multi[0]->value, 'After action execution, the field_string_multi value must have been changed.');
    $this->assertSame(1, $node->get('field_string_multi')->count());

    // Same as above, but using the "value" column explicitly.
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'field_string_multi.value',
      'field_value' => '456',
    ] + $defaults);
    $this->assertTrue($action->access($node), 'User with permissions must have access to change the field.');
    $action->execute($node);
    $this->assertTrue(isset($node->field_string_multi[0]), 'First value must be set.');
    $this->assertTrue(!isset($node->field_string_multi[1]), 'Second value must not be set.');
    $this->assertTrue(!isset($node->field_string_multi[2]), 'Third value must not be set.');
    $this->assertEquals('456', $node->field_string_multi[0]->value, 'After action execution, the field_string_multi value must have been changed.');

    // Append a value.
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'append:drop_first',
      'field_name' => 'field_string_multi',
      'field_value' => '11111',
    ] + $defaults);
    $action->execute($node);
    $this->assertTrue(isset($node->field_string_multi[0]), 'First value must be set.');
    $this->assertTrue(isset($node->field_string_multi[1]), 'Second value must be set.');
    $this->assertTrue(!isset($node->field_string_multi[2]), 'Third value must not be set.');
    $this->assertEquals('456', $node->field_string_multi[0]->value, 'First value must remain unchanged.');
    $this->assertEquals('11111', $node->field_string_multi[1]->value, 'Second value must now be set with appended value.');
    // Append another one.
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'append:drop_last',
      'field_name' => 'field_string_multi:value',
      'field_value' => '222222222',
    ] + $defaults);
    $action->execute($node);
    $this->assertTrue(isset($node->field_string_multi[0]), 'First value must be set.');
    $this->assertTrue(isset($node->field_string_multi[1]), 'Second value must be set.');
    $this->assertTrue(isset($node->field_string_multi[2]), 'Third value must be set.');
    $this->assertTrue(!isset($node->field_string_multi[3]), 'No fourth value must be set.');
    $this->assertEquals('456', $node->field_string_multi[0]->value, 'First value must remain unchanged.');
    $this->assertEquals('11111', $node->field_string_multi[1]->value, 'Second value must remain unchanged.');
    $this->assertEquals('222222222', $node->field_string_multi[2]->value, 'Third value must now be set with appended value.');

    // Prepend a value.
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'prepend:drop_first',
      'field_name' => '[field_string_multi:value]',
      'field_value' => '33333',
    ] + $defaults);
    $action->execute($node);
    $this->assertTrue(isset($node->field_string_multi[0]), 'First value must be set.');
    $this->assertTrue(isset($node->field_string_multi[1]), 'Second value must be set.');
    $this->assertTrue(isset($node->field_string_multi[2]), 'Third value must be set.');
    $this->assertTrue(!isset($node->field_string_multi[3]), 'No fourth value must be set.');
    $this->assertEquals('33333', $node->field_string_multi[0]->value, 'First value must have been changed.');
    $this->assertEquals('11111', $node->field_string_multi[1]->value, 'Second value must remain unchanged.');
    $this->assertEquals('222222222', $node->field_string_multi[2]->value, 'Third value must remain unchanged.');

    // Set a value using an explicit delta.
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'field_string_multi.1.value',
      'field_value' => '444444444',
    ] + $defaults);
    $action->execute($node);
    $this->assertTrue(isset($node->field_string_multi[0]), 'First value must be set.');
    $this->assertTrue(isset($node->field_string_multi[1]), 'Second value must be set.');
    $this->assertTrue(isset($node->field_string_multi[2]), 'Third value must be set.');
    $this->assertEquals('33333', $node->field_string_multi[0]->value, 'First value must remain unchanged.');
    $this->assertEquals('444444444', $node->field_string_multi[1]->value, 'Second value must have been changed.');
    $this->assertEquals('222222222', $node->field_string_multi[2]->value, 'Third value must remain unchanged.');

    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'append:not_full',
      'field_name' => 'field_string_multi',
      'field_value' => '121212121212',
    ] + $defaults);
    $action->execute($node);
    $this->assertTrue(isset($node->field_string_multi[0]), 'First value must be set.');
    $this->assertTrue(isset($node->field_string_multi[1]), 'Second value must be set.');
    $this->assertTrue(isset($node->field_string_multi[2]), 'Third value must be set.');
    $this->assertTrue(!isset($node->field_string_multi[3]), 'No fourth value must be set.');
    $this->assertEquals('33333', $node->field_string_multi[0]->value, 'First value must remain unchanged.');
    $this->assertEquals('444444444', $node->field_string_multi[1]->value, 'Second value must remain unchanged.');
    $this->assertEquals('222222222', $node->field_string_multi[2]->value, 'Third value must remain unchanged.');
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'append:drop_first',
      'field_name' => 'field_string_multi',
      'field_value' => '121212121212',
    ] + $defaults);
    $action->execute($node);
    $this->assertTrue(isset($node->field_string_multi[0]), 'First value must be set.');
    $this->assertTrue(isset($node->field_string_multi[1]), 'Second value must be set.');
    $this->assertTrue(isset($node->field_string_multi[2]), 'Third value must be set.');
    $this->assertTrue(!isset($node->field_string_multi[3]), 'No fourth value must be set.');
    $this->assertEquals('444444444', $node->field_string_multi[0]->value, 'First value must have gotten value from second entry.');
    $this->assertEquals('222222222', $node->field_string_multi[1]->value, 'Second value must have gotten value from third entry.');
    $this->assertEquals('121212121212', $node->field_string_multi[2]->value, 'Third value must have been changed.');
    // This action would do nothing, because the value already exists.
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'append:drop_last',
      'field_name' => 'field_string_multi',
      'field_value' => '121212121212',
    ] + $defaults);
    $action->execute($node);
    $this->assertTrue(isset($node->field_string_multi[0]), 'First value must be set.');
    $this->assertTrue(isset($node->field_string_multi[1]), 'Second value must be set.');
    $this->assertTrue(isset($node->field_string_multi[2]), 'Third value must be set.');
    $this->assertTrue(!isset($node->field_string_multi[3]), 'No fourth value must be set.');
    $this->assertEquals('444444444', $node->field_string_multi[0]->value, 'First value must remain unchanged.');
    $this->assertEquals('222222222', $node->field_string_multi[1]->value, 'Second value must remain unchanged.');
    $this->assertEquals('121212121212', $node->field_string_multi[2]->value, 'Third value must remain unchanged.');
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'append:drop_last',
      'field_name' => 'field_string_multi',
      'field_value' => '9898988',
    ] + $defaults);
    $action->execute($node);
    $this->assertTrue(isset($node->field_string_multi[0]), 'First value must be set.');
    $this->assertTrue(isset($node->field_string_multi[1]), 'Second value must be set.');
    $this->assertTrue(isset($node->field_string_multi[2]), 'Third value must be set.');
    $this->assertTrue(!isset($node->field_string_multi[3]), 'No fourth value must be set.');
    $this->assertEquals('444444444', $node->field_string_multi[0]->value, 'First value must remain unchanged.');
    $this->assertEquals('222222222', $node->field_string_multi[1]->value, 'Second value must remain unchanged.');
    $this->assertEquals('9898988', $node->field_string_multi[2]->value, 'Third value must have been changed.');

    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'prepend:not_full',
      'field_name' => 'field_string_multi',
      'field_value' => '55555555555',
    ] + $defaults);
    $action->execute($node);
    $this->assertTrue(isset($node->field_string_multi[0]), 'First value must be set.');
    $this->assertTrue(isset($node->field_string_multi[1]), 'Second value must be set.');
    $this->assertTrue(isset($node->field_string_multi[2]), 'Third value must be set.');
    $this->assertTrue(!isset($node->field_string_multi[3]), 'No fourth value must be set.');
    $this->assertEquals('444444444', $node->field_string_multi[0]->value, 'First value must remain unchanged.');
    $this->assertEquals('222222222', $node->field_string_multi[1]->value, 'Second value must remain unchanged.');
    $this->assertEquals('9898988', $node->field_string_multi[2]->value, 'Third value must remain unchanged.');
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'prepend:drop_first',
      'field_name' => 'field_string_multi',
      'field_value' => '55555555555',
    ] + $defaults);
    $action->execute($node);
    $this->assertTrue(isset($node->field_string_multi[0]), 'First value must be set.');
    $this->assertTrue(isset($node->field_string_multi[1]), 'Second value must be set.');
    $this->assertTrue(isset($node->field_string_multi[2]), 'Third value must be set.');
    $this->assertTrue(!isset($node->field_string_multi[3]), 'No fourth value must be set.');
    $this->assertEquals('55555555555', $node->field_string_multi[0]->value, 'First value must have been changed.');
    $this->assertEquals('222222222', $node->field_string_multi[1]->value, 'Second value must remain unchanged.');
    $this->assertEquals('9898988', $node->field_string_multi[2]->value, 'Third value must remain unchanged.');
    // This action would do nothing, because the value already exists.
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'prepend:drop_last',
      'field_name' => 'field_string_multi',
      'field_value' => '55555555555',
    ] + $defaults);
    $action->execute($node);
    $this->assertTrue(isset($node->field_string_multi[0]), 'First value must be set.');
    $this->assertTrue(isset($node->field_string_multi[1]), 'Second value must be set.');
    $this->assertTrue(isset($node->field_string_multi[2]), 'Third value must be set.');
    $this->assertTrue(!isset($node->field_string_multi[3]), 'No fourth value must be set.');
    $this->assertEquals('55555555555', $node->field_string_multi[0]->value, 'First value must remain unchanged.');
    $this->assertEquals('222222222', $node->field_string_multi[1]->value, 'Second value must remain unchanged.');
    $this->assertEquals('9898988', $node->field_string_multi[2]->value, 'Third value must remain unchanged.');
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'prepend:drop_last',
      'field_name' => 'field_string_multi',
      'field_value' => 'v8',
    ] + $defaults);
    $action->execute($node);
    $this->assertTrue(isset($node->field_string_multi[0]), 'First value must be set.');
    $this->assertTrue(isset($node->field_string_multi[1]), 'Second value must be set.');
    $this->assertTrue(isset($node->field_string_multi[2]), 'Third value must be set.');
    $this->assertTrue(!isset($node->field_string_multi[3]), 'No fourth value must be set.');
    $this->assertEquals('v8', $node->field_string_multi[0]->value, 'First value must have been changed.');
    $this->assertEquals('55555555555', $node->field_string_multi[1]->value, 'Second value must remain unchanged.');
    $this->assertEquals('222222222', $node->field_string_multi[2]->value, 'Third value must have been changed.');
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'remove',
      'field_name' => 'field_string_multi',
      'field_value' => 'tttttttt',
    ] + $defaults);
    $action->execute($node);
    $this->assertTrue(isset($node->field_string_multi[0]), 'First value must be set.');
    $this->assertTrue(isset($node->field_string_multi[1]), 'Second value must be set.');
    $this->assertTrue(isset($node->field_string_multi[2]), 'Third value must be set.');
    $this->assertTrue(!isset($node->field_string_multi[3]), 'No fourth value must be set.');
    $this->assertEquals('v8', $node->field_string_multi[0]->value, 'First value must remain unchanged.');
    $this->assertEquals('55555555555', $node->field_string_multi[1]->value, 'Second value must remain unchanged.');
    $this->assertEquals('222222222', $node->field_string_multi[2]->value, 'Third value must remain unchanged.');
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'remove',
      'field_name' => 'field_string_multi',
      'field_value' => '222222222',
    ] + $defaults);
    $action->execute($node);
    $this->assertTrue(isset($node->field_string_multi[0]), 'First value must be set.');
    $this->assertTrue(isset($node->field_string_multi[1]), 'Second value must be set.');
    $this->assertTrue(!isset($node->field_string_multi[2]), 'Third value must not be set.');
    $this->assertTrue(!isset($node->field_string_multi[3]), 'No fourth value must be set.');
    $this->assertEquals('v8', $node->field_string_multi[0]->value, 'First value must remain unchanged.');
    $this->assertEquals('55555555555', $node->field_string_multi[1]->value, 'Second value must have gotten value from third entry.');
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'remove',
      'field_name' => 'field_string_multi',
      'field_value' => '55555555555',
    ] + $defaults);
    $action->execute($node);
    $this->assertTrue(isset($node->field_string_multi[0]), 'First value must be set.');
    $this->assertTrue(!isset($node->field_string_multi[1]), 'Second value must not be set.');
    $this->assertTrue(!isset($node->field_string_multi[2]), 'Third value must not be set.');
    $this->assertTrue(!isset($node->field_string_multi[3]), 'No fourth value must be set.');
    $this->assertEquals('v8', $node->field_string_multi[0]->value, 'First value must remain unchanged.');
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'remove',
      'field_name' => 'field_string_multi',
      'field_value' => 'v8',
    ] + $defaults);
    $action->execute($node);
    $this->assertSame(0, $node->get('field_string_multi')->count(), 'The field must be empty.');

    $account_switcher->switchBack();

    // Create an action that sets a string value of the node.
    /** @var \Drupal\eca_content\Plugin\Action\SetFieldValue $action */
    $defaults = [
      'strip_tags' => FALSE,
      'trim' => FALSE,
      'save_entity' => FALSE,
    ];
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'field_text_multi',
      'field_value' => '123',
    ] + $defaults);
    $this->assertFalse($action->access($node), 'User without permissions must not have access to change the field.');
    // Same as above, but using the "value" column explicitly.
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'field_text_multi.value',
      'field_value' => '456',
    ] + $defaults);
    $this->assertFalse($action->access($node), 'User without permissions must not have access to change the field.');

    // Now switching to priviledged user.
    $account_switcher->switchTo(User::load(1));
    // Create an action that sets the text value of the node.
    /** @var \Drupal\eca_content\Plugin\Action\SetFieldValue $action */
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'field_text_multi',
      'field_value' => '123',
    ] + $defaults);
    $this->assertTrue($action->access($node), 'User with permissions must have access to change the field.');
    $this->assertEquals($text, $node->field_text_multi[0]->value, 'Original field_text_multi[0] value before action execution must remain the same.');
    $this->assertEquals($text . '2', $node->field_text_multi[1]->value, 'Original field_text_multi[1] value before action execution must remain the same.');
    $this->assertEquals($text . '3', $node->field_text_multi[2]->value, 'Original field_text_multi[2] value before action execution must remain the same.');
    $this->assertEquals($summary, $node->field_text_multi[0]->summary, 'Original field_text_multi[0] summary before action execution must remain the same.');
    $this->assertEquals($summary . '2', $node->field_text_multi[1]->summary, 'Original field_text_multi[1] summary before action execution must remain the same.');
    $this->assertEquals($summary . '3', $node->field_text_multi[2]->summary, 'Original field_text_multi[2] summary before action execution must remain the same.');
    $action->execute($node);
    $this->assertTrue(isset($node->field_text_multi[0]), 'First value must be set.');
    $this->assertTrue(!isset($node->field_text_multi[1]), 'Second value must not be set anymore.');
    $this->assertTrue(!isset($node->field_text_multi[2]), 'Third value must not be set anymore.');
    $this->assertEquals('123', $node->field_text_multi[0]->value, 'After action execution, the field_text_multi value must have been changed.');
    $this->assertEquals($summary, $node->field_text_multi[0]->summary, 'Original field_text_multi[0] summary must remain the same.');
    $this->assertSame(1, $node->get('field_text_multi')->count());

    // Same as above, but using the "value" column explicitly.
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'field_text_multi.value',
      'field_value' => '456',
    ] + $defaults);
    $this->assertTrue($action->access($node), 'User with permissions must have access to change the field.');
    $action->execute($node);
    $this->assertTrue(isset($node->field_text_multi[0]), 'First value must be set.');
    $this->assertTrue(!isset($node->field_text_multi[1]), 'Second value must not be set.');
    $this->assertTrue(!isset($node->field_text_multi[2]), 'Third value must not be set.');
    $this->assertEquals('456', $node->field_text_multi[0]->value, 'After action execution, the field_text_multi value must have been changed.');
    $this->assertEquals($summary, $node->field_text_multi[0]->summary, 'Original field_text_multi[0] summary must remain the same.');

    // Append a value.
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'append:drop_first',
      'field_name' => 'field_text_multi',
      'field_value' => '11111',
    ] + $defaults);
    $action->execute($node);
    $this->assertTrue(isset($node->field_text_multi[0]), 'First value must be set.');
    $this->assertTrue(isset($node->field_text_multi[1]), 'Second value must be set.');
    $this->assertTrue(!isset($node->field_text_multi[2]), 'Third value must not be set.');
    $this->assertEquals('456', $node->field_text_multi[0]->value, 'First value must remain unchanged.');
    $this->assertEquals($summary, $node->field_text_multi[0]->summary, 'Original field_text_multi[0] summary must remain the same.');
    $this->assertEquals('11111', $node->field_text_multi[1]->value, 'Second value must now be set with appended value.');
    $this->assertEquals('', $node->field_text_multi[1]->summary, 'Second summary must be empty.');
    // Append another one.
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'append:drop_last',
      'field_name' => 'field_text_multi:value',
      'field_value' => '222222222',
    ] + $defaults);
    $action->execute($node);
    $this->assertTrue(isset($node->field_text_multi[0]), 'First value must be set.');
    $this->assertTrue(isset($node->field_text_multi[1]), 'Second value must be set.');
    $this->assertTrue(isset($node->field_text_multi[2]), 'Third value must be set.');
    $this->assertTrue(!isset($node->field_text_multi[3]), 'No fourth value must be set.');
    $this->assertEquals('456', $node->field_text_multi[0]->value, 'First value must remain unchanged.');
    $this->assertEquals($summary, $node->field_text_multi[0]->summary, 'Original field_text_multi[0] summary must remain the same.');
    $this->assertEquals('11111', $node->field_text_multi[1]->value, 'Second value must remain unchanged.');
    $this->assertEquals('', $node->field_text_multi[1]->summary, 'Second summary must be empty.');
    $this->assertEquals('222222222', $node->field_text_multi[2]->value, 'Third value must now be set with appended value.');
    $this->assertEquals('', $node->field_text_multi[2]->summary, 'Third summary must be empty.');

    // Prepend a value with explicit property.
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'prepend:drop_first',
      'field_name' => '[field_text_multi:value]',
      'field_value' => '33333',
    ] + $defaults);
    $action->execute($node);
    $this->assertTrue(isset($node->field_text_multi[0]), 'First value must be set.');
    $this->assertTrue(isset($node->field_text_multi[1]), 'Second value must be set.');
    $this->assertTrue(isset($node->field_text_multi[2]), 'Third value must be set.');
    $this->assertTrue(isset($node->field_text_multi[3]), 'Fourth value must be set.');
    $this->assertEquals('33333', $node->field_text_multi[0]->value, 'First value must have been changed.');
    $this->assertEquals('', $node->field_text_multi[0]->summary, 'First summary must be empty.');
    $this->assertEquals('456', $node->field_text_multi[1]->value, 'Second value must remain unchanged.');
    $this->assertEquals($summary, $node->field_text_multi[1]->summary, 'Second summary must remain the same.');
    $this->assertEquals('11111', $node->field_text_multi[2]->value, 'Third value must remain unchanged.');
    $this->assertEquals('', $node->field_text_multi[2]->summary, 'Third summary must be empty.');
    $this->assertEquals('222222222', $node->field_text_multi[3]->value, 'Fourth value must remain unchanged.');
    $this->assertEquals('', $node->field_text_multi[3]->summary, 'Fourth summary must be empty.');

    // Set a summary.
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'field_text_multi.0.summary',
      'field_value' => '42',
    ] + $defaults);
    $action->execute($node);
    $this->assertTrue(isset($node->field_text_multi[0]), 'First value must be set.');
    $this->assertTrue(isset($node->field_text_multi[1]), 'Second value must be set.');
    $this->assertTrue(isset($node->field_text_multi[2]), 'Third value must be set.');
    $this->assertTrue(isset($node->field_text_multi[3]), 'Fourth value must be set.');
    $this->assertEquals('33333', $node->field_text_multi[0]->value, 'First value must remain unchanged.');
    $this->assertEquals('42', $node->field_text_multi[0]->summary, 'First summary must have been changed.');
    $this->assertEquals('456', $node->field_text_multi[1]->value, 'Second value must remain unchanged.');
    $this->assertEquals($summary, $node->field_text_multi[1]->summary, 'Second summary must remain the same.');
    $this->assertEquals('11111', $node->field_text_multi[2]->value, 'Third value must remain unchanged.');
    $this->assertEquals('', $node->field_text_multi[2]->summary, 'Third summary must be empty.');
    $this->assertEquals('222222222', $node->field_text_multi[3]->value, 'Fourth value must remain unchanged.');
    $this->assertEquals('', $node->field_text_multi[3]->summary, 'Fourth summary must be empty.');
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:empty',
      'field_name' => 'field_text_multi.2.summary',
      'field_value' => '50',
    ] + $defaults);
    $action->execute($node);
    $this->assertTrue(isset($node->field_text_multi[0]), 'First value must be set.');
    $this->assertTrue(isset($node->field_text_multi[1]), 'Second value must be set.');
    $this->assertTrue(isset($node->field_text_multi[2]), 'Third value must be set.');
    $this->assertTrue(isset($node->field_text_multi[3]), 'Fourth value must be set.');
    $this->assertEquals('33333', $node->field_text_multi[0]->value, 'First value must remain unchanged.');
    $this->assertEquals('42', $node->field_text_multi[0]->summary, 'First summary must must remain unchanged.');
    $this->assertEquals('456', $node->field_text_multi[1]->value, 'Second value must remain unchanged.');
    $this->assertEquals($summary, $node->field_text_multi[1]->summary, 'Second summary must remain unchanged.');
    $this->assertEquals('11111', $node->field_text_multi[2]->value, 'Third value must remain unchanged.');
    $this->assertEquals('50', $node->field_text_multi[2]->summary, 'Third summary must have been changed.');
    $this->assertEquals('222222222', $node->field_text_multi[3]->value, 'Fourth value must remain unchanged.');
    $this->assertEquals('', $node->field_text_multi[3]->summary, 'Fourth summary must be empty.');
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:empty',
      'field_name' => 'field_text_multi.2.summary',
      'field_value' => '51',
    ] + $defaults);
    $action->execute($node);
    $this->assertTrue(isset($node->field_text_multi[0]), 'First value must be set.');
    $this->assertTrue(isset($node->field_text_multi[1]), 'Second value must be set.');
    $this->assertTrue(isset($node->field_text_multi[2]), 'Third value must be set.');
    $this->assertTrue(isset($node->field_text_multi[3]), 'Fourth value must be set.');
    $this->assertEquals('33333', $node->field_text_multi[0]->value, 'First value must remain unchanged.');
    $this->assertEquals('42', $node->field_text_multi[0]->summary, 'First summary must must remain unchanged.');
    $this->assertEquals('456', $node->field_text_multi[1]->value, 'Second value must remain unchanged.');
    $this->assertEquals($summary, $node->field_text_multi[1]->summary, 'Second summary must remain unchanged.');
    $this->assertEquals('11111', $node->field_text_multi[2]->value, 'Third value must remain unchanged.');
    $this->assertEquals('50', $node->field_text_multi[2]->summary, 'Third summary must remain unchanged.');
    $this->assertEquals('222222222', $node->field_text_multi[3]->value, 'Fourth value must remain unchanged.');
    $this->assertEquals('', $node->field_text_multi[3]->summary, 'Fourth summary must be empty.');

    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'append:drop_last',
      'field_name' => 'field_text_multi.value',
      'field_value' => '50',
    ] + $defaults);
    $action->execute($node);
    $this->assertTrue(isset($node->field_text_multi[0]), 'First value must be set.');
    $this->assertTrue(isset($node->field_text_multi[1]), 'Second value must be set.');
    $this->assertTrue(isset($node->field_text_multi[2]), 'Third value must be set.');
    $this->assertTrue(isset($node->field_text_multi[3]), 'Fourth value must be set.');
    $this->assertTrue(isset($node->field_text_multi[4]), '5th value must be set.');
    $this->assertTrue(!isset($node->field_text_multi[5]), '6th value must not be set.');
    $this->assertEquals('33333', $node->field_text_multi[0]->value, 'First value must remain unchanged.');
    $this->assertEquals('42', $node->field_text_multi[0]->summary, 'First summary must must remain unchanged.');
    $this->assertEquals('456', $node->field_text_multi[1]->value, 'Second value must remain unchanged.');
    $this->assertEquals($summary, $node->field_text_multi[1]->summary, 'Second summary must remain unchanged.');
    $this->assertEquals('11111', $node->field_text_multi[2]->value, 'Third value must remain unchanged.');
    $this->assertEquals('50', $node->field_text_multi[2]->summary, 'Third summary must remain unchanged.');
    $this->assertEquals('222222222', $node->field_text_multi[3]->value, 'Fourth value must remain unchanged.');
    $this->assertEquals('', $node->field_text_multi[3]->summary, 'Fourth summary must remain unchanged.');
    $this->assertEquals('222222222', $node->field_text_multi[3]->value, '5th value must remain unchanged.');
    $this->assertEquals('', $node->field_text_multi[3]->summary, '5th summary must remain unchanged.');
    $this->assertEquals('50', $node->field_text_multi[4]->value, '6th value must have been added.');
    $this->assertEquals('', $node->field_text_multi[4]->summary, '6th summary must be empty.');

    $string = $this->randomMachineName(32);
    $text = $this->randomMachineName(32);
    $summary = $this->randomMachineName(16);
    /** @var \Drupal\node\NodeInterface $node */
    $another_node = Node::create([
      'type' => 'article',
      'uid' => 0,
      'title' => '123',
      'field_string_multi' => [$string, $string . '2', $string . '3'],
      'field_text_multi' => [
        [
          'value' => $text,
          'summary' => $summary,
          'format' => 'plain_text',
        ],
        [
          'value' => $text . '2',
          'summary' => $summary . '2',
          'format' => 'plain_text',
        ],
        [
          'value' => $text . '3',
          'summary' => $summary . '3',
          'format' => 'plain_text',
        ],
      ],
    ]);
    $another_node->save();
    $token_services->addTokenData('another', $another_node);

    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'field_text_multi',
      'field_value' => '[another:field_text_multi]',
    ] + $defaults);
    $action->execute($node);
    $this->assertTrue(isset($node->field_text_multi[0]), 'First value must be set.');
    $this->assertTrue(isset($node->field_text_multi[1]), 'Second value must be set.');
    $this->assertTrue(isset($node->field_text_multi[2]), 'Third value must be set.');
    $this->assertTrue(!isset($node->field_text_multi[3]), 'Fourth value must not be set.');
    $this->assertTrue(!isset($node->field_text_multi[4]), '5th value must not be set.');
    $this->assertTrue(!isset($node->field_text_multi[5]), '6th value must not be set.');
    $this->assertEquals($text, $node->field_text_multi[0]->value, 'First value must be copied from another node.');
    $this->assertEquals($summary, $node->field_text_multi[0]->summary, 'First summary must be copied from another node.');
    $this->assertEquals($text . '2', $node->field_text_multi[1]->value, 'Second value must be copied from another node.');
    $this->assertEquals($summary . '2', $node->field_text_multi[1]->summary, 'Second summary must be copied from another node.');
    $this->assertEquals($text . '3', $node->field_text_multi[2]->value, 'Third value must be copied from another node.');
    $this->assertEquals($summary . '3', $node->field_text_multi[2]->summary, 'Third summary must be copied from another node.');

    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'append:not_empty',
      'field_name' => 'field_text_multi',
      'field_value' => '[another:field_string_multi]',
    ] + $defaults);
    $action->execute($node);
    $this->assertTrue(isset($node->field_text_multi[0]), 'First value must be set.');
    $this->assertTrue(isset($node->field_text_multi[1]), 'Second value must be set.');
    $this->assertTrue(isset($node->field_text_multi[2]), 'Third value must be set.');
    $this->assertTrue(isset($node->field_text_multi[3]), 'Fourth value must be set.');
    $this->assertTrue(isset($node->field_text_multi[4]), '5th value must be set.');
    $this->assertTrue(isset($node->field_text_multi[5]), '6th value must be set.');
    $this->assertEquals($text, $node->field_text_multi[0]->value, 'First value must remain unchanged.');
    $this->assertEquals($summary, $node->field_text_multi[0]->summary, 'First summary must remain unchanged.');
    $this->assertEquals($text . '2', $node->field_text_multi[1]->value, 'Second value must remain unchanged.');
    $this->assertEquals($summary . '2', $node->field_text_multi[1]->summary, 'Second summary must remain unchanged.');
    $this->assertEquals($text . '3', $node->field_text_multi[2]->value, 'Third value must remain unchanged.');
    $this->assertEquals($summary . '3', $node->field_text_multi[2]->summary, 'Third summary must remain unchanged.');
    $this->assertEquals($string, $node->field_text_multi[3]->value, 'Fourth value must have copy from string field of another node.');
    $this->assertEquals('', $node->field_text_multi[3]->summary, 'Fourth summary must be empty.');
    $this->assertEquals($string . '2', $node->field_text_multi[4]->value, '5th value must have copy from string field of another node.');
    $this->assertEquals('', $node->field_text_multi[4]->summary, '5th summary must be empty.');
    $this->assertEquals($string . '3', $node->field_text_multi[5]->value, '6th value must have copy from string field of another node.');
    $this->assertEquals('', $node->field_text_multi[5]->summary, '6th summary must be empty.');

    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'append:drop_first',
      'field_name' => 'field_text_multi',
      'field_value' => '[another:field_string_multi]',
    ] + $defaults);
    $action->execute($node);
    $this->assertTrue(isset($node->field_text_multi[0]), 'First value must be set.');
    $this->assertTrue(isset($node->field_text_multi[1]), 'Second value must be set.');
    $this->assertTrue(isset($node->field_text_multi[2]), 'Third value must be set.');
    $this->assertTrue(isset($node->field_text_multi[3]), 'Fourth value must be set.');
    $this->assertTrue(isset($node->field_text_multi[4]), '5th value must be set.');
    $this->assertTrue(isset($node->field_text_multi[5]), '6th value must be set.');
    $this->assertTrue(!isset($node->field_text_multi[6]), '7th value must not be set.');
    $this->assertTrue(!isset($node->field_text_multi[7]), '8th value must not be set.');
    $this->assertTrue(!isset($node->field_text_multi[8]), '9th value must not be set.');
    $this->assertEquals($text, $node->field_text_multi[0]->value, 'First value must remain unchanged.');
    $this->assertEquals($summary, $node->field_text_multi[0]->summary, 'First summary must remain unchanged.');
    $this->assertEquals($text . '2', $node->field_text_multi[1]->value, 'Second value must remain unchanged.');
    $this->assertEquals($summary . '2', $node->field_text_multi[1]->summary, 'Second summary must remain unchanged.');
    $this->assertEquals($text . '3', $node->field_text_multi[2]->value, 'Third value must remain unchanged.');
    $this->assertEquals($summary . '3', $node->field_text_multi[2]->summary, 'Third summary must remain unchanged.');
    $this->assertEquals($string, $node->field_text_multi[3]->value, 'Fourth value must remain unchanged.');
    $this->assertEquals('', $node->field_text_multi[3]->summary, 'Fourth summary must remain unchanged.');
    $this->assertEquals($string . '2', $node->field_text_multi[4]->value, '5th value must remain unchanged.');
    $this->assertEquals('', $node->field_text_multi[4]->summary, '5th summary must remain unchanged.');
    $this->assertEquals($string . '3', $node->field_text_multi[5]->value, '6th value must remain unchanged.');
    $this->assertEquals('', $node->field_text_multi[5]->summary, '6th summary must remain unchanged.');

    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:empty',
      'field_name' => 'field_text_multi',
      'field_value' => '[another:field_string_multi]',
    ] + $defaults);
    $action->execute($node);
    $this->assertTrue(isset($node->field_text_multi[0]), 'First value must be set.');
    $this->assertTrue(isset($node->field_text_multi[1]), 'Second value must be set.');
    $this->assertTrue(isset($node->field_text_multi[2]), 'Third value must be set.');
    $this->assertTrue(isset($node->field_text_multi[3]), 'Fourth value must be set.');
    $this->assertTrue(isset($node->field_text_multi[4]), '5th value must be set.');
    $this->assertTrue(isset($node->field_text_multi[5]), '6th value must be set.');
    $this->assertTrue(!isset($node->field_text_multi[6]), '7th value must not be set.');
    $this->assertTrue(!isset($node->field_text_multi[7]), '8th value must not be set.');
    $this->assertTrue(!isset($node->field_text_multi[8]), '9th value must not be set.');
    $this->assertEquals($text, $node->field_text_multi[0]->value, 'First value must remain unchanged.');
    $this->assertEquals($summary, $node->field_text_multi[0]->summary, 'First summary must remain unchanged.');
    $this->assertEquals($text . '2', $node->field_text_multi[1]->value, 'Second value must remain unchanged.');
    $this->assertEquals($summary . '2', $node->field_text_multi[1]->summary, 'Second summary must remain unchanged.');
    $this->assertEquals($text . '3', $node->field_text_multi[2]->value, 'Third value must remain unchanged.');
    $this->assertEquals($summary . '3', $node->field_text_multi[2]->summary, 'Third summary must remain unchanged.');
    $this->assertEquals($string, $node->field_text_multi[3]->value, 'Fourth value must remain unchanged.');
    $this->assertEquals('', $node->field_text_multi[3]->summary, 'Fourth summary must remain unchanged.');
    $this->assertEquals($string . '2', $node->field_text_multi[4]->value, '5th value must remain unchanged.');
    $this->assertEquals('', $node->field_text_multi[4]->summary, '5th summary must remain unchanged.');
    $this->assertEquals($string . '3', $node->field_text_multi[5]->value, '6th value must remain unchanged.');
    $this->assertEquals('', $node->field_text_multi[5]->summary, '6th summary must remain unchanged.');

    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'field_text_multi',
      'field_value' => '[another:field_string_multi]',
    ] + $defaults);
    $action->execute($node);
    $this->assertTrue(isset($node->field_text_multi[0]), 'First value must be set.');
    $this->assertTrue(isset($node->field_text_multi[1]), 'Second value must be set.');
    $this->assertTrue(isset($node->field_text_multi[2]), 'Third value must be set.');
    $this->assertTrue(!isset($node->field_text_multi[3]), 'Fourth value must not be set.');
    $this->assertTrue(!isset($node->field_text_multi[4]), '5th value must not be set.');
    $this->assertTrue(!isset($node->field_text_multi[5]), '6th value must not be set.');
    $this->assertTrue(!isset($node->field_text_multi[6]), '7th value must not be set.');
    $this->assertTrue(!isset($node->field_text_multi[7]), '8th value must not be set.');
    $this->assertTrue(!isset($node->field_text_multi[8]), '9th value must not be set.');
    $this->assertEquals($string, $node->field_text_multi[0]->value, 'First value must have copy from string field of another node.');
    $this->assertEquals('', $node->field_text_multi[0]->summary, 'First summary must be empty.');
    $this->assertEquals($string . '2', $node->field_text_multi[1]->value, 'Second value have copy from string field of another node.');
    $this->assertEquals('', $node->field_text_multi[1]->summary, 'Second summary must be empty.');
    $this->assertEquals($string . '3', $node->field_text_multi[2]->value, 'Third value must have copy from string field of another node.');
    $this->assertEquals('', $node->field_text_multi[2]->summary, 'Third summary must be empty.');

    $account_switcher->switchBack();
  }

  /**
   * Tests setting single references.
   */
  public function testNodeReferenceSingle() {
    // Create the Article content type with a standard body field.
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = NodeType::create(['type' => 'article', 'name' => 'Article']);
    $node_type->save();
    // Create the single-value reference field.
    $field_definition = FieldStorageConfig::create([
      'field_name' => 'field_node_single',
      'type' => 'entity_reference',
      'entity_type' => 'node',
      'settings' => [
        'target_type' => 'node',
      ],
      'cardinality' => 1,
    ]);
    $field_definition->save();
    $field = FieldConfig::create([
      'field_storage' => $field_definition,
      'label' => 'A single entity reference.',
      'entity_type' => 'node',
      'bundle' => 'article',
    ]);
    $field->save();

    $node1 = Node::create([
      'type' => 'article',
      'uid' => 0,
      'title' => '123',
    ]);
    $node1->save();
    $node2 = Node::create([
      'type' => 'article',
      'uid' => 0,
      'title' => '456',
    ]);
    $node2->save();

    /** @var \Drupal\Core\Action\ActionManager $action_manager */
    $action_manager = \Drupal::service('plugin.manager.action');
    /** @var \Drupal\eca\Token\TokenInterface $token_services */
    $token_services = \Drupal::service('eca.token_services');
    /** @var \Drupal\Core\Session\AccountSwitcherInterface $account_switcher */
    $account_switcher = \Drupal::service('account_switcher');

    $token_services->addTokenData('node1', $node1);
    $token_services->addTokenData('node2', $node2);

    // Create an action that sets a target of the node.
    /** @var \Drupal\eca_content\Plugin\Action\SetFieldValue $action */
    $defaults = [
      'strip_tags' => FALSE,
      'trim' => FALSE,
      'save_entity' => TRUE,
    ];
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'field_node_single',
      'field_value' => $node2->id(),
    ] + $defaults);
    $this->assertFalse($action->access($node1), 'User without permissions must not have access to change the field.');
    // Same as above, but using the "target_id" column explicitly.
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'field_node_single.target_id',
      'field_value' => $node2->id(),
    ] + $defaults);
    $this->assertFalse($action->access($node1), 'User without permissions must not have access to change the field.');

    // Now switching to priviledged user.
    $account_switcher->switchTo(User::load(1));
    // Create an action that sets the target of the node.
    /** @var \Drupal\eca_content\Plugin\Action\SetFieldValue $action */
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'field_node_single.target_id',
      'field_value' => '[node2:nid]',
    ] + $defaults);
    $this->assertTrue($action->access($node1), 'User with permissions must have access to change the field.');
    $this->assertEquals(NULL, $node1->field_node_single->target_id, 'Original field_node_single target before action execution must remain the same.');
    $this->assertEquals(NULL, $node2->field_node_single->target_id, 'Original field_node_single target before action execution must remain the same.');
    $action->execute($node1);
    $this->assertTrue(isset($node1->field_node_single->target_id), 'Reference target must be set.');
    $this->assertEquals($node2->id(), $node1->field_node_single->target_id, 'The target ID must match with the ID of node #2.');

    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'field_node_single',
      'field_value' => '',
    ] + $defaults);
    $action->execute($node1);
    $this->assertTrue(!isset($node1->field_node_single->target_id), 'Reference target must not be set.');

    $new_node = Node::create([
      'type' => 'article',
      'uid' => 0,
      'title' => 'NEW',
    ]);
    $token_services->addTokenData('new_node', $new_node);

    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:empty',
      'field_name' => 'field_node_single',
      'field_value' => '[new_node]',
    ] + $defaults);
    $this->assertTrue($new_node->isNew(), 'New node must not have been saved yet.');
    $action->execute($node1);
    $this->assertFalse($new_node->isNew(), 'New node must have been saved because the action is configured to save the entity in scope.');
    $this->assertTrue(isset($node1->field_node_single->target_id), 'Reference target must be set.');
    $this->assertEquals($new_node->id(), $node1->field_node_single->target_id, 'The target ID must match with the ID of new node.');

    $account_switcher->switchBack();
  }

  /**
   * Tests setting multiple references.
   */
  public function testNodeReferenceMulti() {
    // Create the Article content type.
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = NodeType::create(['type' => 'article', 'name' => 'Article']);
    $node_type->save();
    // Create the multi-value reference, using inlimited cardinality.
    $field_definition = FieldStorageConfig::create([
      'field_name' => 'field_node_multi',
      'type' => 'entity_reference',
      'entity_type' => 'node',
      'settings' => [
        'target_type' => 'node',
      ],
      'cardinality' => FieldStorageConfig::CARDINALITY_UNLIMITED,
    ]);
    $field_definition->save();
    $field = FieldConfig::create([
      'field_storage' => $field_definition,
      'label' => 'A single entity reference.',
      'entity_type' => 'node',
      'bundle' => 'article',
    ]);
    $field->save();

    $node1 = Node::create([
      'type' => 'article',
      'uid' => 0,
      'title' => '123',
    ]);
    $node1->save();
    $node2 = Node::create([
      'type' => 'article',
      'uid' => 0,
      'title' => '456',
    ]);
    $node2->save();
    $node3 = Node::create([
      'type' => 'article',
      'uid' => 0,
      'title' => '9999',
    ]);
    $node3->save();

    /** @var \Drupal\Core\Action\ActionManager $action_manager */
    $action_manager = \Drupal::service('plugin.manager.action');
    /** @var \Drupal\eca\Token\TokenInterface $token_services */
    $token_services = \Drupal::service('eca.token_services');
    /** @var \Drupal\Core\Session\AccountSwitcherInterface $account_switcher */
    $account_switcher = \Drupal::service('account_switcher');

    $token_services->addTokenData('node1', $node1);
    $token_services->addTokenData('node2', $node2);
    $token_services->addTokenData('node3', $node3);
    $token_services->addTokenData('nodes', [$node2, $node3]);

    // Create an action that sets a target of the node.
    /** @var \Drupal\eca_content\Plugin\Action\SetFieldValue $action */
    $defaults = [
      'strip_tags' => FALSE,
      'trim' => FALSE,
      'save_entity' => TRUE,
    ];
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'field_node_multi',
      'field_value' => $node2->id(),
    ] + $defaults);
    $this->assertFalse($action->access($node1), 'User without permissions must not have access to change the field.');
    // Same as above, but using the "target_id" column explicitly.
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'field_node_multi.target_id',
      'field_value' => $node2->id(),
    ] + $defaults);
    $this->assertFalse($action->access($node1), 'User without permissions must not have access to change the field.');

    // Now switching to priviledged user.
    $account_switcher->switchTo(User::load(1));
    // Create an action that sets the target of the node.
    /** @var \Drupal\eca_content\Plugin\Action\SetFieldValue $action */
    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'field_node_multi.target_id',
      'field_value' => '[node2:nid]',
    ] + $defaults);
    $this->assertTrue($action->access($node1), 'User with permissions must have access to change the field.');
    $this->assertEquals(NULL, $node1->field_node_multi->target_id, 'Original field_node_multi target before action execution must remain the same.');
    $this->assertEquals(NULL, $node2->field_node_multi->target_id, 'Original field_node_multi target before action execution must remain the same.');
    $action->execute($node1);
    $this->assertTrue(isset($node1->field_node_multi->target_id), 'Reference target must be set.');
    $this->assertEquals($node2->id(), $node1->field_node_multi->target_id, 'The target ID must match with the ID of node #2.');
    $this->assertSame(1, $node1->get('field_node_multi')->count(), 'Exactly one item must be present in node1.');
    $this->assertSame(0, $node2->get('field_node_multi')->count(), 'No item must be present in node2.');
    $this->assertSame(0, $node3->get('field_node_multi')->count(), 'No item must be present in node3.');

    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'field_node_multi',
      'field_value' => '',
    ] + $defaults);
    $action->execute($node1);
    $this->assertTrue(!isset($node1->field_node_multi->target_id), 'Reference target must not be set.');

    $new_node = Node::create([
      'type' => 'article',
      'uid' => 0,
      'title' => 'NEW',
    ]);
    $token_services->addTokenData('new_node', $new_node);

    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:empty',
      'field_name' => 'field_node_multi',
      'field_value' => '[new_node]',
    ] + $defaults);
    $this->assertTrue($new_node->isNew(), 'New node must not have been saved yet.');
    $action->execute($node1);
    $this->assertFalse($new_node->isNew(), 'New node must have been saved because the action is configured to save the entity in scope.');
    $this->assertTrue(isset($node1->field_node_multi->target_id), 'Reference target must be set.');
    $this->assertEquals($new_node->id(), $node1->field_node_multi->target_id, 'The target ID must match with the ID of new node.');

    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'append:drop_first',
      'field_name' => 'field_node_multi.target_id',
      'field_value' => '[node2:nid]',
    ] + $defaults);
    $action->execute($node1);
    $this->assertTrue(isset($node1->field_node_multi[0]), 'First item must be set.');
    $this->assertTrue(isset($node1->field_node_multi[1]), 'Second item must be set.');
    $this->assertTrue(!isset($node1->field_node_multi[2]), 'No third item must be set.');
    $this->assertEquals($new_node->id(), $node1->field_node_multi[0]->target_id, 'The target ID must match with the ID of new node.');
    $this->assertEquals($node2->id(), $node1->field_node_multi[1]->target_id, 'The target ID must match with the ID of node2.');

    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'prepend:drop_last',
      'field_name' => 'field_node_multi.target_id',
      'field_value' => '[node3]',
    ] + $defaults);
    $action->execute($node1);
    $this->assertTrue(isset($node1->field_node_multi[0]), 'First item must be set.');
    $this->assertTrue(isset($node1->field_node_multi[1]), 'Second item must be set.');
    $this->assertTrue(isset($node1->field_node_multi[2]), 'Third item must be set.');
    $this->assertEquals($node3->id(), $node1->field_node_multi[0]->target_id, 'The target ID of the first item must match with the ID of node3.');
    $this->assertEquals($new_node->id(), $node1->field_node_multi[1]->target_id, 'The target ID of second item must match with the ID of new node.');
    $this->assertEquals($node2->id(), $node1->field_node_multi[2]->target_id, 'The target ID of third item must match with the ID of node2.');

    $action = $action_manager->createInstance('eca_set_field_value', [
      'method' => 'set:clear',
      'field_name' => 'field_node_multi',
      'field_value' => '[nodes]',
    ] + $defaults);
    $action->execute($node1);
    $this->assertTrue(isset($node1->field_node_multi[0]), 'First item must be set.');
    $this->assertTrue(isset($node1->field_node_multi[1]), 'Second item must be set.');
    $this->assertFalse(isset($node1->field_node_multi[2]), 'Third item must not be set.');
    $this->assertEquals($node2->id(), $node1->field_node_multi[0]->target_id);
    $this->assertEquals($node3->id(), $node1->field_node_multi[1]->target_id);

    $account_switcher->switchBack();
  }

}
