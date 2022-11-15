<?php

/**
 * Prepares variables for donation form templates.
 *
 * Default template: donations_form.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - item: An ImageItem object.
 *   - item_attributes: An optional associative array of html attributes to be
 *     placed in the img tag.
 *   - entity: An entity object.
 *   - settings: Formatter settings array.
 *
 * @codingStandardsIgnoreStart
 */
function template_preprocess_donations_block(&$variables) {
  // @codingStandardsIgnoreEnd
  $config = \Drupal::config('donations.settings');

  $variables['donations_title'] = t('Donations');
  $variables['donations_intro'] = $config->get('basic.donations_intro');
  $variables['label_minus'] = $config->get('basic.label_minus');
  $variables['label_plus'] = $config->get('basic.label_plus');
  $variables['label_info_name'] = $config->get('basic.label_info_name');
  $variables['label_info_email'] = $config->get('basic.label_info_email');
  $variables['fund_text'] = $config->get('basic.fund_text');
  $variables['funds'] = $config->get('funds');
  $variables['bank_text'] = $config->get('bank_text');
  $variables['recurring_intro'] = $config->get('recurring_intro');
  $variables['recurring_text'] = $config->get('recurring_text');

  $variables['banks'] = array();
  for($i = 0;$i < count($config->get('banks'));$i++) {
    $key = array_keys($config->get('banks'))[$i];
    if ($config->get('banks_'.$key.'_enabled') == 1) {
      $bank = $config->get('banks.'.$key);
      $img_path = '/images/'.$key.(($key == 'lhv' or $key == 'coop' or $key == 'nordea' or $key == 'luminor') ? '.png' : '.gif');
      $bank['img'] = file_create_url(drupal_get_path('module', 'donations').$img_path);
      $variables['banks'][$key] = $bank;
    }
  }
  // todo: change by route name with path
  $variables['donation_payment_url'] = base_path().'donations/payment';
}

function donations_form_donations_form_alter(&$form, $form_state, $form_id) {
  $view_ids = ['view_id_1', 'view_id_2'];
  $view = $form_state->getStorage('view');
  if ($form_id == 'views_exposed_form' && in_array($view['view']->id(), $view_ids)) {
    // Do some shlanga.
  }
}
