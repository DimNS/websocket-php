<?php

/**
 * @author  Дмитрий Щербаков <atomcms@ya.ru>
 *
 * @version 15.09.2020
 */

namespace WebSocketPHP;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;

/**
 * @package WebSocketPHP
 */
class Log
{
    /**
     * @param string $log_folder Каталог для логов
     *
     * @return Logger
     *
     * @author  Дмитрий Щербаков <atomcms@ya.ru>
     *
     * @version 15.09.2020
     */
    static function create($log_folder)
    {
        $log_folder = LogFolder::validate($log_folder);

        $log = new Logger('WebSocketPHP');
        $filename = $log_folder . 'websocket-php.log';
        $handler = new RotatingFileHandler($filename);

        $handler->setFilenameFormat('{date}-{filename}', 'Y/m/d');

        $log->pushHandler($handler);

        return $log;
    }
}
