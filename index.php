<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/sign/lib.php");

// берем данные о пользователе
$id = 2423;

// берем данные о документе
$res = $mysqli->query("SELECT * FROM resume_sign WHERE id ='" . $id . "';");
$row = $res->fetch_assoc();

$fileName = $row['filename'];
$fileNote = $row['note'];
$fileSize = $row['filesize'];
$fileHash = $row['hash'];
$fileDate = $row['docdate'];
$smsPhone = $row['mobile'];

// можно отправлять SMS?
list($smsSec, $smsWait) = getSmsTime($row);
?>

<!DOCTYPE HTML>
<html lang="ru">
<head>
    <!-- Compiled and minified CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
    <!-- Compiled and minified JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"
            integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0="
            crossorigin="anonymous"></script>
    <meta charset="utf-8">
    <title>Тестовая страница</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <meta name="format-detection" content="telephone=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
</head>
<body>

<div class="container">

    <h3>Подписание документа</h3>

    <form name="postform" id='postform' method="post">

        <p>
            Мы отправим SMS с кодом на номер телефона: <b><?= $smsPhone ?></b><br>
            Проверьте входящие сообщения на телефоне
        </p>
        <p>
            <b>Документ: <a href='/docs/<?= $id ?>'><?= $fileName ?></a></b> (<?= $fileSize ?>)<br>
            <i><?= $fileNote ?></i>
        </p>
        <p style="color: gray">Хэш код (MD5 электронный ключ документа): <i><?= $fileHash ?></i><br>
            Загружен: <?= $fileDate ?>
        </p>
        <div>
            <button id="send_btn"
                    class="btn waves-effect waves-light">Отправить SMS
            </button>
            <span id="send_sms" style='padding-left: 20px'>
            </span>
        </div>
        <br><br>
        <div>Код SMS: <input type="text"
                             name="sms_code" id="sms_code"
                             style="width:100px;"
                             value="1234"
                             class="static-form-input"
            >
            <button id="check_btn"
                    class="btn waves-effect waves-light">Подтвердить
            </button>


            <span id="check_load" style='display:none; padding-left: 20px'> Ждите...</span>
            <span id="check_txt" style='color:red; padding-left: 20px'></span>
        </div>

    </form>

</div>

<script>
    $(function () {

        var $check_btn = $('#check_btn'),
            $check_load = $('#check_load'),
            $check_txt = $('#check_txt');

        $check_btn.click(function () {

            $check_load.show();
            $check_txt.html('');
            $check_btn.prop('disabled', 'disabled');

            $.ajax({
                type: 'POST',
                url: '/sign/$ajax.php',
                dataType: 'json',
                data: {id: '<?= $id ?>', action: 'check', code: $("#sms_code").val()}
            }).done(function (response) {
                if (response.status == 'ok') {
                    alert("Документ подписан!");
                } else {
                    $check_btn.prop('disabled', '');
                    $check_load.hide();
                    $check_txt.html(response.message);
                }
                $check_load.hide();
            }).fail(function (response) {
                $check_load.hide();
                $check_txt.html('Техническая ошибка1. Код: ' + response.status + ' (' + response.statusText + ')');
            });
            return false;
        });

        /**
         * SMS таймер
         */
        $('#send_btn').smstimer({
            docid: "<?= $id ?>",
            smssec: "<?= $smsSec ?>",
            smswait: "<?= $smsWait ?>",
        });
    });

    /**
     * Плагин таймера SMS
     *
     * @author    Dmitry Dementiev
     * @copyright dementiev.net
     */
    ;$(function ($) {
        /**
         *
         * @param userOptions
         */
        $.fn.smstimer = function (userOptions) {

            var options = $.extend(true, {
                    smssec: 0,
                    smswait: 60,
                    docid: 0,
                    interval: 5,
                    url: '/sign/$ajax.php',
                    onSend: function () {
                        return true;
                    },
                    statusMsg: $('#send_sms'),
                    waitText: 'Новый код можно будет запросить через XXX сек.',
                    getText: 'Отправить SMS повторно',
                    sendText: 'Идет отправка, подождите...'
                }, userOptions),
                widget = this,
                smssec = options.smssec;

            /**
             * возможна отправка?
             */
            if (smssec > 0) {
                widget.prop('disabled', 'disabled');
                options.statusMsg.html(options.waitText.replace('XXX', smssec));
                startCountSms();
            }

            /**
             * счетчик
             */
            function startCountSms() {
                var timer;
                timer = setInterval(function () {
                    smssec = smssec - options.interval;
                    options.statusMsg.html(options.waitText.replace('XXX', smssec));
                    if (smssec <= 0) {
                        clearInterval(timer);
                        widget.html(options.getText).prop('disabled', '');
                        options.statusMsg.html("");
                    }
                }, options.interval * 1000);
            }

            /**
             * повторный запрос SMS
             */
            widget.on('click', function (e) {
                widget.prop('disabled', 'disabled');
                e.preventDefault();
                if (smssec == 0) {
                    options.statusMsg.html(options.waitText.replace('XXX', smssec));
                    options.statusMsg.html(options.sendText);
                    $.ajax({
                        type: 'POST',
                        url: options.url,
                        dataType: 'json',
                        data: {id: options.docid, action: 'send'}
                    }).done(function (response) {
                        if (response.status == 'ok') {
                            smssec = options.smswait;
                            options.statusMsg.html(options.waitText.replace('XXX', smssec));
                            startCountSms();
                        } else {
                            options.statusMsg.html(response.message);
                        }
                        if (typeof (options.onSend) == 'function') options.onSend(response);
                    }).fail(function (response) {
                        options.statusMsg.html('Техническая ошибка. Код: ' + response.status + ' (' + response.statusText + ')');
                        widget.prop('disabled', '');
                    });
                }
                return false;
            });
        };
    }(jQuery));
</script>

</body>
</html>