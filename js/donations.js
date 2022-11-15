(function ($, Drupal, once) {

  function donations(context, settings) {
      console.log('init don');
      $('.donations-banklink').once("donations-0").click(function (e) {
        console.log('a');
        e.preventDefault();
        bank_id = $(this).find('a').attr('data-bank-id');
        $.ajax({
          type: 'GET',
          url: $('#donation-payment').val(),
          data: { amount: $('#donation-amount').val(), bank: bank_id, name: $('#donation-name').val(), email: $('#donation-email').val(), purpose: $('#donations-wrapper input[name=donation_purpose]:checked').val() },
          success: function (response) {
            $('#donations-wrapper').append(response.html);
            $('#donations-banklink-form').submit();
          },
          dataType: 'json'
        });
      });

      $('.donations-amount button').once("donations-1").click(function (e) {
        e.preventDefault();
        presetAmounts = [5, 10, 15, 20, 30, 50, 100, 250, 1000, 5000, 10000, 50000, 100000];
        currentAmount = parseInt($('#donation-amount').val());
        console.log('click amount: ' + currentAmount);
        if (isNaN(currentAmount)) currentAmount = 20;
        if ($(this).hasClass('plus')) {
          if (currentAmount < 100000) {
            for (var i = 0; i < presetAmounts.length; i++) {
              if (presetAmounts[i] > currentAmount) {
                currentAmount = presetAmounts[i];
                break;
              }
            }
          }
        }
        else {
          if (currentAmount > 5) {
            for (var i = presetAmounts.length - 1; i >= 0; i--) {
              if (presetAmounts[i] < currentAmount) {
                currentAmount = presetAmounts[i];
                break;
              }
            }
          }
        }
        $('#donation-amount').val(currentAmount);
      });

  }

  Drupal.behaviors.initDonations = {
    attach: function () {
      donations();
    }
  };

})(jQuery, Drupal, once);
