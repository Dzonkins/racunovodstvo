<?php

namespace Drupal\invoices\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class DetailsCompanyContact extends FormBase {

  public function getFormId() {
    return 'detail_company_contact_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $company_id = NULL) {
    $company = NULL;
    if ($company_id) {
      $company = Node::load($company_id);
    }

    $form['naziv'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Naziv'),
      '#default_value' => $company ? $company->getTitle() : '',
      '#attributes' => ['readonly' => 'readonly'],
    ];
    $form['pib'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PIB'),
      '#default_value' => $company ? $company->get('field_pib')->value : '',
      '#attributes' => ['readonly' => 'readonly'],
    ];
    $form['maticni_broj'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Matični broj'),
      '#default_value' => $company ? $company->get('field_maticni_broj')->value : '',
      '#attributes' => ['readonly' => 'readonly'],
    ];
    $form['grad'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Grad'),
      '#default_value' => $company ? $company->get('field_grad')->value : '',
      '#attributes' => ['readonly' => 'readonly'],
    ];
    $form['adresa'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Adresa'),
      '#default_value' => $company ? $company->get('field_adreasa')->value : '',
      '#attributes' => ['readonly' => 'readonly'],
    ];
    $form['broj_racuna'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Broj računa'),
      '#default_value' => $company ? $company->get('field_broj_racuna')->value : '',
      '#attributes' => ['readonly' => 'readonly'],
    ];
    $form['zastupnik'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Zastupnik'),
      '#default_value' => $company ? $company->get('field_zastupnik')->value : '',
      '#attributes' => ['readonly' => 'readonly'],
    ];
    $form['nazad'] = [
      '#type' => 'link',
      '#title' => $this->t('Nazad'),
      '#url' => \Drupal\Core\Url::fromUserInput('/company-contact'),
      '#attributes' => [
        'class' => ['btn', 'btn-danger'],
        'style' => 'display: inline-block;',
      ],
    ];
    return $form;
  }
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}