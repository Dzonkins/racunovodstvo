<?php

namespace Drupal\main_page\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class MainPageController extends ControllerBase {

  protected $requestStack;
  protected $session;

  public function __construct(RequestStack $request_stack, SessionInterface $session) {
    $this->requestStack = $request_stack;
    $this->session = $session;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('session')
    );
  }

  public function hello() {
    $request = $this->requestStack->getCurrentRequest();

    // Try to get company_id from URL.
    if ($request->query->has('company_id')) {
      $company_id_from_url = $request->query->get('company_id');
      $this->session->set('company_id', $company_id_from_url);
      $company_id = $company_id_from_url;
    } else {
      // Fallback: get company_id from session.
      $company_id = $this->session->get('company_id');
    }

    if ($company_id) {
      $node = \Drupal\node\Entity\Node::load($company_id);
      if ($node && $node->getType() === 'companies') {
        $company_name = $node->label();
        return [
          '#markup' => $this->t('Dobrodosli @name', ['@name' => $company_name]),
        ];
      }
    }

    // Optional: fallback message if no company found.
    return [
      '#markup' => $this->t('Nije pronađena firma.'),
    ];
  }

}
