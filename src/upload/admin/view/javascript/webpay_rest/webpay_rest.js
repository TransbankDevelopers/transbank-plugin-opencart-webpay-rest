$(document).ready(function() {

    $('#tb_commerce_mod_info').on('show.bs.modal', function() {
        $('.modal .modal-body').css('overflow-y', 'auto');
        $('.modal .modal-body').css('max-height', $(window).height() * 0.7);
    });

    $('#tb_logs').hide();

    $('.tabInfo').click(function(e) {
        var target = $(this).attr("href");
        if (target == '#tb_main_info') {
            $('#tb_main_info').show();
            $('#tb_logs').hide();
        } else {
            $('#tb_main_info').hide();
            $('#tb_logs').show();
        }
    });

    $(".check_conn").click(function() {

        var url = $(this).data('url');

        $(".check_conn").text("Verificando ...");
        $(".tbk_table_trans").empty();

        $.post(url, {}, function(response){

            $(".check_conn").text("Verificar Conexión");
            $("#div_response_status").removeClass("tbk-hide");
            $("#response_title").removeClass("tbk-hide");
            $("#response_status_text").removeClass("label-success").removeClass("label-danger");

            if(response.status.string == "OK") {

                $("#response_status_text").addClass("label-success").text("OK").show();
                $("#response_url_text").text(response.response.url);
                $("#response_token_text").empty().append($('<pre>').text(response.response.token_ws));

                $("#div_response_url").removeClass("tbk-hide");
                $("#div_response_token").removeClass("tbk-hide");

            } else {

                $("#response_status_text").addClass("label-danger").text("ERROR").show();
                $("#error_response_text").text(response.response.error);
                $("#error_detail_response_text").empty().append($('<pre>').text(response.response.detail));

                $("#div_error_response").removeClass("tbk-hide");
                $("#div_error_detail_response").removeClass("tbk-hide");
            }

        },'json');
    });
});
