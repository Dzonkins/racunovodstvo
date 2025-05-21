<?php

namespace Drupal\invoices\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class CreateOutDocument extends FormBase {
  
  public function getFormId() {
    return 'create_out_document_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['document_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Broj fakture'),
      '#required' => TRUE,
    ];
    $form['document_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Datum slanja'),
      '#required' => TRUE,
    ];

    $session = \Drupal::request()->getSession();
    $company_id = $session->get('company_id');

    $options = ['' => $this->t('Izaberi kupca')];
    if ($company_id) {
      $nids = \Drupal::entityQuery('node')
        ->condition('type', 'contacts')
        ->condition('field_firma_id', $company_id)
        ->condition('status', 1)
        ->accessCheck(TRUE)
        ->execute();

      if ($nids) {
        $nodes = \Drupal\node\Entity\Node::loadMultiple($nids);
        foreach ($nodes as $node) {
          $options[$node->id()] = $node->getTitle();
        }
      }
    }

    $form['top_controls'] = [
      '#type' => 'container',
      '#attributes' => [
        'style' => 'display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;',
      ],
    ];

    $form['top_controls']['kupac'] = [
      '#type' => 'select',
      '#title' => $this->t('Kupac'),
      '#required' => TRUE,
      '#options' => $options,
      '#default_value' => '',
    ];

    $form['top_controls']['confirm'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Placeno'),
      '#default_value' => 0,
    ];

    $items = $form_state->getValue('items');
    if (!$items) {
      $items = [
        ['naziv' => '', 'kolicina' => 1, 'cena' => 0, 'osnovica' => 0, 'iznos_pdv' => 0, 'pdv_posto' => 0, 'ukupno' => 0],
      ];
      $form_state->setValue('items', $items);
    }

    $form['items'] = [
      '#type' => 'table',
      '#title' => $this->t('Items'),
      '#header' => [
        $this->t('Naziv'),
        $this->t('Količina'),
        $this->t('Cena'),
        $this->t('Osnovica'),
        $this->t('Iznos PDV-a'),
        $this->t('PDV posto'),
        $this->t('Ukupno'),
        $this->t('Akcije'),
      ],
      '#prefix' => '<div id="items-table-wrapper">',
      '#suffix' => '</div>',
    ];

    foreach ($items as $delta => $item) {
      $form['items'][$delta]['naziv'] = [
        '#type' => 'textfield',
        '#default_value' => $item['naziv'],
      ];
      $form['items'][$delta]['kolicina'] = [
        '#type' => 'number',
        '#default_value' => $item['kolicina'],
        '#min' => 1,
        '#step' => 1,
        '#ajax' => [
          'callback' => '::ajaxCallback',
          'event' => 'change',
          'wrapper' => 'items-table-wrapper',
        ],
      ];
      $form['items'][$delta]['cena'] = [
        '#type' => 'number',
        '#default_value' => isset($item['cena']) ? $item['cena'] : '',
        '#step' => 0.01,
        '#min' => 0,
        '#ajax' => [
          'callback' => '::ajaxCallback',
          'event' => 'change',
          'wrapper' => 'items-table-wrapper',
        ],
      ];
      $form['items'][$delta]['osnovica'] = [
        '#type' => 'number',
        '#default_value' => $item['osnovica'],
        '#step' => 0.01,
        '#min' => 0,
        '#attributes' => [
          'readonly' => 'readonly',
          'tabindex' => '-1',
          'style' => 'pointer-events: none;',
        ],
      ];
      $form['items'][$delta]['iznos_pdv'] = [
        '#type' => 'number',
        '#default_value' => $item['iznos_pdv'],
        '#step' => 0.01,
        '#min' => 0,
        '#attributes' => [
          'readonly' => 'readonly',
          'tabindex' => '-1',
          'style' => 'pointer-events: none;',
        ],
      ];
      $form['items'][$delta]['pdv_posto'] = [
        '#type' => 'number',
        '#default_value' => $item['pdv_posto'],
        '#step' => 1,
      ];
      $form['items'][$delta]['ukupno'] = [
        '#type' => 'number',
        '#default_value' => $item['ukupno'],
        '#step' => 0.01,
        '#min' => 0,
        '#attributes' => [
          'readonly' => 'readonly',
          'tabindex' => '-1',
          'style' => 'pointer-events: none;',
        ],
      ];
      $form['items'][$delta]['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Ukloni'),
        '#submit' => ['::removeItem'],
        '#name' => 'remove-' . $delta,
        '#limit_validation_errors' => [],
        '#ajax' => [
          'callback' => '::ajaxCallback',
          'wrapper' => 'items-table-wrapper',
        ],
      ];
    }

    $form['add_item'] = [
      '#type' => 'submit',
      '#value' => $this->t('Dodaj stavku'),
      '#submit' => ['::addItem'],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'items-table-wrapper',
      ],
    ];

    $form['actions'] = [
      '#type' => 'container',
      '#attributes' => [
        'style' => 'display: flex; justify-content: center; gap: 1rem; margin-top: 1rem;',
      ],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Sacuvaj'),
      '#attributes' => [
        'class' => ['btn', 'btn-success'],
      ],
    ];

    $form['actions']['nazad'] = [
      '#type' => 'link',
      '#title' => $this->t('Nazad'),
      '#url' => \Drupal\Core\Url::fromUserInput('/company-contact'),
      '#attributes' => [
        'class' => ['btn', 'btn-danger'],
      ],
    ];

    return $form;
  }

  public function addItem(array &$form, FormStateInterface $form_state) {
    $items = $form_state->get('items') ?? [];
    $items[] = ['naziv' => '', 'kolicina' => 1, 'cena' => 0, 'osnovica' => 0, 'iznos_pdv' => 0, 'pdv_posto' => 0, 'ukupno' => 0];
    $form_state->set('items', $items);
    $form_state->setRebuild();
  }

  public function removeItem(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $delta = str_replace('remove-', '', $triggering_element['#name']);
    $items = $form_state->get('items') ?? [];
    unset($items[$delta]);
    $form_state->set('items', array_values($items));
    $form_state->setRebuild();
  }

  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    $items = $form_state->getValue('items');
    foreach ($items as $delta => &$item) {
        $kolicina = isset($item['kolicina']) && is_numeric($item['kolicina']) ? $item['kolicina'] : 0;
        $cena = isset($item['cena']) && is_numeric($item['cena']) ? $item['cena'] : 0;
        $item['osnovica'] = round($kolicina * $cena, 2);

        // Log each item's calculation
        \Drupal::logger('invoices')->notice('Row @delta: kolicina=@kolicina, cena=@cena, osnovica=@osnovica', [
            '@delta' => $delta,
            '@kolicina' => $kolicina,
            '@cena' => $cena,
            '@osnovica' => $item['osnovica'],
        ]);
    }
    // Log the whole items array
    \Drupal::logger('invoices')->notice('All items after calculation: <pre>@items</pre>', [
        '@items' => print_r($items, TRUE),
    ]);
    $form_state->set('items', $items);
    $form_state->setValue('items', $items);

    $form_state->setRebuild(TRUE);

    return $form['items'];
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
     \Drupal::messenger()->addStatus($this->t('Prodaja sacuvana'));
  }
}