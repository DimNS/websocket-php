# WebSocketPHP
WebSocket сервер на php (построен на базе Workerman)

## Сервер
1. Установка (рекомендуется устанавливать внутри проекта, чтобы иметь доступ к вашему коду для определения ID пользователя и удобной отправки сообщений клиентам)
  ```shell
  composer require dimns/websocket-php
```
2. Создаём файл `/var/www/ws_server.php` с содержимым:
  ```php
<?php
use WebSocketPHP\Server;

require_once __DIR__ . '/vendor/autoload.php';

// Каталог для логов
$log_folder = __DIR__ . '/logs/';

// PID-файл
$pid_file = __DIR__ . '/websocketphp.pid';

// Websocket порт
$ws_port = 8090;

// TCP порт
$tcp_port = 5060;

/**
 * Функция для определения ИД пользователя (может отсутствовать)
 *
 * @param string $sid
 *
 * @return integer
 *
 * @version 01.03.2019
 * @author  Дмитрий Щербаков <atomcms@ya.ru>
 */
$func_get_user_id = function ($sid) {
    return 0;
};

$websocket = new Server($log_folder, $pid_file, $ws_port, $tcp_port, $func_get_user_id);
$websocket->start();
```
3. Запускаем сервер `/usr/bin/php /var/www/ws_server.php start`

\* Сервер при старте отвязывается от консоли и создаёт форк для создания pid-файла (с помощью него блокируется множественный запуск сервера, а также можно настроить автоматический запуск сервера в случае его падения)

## Клиент
1. Установка (внутри вашего проекта)
  ```shell
npm i dimns-websocket-js
```
2. Подключение
  ```html
<script type="text/javascript" src="dist/websocket.min.js"></script>
```
3. Инициализация
  ```javascript
webSocketPHP.init({
    url            : 'wss://' + window.location.host + '/websocket',
    sid            : '<SESSIONID-OR-USERID>',
    attemptsCount  : 5,
    attemptsTimeout: 10,
    onMessage      : function (message) {
        console.info(message);
    }
});
```
  - **url** (string) *Обязательно* - URL до сервера
  - **sid** (string) *Обязательно* - ИД сессии, чтобы потом сервер определил ИД пользователя самостоятельно (рекомендуется) или сразу ИД пользователя (крайне не рекомендуется, потому что любой сможет получать сообщения ему не предназначенные)
  - **attemptsCount** (string) *Не обязательно, по умолчанию: 5* - Количество попыток подключения к серверу
  - **attemptsTimeout** (string) *Не обязательно, по умолчанию: 10* - Количество секунд между попытками (в секундах),  каждый номер попытки умножается на это число: 1 - 10 сек, 2 - 20 сек и т.д.
  - **onMessage** (string) *Обязательно* - функция, которая получает объект с сообщением от сервера

## Отправка сообщения
```php
use WebSocketPHP\Sender;

// TCP порт
$tcp_port = 5060;

$sender = new Sender($tcp_port);

// Всем пользователям
$user_id = 0;
$type = 'test_all';
$data = [
    'variable 1' => 'value 1',
    'variable 2' => 'value 2',
];
$sender->send($user_id, $type, $data);

// Только одному конкретному пользователю
$user_id = 1;
$type = 'test_1';
$data = [
    'variable 1' => 'value 1',
    'variable 2' => 'value 2',
];
$sender->send($user_id, $type, $data);

// Только одному конкретному пользователю
$user_id = 2;
$type = 'test_2';
$sender->send($user_id, $type);
```
  - **$user_id** (integer) *Обязательно* - ИД пользователя (или 0 для отправки всем пользователям)
  - **$type** (string) *Обязательно* - Тип сообщения
  - **$data** (array) *Не обязательно* - Массив данных сообщения