<?php

namespace Drupal\registration\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContentTypeButtonsController extends ControllerBase {

  protected $currentUser;

  public function __construct(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
  }


  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }


  public function contentButtons() {
    $build = [];

    $current_user_id = $this->currentUser->id();

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'companies')
      ->condition('status', 1)
      ->condition('uid', $current_user_id)
      ->sort('created', 'DESC')
      ->accessCheck(TRUE);

    $nids = $query->execute();

    if (!empty($nids)) {
      $nodes = \Drupal\node\Entity\Node::loadMultiple($nids);

      foreach ($nodes as $node) {
       \Drupal::service('session')->set('company_id', $node->id());

        $url = Url::fromUserInput('/main');
        $url->setOptions(['query' => ['company_id' => $node->id()]]);
        $build[] = [
          '#type' => 'link',
          '#title' => $node->label(),
          '#url' => $url,
          '#attributes' => [
            'class' => ['btn', 'btn-primary', 'content-type-button'],
          ],
        ];
      }
    } else {
      $build[] = [
        '#markup' => $this->t('You have not created any companies yet.'),
        '#prefix' => '<div class="no-companies-message">',
        '#suffix' => '</div>',
      ];
    }

    $build['bottom_button'] = [
    '#type' => 'link',
    '#title' => $this->t('Dodaj firmu'),
    '#url' => Url::fromUserInput('/node/add/companies'),
    '#attributes' => [
      'class' => ['btn', 'btn-primary', 'bottom-button'],
      'style' => 'display: block; margin: 20px auto; text-align: center;',
    ],
  ];

    return [
        '#type' => 'container',
        '#attributes' => ['class' => ['content-type-buttons', 'mt-4']],
        'buttons' => $build,
    ];
  }

}