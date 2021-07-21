<?php

require_once 'vendor/autoload.php';
require_once 'functions/bd_functions.php';
require_once 'db_t.php';

use Telegram\Bot\Api;

$object = new Api('*Token from @BotFather*');
$bd = new dataBaseClass;
$curl = new Curl\Curl;
$conn = connect();
function kz_to_ru($text, $source, $target)
{
    global $curl;

    $data = array(
        "sourceLanguageCode" => $source,
        "targetLanguageCode" => $target,
        "texts" => [$text],
        "folderId" => '*FolderId from yandex cloud*'
    );
    $data = json_encode($data);
    $query = 'SELECT token from iam ORDER BY id DESC LIMIT 1';

    $url = 'https://translate.api.cloud.yandex.net/translate/v2/translate';


    $iam = select($query);

    $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
    $curl->setHeader('Content-Type', 'application/json');
    $curl->setHeader('Authorization', 'Bearer *Iam=Token-From-YandexCloud*');
    $curl->post($url, $data);
    $curl->close();

    $resp = json_decode($curl->response,true);
    $textReal = '';
    foreach ($resp['translations'] as &$textsT) {
        $textReal .= $textsT['text'];
    }
    return $textReal;


}
function TranslateFunc($arrParts, $source, $target) {
    $translatedParts=[];
    foreach ($arrParts as $parts) {
        $translatedParts[] = kz_to_ru($parts, $source, $target) . ' ';
    }
    return $translatedParts;
}
$result = $object->getWebhookUpdates();

$text = $result["message"]["text"];
$photo = $result["message"]["photo"];

$chat_id = $result["message"]["chat"]["id"];
$name = $result["message"]["from"]["username"];
$keyboard = [["Рус-Каз","Каз-Рус"],["Каз-Анг","Анг-Каз"],["Анг-Рус","Рус-Анг"],["🔍 обо мне"]
];


if (!$result["message"]) exit();


date_default_timezone_set('GMT+5');


$date = date('d H:i', time());

if (!$text) {
    $text = 'Картинка или аудио';
}


$queryMetric = 'INSERT INTO metric SET chatid ="' . $chat_id . '", text ="' . $text . '", FL ="' . $result['message']['chat']['first_name'] . ' ' . $result['message']['chat']['last_name'] . '", timestamp="' . $date . '"';

$bd->execQuery1($queryMetric);

$textArr = explode(' ', trim($text));


$query = 'SELECT chatid FROM users_t WHERE chatid = "' . $chat_id . '"';
$resultselect = $bd->selectQuery($query);
$mode = 'kzru';

if (count($resultselect) === 0) {
    $object->sendMessage(['chat_id' => $chat_id, 'text' => 'Привет, я - бот. Перевожу текст между казахским, русским, английским языками. Нажмите на кнопку нужного направления перевода (если не видите кнопки, введите /start). Также, я перевожу картинки и фотографии!']);
    $queryInsert = "INSERT INTO users_t SET chatid='" . $chat_id . "', username='" . $result['message']['chat']['username'] . "', FL='" . $result['message']['chat']['first_name'] . ' ' . $result['message']['chat']['last_name'] . "', mode='" . $mode . "'" ;
    $bd->execQuery1($queryInsert);
}

$queryswitch = 'SELECT * FROM users_t WHERE chatid="' . $chat_id . '"';
$mode = $bd->selectQuery($queryswitch);

if ($photo) {
    $arrays = [];
    $query = 'SELECT token from iam ORDER BY id DESC LIMIT 1';
    $photos = [];
    $iam = $bd->selectQuery($query);
    $file = $object->getFile(['file_id' => $photo[2]['file_id']]);
    $curl->setOpt(CURLOPT_RETURNTRANSFER, TRUE);
    $curl->setOpt(CURLOPT_SSL_VERIFYPEER, FALSE);
    $curl->get('https://api.telegram.org/file/bot*and another token from @BotFather*/' . $file['file_path']);
    $photos[] = base64_encode($curl->response);
    $datas = [];
    foreach ($photos as $base64) {
        $data = array(
            "folderId" => 'b1g0evkooq42v49vmr5h',
            'analyze_specs' => [
                0 => [
                    'content' =>
                        $base64,
                    'features' => [
                        0 => [
                            'type' =>
                                'TEXT_DETECTION',
                            'text_detection_config' =>
                                [
                                    'language_codes' =>
                                        [
                                            0 =>
                                                '*'
                                        ]
                                ]
                        ]
                    ]
                ]
            ]
        );
        $datas[] = json_encode($data);
        $url = 'https://vision.api.cloud.yandex.net/vision/v1/batchAnalyze';
        $curl->setOpt(CURLOPT_RETURNTRANSFER, TRUE);
        $curl->setOpt(CURLOPT_SSL_VERIFYPEER, FALSE);
        $curl->setHeader('Content-type', 'application/json');
        $curl->setHeader('Authorization', 'Bearer *And anooother iam token from yandex cloud*');
        foreach ($datas as &$finalData) {
            $curl->post($url,
                $finalData

            );
            $arrays[] = json_decode($curl->response, true);
        }

    }


    $texts = '';
    foreach ($arrays as &$array) {
        foreach ($array['results'][0]['results'][0]['textDetection']['pages'][0]['blocks'] as &$words) {
            foreach($words['lines'] as &$lines) {
                foreach($lines['words'] as &$text) {
                    $texts .= $text['text'] . ' ';
                }
            }

        }
    }


    if(!$texts) {
        $object->sendMessage(['chat_id' => $chat_id,'text'=> 'Sorry image has dont have any text :(']);
        exit();
    }

    switch ($mode[0]['mode']) {
        case 'kzru':
            $trans = kz_to_ru($texts, 'kk', 'ru');
            break;
        case 'kzen':
            $trans = kz_to_ru($texts, 'kk', 'en');
            break;
        case 'rukz':
            $trans = kz_to_ru($texts, 'ru', 'kk');
            break;
        case 'ruen':
            $trans = kz_to_ru($texts, 'ru', 'en');
            break;
        case 'enkz':
            $trans = kz_to_ru($texts, 'en', 'kk');
            break;
        case 'enru':
            $trans = kz_to_ru($texts, 'en', 'ru');
            break;
    }
    $object->sendMessage(['chat_id'=>$chat_id,'text'=> '*Translated_Text*: ' . $trans . PHP_EOL. '--------------'. PHP_EOL . '*Original_text*: ' . $texts, 'parse_mode' => 'Markdown']);

    exit();
}


switch (mb_strtolower($text, 'UTF-8')) {

    // update user's mode
    case '/start':
    {
        $reply_markup = $object->replyKeyboardMarkup([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ]);
        $object->sendMessage([
            'chat_id'=>$chat_id,
            'text'=>'Tab to any button to set translate mode:',
            'reply_markup' => $reply_markup
        ]);
        exit();
    }
    case '🔍 обо мне':
    {
        $object->sendMessage(['chat_id' => $chat_id, 'text' => 'https://smart-aktau.kz/bot-factory']);
        break;
    }
    case 'рус-каз':
    {
        $object->sendMessage(['chat_id' => $chat_id,'text' => 'You change translate mdoe to: Ru-Kz']);
        $queryrukz = 'UPDATE users_t SET mode="rukz" WHERE chatid="' . $chat_id . '"';
        $bd->execQuery1($queryrukz);
        break;
    }
    case 'анг-каз':
    {
        $object->sendMessage(['chat_id' => $chat_id,'text' => 'Вы изменили режим на: Анг-Каз']);
        $queryenkz = 'UPDATE users_t SET mode="enkz" WHERE chatid="' . $chat_id . '"';
        $bd->execQuery1($queryenkz);
        break;
    }
    case 'рус-анг':
    {
        $object->sendMessage(['chat_id' => $chat_id,'text' => 'Вы изменили режим на: Рус-Анг']);
        $queryruen = 'UPDATE users_t SET mode="ruen" WHERE chatid="' . $chat_id . '"';
        $bd->execQuery1($queryruen);
        break;
    }
    case 'анг-рус':
    {
        $object->sendMessage(['chat_id'=>$chat_id,'text'=> 'Вы изменили режим на: Анг-Рус']);
        $queryenru = 'UPDATE users_t SET mode="enru" WHERE chatid="' . $chat_id . '"';
        $bd->execQuery1($queryenru);
        break;
    }
    case 'каз-рус':
    {
        $object->sendMessage(['chat_id'=>$chat_id,'text'=> 'Вы изменили режим на: Каз-Рус']);
        $querykzru = 'UPDATE users_t SET mode="kzru" WHERE chatid="' . $chat_id . '"';
        $bd->execQuery1($querykzru);
        break;
    }
    case 'каз-анг':
    {
        $object->sendMessage(['chat_id'=>$chat_id,'text'=> 'Вы изменили режим на: Каз-Анг']);
        $querykzen = 'UPDATE users_t SET Mode="kzen" WHERE chatid="' . $chat_id . '"';
        $bd->execQuery1($querykzen);
        break;
    }
}



$commands = ['🔍 обо мне','каз-рус', 'каз-анг', 'рус-каз', 'рус-анг', 'анг-рус', 'анг-каз', '/start'];
if (in_array(mb_strtolower($text, 'UTF-8'), $commands)) exit();


switch ($mode[0]['mode']) {
    case 'kzru':
        $trans = kz_to_ru($text, 'kk', 'ru');
        break;
    case 'kzen':
        $trans = kz_to_ru($text, 'kk', 'en');
        break;
    case 'rukz':
        $trans = kz_to_ru($text, 'ru', 'kk');
        break;
    case 'ruen':
        $trans = kz_to_ru($text, 'ru', 'en');
        break;
    case 'enkz':
        $trans = kz_to_ru($text, 'en', 'kk');
        break;
    case 'enru':
        $trans = kz_to_ru($text, 'en', 'ru');
        break;
}

$object->sendMessage(['chat_id'=>$chat_id,'text'=> $trans]);

similar_text($trans, $text, $perc);
settype($perc, 'integer');
if ($perc > 70) {
    $object->sendMessage(['chat_id'=>$chat_id,'text'=> "⚠️Возможно, у вас не (верно) выбрано направление перевода. Наберите команду /start и прочитайте подробнее."]);
}