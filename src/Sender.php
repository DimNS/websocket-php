<?php
/**
 * Отправка сообщений в WebSocket
 *
 * @version 03.04.2019
 * @author  Дмитрий Щербаков <atomcms@ya.ru>
 */

namespace WebSocketPHP;

/**
 * Class Sender
 *
 * @package WebSocketPHP
 */
class Sender
{
    /**
     * @var integer
     */
    protected $tcp_port;

    /**
     * Конструктор
     *
     * @param integer $tcp_port TCP порт
     *
     * @version 01.03.2019
     * @author  Дмитрий Щербаков <atomcms@ya.ru>
     */
    public function __construct($tcp_port)
    {
        $this->tcp_port = $tcp_port;
    }

    /**
     * Отправка сообщения
     *
     * @param integer|array $user_id ИД пользователя (0 для отправки всем пользователям) или массив ИД пользователей
     * @param string        $type    Тип сообщения
     * @param array         $data    Массив данных для сообщения
     *
     * @return integer|boolean
     *
     * @version 03.04.2019
     * @author  Дмитрий Щербаков <atomcms@ya.ru>
     */
    public function send($user_id, $type, $data = [])
    {
        // Соединяемся с локальным tcp-сервером
        $instance = stream_socket_client('tcp://127.0.0.1:' . $this->tcp_port);

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
}
