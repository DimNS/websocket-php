<?php
/**
 * Отправка сообщений в WebSocket
 *
 * @version 01.03.2019
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
     * @param integer $user_id ИД пользователя (или 0 для отправки всем пользователям)
     * @param string  $type    Тип сообщения
     * @param array   $data    Массив данных для сообщения
     *
     * @return integer|boolean
     *
     * @version 01.03.2019
     * @author  Дмитрий Щербаков <atomcms@ya.ru>
     */
    public function send($user_id, $type, $data = [])
    {
        // Соединяемся с локальным tcp-сервером
        $instance = stream_socket_client('tcp://127.0.0.1:' . $this->tcp_port);

        $data = [
            'uid'     => $user_id,
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
