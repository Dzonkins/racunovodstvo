<?php

namespace Drupal\invoices\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

class companies extends ControllerBase {
  public function showContacts() {
    $build = [];
    $build['contacts_view'] = [
      '#type' => 'container',
      '#attributes' => ['style' => 'margin-top: 2rem;'],
      'view' => views_embed_view('contacts', 'page_1'),
    ];

    $build['bottom_button'] = [
        '#type' => 'link',
        '#title' => $this->t('Dodaj pravno lice'),
        '#url' => Url::fromUserInput('/node/add/contacts'),
        '#attributes' => [
            'class' => ['btn', 'btn-primary', 'bottom-button'],
            'style' => 'display: block; margin: 20px auto; text-align: center;',
        ],
    ];
    return $build;
  }
}