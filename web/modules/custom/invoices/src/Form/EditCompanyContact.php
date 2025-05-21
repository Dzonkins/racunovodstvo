<?php

namespace Drupal\invoices\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class EditCompanyContact extends FormBase {

  public function getFormId() {
    return 'edit_company_contact_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $company_id = NULL) {
    $company = NULL;
    if ($company_id) {
      $company = Node::load($company_id);
    }

    $form['naziv'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Naziv'),
      '#required' => TRUE,
      '#default_value' => $company ? $company->getTitle() : '',
    ];
    $form['pib'] = [
      '#type' => 'textfield',
      '#title' => $this->t('PIB'),
      '#required' => TRUE,
      '#default_value' => $company ? $company->get('field_pib')->value : '',
    ];
    $form['maticni_broj'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Matični broj'),
      '#required' => TRUE,
      '#default_value' => $company ? $company->get('field_maticni_broj')->value : '',
    ];
    $form['grad'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Grad'),
      '#required' => TRUE,
      '#default_value' => $company ? $company->get('field_grad')->value : '',
    ];
    $form['adresa'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Adresa'),
      '#required' => TRUE,
      '#default_value' => $company ? $company->get('field_adreasa')->value : '',
    ];
    $form['broj_racuna'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Broj računa'),
      '#required' => TRUE,
      '#default_value' => $company ? $company->get('field_broj_racuna')->value : '',
    ];
    $form['zastupnik'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Zastupnik'),
      '#required' => TRUE,
      '#default_value' => $company ? $company->get('field_zastupnik')->value : '',
    ];
    $form['company_id'] = [
      '#type' => 'hidden',
      '#value' => $company_id,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Sačuvaj'),
      '#button_type' => 'primary',
      '#attributes' => [
        'id' => 'edit-company-' . $company_id,
      ],
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
    $company_id = $form_state->getValue('company_id');
    $company = \Drupal\node\Entity\Node::load($company_id);

    if ($company) {
      $company->setTitle($form_state->getValue('naziv'));
      $company->set('field_pib', $form_state->getValue('pib'));
      $company->set('field_maticni_broj', $form_state->getValue('maticni_broj'));
      $company->set('field_grad', $form_state->getValue('grad'));
      $company->set('field_adreasa', $form_state->getValue('adresa'));
      $company->set('field_broj_racuna', $form_state->getValue('broj_racuna'));
      $company->set('field_zastupnik', $form_state->getValue('zastupnik'));
      $company->save();
      \Drupal::messenger()->addStatus($this->t('Podaci su sačuvani.'));
      $form_state->setRedirectUrl(\Drupal\Core\Url::fromUserInput('/company-contact'));
    } else {
      \Drupal::messenger()->addError($this->t('Greška: Nije pronađena firma.'));
    }
  }
}