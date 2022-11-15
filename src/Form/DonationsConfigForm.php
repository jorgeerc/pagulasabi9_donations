<?php

namespace Drupal\donations\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure settings for donations form.
 */
class DonationsConfigForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'donations.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'banks_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    // my form here -->
    $banks = $config->get('banks');
    $weight = 20;
    foreach ($banks as $key => $val) {
      $form['banks_'.$key] = array(
        '#type' => 'fieldset',
        '#title' => $val['name'],
        '#weight' => $weight,
        '#collapsible' => true,
        '#collapsed' => true,
      );
      $form['banks_'.$key]['banks_'.$key.'_enabled'] = array(
        '#type' => 'checkbox',
        '#title' => $val['name'].' enabled',
        '#default_value' => $config->get('banks_'.$key.'_enabled'),
      );
      if (!($config->get('banks_'.$key.'_enabled'))) {
        continue;
      }
      $form['banks_'.$key]['banks_'.$key.'_banklink_url'] = array(
        '#type' => 'textfield',
        '#title' => 'Banklink URL',
        '#default_value' => $config->get('banks_'.$key.'_banklink_url'),
        '#size' => 60,
      );
      if ($key != 'nordea') {
        $form['banks_'.$key]['banks_'.$key.'_public_key'] = array(
          '#type' => 'textarea',
          '#title' => 'Bank public key',
          '#default_value' => $config->get('banks_'.$key.'_public_key'),
        );
      }
      $form['banks_'.$key]['banks_'.$key.'_merchant_id'] = array(
        '#type' => 'textfield',
        '#title' => 'Merchant ID',
        '#default_value' => $config->get('banks_'.$key.'_merchant_id'),
        '#size' => 20,
      );
      if ($key != 'nordea') {
        $form['banks_'.$key]['banks_'.$key.'_private_key'] = array(
          '#type' => 'textarea',
          '#title' => 'Merchant private key',
          '#default_value' => $config->get('banks_'.$key.'_private_key'),
        );
      }
      else {
        $form['banks_'.$key]['banks_'.$key.'_mac_key'] = array(
          '#type' => 'textfield',
          '#title' => 'Merchant MAC key',
          '#default_value' => $config->get('banks_'.$key.'_mac_key'),
          '#size' => 60,
        );
      }
      $form['banks_'.$key]['banks_'.$key.'_linkrecurr'] = array(
        '#type' => 'textarea',
        '#title' => 'Recurring URL',
        '#default_value' => $config->get('banks_'.$key.'_linkrecurr') ? $config->get('banks_'.$key.'_linkrecurr') : $val['linkrecurr'],
        '#size' => 60,
      );
      $weight++;
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $banks = $this->config(static::SETTINGS)->get('banks');
    foreach ($banks as $key => $val) {
        $this->config(static::SETTINGS)
          ->set('banks_'.$key.'_enabled', $form_state->getValue('banks_'.$key.'_enabled'))
          ->set('banks_'.$key.'_banklink_url', $form_state->getValue('banks_'.$key.'_banklink_url'))
          ->set('banks_'.$key.'_linkrecurr', $form_state->getValue('banks_'.$key.'_linkrecurr'))
          ->set('banks_'.$key.'_merchant_id', $form_state->getValue('banks_'.$key.'_merchant_id'))
          ->set('banks_'.$key.'_public_key', $form_state->getValue('banks_'.$key.'_public_key'))
          ->set('banks_'.$key.'_private_key', $form_state->getValue('banks_'.$key.'_private_key'));
        if ($key == 'nordea') {
          $this->config(static::SETTINGS)->set('banks_'.$key.'_mac_key', $form_state->getValue('banks_'.$key.'_mac_key'));
        }
    }
    $this->config(static::SETTINGS)->save();
    parent::submitForm($form, $form_state);
  }

}
