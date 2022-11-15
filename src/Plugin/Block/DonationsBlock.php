<?php

namespace Drupal\donations\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a block that has custom configuration option.
 *
 * @Block(
 *   id = "donations_config",
 *   admin_label = @Translation("Donations: configuration form"),
 *   category = "Pagulasabi"
 * )
 */
class DonationsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm('Drupal\donations\Form\DonationsForm');
    // return [
    //   '#theme' => 'donations_block',
    //   '#attached' => [
    //     'library' => [
    //       'donations/css_lib',
    //       'donations/js_lib',
    //     ],
    //   ],
    // ];
  }

  /**
    * {@inheritdoc}
    */
   public function blockForm($form, FormStateInterface $form_state) {
     $form = parent::blockForm($form, $form_state);
    //  $settings = $this->config('donations.settings');
    //  $config = $this->getConfiguration();
    //  $weight = 20;
    //  foreach ($banks as $key => $val) {
    //    $form['banks_'.$key.'_enabled'] = array(
    //      '#type' => 'checkbox',
    //      '#title' => $val.' enabled',
    //      '#default_value' => $config['banks_'.$key.'_enabled'],
    //    );
    //    $weight++;
    //  }
     return $form;
   }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) : void {
    // The configuration form validation is performed here.
    // In this example we don't want the message text to be 'Hello world!'
    if ($form_state->getValue('message') === 'Hello world!') {
      $form_state->setErrorByName(
        'message',
        $this->t('You cannot enter the most generic text ever!')
      );
    }
  }

  /**
   *
   */
  public function blockSubmit($form, FormStateInterface $form_state) : void {
    // We do this to ensure no other configuration options get lost.
    parent::blockSubmit($form, $form_state);

    // Here the value entered by the user is saved into the configuration.
    // $this->configuration['message'] = $form_state->getValue('message');
    // $banks = array('seb' => 'SEB');
    // foreach ($banks as $key => $val) {
    //   $values_bank = $form_state->getValue('donations_'.$key);
    //   $this->configuration['banks_'.$key.'_enabled'] = $values_bank['banks_'.$key.'_enabled'];
    // }
  }

}
