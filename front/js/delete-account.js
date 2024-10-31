var PLCOM_Main = PLCOM_Main || {};


(function ($) {
    $('.plcom-yes-delete').on('click', (e) => {
        const id = e.target.dataset.id;
        PLCOM_Main.showLoader();
        let xhr = $.ajax({
            method: "DELETE",
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-WP-Nonce', PLCOM_Main.nonce);
            },
            url: "/wp-json/plco/v1/account/" + id,
            data: {user_id: id}
        });

        if (xhr) {
            xhr.done(function (data) {
                PLCOM_Main.hideLoader();
                document.location.href="/";
            });
        }
    });



})(jQuery);
