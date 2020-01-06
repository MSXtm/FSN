<?php
if ($_SERVER['REQUEST_METHOD'] != 'POST') die('REQUEST NOT ALLOWED');
// Load bot config
require('config.php');
// decode json to array
$json = json_decode(file_get_contents('php://input'), true);
// if not valid json
if (!is_array($json))
  throw new Exception('Invalid JSON');
$db = new SQLite3($config['db']);
$post = $json['message'];
if ($post['chat']['type'] == 'private') {
  if (isset($post['text']) && substr($post['text'], 0, 1) == '/') {
    // explode text
$ex = explode(' ', $post['text']);
$chat_id = $post['chat']['id'];
$user = file_get_contents('user.txt');
$members = explode("\n",$user);
if (!in_array($chat_id,$members)){
$add_user = file_get_contents('user.txt');
$add_user .= $chat_id."\n";
file_put_contents('user.txt',$add_user);
}
    switch ($ex[0]) {
      case '/start':
      case '/help':
      default:
        if (empty($ex[1])) {
          $req = array(
            'action' => 'sendMessage',
            'data' => array(
              'text' => "📦 همین امروز فایلاتو به اشتراک بزار!\n\n > /start <b>[FILE ID]</b> : فایلتون رو با ایدی [FILE ID] دریافت کنید.\n\n > /list : فایل های به اشتراک گذاشته خودتون رو دریافت کنید.\n\n > /delete <b>[FILE ID]</b> : فایلی با ایدی [FILE ID] رو پاک کنید.\n\n > /about : اطلاعات بیشتری درمورد ما کسب کنید.\n\nیه فایل رو برام ارسال کن تا مراحل بارگذاری شروع بشه! دقت کنین که حتما بصورت فایل باشه، نه عکس یا فیلم.",
              'parse_mode' => 'html'
              )
            );
        } else {
          $query = $db->querySingle('SELECT `file_id` FROM `rfiles_bot` WHERE `id` = "'.SQLite3::escapeString($ex[1]).'" LIMIT 1');
          if (!empty($query)) {
            $req = array(
              'action' => 'sendDocument',
              'data' => array('document' => $query)
              );
          } else {
            $req = array(
              'action' => 'sendMessage',
              'data' => array('text' => "متاسفانه نتونستیم فایلی با ایدی مورد نظر شما پیدا کنیم.\nحدس میزنیم که ایدی اشتباه باشه.\nمیتونین با یه ایدی دیگه امتحان کنین...")
              );
          }
        }
        break;
      // list command
      case '/list':
        $count = $db->querySingle('SELECT COUNT(`id`) FROM `rfiles_bot` WHERE `uploader` = "'.$post['from']['id'].'"');
        if ($count == 0) {
          $req['data']['text'] = 'پوزش! شما فایلی به اشتراک نزاشتید.';
        } else {
          $files = $db->query('SELECT `id`,`file_name`,`file_size` FROM `rfiles_bot` WHERE `uploader` = "'.$post['from']['id'].'"');
          $req['data']['text'] = 'فایل های شما در شبکه:'."\n\n";
          $i = 1;
          while ($file = $files->fetchArray()) {
            $req['data']['text'] .= $i.'. <a href="http://t.me/'.$config['bot_username'].'?start='.$file['id'].'">'.htmlspecialchars($file['file_name']).'</a> ('.humanFileSize($file['file_size']).')'."\n".'شناسه: <code>'.$file['id']."</code>\n\n";
            $i++;
          }
        }
        $req['action'] = 'sendMessage';
        $req['data']['parse_mode'] = 'html';
        break;
      // delete command
      case '/delete':
        if (empty($ex[1])) {
          $req['data']['text'] = "/delete <b>FILE ID</b>\nاگه میخوای، میتونی فایل هاتو با دستور /list نمایش بدی.";
        } else {
          $count = $db->querySingle('SELECT COUNT(`id`) FROM `rfiles_bot` WHERE `uploader` = "'.$post['from']['id'].'" AND `id` = "'.SQLite3::escapeString($ex[1]).'"');
          if ($count == 0) {
            $req['data']['text'] = 'پوزش، شما فایلی با این ایدی ندارید.';
          } else {
            $db->exec('DELETE FROM `rfiles_bot` WHERE `id` = "'.SQLite3::escapeString($ex[1]).'"');
            $req['data']['text'] = 'فایل با موفقیت از شبکه پاک شد.';
          }
        }
        $req['action'] = 'sendMessage';
        $req['data']['parse_mode'] = 'html';
      break;
      case '/about':
        $req = array(
          'action' => 'sendMessage',
          'data' => array(
            'text' => "این یک ربات هست که شما رو به شبکه اشتراک فایل FSN وصل میکنه. میتونید همین امروز فایل خودتون رو به اشتراک بزارین...\n امیدوارم از کارمون لذت برده باشین.\n\n⚡️ @MSXtm"
            )
          );
      break;
    }
  } elseif (empty($post['document'])) {
    $req = array(
      'action' => 'sendMessage',
      'data' => array('text' => 'لطفا فایل خودتون رو برای اشتراک بفرستید، مطمئن باشید که بصورت فایل باشد، نه فیلم یا عکس.')
      );
  } else {
    $file_id = uniqid();
    if ( $db->exec('INSERT INTO `rfiles_bot` VALUES (
      "'.$file_id.'",
      "'.$post['document']['file_id'].'",
      "'.SQLite3::escapeString($post['document']['file_name']).'",
      "'.$post['from']['id'].'",
      "'.$post['document']['file_size'].'"
      )') ) {

        $req = array(
          'action' => 'sendMessage',
          'data' => array('text' => 'فایل شما با موفقیت بارگذاری شد. با اشتراک آدرس، به بقیه فایلتون رو پخش کنید.'."\n\nآدرس:\n".'https://t.me/'.$config['bot_username'].'?start='.$file_id)
          );
    } else {
      $req = array(
        'action' => 'sendMessage',
        'data' => array('text' => 'خطا !')
        );
    }
  }
  $req['data']['chat_id'] = $post['chat']['id'];
  $req['data']['reply_to_message_id'] = $post['message_id'];
  sendRequest($req['action'], $req['data']);
}

function sendRequest($action = 'sendMessage', $data = array()) {
  // get config
  global $config;
  // init curl
  $ch = curl_init();
  $config = array(
    CURLOPT_URL => 'https://api.telegram.org/bot'.$config['token'].'/'.$action,
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS => $data
  );
  curl_setopt_array($ch, $config);
  $result = curl_exec($ch);
  curl_close($ch);
  // return and decode json
  return (!empty($result) ? json_decode($result, true) : false);
}

function humanFileSize($size) {
  $units = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
  $power = $size > 0 ? floor(log($size, 1024)) : 0;
  return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
}
