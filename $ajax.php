<?php
require_once($_SERVER["DOCUMENT_ROOT"] . "/sign/lib.php");

// входные данные
$id = $_POST['id'] + 0;
$inCode = $_POST['code'];
if (!is_numeric($id)) {
    returnResult("error", "Ошибка передачи данных");
}

// берем данные о документе
$res = $mysqli->query("SELECT * FROM resume_sign WHERE id ='" . $id . "';");
$row = $res->fetch_assoc();
if (!$row['id']) {
    returnResult("error", "Ошибка передачи данных. Неверный ID. id=" . $id);
}

if (count($_POST) < 1) unset($_POST);
/**
 * проверка кода
 */
if (isset($_POST) && $_POST['action'] == 'check') {

    if (!intval($inCode) || is_null($inCode)) {
        returnResult("error", "Введите код!");
    }

    // проверяем код
    if ($inCode != $row['sms_code']) {

        $attempts = $row['sms_att_count'] - 1;

        // исчерпали попытки для ввода
        if ($attempts == 0) {
            returnResult("error", "Число попыток закончилось! Отправьте SMS повторно.");
        } else {

            $mysqli->query("UPDATE resume_sign SET sms_att_count=sms_att_count-1, sms_date=NOW() WHERE id='" . $id . "';");
            if ($attempts < 0) $attempts = 0;

            returnResult("error", "Введен неверный код. Осталось попыток: " . $attempts);
        }
    }

    // подписано
    $return = updateDoc();
    if (!$return) {
        returnResult("error", "Ошибка подписания документа");
    }

    returnResult("ok", "");

    /**
     * отправка
     */
} else if (isset($_POST) && $_POST['action'] == 'send') {

    // генерируем код SMS
    $smsCode = getSmsCode();

    // можно отправлять SMS?
    list($smsSec, $smsWait) = getSmsTime($id);

    // еще рано
    if ($smsSec != 0) {
        returnResult("error", "Новый код можно будет запросить через несколько сек.!");
    }

    // отправляем SMS
    $return = sendSmsCode();
    if (!$return) {
        returnResult("error", "SMS не отправлено. Попробуйте позже!");
    }

    // посчитали попытки
    $mysqli->query("UPDATE resume_sign SET sms_count=sms_count+1, sms_code='" . $smsCode . "', sms_date=NOW(), sms_att_count=5 WHERE id='" . $id . "';");

    returnResult("ok", "");
}
