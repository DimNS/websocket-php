<?php

/**
 * Отправка сообщений в WebSocket
 *
 * @author  Дмитрий Щербаков <atomcms@ya.ru>
 *
 * @version 15.09.2020
 */

namespace WebSocketPHP;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Throwable;

/**
 * @package WebSocketPHP
 */
class Sender
{
    /**
     * @var integer
     */
    protected $tcp_port;

    /**
     * @var Logger
     */
    protected $log;

    /**
     * @param integer $tcp_port   Каталог для логов
     * @param string  $log_folder TCP порт
     *
     * @author  Дмитрий Щербаков <atomcms@ya.ru>
     *
     * @version 15.09.2020
     */
    public function __construct($tcp_port, $log_folder = '')
    {
        $this->tcp_port = $tcp_port;

        if (empty($log_folder)) {
            $this->log = new Logger('WebSocketPHP');
            $this->log->pushHandler(new StreamHandler(__DIR__ . '/../error.log'));
        } else {
            $this->log = Log::create($log_folder);
        }
    }

    /**
     * @param integer|array $user_id ИД пользователя (0 для отправки всем пользователям) или массив ИД пользователей
     * @param string        $type    Тип сообщения
     * @param array         $data    Массив данных для сообщения
     *
     * @return integer|boolean
     *
     * @author  Дмитрий Щербаков <atomcms@ya.ru>
     *
     * @version 15.09.2020
     */
    public function send($user_id, $type, $data = [])
    {
        try {
            // stream_socket_client при ошибках кидает Warning, который не ловится через try...catch
            // поэтому ставим такой костыль чтобы не было варнинга, ведь мы итак ловим ошибку и проверяем через if
            $old_error_reporting = error_reporting(); // Сохраним существующий уровень ошибок
            error_reporting($old_error_reporting & ~E_WARNING); // Отключим уровень E_WARNING
            $instance = stream_socket_client('tcp://127.0.0.1:' . $this->tcp_port, $errno, $errstr); // Соединяемся с локальным tcp-сервером
            error_reporting($old_error_reporting); // Восстановим старый уровень ошибок

            if (!$instance) {
                $this->log->error("$errstr ($errno)");

                return false;
            } else {
                $uids = [];

                if (is_integer($user_id)) {
                    $uids[] = $user_id;
                }

                if (is_array($user_id)) {
                    $uids = $user_id;
                }

                $data = [
                    'uids'    => $uids,
                    'message' => [
                        'type' => $type,
                        'data' => $data,
                    ],
                ];

                // Отправляем сообщение
                $result = fwrite($instance, json_encode($data) . "\n");

                fclose($instance);

                return $result;
            }
        } catch (Throwable $th) {
            $file = $th->getFile();
            $line = $th->getLine();
            $msg = $th->getMessage();

            $this->log->error("$file (line: $line) - $msg", $th->getTrace());

            return false;
        }
    }
}
