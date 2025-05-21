<?php

namespace Drupal\invoices\Plugin\views\argument_default;

use Drupal\views\Plugin\views\argument_default\ArgumentDefaultPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Default argument plugin to get company_id from session.
 *
 * @ViewsArgumentDefault(
 *   id = "contacts_view_filter",
 *   title = @Translation("Company ID from session")
 * )
 */
class ContactsViewFilter extends ArgumentDefaultPluginBase implements ContainerFactoryPluginInterface {

  protected $session;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, SessionInterface $session) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->session = $session;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('session')
    );
  }

  public function getArgument() {
    $company_id = $this->session->get('company_id');
    return $company_id;
  }
}