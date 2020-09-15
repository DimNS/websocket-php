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
     * @param integer $tcp_port TCP порт
     *
     * @author  Дмитрий Щербаков <atomcms@ya.ru>
     *
     * @version 15.09.2020
     */
    public function __construct($tcp_port)
    {
        $this->tcp_port = $tcp_port;

        $this->log = new Logger('WebSocketPHP');
        $this->log->pushHandler(new StreamHandler(__DIR__ . '/../error.log'));
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
            // Соединяемся с локальным tcp-сервером
            $instance = stream_socket_client('tcp://127.0.0.1:' . $this->tcp_port, $errno, $errstr);

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
