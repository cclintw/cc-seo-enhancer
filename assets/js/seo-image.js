jQuery(document).ready(function ($) {
    let file_frame;
    const mediaText = window.ccSeoEnhancerMedia || {};

    $('#select_org_logo').on('click', function (e) {
        e.preventDefault();

        // Open the media library.
        file_frame = wp.media({
            title: mediaText.title || 'Select or upload image',
            button: { text: mediaText.buttonText || 'Use this image' },
            library: { type: 'image' },
            multiple: false
        });

        file_frame.on('select', function () {
            const attachment = file_frame.state().get('selection').first().toJSON();
            $('#org_logo').val(attachment.url);
            $('#org_logo_preview').attr('src', attachment.url).show();
        });

        file_frame.open();
    });
});
