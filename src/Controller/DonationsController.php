<?php

namespace Drupal\donations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for donations module.
 */
class DonationsController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function donations_generate_banklink() {
    if (!isset($_REQUEST['amount']) or !isset($_REQUEST['bank'])) exit;
    $amount = number_format($_REQUEST['amount'], 2, '.', '');
    $funds = $this->config('donations.settings')->get('funds');

    $purpose = $_REQUEST['purpose'];
    $message = 'Annetus '.$amount.' EUR ('.$funds[$purpose].')';
    $bank_id = $_REQUEST['bank'];

    $connection = \Drupal::service('database');
    $payment_id = $connection->insert('donations')
      ->fields([
        'amount' => $amount,
        'bank_id' => $bank_id,
        'name' => $_REQUEST['name'],
        'email' => $_REQUEST['email'],
        'purpose' => $_REQUEST['purpose'],
        'created' => date('Y-m-d H:m:s'),
        'payment_success' => 0,
        'payment_message' => '',
        'return_url' => urldecode($_SERVER['REDIRECT_URL']),
      ])
      ->execute();

    $return_url = $GLOBALS['base_root'].base_path().'donations/return/'.$payment_id;
    $cancel_url = $GLOBALS['base_root'].base_path().'donations/return/'.$payment_id.'/cancel';

    if ($bank_id == 'paypal') { // PAYPAL
      $VK = array(
        'cmd' => '_cart',
        'upload' => 1,
        'invoice' => $payment_id,
        'business' => $this->config('donations.settings')->get('banks_paypal_merchant_id'),
        'item_name_1' => $message,
        'amount_1' => $amount,
        'currency_code' => 'EUR',
        'no_shipping' => 1,
        'no_note' => 1,
        'return' => $return_url,
        'rm' => 2,
        'cancel_return' => $cancel_url
      );
    }
    elseif ($bank_id == 'nordea') { // NORDEA BANKLINK
      $VK = array(
        'SOLOPMT_VERSION' => '0003',
        'SOLOPMT_STAMP' => $payment_id,
        'SOLOPMT_RCV_ID' => $this->config('donations.settings')->get('banks_nordea_merchant_id'),
        'SOLOPMT_LANGUAGE' => 4,
        'SOLOPMT_AMOUNT' => $amount,
        'SOLOPMT_REF' => $this->donations_get_reference_number($payment_id),
        'SOLOPMT_DATE' => 'EXPRESS',
        'SOLOPMT_MSG' => $message,
        'SOLOPMT_KEYVERS' => '0001',
        'SOLOPMT_CUR' => 'EUR',
        'SOLOPMT_CONFIRM' => 'YES',
        'SOLOPMT_REJECT' => $cancel_url,
        'SOLOPMT_CANCEL' => $cancel_url,
        'SOLOPMT_RETURN' =>  $return_url
      );
      $VK['SOLOPMT_MAC'] = $this->donations_create_nordea_signature($VK, $this->config('donations.settings')->get('banks_nordea_mac_key'));
    }
    else {
      $timestamp = \Drupal::time()->getRequestTime();
      $VK = array(
        'VK_SERVICE' => '1012',
        'VK_VERSION' => '008',
        'VK_SND_ID' => $this->config('donations.settings')->get('banks_'.$bank_id.'_merchant_id'),
        'VK_STAMP' => $payment_id,
        'VK_AMOUNT' => $amount,
        'VK_CURR' => 'EUR',
        'VK_REF' => $this->donations_get_reference_number($payment_id),
        'VK_MSG' => $message,
        'VK_RETURN' => $return_url,
        'VK_CANCEL' => $cancel_url,
        'VK_DATETIME' => date('c',$timestamp)
      );
      $VK['VK_MAC'] = $this->donations_create_banklink_signature($VK, $this->config('donations.settings')->get('banks_'.$bank_id.'_private_key'));
      $VK['VK_LANG'] = 'EST';
      $VK['VK_ENCODING'] = 'UTF-8';
    }

    $bank_link_url = $this->config('donations.settings')->get('banks_'.$bank_id.'_banklink_url');
    $output = '<form action="'.$bank_link_url.'" method="POST" id="donations-banklink-form">';
    foreach ($VK as $key => $val) {
      $output .= '<input type="hidden" name="'.$key.'" value="'.htmlspecialchars($val).'" />';
    }
    $output .= '</form>';

    return new JsonResponse(['html' => $output]);
  }

  /**
   * {@inheritdoc}
   */
  function donations_payment_return_cancel($payment_id) {
    $this->donations_payment_return($payment_id, 0);
  }

  /**
   *
   * {@inheritdoc}
   */
  function donations_payment_return($payment_id, $success = 1) {
    $VK = array();
    if (isset($_POST['invoice']) and isset($_POST['payment_status'])) {
      $VK = $_POST;
    }
    else {
      foreach ((array)$_REQUEST as $key => $val) {
        if (substr($key, 0, 3) == 'VK_' or substr($key, 0, 8) == 'SOLOPMT_') $VK[$key] = $val;
      }
    }

    $payment_success = 0;
    $payment_message = '';
    if ($success == 1 and ((isset($VK['VK_SERVICE']) and $VK['VK_SERVICE'] == 1111) or isset($VK['SOLOPMT_RETURN_STAMP']) or (isset($VK['payment_status']) and in_array($VK['payment_status'], array('Pending', 'Completed', 'Processed'))))) {
      $payment_success = 1;
      $payment_message = http_build_query($VK);
    }

    $connection = \Drupal::service('database');
    $connection->update('donations')
      ->fields([
        'payment_success' => $payment_success,
        'payment_message' => $payment_message,
      ])
      ->condition('id', $payment_id, '=')
      ->execute();

    $return_url = $connection->query('SELECT return_url FROM {donations} WHERE id = :pid', [':pid' => $payment_id])->fetchField();

    //TODO
    if ($payment_success == 1) {
      drupal_set_message(t('Payment was successful. Thank you for your support!'));
      drupal_goto($return_url);
    }
    else {
      drupal_set_message(t('Donation payment failed. In the case of repeated errors, please contact the site administrator.'), 'error');
      drupal_goto($return_url);
    }
  }

  /* BANKLINKS */
  function donations_create_banklink_signature($VK, $merchant_private_key, $passphrase = '') {
    $data = $this->donations_compose_banklink_data($VK);
    $pkeyid = openssl_get_privatekey($merchant_private_key, $passphrase);
    openssl_sign($data, $signature, $pkeyid);
    $VK_MAC = base64_encode($signature);
    openssl_free_key($pkeyid);
    return $VK_MAC;
  }

  function donations_compose_banklink_data($VK) {
    $data = '';
    foreach ($VK as $data_bit) {
      $data .= $this->donations_banklink_str_pad($data_bit);
    }
    return $data;
  }

  function donations_banklink_str_pad($str = '') {
    return str_pad(mb_strlen($str, 'UTF-8'), 3, '0', STR_PAD_LEFT).$str;
  }

  function donations_create_nordea_signature($VK, $mac_key) {
    return strtoupper(md5("{$VK['SOLOPMT_VERSION']}&{$VK['SOLOPMT_STAMP']}&{$VK['SOLOPMT_RCV_ID']}&{$VK['SOLOPMT_AMOUNT']}&{$VK['SOLOPMT_REF']}&{$VK['SOLOPMT_DATE']}&{$VK['SOLOPMT_CUR']}&{$mac_key}&"));
  }

  function donations_get_reference_number($str) {
    $weights = array(7, 3, 1, 7, 3, 1, 7, 3, 1, 7, 3, 1, 7, 3, 1, 7, 3, 1, 7, 3);
    $str_a = preg_split("//", $str, -1, PREG_SPLIT_NO_EMPTY);
    $sum = 0;
    $weights = array_reverse(array_slice($weights, 0, count($str_a)));
    foreach ($str_a as $index => $num) {
      $add = $num * $weights[$index];
      $sum += $add;
    }
    if (($sum % 10) != 0) $jrk = (10 - ($sum % 10));
    else $jrk = 0;
    return $str.$jrk;
  }

}
