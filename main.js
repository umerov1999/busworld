function not_error(resp) {
    if (typeof resp.error != 'undefined') {
        alert_error(resp.error.msg);
        return false;
    } else {
        return true;
    }
}

function page_work(resp) {
    if (not_error(resp)) {
        remove_hooks();
        $('#main_content').fadeOut("fast", function () {
            $('#main_content').html(resp.htmlPage);
            $('#main_content').fadeIn("fast");
            create_hooks();
            update_nav_page(resp);
        });
        return true;
    } else {
        return false;
    }
}

function page_error(req, status, err) {
    alert_error('что-то пошло не так ' + req + ' ' + status + ' ' + err);
}

function update_nav_page(resp) {
    if (resp.page_active == 'main_page') {
        $('#main').addClass('active');
        $('#lk').removeClass('active');
        $('#auth').removeClass('active');
        $('#register').removeClass('active');
        $('#search').removeClass('active');
        $('#stocks').removeClass('active');
        $('#reservations').removeClass('active');
        $('#profile').removeClass('active');
        $.ajax({
            url: './rest.php?action=fetch_places',
            dataType: 'json',
            success: function( resp ) {
                if (not_error(resp)) {
                    document.getElementById('place_from').innerHTML = resp.place_from;
                    document.getElementById('place_to').innerHTML = resp.place_to;
                }
            },
            error: page_error
        });
    } else if (resp.page_active == 'auth') {
        $('#main').removeClass('active');
        $('#lk').addClass('active');
        $('#auth').addClass('active');
        $('#register').removeClass('active');
        $('#search').removeClass('active');
        $('#stocks').removeClass('active');
        $('#reservations').removeClass('active');
        $('#profile').removeClass('active');
    } else if (resp.page_active == 'register') {
        $('#main').removeClass('active');
        $('#lk').addClass('active');
        $('#auth').removeClass('active');
        $('#register').addClass('active');
        $('#search').removeClass('active');
        $('#stocks').removeClass('active');
        $('#reservations').removeClass('active');
        $('#profile').removeClass('active');
    } else if (resp.page_active == 'search') {
        $('#main').removeClass('active');
        $('#lk').removeClass('active');
        $('#auth').removeClass('active');
        $('#register').removeClass('active');
        $('#search').addClass('active');
        $('#stocks').removeClass('active');
        $('#reservations').removeClass('active');
        $('#profile').removeClass('active');
        $.ajax({
            url: './rest.php?action=fetch_routes',
            dataType: 'json',
            success: function( resp ) {
                if (not_error(resp)) {
                    document.getElementById('finded_routes').innerHTML = resp.result;
                }
            },
            error: page_error
        });
    } else if (resp.page_active == 'stocks') {
        $('#main').removeClass('active');
        $('#lk').removeClass('active');
        $('#auth').removeClass('active');
        $('#register').removeClass('active');
        $('#search').removeClass('active');
        $('#stocks').addClass('active');
        $('#reservations').removeClass('active');
        $('#profile').removeClass('active');
        $.ajax({
            url: './rest.php?action=fetch_stocks',
            dataType: 'json',
            success: function( resp ) {
                if (not_error(resp)) {
                    document.getElementById('finded_stocks').innerHTML = resp.result;
                }
            },
            error: page_error
        });
    } else if (resp.page_active == 'reservations_page') {
        $('#main').removeClass('active');
        $('#lk').removeClass('active');
        $('#auth').removeClass('active');
        $('#register').removeClass('active');
        $('#search').removeClass('active');
        $('#stocks').removeClass('active');
        $('#reservations').addClass('active');
        $('#profile').removeClass('active');
        $.ajax({
            url: './rest.php?action=fetch_my_reservations',
            dataType: 'json',
            success: function( resp ) {
                if (not_error(resp)) {
                    document.getElementById('finded_reservations').innerHTML = resp.result;
                }
            },
            error: page_error
        });
    }  else if (resp.page_active == 'reservation_info_page') {
        $('#main').removeClass('active');
        $('#lk').removeClass('active');
        $('#auth').removeClass('active');
        $('#register').removeClass('active');
        $('#search').removeClass('active');
        $('#stocks').removeClass('active');
        $('#reservations').addClass('active');
        $('#profile').removeClass('active');
        $.ajax({
            url: './rest.php?action=fetch_my_reservation_info',
            dataType: 'json',
            success: function( resp ) {
                if (not_error(resp)) {
                    document.getElementById('finded_reservation_info').innerHTML = resp.result;
                }
            },
            error: page_error
        });
    } else if (resp.page_active == 'reservation') {
        $('#main').removeClass('active');
        $('#lk').removeClass('active');
        $('#auth').removeClass('active');
        $('#register').removeClass('active');
        $('#search').addClass('active');
        $('#stocks').removeClass('active');
        $('#reservations').removeClass('active');
        $('#profile').removeClass('active');
        $.ajax({
            url: './rest.php?action=fetch_price_info',
            dataType: 'json',
            success: function( resp ) {
                if (not_error(resp)) {
                    document.getElementById('price').innerHTML = resp.price;
                    document.getElementById('price_stock').innerHTML = resp.percent;
                }
            },
            error: page_error
        });
    } else if (resp.page_active == 'profile_page') {
        $('#main').removeClass('active');
        $('#lk').removeClass('active');
        $('#auth').removeClass('active');
        $('#register').removeClass('active');
        $('#search').removeClass('active');
        $('#stocks').removeClass('active');
        $('#reservations').removeClass('active');
        $('#profile').addClass('active');
        $.ajax({
            url: './rest.php?action=fetch_my_info',
            dataType: 'json',
            success: function( resp ) {
                if (not_error(resp)) {
                    document.getElementById('profile_fio').innerHTML = resp.lastname + " " + resp.firstname + " " + resp.surname;

                    document.getElementById('profile_mail').innerHTML = resp.mail;
                    document.getElementById('profile_mail').href = "mailto:" + resp.mail;

                    document.getElementById('profile_phone').innerHTML = resp.phone;
                    document.getElementById('profile_phone').href = "tel:" + resp.phone;

                    document.getElementById('profile_bdate').innerHTML = "Дата рождения: <b>" + resp.bdate + "</b>";

                    document.getElementById('profile_passport').innerHTML = "Паспорт: <b>" + resp.passport + "</b>";

                    document.getElementById('profile_reserved').innerHTML = resp.total_reserved;
                }
            },
            error: page_error
        });
    }
}

function update_nav_user(resp) {
    if (resp.has_auth) {
        document.getElementById('auth_view').style.display = 'none'
        document.getElementById('register_view').style.display = 'none'

        document.getElementById('profile_view').style.display = 'block'
        document.getElementById('logout_view').style.display = 'block'

        document.getElementById('profile').innerHTML = resp.full_name;
    } else {
        document.getElementById('auth_view').style.display = 'block'
        document.getElementById('register_view').style.display = 'block'

        document.getElementById('profile_view').style.display = 'none'
        document.getElementById('logout_view').style.display = 'none'
    }
}

function go_to_reservation(route_id) {
    let formData = new FormData();
    formData.append("reservation_route_id", route_id.toString());
    $.ajax({
        url: './rest.php?action=go_reservation',
        data: formData,
        type: 'POST',
        dataType: 'json',
        cache: false,
        processData: false,
        contentType: false,
        success: page_work,
        error: page_error
    });
}

function go_to_unreservation(route_id) {
    let formData = new FormData();
    formData.append("reservation_route_id", route_id.toString());
    $.ajax({
        url: './rest.php?action=go_unreservation',
        data: formData,
        type: 'POST',
        dataType: 'json',
        cache: false,
        processData: false,
        contentType: false,
        success: page_work,
        error: page_error
    });
}

function go_to_reservation_info(route_id) {
    let formData = new FormData();
    formData.append("reservation_route_id", route_id.toString());
    $.ajax({
        url: './rest.php?action=go_reservation_info',
        data: formData,
        type: 'POST',
        dataType: 'json',
        cache: false,
        processData: false,
        contentType: false,
        success: page_work,
        error: page_error
    });
}

function use_stock(route_id) {
    let formData = new FormData();
    formData.append("use_stock_id", route_id.toString());
    $.ajax({
        url: './rest.php?action=use_stock',
        data: formData,
        type: 'POST',
        dataType: 'json',
        cache: false,
        processData: false,
        contentType: false,
        success: page_work,
        error: page_error
    });
}

function remove_hooks() {
    $('#auth').unbind('click');
    $('#register').unbind('click');
    $('#auth_sub').unbind('click');
    $('#register_sub').unbind('click');
    $('#logout').unbind('click');
    $('#main_sub').unbind('click');
    $('#main').unbind('click');
    $('#search').unbind('click');
    $('#register_form').unbind('submit');
    $('#auth_form').unbind('submit');
    $('#search_route_form').unbind('submit');
    $('#process_reserved_sub').unbind('click');
    $('#my_info').unbind('click');
    $('#add_passanger').unbind('submit');
    $('#remove_passanger').unbind('click');
    $('#profile').unbind('click');
    $('#stocks').unbind('click');
    $('#reservations').unbind('click');
    $('#remove_account').unbind('click');
    $('#my_reservations').unbind('click');
    $('#update_profile_info_form').unbind('submit');
}

function create_hooks() {
    $('#auth').on("click", function() {
        $.ajax({
            url: './rest.php?action=fetch_auth_page',
            dataType: 'json',
            success: page_work,
            error: page_error
        });
    });

    $('#register').on("click", function() {
        $.ajax({
            url: './rest.php?action=fetch_register_page',
            dataType: 'json',
            success: page_work,
            error: page_error
        });
    });

    $('#auth_sub').on("click", function() {
        $.ajax({
            url: './rest.php?action=fetch_auth_page',
            dataType: 'json',
            success: page_work,
            error: page_error
        });
    });

    $('#register_sub').on("click", function() {
        $.ajax({
            url: './rest.php?action=fetch_register_page',
            dataType: 'json',
            success: page_work,
            error: page_error
        });
    });

    $('#logout').on("click", function() {
        $.ajax({
            url: './rest.php?action=unregister',
            dataType: 'json',
            success: function( resp ) {
                if (page_work(resp)) {
                    update_nav_user(resp);
                    alert_succ("Выход выполнен!");
                }
            },
            error: page_error
        });
    });

    $('#remove_account').on("click", function() {
        $.ajax({
            url: './rest.php?action=remove_account',
            dataType: 'json',
            success: function( resp ) {
                if (page_work(resp)) {
                    update_nav_user(resp);
                    alert_succ("Аккаунт удалён!");
                }
            },
            error: page_error
        });
    });

    $('#main_sub').on("click", function() {
        $.ajax({
            url: './rest.php?action=fetch_main_page',
            dataType: 'json',
            success: page_work,
            error: page_error
        });
    });

    $('#main').on("click", function() {
        $.ajax({
            url: './rest.php?action=fetch_main_page',
            dataType: 'json',
            success: page_work,
            error: page_error
        });
    });

    $('#search').on("click", function() {
        $.ajax({
            url: './rest.php?action=fetch_search_page',
            dataType: 'json',
            success: page_work,
            error: page_error
        });
    });

    $('#profile').on("click", function() {
        $.ajax({
            url: './rest.php?action=fetch_profile_page',
            dataType: 'json',
            success: page_work,
            error: page_error
        });
    });

    $('#stocks').on("click", function() {
        $.ajax({
            url: './rest.php?action=fetch_stocks_page',
            dataType: 'json',
            success: page_work,
            error: page_error
        });
    });

    $('#reservations').on("click", function() {
        $.ajax({
            url: './rest.php?action=fetch_reservations_page',
            dataType: 'json',
            success: page_work,
            error: page_error
        });
    });

    $('#my_reservations').on("click", function() {
        $.ajax({
            url: './rest.php?action=fetch_reservations_page',
            dataType: 'json',
            success: page_work,
            error: page_error
        });
    });

    $('#process_reserved_sub').on("click", function() {
        let rowData = {};
        let s = 0;
        let array_v = new Array("check", "lastname","firstname","surname","bdate","passport");
        let array_s = [];
        $('#passengers tbody tr td').each(function() {
            if (s != 0) {
                rowData[array_v[s]] = $(this).text();
            }
            s++;
            if (s >= array_v.length) {
                array_s.push(rowData);
                s = 0;
            }
        });

        $.ajax({
            url: './rest.php?action=make_reservation',
            data: JSON.stringify(array_s),
            type: 'POST',
            dataType: 'json',
            contentType: 'application/json;charset=UTF-8',
            success: function (resp) {
                if (page_work(resp)) {
                    alert_succ("Успешно забронировано");
                }
            },
            error: page_error
        });
    });

    $('#register_form').submit(function(e) {
        e.preventDefault();
        let formData = new FormData($('#register_form')[0]);
        $.ajax({
            url: './rest.php?action=register',
            data: formData,
            type: 'POST',
            dataType: 'json',
            cache : false,
            processData: false,
            contentType: false,
            success: function(resp) {
                if (page_work(resp)) {
                    update_nav_user(resp);
                    alert_succ("Зарегистрирован!");
                }
            },
            error: page_error
        });
    });

    $('#auth_form').submit(function(e) {
        e.preventDefault();
        let formData = new FormData($('#auth_form')[0]);
        $.ajax({
            url: './rest.php?action=auth',
            data: formData,
            type: 'POST',
            dataType: 'json',
            cache : false,
            processData: false,
            contentType: false,
            success: function(resp) {
                if (page_work(resp)) {
                    update_nav_user(resp);
                    alert_succ("Успешный вход!");
                }
            },
            error: page_error
        });
    });

    $('#search_route_form').submit(function(e) {
        e.preventDefault();
        let formData = new FormData($('#search_route_form')[0]);
        $.ajax({
            url: './rest.php?action=search_route',
            data: formData,
            type: 'POST',
            dataType: 'json',
            cache : false,
            processData: false,
            contentType: false,
            success: page_work,
            error: page_error
        });
    });

    $('#update_profile_info_form').submit(function(e) {
        e.preventDefault();
        let formData = new FormData($('#update_profile_info_form')[0]);
        $.ajax({
            url: './rest.php?action=update_password',
            data: formData,
            type: 'POST',
            dataType: 'json',
            cache : false,
            processData: false,
            contentType: false,
            success: function(resp) {
                if (page_work(resp)) {
                    update_nav_user(resp);
                    alert_succ("Пароль обновлён!");
                }
            },
            error: page_error
        });
    });

    $("#my_info").click(function() {
        $.ajax({
            url: './rest.php?action=fetch_my_info',
            dataType: 'json',
            success: function( resp ) {
                if (not_error(resp)) {
                    let markup = "<tr><td><input class=\"form-check-input\" type='checkbox' name='record'></td><td>" + resp.lastname + "</td><td>" + resp.firstname + "</td><td>" + resp.surname + "</td><td>" + resp.bdate + "</td><td>" + resp.passport + "</td></tr>";
                    $("#passengers").append(markup);
                    $("#add_passanger")[0].reset();
                    let pp = 0;
                    $("#passengers").find('input[name="record"]').each(function() {
                        pp += 1;
                    });
                    $('#price_result').html((Number($("#price").text()) * pp * (Number($("#price_stock").text())) / 100).toFixed(2) + "₽");
                }
            },
            error: page_error
        });
    });

    $("#add_passanger").submit(function(e) {
        e.preventDefault();
        let formData = new FormData($('#add_passanger')[0]);
        let markup = "<tr><td><input class=\"form-check-input\" type='checkbox' name='record'></td><td>" + formData.get("lastname") + "</td><td>" + formData.get("firstname") + "</td><td>" + formData.get("surname") + "</td><td>" + formData.get("bdate") + "</td><td>" + formData.get("passport") + "</td></tr>";
        $("#passengers").append(markup);
        $("#add_passanger")[0].reset();

        let pp = 0;
        $("#passengers").find('input[name="record"]').each(function(){
            pp += 1;
        });
        $('#price_result').html((Number($("#price").text()) * pp * (Number($("#price_stock").text())) / 100).toFixed(2) + "₽");
    });

    $("#remove_passanger").click(function() {
        $("#passengers").find('input[name="record"]').each(function(){
            if($(this).is(":checked")){
                $(this).parents("tr").remove();
            }
        });
        let pp = 0;
        $("#passengers").find('input[name="record"]').each(function(){
            pp += 1;
        });
        $('#price_result').html((Number($("#price").text()) * pp * (Number($("#price_stock").text())) / 100).toFixed(2) + "₽");
    });
}

function alert_error(errorMessage) {
    const html = `<div id="error-alert" class="alert alert-danger alert-dismissible fade show" role="alert" data-auto-dismiss="2000" style="position: fixed; bottom: 20px; left: 0; right: 0; max-width: 360px; margin: 0 auto;"><p><strong>` + errorMessage + `</strong></p><div class="text-center"><button type="button" class="btn btn-secondary" data-bs-dismiss="alert">ОК</button></div></div>`
    document.body.insertAdjacentHTML('beforeend', html);

    $('.alert[data-auto-dismiss]').each(function (index, element) {
        let $element = $(element),
            timeout  = $element.data('auto-dismiss') || 5000;

        setTimeout(function () {
            $element.alert('close');
        }, timeout);
    });
}

function alert_succ(succMessage) {
    const html = `<div id="succ-alert" class="alert alert-success alert-dismissible fade show" role="alert" data-auto-dismiss="2000" style="position: fixed; bottom: 20px; left: 0; right: 0; max-width: 360px; margin: 0 auto;"><p><strong>` + succMessage + `</strong></p><div class="text-center"><button type="button" class="btn btn-secondary" data-bs-dismiss="alert">ОК</button></div></div>`
    document.body.insertAdjacentHTML('beforeend', html);

    $('.alert[data-auto-dismiss]').each(function (index, element) {
        let $element = $(element),
            timeout  = $element.data('auto-dismiss') || 5000;

        setTimeout(function () {
            $element.alert('close');
        }, timeout);
    });
}

window.onbeforeunload = function() { return "Ваши данные могут не сохраниться."; };

$(document).ready(function () {
    $.ajax({
        url: './rest.php?action=fetch_last_page',
        dataType: 'json',
        success: function ( resp ) {
            if (page_work(resp)) {
                update_nav_user(resp);
            } else {
                create_hooks();
            }
        },
        error: page_error
    });
});
