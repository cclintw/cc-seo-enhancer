jQuery(document).ready(function ($) {
  // Image picker.
  // $('.select-media').on('click', function (e) {
  //   e.preventDefault();
  //   var target = $(this).data('target');
  //   var frame = wp.media({
  //     title: 'Select image',
  //     multiple: false,
  //     library: { type: 'image' },
  //     button: { text: 'Use this image' }
  //   });
  //   frame.on('select', function () {
  //     var attachment = frame.state().get('selection').first().toJSON();
  //     $('#' + target).val(attachment.url).trigger('input');
  //   });
  //   frame.open();
  // });

  // Validate the GTM ID format.
  function checkGtmFormat(gtmIdRaw) {
    const gtmId = (gtmIdRaw || '').trim().toUpperCase();
    const pattern = /^GTM-[A-Z0-9]{6,}$/;
    return pattern.test(gtmId);
  }

  // Centralized toggle state control.
  function applyStates() {
    const $gtmId = $('input[name="seo_enhancer_gtm_id"]');
    const $gtmToggle = $('input[name="enable_gtm_tracking"]');
    const $gaToggle = $('input[name="enable_ga_tracking"]');
    const $fbToggle = $('input[name="enable_fb_pixel"]');

    const gtmIdVal = ($gtmId.val() || '').trim().toUpperCase();
    $gtmId.val(gtmIdVal);
    const gtmValid = checkGtmFormat(gtmIdVal);
    const gtmOn = $gtmToggle.is(':checked');

    // Evaluate GTM state.
    $gtmToggle.prop('disabled', !gtmValid);

    if (gtmValid && gtmOn) {
      // Disable GA/FB when GTM is enabled.
      $gaToggle.prop('checked', false).prop('disabled', true);
      $fbToggle.prop('checked', false).prop('disabled', true);
    } else {
      // Otherwise GA/FB can be used.
      $gaToggle.prop('disabled', false);
      $fbToggle.prop('disabled', false);
    }
  }

  // GTM ID input handler: uppercase and refresh toggle state.
  $('input[name="seo_enhancer_gtm_id"]').on('input', function () {
    $(this).val($(this).val().toUpperCase());
    applyStates();
  });

  // GTM toggle state changes.
  $('input[name="enable_gtm_tracking"]').on('change', applyStates);

  // Initial page state.
  applyStates();

  //if enable organization address schema
  $('#toggle_address_fields').on('change', function () {
    if ($(this).is(':checked')) {
      $('#address_fields_group').slideDown(200);
    } else {
      $('#address_fields_group').slideUp(200);
    }
  });


});
