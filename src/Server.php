<?php
/**
 * WebSocketPHP
 *
 * @version 03.04.2019
 * @author  Дмитрий Щербаков <atomcms@ya.ru>
 */

namespace WebSocketPHP;

use Exception;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

/**
 * Class Server
 *
 * @package WebSocketPHP
 */
class Server
{
    /**
     * @var Logger
     */
    protected $log;

    /**
     * @var string
     */
    protected $pid_file;

    /**
     * @var integer
     */
    protected $ws_port;

    /**
     * @var integer
     */
    protected $tcp_port;

    /**
     * @var Worker
     */
    protected $ws_worker;

    /**
     * @var Worker
     */
    protected $tcp_worker;

    /**
     * @var callable
     */
    protected $func_get_user_id;

    /**
     * @var array Связка соединений и user_id (у каждого пользователя может быть несколько соединений)
     */
    protected $users_connections = [];

    /**
     * Конструктор
     *
     * @param string        $log_folder       Каталог для логов
     * @param string        $pid_file         PID-файл
     * @param integer       $ws_port          Websocket порт
     * @param integer       $tcp_port         TCP порт
     * @param callable|null $func_get_user_id Функция для определения ИД пользователя
     *
     * @version 01.03.2019
     * @author  Дмитрий Щербаков <atomcms@ya.ru>
     */
    public function __construct($log_folder, $pid_file, $ws_port, $tcp_port, $func_get_user_id = null)
    {
        $log_folder = LogFolder::validate($log_folder);

        $this->log = new Logger('WebSocketPHP');
        $filename = $log_folder . 'websocket-php.log';
        $handler = new RotatingFileHandler($filename);

        $handler->setFilenameFormat('{date}-{filename}', 'Y/m/d');

        $this->log->pushHandler($handler);

        $this->ws_port = $ws_port;
        $this->tcp_port = $tcp_port;
        $this->func_get_user_id = $func_get_user_id;
        $this->pid_file = $pid_file;
    }

    /**
     * Запуск сервера
     *
     * @version 01.03.2019
     * @author  Дмитрий Щербаков <atomcms@ya.ru>
     */
    public function start()
    {
        if (
            function_exists('pcntl_fork')
            &&
            function_exists('posix_getpid')
            &&
            function_exists('posix_kill')
        ) {
            // Проверяем запущен ли процесс указанный в pid файле
            if (is_file($this->pid_file)) {
                $pid = file_get_contents($this->pid_file);

                if (posix_kill($pid, 0)) {
                    exit();
                }
            }

            $pid = pcntl_fork();

            if ($pid !== 0) {
                /* Здесь выполняется родитель */

                exit();
            }

            /* Здесь выполняется дочерний процесс */

            // PID-файл
            file_put_contents($this->pid_file, posix_getpid());
        }

        // Создаём ws-сервер, к которому будут подключаться все наши пользователи
        $this->ws_worker = new Worker('websocket://0.0.0.0:' . $this->ws_port);

        // Создаём обработчик, который будет выполняться при запуске ws-сервера
        $this->ws_worker->onWorkerStart = function () {
            $this->initMessenger();
        };

        // При подключении нового пользователя сохраняем get-параметр, который сами же и передали со страницы сайта
        $this->ws_worker->onConnect = function ($connection) {
            $connection->onWebSocketConnect = function ($connection) {
                if (empty($this->func_get_user_id)) {
                    $uid = $_GET['sid'];
                } else {
                    $uid = call_user_func($this->func_get_user_id, $_GET['sid']);
                }

                if (!empty($uid)) {
                    $this->users_connections[$connection->id] = $uid;

                    $this->log->info('Connected: ' . $uid);
                } else {
                    $this->log->warning('Not connected (empty uid): ' . $uid);
                }
            };
        };

        // Удаляем соединение при отключении пользователя
        $this->ws_worker->onClose = function ($connection) {
            if (isset($this->users_connections[$connection->id])) {
                $uid = $this->users_connections[$connection->id];

                unset($this->users_connections[$connection->id]);

                $this->log->info('Disconnected: ' . $uid);
            }
        };

        Worker::runAll();
    }

    /**
     * Мессенджер
     *
     * @version 03.04.2019
     * @author  Дмитрий Щербаков <atomcms@ya.ru>
     */
    protected function initMessenger()
    {
        // Создаём локальный tcp-сервер, чтобы отправлять на него сообщения из кода нашего сайта
        $this->tcp_worker = new Worker('tcp://127.0.0.1:' . $this->tcp_port);

        // Создаём обработчик сообщений, который будет срабатывать, когда на локальный tcp-сокет приходит сообщение
        $this->tcp_worker->onMessage = function ($connection, $data) {
            if ($connection) {
                $data = json_decode($data, true);

                if (is_array($data['uids']) && count($data['uids']) > 0) {
                    foreach ($data['uids'] as $uid) {
                        if ($uid === 0) {
                            // Отправляем сообщение всем пользователям
                            foreach ($this->ws_worker->connections as $webconnection) {
                                /** @var TcpConnection $webconnection */
                                $webconnection->send(json_encode($data['message']));
                            }
                        } else {
                            // Отправляем сообщение пользователю по user_id
                            $user_connections = array_keys($this->users_connections, $uid);
                            if (!empty($user_connections)) {
                                foreach ($user_connections as $conn_id) {
                                    if (isset($this->ws_worker->connections[$conn_id])) {
                                        /** @var TcpConnection $webconnection */
                                        $webconnection = $this->ws_worker->connections[$conn_id];
                                        $webconnection->send(json_encode($data['message']));
                                    }
                                }
                            }
                        }
                    }
                }
            }
        };

        try {
            $this->tcp_worker->listen();
        } catch (Exception $e) {
            $this->log->error('initMessenger(): ' . $e->getMessage());
        }
    }
}
