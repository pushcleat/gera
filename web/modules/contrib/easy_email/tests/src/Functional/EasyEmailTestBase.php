<?php

namespace Drupal\Tests\easy_email\Functional;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityStorageException;
use Behat\Mink\Element\NodeElement;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\easy_email\Entity\EasyEmailTypeInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\BrowserTestBase;

/**
 * Class EasyEmailTestBase
 */
abstract class EasyEmailTestBase extends BrowserTestBase {

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'field',
    'field_ui',
    'text',
    'file',
    'options',
    'token',
    'mailsystem',
    'symfony_mailer_lite',
    'easy_email',
  ];

  /**
   * @var \Drupal\filter\FilterFormatInterface
   */
  protected $htmlFormat;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  protected function setUp(): void{
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser($this->getAdministratorPermissions());
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/modules');
    $this->createHtmlTextFormat();
    $this->drupalLogout();
    $this->adminUser = $this->drupalCreateUser($this->getAdministratorPermissions());
    $this->drupalLogin($this->adminUser);
    $this->initBrowserOutputFile();
  }

  protected function createHtmlTextFormat() {
    $edit = [
      'format' => 'html',
      'name' => 'HTML',
    ];
    $this->drupalGet('admin/config/content/formats/add');
    $this->submitForm($edit, t('Save configuration'));
    filter_formats_reset();
    $this->htmlFormat = FilterFormat::load($edit['format']);
  }

  protected function getAdministratorPermissions() {
    $permissions = [
      'administer email types',
      'administer easy_email fields',
      'add email entities',
      'edit email entities',
      'view all email entities',
      'administer filters',
      'administer modules',
    ];
    if (!empty($this->htmlFormat)) {
      $permissions[] = $this->htmlFormat->getPermissionName();
    }
    return $permissions;
  }

  /**
   * @param array $values
   * @param bool $save
   *
   * @return \Drupal\easy_email\Entity\EasyEmailTypeInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function createTemplate($values = [], $save = TRUE) {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $email_template_storage */
    $email_template_storage = \Drupal::entityTypeManager()->getStorage('easy_email_type');
    $template = $email_template_storage->create($values);
    if ($save) {
      $template->save();
    }
    return $template;
  }

  /**
   * Get sent emails captured by the Test Mail Collector.
   *
   * @param array $params
   *   Parameters to use for matching emails.
   *
   * @return array
   */
  protected function getSentEmails(array $params) {
    \Drupal::state()->resetCache();
    $captured_emails = \Drupal::state()->get('system.test_mail_collector');
    $matched_emails = [];
    if (!empty($captured_emails)) {
      foreach ($captured_emails as $email) {
        $is_match = [];
        foreach ($params as $key => $value) {
          $param_match = FALSE;
          if (isset($email[$key]) && is_string($email[$key]) && $email[$key] == $value) {
            $param_match = TRUE;
          }
          elseif (isset($email['params'][$key]) && is_string($email['params'][$key]) && $email['params'][$key] == $value) {
            $param_match = TRUE;
          }
          elseif (isset($email[$key]) && $email[$key] instanceof TranslatableMarkup && $email[$key]->getUntranslatedString() == $value) {
            $param_match = TRUE;
          }
          elseif (isset($email['params'][$key]) && $email['params'][$key] instanceof TranslatableMarkup && $email['params'][$key]->getUntranslatedString() == $value) {
            $param_match = TRUE;
          }
          $is_match[] = $param_match;
        }
        if (count($is_match) == count(array_filter($is_match))) {
          $matched_emails[] = $email;
        }
      }
    }
    return $matched_emails;
  }

  /**
   * @param \Drupal\easy_email\Entity\EasyEmailTypeInterface $easy_email_type
   * @param string $field_name
   * @param string $label
   */
  protected function addUserField(EasyEmailTypeInterface $easy_email_type, $field_name = 'field_user', $label = 'User') {
    $field_definition = BaseFieldDefinition::create('entity_reference')
      ->setTargetEntityTypeId('easy_email')
      ->setTargetBundle($easy_email_type->id())
      ->setName($field_name)
      ->setLabel($label)
      ->setRevisionable(TRUE)
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setRequired(FALSE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    try {
      easy_email_create_field($field_definition, FALSE);
    }
    catch (\RuntimeException $e) {
      // In this case, field already exists from installation
    }
    catch (EntityStorageException $e) {
      // In this case, field already exists from installation
    }
  }

  /**
   * @param \Drupal\easy_email\Entity\EasyEmailTypeInterface $easy_email_type
   * @param string $field_name
   */
  protected function removeField(EasyEmailTypeInterface $easy_email_type, $field_name) {
    $this->drupalGet('admin/structure/email-templates/templates/' . $easy_email_type->id() . '/edit/fields/easy_email.' . $easy_email_type->id() . '.' . $field_name . '/delete');
    $this->submitForm([], 'Delete');
  }

  /**
   * @param \Behat\Mink\Element\NodeElement $iframe
   *
   * @return array
   */
  protected function getIframeUrlAndQuery(NodeElement $iframe) {
    $url = $iframe->getAttribute('src');
    $front_url = Url::fromRoute('<front>', [], ['absolute' => FALSE]);
    $base_url = $front_url->toString();
    $url = preg_replace('#^' . $base_url . '#', '', $url);
    $url = explode('?', $url);
    $query = [];
    if (!empty($url[1])) {
      $query_parts = explode('=', $url[1]);
      for ($i = 0; $i < count($query_parts); $i += 2) {
        $query[$query_parts[$i]] = $query_parts[$i+1];
      }
    }

    return ['path' => $url[0], 'query' => $query];
  }

}
