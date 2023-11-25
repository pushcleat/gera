<?php

namespace Drupal\Tests\eca_render\Kernel;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Action\ActionManager;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\eca\Token\TokenInterface;
use Drupal\eca_test_render_basics\Event\BasicRenderEvent;
use Drupal\eca_test_render_basics\RenderBasicsEvents;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;
use Drupal\views\Entity\View;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Kernel tests regarding ECA render actions.
 *
 * @group eca
 * @group eca_render
 */
class RenderActionsTest extends KernelTestBase {

  /**
   * Core action manager.
   *
   * @var \Drupal\Core\Action\ActionManager|null
   */
  protected ?ActionManager $actionManager;

  /**
   * Token services.
   *
   * @var \Drupal\eca\Token\TokenInterface|null
   */
  protected ?TokenInterface $tokenServices;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|null
   */
  protected ?EventDispatcherInterface $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'filter',
    'text',
    'options',
    'node',
    'image',
    'responsive_image',
    'serialization',
    'views',
    'eca',
    'eca_render',
    'eca_test_render_basics',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->container->get('theme_installer')->install(['claro', 'olivero']);

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->installConfig(static::$modules);
    User::create(['uid' => 0, 'name' => 'guest'])->save();
    User::create(['uid' => 1, 'name' => 'admin'])->save();
    User::create(['uid' => 2, 'name' => 'auth'])->save();

    // Create the Article content type with a standard body field.
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $node_type->save();
    node_add_body_field($node_type);

    $request = Request::create('/eca/first/second?a=b', 'POST', [], [], [], [], 'hello');
    $request->setSession(new Session());
    /** @var \Symfony\Component\HttpFoundation\RequestStack $stack */
    $stack = $this->container->get('request_stack');
    $stack->pop();
    $stack->push($request);

    $this->actionManager = \Drupal::service('plugin.manager.action');
    $this->tokenServices = \Drupal::service('eca.token_services');
    $this->eventDispatcher = \Drupal::service('event_dispatcher');
  }

  /**
   * Tests the action plugin "eca_render_build".
   */
  public function testBuild(): void {
    /** @var \Drupal\eca_render\Plugin\Action\Build $action */
    $action = $this->actionManager->createInstance('eca_render_build', [
      'value' => '[build]',
      'use_yaml' => FALSE,
      'name' => '',
      'token_name' => '',
      'weight' => '100',
      'mode' => 'append',
    ]);

    $build = [];
    $this->eventDispatcher->addListener(RenderBasicsEvents::BASIC, function (BasicRenderEvent $event) use (&$action, &$build) {
      $action->setEvent($event);
      $action->execute();
      $build = $event->getRenderArray();
    });

    $token_build = [
      '#type' => 'markup',
      '#markup' => "Hello from ECA",
      '#weight' => 100,
    ];
    $this->tokenServices->addTokenData('build', $token_build);

    $this->dispatchBasicRenderEvent();

    $build = array_intersect_key($build, array_flip(Element::children($build)));
    $this->assertSame([$token_build], $build);
  }

  /**
   * Tests the action plugin "eca_render_cacheability".
   */
  public function testCacheability(): void {
    /** @var \Drupal\eca_render\Plugin\Action\Cacheability $action */
    $action = $this->actionManager->createInstance('eca_render_cacheability', [
      'cache_type' => 'tags',
      'cache_value' => 'node:list',
      'name' => '',
      'token_name' => '',
      'weight' => '100',
      'mode' => 'append',
    ]);

    $build = [];
    $this->eventDispatcher->addListener(RenderBasicsEvents::BASIC, function (BasicRenderEvent $event) use (&$action, &$build) {
      $action->setEvent($event);
      $action->execute();
      $build = $event->getRenderArray();
    });

    $event_build = [
      '#type' => 'markup',
      '#markup' => "Hello from ECA",
      '#weight' => 100,
    ];
    $this->dispatchBasicRenderEvent($event_build);

    $event_build['#cache'] = [
      'tags' => ['node:list'],
    ];

    $this->assertSame($event_build, $build);
  }

  /**
   * Tests the action plugin "eca_render_custom_form".
   */
  public function testCustomForm(): void {
    /** @var \Drupal\eca_render\Plugin\Action\CustomForm $action */
    $action = $this->actionManager->createInstance('eca_render_custom_form', [
      'custom_form_id' => 'my_custom_form',
      'name' => '',
      'token_name' => '',
      'weight' => '100',
      'mode' => 'append',
    ]);

    $build = [];
    $this->eventDispatcher->addListener(RenderBasicsEvents::BASIC, function (BasicRenderEvent $event) use (&$action, &$build) {
      $action->setEvent($event);
      $action->execute();
      $build = $event->getRenderArray();
    });

    $this->dispatchBasicRenderEvent([]);

    $this->assertTrue(isset($build[0]));
    $this->assertSame('eca_custom_my_custom_form', $build[0]['#form_id']);
  }

  /**
   * Tests the action plugin "eca_render_details".
   */
  public function testDetails(): void {
    /** @var \Drupal\eca_render\Plugin\Action\Details $action */
    $action = $this->actionManager->createInstance('eca_render_details', [
      'title' => 'Hello',
      'open' => TRUE,
      'introduction_text' => 'Introduction',
      'summary_value' => 'Summary...',
      'name' => 'mydetails',
      'token_name' => '',
      'weight' => '100',
      'mode' => 'set:clear',
    ]);

    $build = [];
    $this->eventDispatcher->addListener(RenderBasicsEvents::BASIC, function (BasicRenderEvent $event) use (&$action, &$build) {
      $action->setEvent($event);
      $action->execute();
      $build = $event->getRenderArray();
    });

    $this->dispatchBasicRenderEvent();

    $this->assertTrue(isset($build['mydetails']));
    $this->assertSame('details', $build['mydetails']['#type']);
    $this->assertSame('Introduction', $build['mydetails']['introduction_text']['#markup']);
    $this->assertSame('Summary...', $build['mydetails']['#value']);
  }

  /**
   * Tests the action plugin "eca_render_custom_form".
   */
  public function testDropbutton(): void {
    /** @var \Drupal\eca_render\Plugin\Action\Dropbutton $action */
    $action = $this->actionManager->createInstance('eca_render_dropbutton', [
      'dropbutton_type' => 'small',
      'links' => '[links]',
      'use_yaml' => FALSE,
      'name' => '',
      'token_name' => '',
      'weight' => '100',
      'mode' => 'append',
    ]);

    $build = [];
    $this->eventDispatcher->addListener(RenderBasicsEvents::BASIC, function (BasicRenderEvent $event) use (&$action, &$build) {
      $action->setEvent($event);
      $action->execute();
      $build = $event->getRenderArray();
    });

    $this->tokenServices->addTokenData('links', [
      ['title' => 'Structure', 'url' => '/admin/structure'],
      ['title' => 'Config', 'url' => '/admin/config'],
    ]);

    $this->dispatchBasicRenderEvent([]);

    $this->assertTrue(isset($build[0]));
    $this->assertSame('dropbutton', $build[0]['#type']);
    $this->assertSame('Structure', $build[0]['#links'][0]['title']);
    $this->assertSame('Config', $build[0]['#links'][1]['title']);
  }

  /**
   * Tests the action plugin "eca_render_entity_form".
   */
  public function testEntityForm(): void {
    /** @var \Drupal\eca_render\Plugin\Action\EntityForm $action */
    $action = $this->actionManager->createInstance('eca_render_entity_form', [
      'operation' => 'default',
      'object' => 'node',
      'name' => '',
      'token_name' => '',
      'weight' => '100',
      'mode' => 'append',
    ]);

    $build = [];
    $this->eventDispatcher->addListener(RenderBasicsEvents::BASIC, function (BasicRenderEvent $event) use (&$action, &$build) {
      $action->setEvent($event);
      $node = $this->tokenServices->getTokenData('node');
      if ($action->access($node)) {
        $action->execute($node);
      }
      $build = $event->getRenderArray();
    });

    $this->tokenServices->addTokenData('node', Node::create([
      'title' => $this->randomMachineName(),
      'body' => $this->randomMachineName(),
      'type' => 'article',
      'status' => TRUE,
    ]));
    $this->dispatchBasicRenderEvent([]);

    $this->assertFalse(isset($build[0]), "User has no access to edit the node.");

    /** @var \Drupal\Core\Session\AccountSwitcherInterface $account_switcher */
    $account_switcher = \Drupal::service('account_switcher');
    $account_switcher->switchTo(User::load(1));

    $this->dispatchBasicRenderEvent([]);
    $this->assertTrue(isset($build[0]), "Admin has access to edit the node.");
  }

  /**
   * Tests the action plugin "eca_render_entity_view".
   */
  public function testEntityView(): void {
    /** @var \Drupal\eca_render\Plugin\Action\EntityView $action */
    $action = $this->actionManager->createInstance('eca_render_entity_view', [
      'view_mode' => 'default',
      'object' => 'node',
      'name' => '',
      'token_name' => '',
      'weight' => '100',
      'mode' => 'append',
    ]);

    $build = [];
    $this->eventDispatcher->addListener(RenderBasicsEvents::BASIC, function (BasicRenderEvent $event) use (&$action, &$build) {
      $action->setEvent($event);
      $node = $this->tokenServices->getTokenData('node');
      if ($action->access($node)) {
        $action->execute($node);
      }
      $build = $event->getRenderArray();
    });

    $this->tokenServices->addTokenData('node', Node::create([
      'title' => $this->randomMachineName(),
      'body' => $this->randomMachineName(),
      'type' => 'article',
      'status' => TRUE,
    ]));
    $this->dispatchBasicRenderEvent([]);

    $this->assertFalse(isset($build[0]), "User has no access to view the node.");

    /** @var \Drupal\Core\Session\AccountSwitcherInterface $account_switcher */
    $account_switcher = \Drupal::service('account_switcher');
    $account_switcher->switchTo(User::load(1));

    $this->dispatchBasicRenderEvent([]);
    $this->assertTrue(isset($build[0]), "Admin has access to view the node.");
  }

  /**
   * Tests the action plugin "eca_render_entity_view_field".
   */
  public function testEntityViewField(): void {
    /** @var \Drupal\eca_render\Plugin\Action\EntityViewField $action */
    $action = $this->actionManager->createInstance('eca_render_entity_view_field', [
      'field_name' => 'body',
      'view_mode' => 'default',
      'display_options' => '',
      'object' => 'node',
      'name' => '',
      'token_name' => '',
      'weight' => '100',
      'mode' => 'append',
    ]);

    $build = [];
    $this->eventDispatcher->addListener(RenderBasicsEvents::BASIC, function (BasicRenderEvent $event) use (&$action, &$build) {
      $action->setEvent($event);
      $node = $this->tokenServices->getTokenData('node');
      if ($action->access($node)) {
        $action->execute($node);
      }
      $build = $event->getRenderArray();
    });

    $body_value = $this->randomMachineName();
    $this->tokenServices->addTokenData('node', Node::create([
      'title' => $this->randomMachineName(),
      'body' => $body_value,
      'type' => 'article',
      'status' => TRUE,
    ]));
    $this->dispatchBasicRenderEvent([]);

    $this->assertFalse(isset($build[0]), "User has no access to view the field.");

    /** @var \Drupal\Core\Session\AccountSwitcherInterface $account_switcher */
    $account_switcher = \Drupal::service('account_switcher');
    $account_switcher->switchTo(User::load(1));

    $this->dispatchBasicRenderEvent([]);
    $this->assertTrue(isset($build[0]), "Admin has access to view the field.");
    $this->assertSame($body_value, $build[0][0]['#text']);
  }

  /**
   * Tests the action plugin "eca_get_active_theme".
   */
  public function testGetActiveTheme(): void {
    /** @var \Drupal\eca_render\Plugin\Action\GetActiveTheme $action */
    $action = $this->actionManager->createInstance('eca_get_active_theme', [
      'token_name' => 'theme_name',
    ]);

    $this->eventDispatcher->addListener(RenderBasicsEvents::BASIC, function (BasicRenderEvent $event) use (&$action, &$build) {
      $action->setEvent($event);
      $action->execute();
      $build = $event->getRenderArray();
    });

    \Drupal::service('theme.manager')->setActiveTheme(\Drupal::service('theme.initialization')->getActiveThemeByName('olivero'));
    $this->dispatchBasicRenderEvent([]);
    $this->assertEquals('olivero', $this->tokenServices->replaceClear('[theme_name]'));
  }

  /**
   * Tests the action plugin "eca_render_file_contents".
   */
  public function testGetFileContents(): void {
    /** @var \Drupal\eca_render\Plugin\Action\GetFileContents $action */
    $action = $this->actionManager->createInstance('eca_render_file_contents', [
      'uri' => 'data:text/plain;base64,' . base64_encode('Hello'),
      'encoding' => 'string:raw',
      'token_mime_type' => '',
      'token_name' => '',
      'name' => '',
      'weight' => '100',
      'mode' => 'append',
    ]);

    $build = [];
    $this->eventDispatcher->addListener(RenderBasicsEvents::BASIC, function (BasicRenderEvent $event) use (&$action, &$build) {
      $action->setEvent($event);
      $action->execute();
      $build = $event->getRenderArray();
    });

    $this->dispatchBasicRenderEvent([]);
    $this->assertSame('Hello', $build[0]['#markup']);
  }

  /**
   * Tests the action plugin "eca_render_image".
   */
  public function testImage(): void {
    /** @var \Drupal\eca_render\Plugin\Action\Image $action */
    $action = $this->actionManager->createInstance('eca_render_image:image', [
      'uri' => '/core/themes/bartik/logo.svg',
      'style_name' => '',
      'alt' => '',
      'title' => '',
      'width' => '',
      'height' => '',
      'token_name' => '',
      'name' => '',
      'weight' => '100',
      'mode' => 'append',
    ]);

    $build = [];
    $this->eventDispatcher->addListener(RenderBasicsEvents::BASIC, function (BasicRenderEvent $event) use (&$action, &$build) {
      $action->setEvent($event);
      $action->execute();
      $build = $event->getRenderArray();
    });

    $this->dispatchBasicRenderEvent([]);
    $this->assertSame('/core/themes/bartik/logo.svg', $build[0]['#uri']);
  }

  /**
   * Tests the action plugin "eca_render_link".
   */
  public function testLink(): void {
    /** @var \Drupal\eca_render\Plugin\Action\Link $action */
    $action = $this->actionManager->createInstance('eca_render_link', [
      'title' => 'Structure',
      'url' => '/admin/structure',
      'link_type' => 'modal',
      'width' => '80',
      'display_as' => 'anchor',
      'absolute' => FALSE,
      'token_name' => '',
      'name' => '',
      'weight' => '100',
      'mode' => 'append',
    ]);

    $build = [];
    $this->eventDispatcher->addListener(RenderBasicsEvents::BASIC, function (BasicRenderEvent $event) use (&$action, &$build) {
      $action->setEvent($event);
      $action->execute();
      $build = $event->getRenderArray();
    });

    $this->dispatchBasicRenderEvent([]);
    $this->assertSame('link', $build[0]['#type']);
    $this->assertInstanceOf(Url::class, $build[0]['#url']);
    $this->assertEquals('Structure', $build[0]['#title']);
  }

  /**
   * Tests the action plugin "eca_render_markup".
   */
  public function testMarkup(): void {
    /** @var \Drupal\eca_render\Plugin\Action\Markup $action */
    $action = $this->actionManager->createInstance('eca_render_markup', [
      'value' => '[build]',
      'use_yaml' => FALSE,
      'token_name' => '',
      'name' => '',
      'weight' => '100',
      'mode' => 'append',
    ]);

    $build = [];
    $this->eventDispatcher->addListener(RenderBasicsEvents::BASIC, function (BasicRenderEvent $event) use (&$action, &$build) {
      $action->setEvent($event);
      $action->execute();
      $build = $event->getRenderArray();
    });

    $token_build = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => 'Hello from ECA',
      '#weight' => 100,
    ];
    $this->tokenServices->addTokenData('build', $token_build);

    $this->dispatchBasicRenderEvent([]);
    $this->assertSame('<div>Hello from ECA</div>', trim((string) $build[0]['#markup']));
  }

  /**
   * Tests the action plugin "eca_render_responsive_image".
   */
  public function testResponsiveImage(): void {
    /** @var \Drupal\eca_render\Plugin\Action\ResponsiveImage $action */
    $action = $this->actionManager->createInstance('eca_render_responsive_image:responsive_image', [
      'uri' => '/core/themes/bartik/logo.svg',
      'style_name' => 'test',
      'alt' => '',
      'title' => '',
      'width' => '',
      'height' => '',
      'token_name' => '',
      'name' => '',
      'weight' => '100',
      'mode' => 'append',
    ]);

    $build = [];
    $this->eventDispatcher->addListener(RenderBasicsEvents::BASIC, function (BasicRenderEvent $event) use (&$action, &$build) {
      $action->setEvent($event);
      $action->execute();
      $build = $event->getRenderArray();
    });

    $this->dispatchBasicRenderEvent([]);
    $this->assertSame('/core/themes/bartik/logo.svg', $build[0]['#uri']);
    $this->assertSame('responsive_image', $build[0]['#type']);
    $this->assertSame('test', $build[0]['#responsive_image_style_id']);
  }

  /**
   * Tests the action plugin "eca_render_serialize".
   */
  public function testSerialize(): void {
    /** @var \Drupal\eca_render\Plugin\Action\Serialize $action */
    $action = $this->actionManager->createInstance('eca_render_serialize:serialization', [
      'format' => 'json',
      'value' => '[node]',
      'use_yaml' => FALSE,
      'token_name' => '',
      'name' => '',
      'weight' => '100',
      'mode' => 'append',
    ]);

    $build = [];
    $this->eventDispatcher->addListener(RenderBasicsEvents::BASIC, function (BasicRenderEvent $event) use (&$action, &$build) {
      $action->setEvent($event);
      $action->execute();
      $build = $event->getRenderArray();
    });

    $node = Node::create([
      'title' => $this->randomMachineName(),
      'body' => $this->randomMachineName(),
      'type' => 'article',
      'status' => TRUE,
    ]);
    $node->save();
    $this->tokenServices->addTokenData('node', $node);

    $this->dispatchBasicRenderEvent([]);
    $this->assertEquals(\Drupal::service('serializer')->serialize($node, 'json'), trim((string) $build[0]['#serialized']));
  }

  /**
   * Tests the action plugin "eca_set_active_theme".
   */
  public function testSetActiveTheme(): void {
    /** @var \Drupal\eca_render\Plugin\Action\SetActiveTheme $action */
    $action = $this->actionManager->createInstance('eca_set_active_theme', [
      'theme_name' => 'claro',
    ]);

    $this->eventDispatcher->addListener(RenderBasicsEvents::BASIC, function (BasicRenderEvent $event) use (&$action, &$build) {
      $action->setEvent($event);
      $action->execute();
      $build = $event->getRenderArray();
    });

    \Drupal::service('theme.manager')->setActiveTheme(\Drupal::service('theme.initialization')->getActiveThemeByName('olivero'));
    $this->dispatchBasicRenderEvent([]);
    $this->assertEquals('claro', \Drupal::theme()->getActiveTheme()->getName());
  }

  /**
   * Tests the action plugin "eca_render_set_weight".
   */
  public function testSetWeight(): void {
    /** @var \Drupal\eca_render\Plugin\Action\SetWeight $action */
    $action = $this->actionManager->createInstance('eca_render_set_weight', [
      'name' => 'some_key',
      'weight' => '107',
    ]);

    $build = [];
    $this->eventDispatcher->addListener(RenderBasicsEvents::BASIC, function (BasicRenderEvent $event) use (&$action, &$build) {
      $action->setEvent($event);
      $action->execute();
      $build = $event->getRenderArray();
    });

    $this->dispatchBasicRenderEvent([
      'some_key' => [
        '#type' => 'markup',
        '#markup' => "Hello from ECA",
        '#weight' => 100,
      ],
    ]);

    $this->assertSame(107, $build['some_key']['#weight']);
  }

  /**
   * Tests the action plugin "eca_render_text".
   */
  public function testText(): void {
    /** @var \Drupal\eca_render\Plugin\Action\Text $action */
    $action = $this->actionManager->createInstance('eca_render_text:filter', [
      'text' => '<h1>Hello from ECA</h1>',
      'format' => 'plain_text',
      'name' => '',
      'token_name' => '',
      'weight' => '100',
      'mode' => 'append',
    ]);

    $build = [];
    $this->eventDispatcher->addListener(RenderBasicsEvents::BASIC, function (BasicRenderEvent $event) use (&$action, &$build) {
      $action->setEvent($event);
      $action->execute();
      $build = $event->getRenderArray();
    });

    $this->dispatchBasicRenderEvent();

    $build = array_intersect_key($build, array_flip(Element::children($build)));
    $this->assertSame('processed_text', $build[0]['#type']);
    $this->assertSame('plain_text', $build[0]['#format']);

    $rendered = trim((string) \Drupal::service('renderer')->renderPlain($build));
    $this->assertEquals('<p>&lt;h1&gt;Hello from ECA&lt;/h1&gt;</p>', $rendered);
  }

  /**
   * Tests the action plugin "eca_render_twig".
   */
  public function testTwig(): void {
    /** @var \Drupal\eca_render\Plugin\Action\Twig $action */
    $action = $this->actionManager->createInstance('eca_render_twig', [
      'template' => 'Hello {{ user.name.value }}!',
      'value' => '[user]',
      'use_yaml' => FALSE,
      'name' => '',
      'token_name' => '',
      'weight' => '100',
      'mode' => 'append',
    ]);

    $build = [];
    $this->eventDispatcher->addListener(RenderBasicsEvents::BASIC, function (BasicRenderEvent $event) use (&$action, &$build) {
      $action->setEvent($event);
      $action->execute();
      $build = $event->getRenderArray();
    });

    /** @var \Drupal\Core\Session\AccountSwitcherInterface $account_switcher */
    $account_switcher = \Drupal::service('account_switcher');
    $account_switcher->switchTo(User::load(1));

    $this->dispatchBasicRenderEvent();

    $this->assertEquals('Hello admin!', $build[0]['#markup']);
  }

  /**
   * Tests the action plugin "eca_render_unserialize".
   */
  public function testUnserialize(): void {
    $title = $this->randomMachineName();
    $node = Node::create([
      'title' => $title,
      'body' => $this->randomMachineName(),
      'type' => 'article',
      'status' => TRUE,
    ]);
    $node->save();

    /** @var \Drupal\eca_render\Plugin\Action\Unserialize $action */
    $action = $this->actionManager->createInstance('eca_render_unserialize:serialization', [
      'format' => 'json',
      'value' => \Drupal::service('serializer')->serialize($node, 'json'),
      'type' => 'node',
      'use_yaml' => FALSE,
      'token_name' => '',
      'name' => '',
      'weight' => '100',
      'mode' => 'append',
    ]);

    $build = [];
    $this->eventDispatcher->addListener(RenderBasicsEvents::BASIC, function (BasicRenderEvent $event) use (&$action, &$build) {
      $action->setEvent($event);
      $action->execute();
      $build = $event->getRenderArray();
    });

    $this->dispatchBasicRenderEvent([]);
    $this->assertInstanceOf(NodeInterface::class, $build[0]['#data']);
    $this->assertEquals($title, $build[0]['#data']->title->value);
  }

  /**
   * Tests the action plugin "eca_render_views".
   */
  public function testViews(): void {
    View::create([
      'id' => 'test_view',
      'label' => 'Test View',
    ])->save();

    /** @var \Drupal\eca_render\Plugin\Action\Views $action */
    $action = $this->actionManager->createInstance('eca_render_views:views', [
      'view_id' => 'test_view',
      'display_id' => 'default',
      'arguments' => '',
      'token_name' => '',
      'name' => '',
      'weight' => '100',
      'mode' => 'append',
    ]);

    $build = [];
    $this->eventDispatcher->addListener(RenderBasicsEvents::BASIC, function (BasicRenderEvent $event) use (&$action, &$build) {
      $action->setEvent($event);
      $action->execute();
      $build = $event->getRenderArray();
    });

    $this->dispatchBasicRenderEvent([]);
    $this->assertInstanceOf(MarkupInterface::class, $build[0]['#markup']);
  }

  /**
   * Dispatches a basic render event.
   *
   * @param array $build
   *   (optional) The render array build.
   */
  protected function dispatchBasicRenderEvent(array $build = []): void {
    $this->eventDispatcher->dispatch(new BasicRenderEvent($build), RenderBasicsEvents::BASIC);
  }

}
