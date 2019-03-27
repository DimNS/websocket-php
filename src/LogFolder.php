<?php
/**
 * Валидация каталога для логов
 *
 * @version 01.03.2019
 * @author  Дмитрий Щербаков <atomcms@ya.ru>
 */

namespace WebSocketPHP;

/**
 * Class LogFolder
 *
 * @package WebSocketPHP
 */
class LogFolder
{
    /**
     * Проверим что есть завершающая косая черта
     *
     * @param string $log_folder Каталог для логов
     *
     * @return string
     *
     * @version 01.03.2019
     * @author  Дмитрий Щербаков <atomcms@ya.ru>
     */
    static function validate($log_folder)
    {
        $last_symbol = substr($log_folder, -1);

        if ($last_symbol === '/' OR $last_symbol === '\\') {
            return $log_folder;
        }

        return $log_folder . DIRECTORY_SEPARATOR;
    }
}
