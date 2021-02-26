<?php
// Heading
$_['heading_title']          = 'Конфигурация';

// Text
$_['text_step_3']            = 'Настройки базы данных, структуры и пользователя';
$_['text_db_connection']     = '1. Введите настройки соединения с базой данных';
$_['text_db_administration'] = '2. Введите данные для организационной структуры';
$_['text_db_storage']        = '3. Введите новый путь к каталогу storage';
$_['text_mysqli']            = 'MySQLi';
$_['text_mysql']             = 'MySQL';
$_['text_mpdo']              = 'mPDO';
$_['text_pgsql']             = 'PostgreSQL';

// Entry
$_['entry_db_driver']        = 'Драйвер базы данных';
$_['entry_db_hostname']      = 'Имя хоста';
$_['entry_db_username']      = 'Имя пользователя';
$_['entry_db_password']      = 'Пароль';
$_['entry_db_database']      = 'База данных';
$_['entry_db_database_info'] = 'Должна уже быть создана; ВСЕ ЕЕ ТАБЛИЦЫ БУДУТ УНИЧТОЖЕНЫ';
$_['entry_db_port']          = 'Порт';
$_['entry_db_prefix']        = 'Префикс';
$_['entry_orgname']          = 'Название организации';
$_['entry_username']         = 'Ваше имя';
$_['entry_usersurname']      = 'Ваша фамилия';
$_['entry_password']         = 'Пароль';
$_['entry_email']            = 'E-Mail';
$_['entry_email_info']       = 'Будет использован для входа в систему';
$_['entry_storage_location'] = 'Путь к каталогу storage';

// Error
$_['error_db_hostname']      = 'Требуется имя хоста!';
$_['error_db_username']      = 'Требуется имя пользователя!';
$_['error_db_database']      = 'Требуется имя базы данных!';
$_['error_db_port']          = 'Требуется порт базы данных!';
$_['error_db_prefix']        = 'Префикс базы данных может содержать только символы из диапазонов a-z, 0-9 и символ подчеркивания.';
$_['error_db_connect']       = 'Ошибка: невозможно соединиться с базой данных, убедитесь что имя сервера, имя пользователя и пароль введены корректно!';
$_['error_orgname']          = 'Требуется название организации для создания Структуры!';
$_['error_username']         = 'Требуется Ваше имя для создания Структуры!';
$_['error_usersurname']      = 'Требуется Ваша фамилия для создания Структуры!';
$_['error_password']         = 'Требуется пароль!';
$_['error_email']            = 'Некорректный E-Mail!';
$_['error_config']           = 'Ошибка: невозможно произвести запись в config.php, пожалуйста проверьте права доступа: ';
$_['error_storage_location'] = 'Ошибка: невозможно переместить каталог storage.';
$_['error_version_mysql']    = 'Версия MySQL должна быть не ниже 5.5';
