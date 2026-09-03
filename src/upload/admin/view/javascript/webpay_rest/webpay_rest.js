$(document).ready(function() {
    $('.open_commerce_modal').click(onOpenModalClick);
    $('.close_commerce_modal').click(onCloseModalClick);
    $('#tb_commerce_mod_info').on('click', onModalBackdropClick);
    $('#tb_logs').hide();
    $('.tabInfo').click(onTabClick);
    $(".check_conn").click(onCheckConnClick);
});

function onOpenModalClick() {
    const dialog = document.getElementById('tb_commerce_mod_info');
    dialog.showModal();
    $('.modal-body', dialog).css('overflow-y', 'auto');
    $('.modal-body', dialog).css('max-height', $(window).height() * 0.7);
}

function onCloseModalClick() {
    document.getElementById('tb_commerce_mod_info').close();
}

function onModalBackdropClick(e) {
    if (e.target === this) {
        this.close();
    }
}

function onTabClick(e) {
    e.preventDefault();
    const target = $(this).attr("href");

    if (target == '#tb_main_info') {
        $('#tb_main_info').show();
        $('#tb_logs').hide();
    } else {
        $('#tb_main_info').hide();
        $('#tb_logs').show();
    }
}

function onCheckConnClick() {
    const url = $(this).data('url');

    $(".check_conn").text("Verificando ...");
    $(".tbk_table_trans").empty();

    $.post(url, {}, onCheckConnResponse, 'json').fail(onCheckConnFail);
}

function onCheckConnResponse(response) {
    resetCheckConnResult();

    if (!response?.status) {
        showCheckConnError({error: "Respuesta inesperada del servidor", detail: ""});
        return;
    }

    if (response.status.string == "OK") {
        showCheckConnSuccess(response.response);
    } else {
        showCheckConnError(response.response);
    }
}

function onCheckConnFail() {
    resetCheckConnResult();
    showCheckConnError({error: "No se pudo contactar al servidor", detail: ""});
}

function resetCheckConnResult() {
    $(".check_conn").text("Verificar Conexión");
    $("#div_response_status").removeClass("tbk-hide");
    $("#response_title").removeClass("tbk-hide");
    $("#response_status_text").removeClass("label-success").removeClass("label-danger");
    $("#div_response_url").addClass("tbk-hide");
    $("#div_response_token").addClass("tbk-hide");
    $("#div_error_response").addClass("tbk-hide");
    $("#div_error_detail_response").addClass("tbk-hide");
}

function showCheckConnSuccess(data) {
    $("#response_status_text").addClass("label-success").text("OK").show();
    $("#response_url_text").text(data.url);
    $("#response_token_text").empty().append($('<pre>').text(data.token_ws));
    $("#div_response_url").removeClass("tbk-hide");
    $("#div_response_token").removeClass("tbk-hide");
}

function showCheckConnError(data) {
    $("#response_status_text").addClass("label-danger").text("ERROR").show();
    $("#error_response_text").text(data.error);
    $("#error_detail_response_text").empty().append($('<pre>').text(data.detail));
    $("#div_error_response").removeClass("tbk-hide");
    $("#div_error_detail_response").removeClass("tbk-hide");
}
