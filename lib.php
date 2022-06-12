<?php
error_reporting(0);

$mysqli = mysqli_init();
$mysqli->real_connect("host", "user", "pass", "db");
$mysqli->set_charset('utf8');
if ($mysqli->connect_errno) {
    echo "Не удалось подключиться к MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    die;
}

/**
 * @return string
 */
function getSmsCode()
{
    return '1234';
}

/**
 * @return bool
 */
function updateDoc()
{
    return true;
}

/**
 * @return bool
 */
function sendSmsCode()
{
    return true;
}

/**
 * @param $status
 * @param $message
 */
function returnResult($status, $message)
{
    header('Content-Type: application/json');
    $arResult = array("status" => $status, "message" => $message);
    echo json_encode($arResult);
    die;
}

/**
 * @param $input
 * @return array
 */
function getSmsTime($input)
{
    $timestamp = strtotime($input['sms_date']);

    // если попыток много, то интервал увеличивается
    $time = 60;
    if ($input['sms_count'] > 3) $time = 120;
    if ($input['sms_count'] > 5) $time = 240;
    if ($input['sms_count'] > 9) $time = 360;

    // если уже отправляли, но не прошло $time секунд
    $smstime = 0;
    if (isset($timestamp)) {
        $smstime = time() - $timestamp;
        $smstime = $time - ceil($smstime / 10) * 10;
        if ($smstime > $time || $smstime < 0) $smstime = 0;
    }

    return array($smstime, $time);
}