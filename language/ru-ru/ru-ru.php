<?php
// Locale
$_['code']                  = 'ru-ru';
$_['direction']             = 'ltr';
$_['date_format_short']     = 'd.m.Y';
$_['date_format_long']      = 'l, d F Y';
$_['time_format']           = 'H:i:s';
$_['datetime_format']       = 'd.m.Y H:i:s';
$_['decimal_point']         = ',';
$_['thousand_point']        = ' ';
$_['week_start']            = 1; //неделя начинаяется с понедельника

// Text
$_['text_enabled']                  = 'Включено';
$_['text_disabled']                 = 'Отключено';
$_['text_back']                     = 'Назад';
$_['text_forward']                  = 'Вперед';
$_['text_home']                     = '<i class="fa fa-home"></i>';
$_['text_yes']                      = 'Да';
$_['text_no']                       = 'Нет';
$_['text_none']                     = ' --- Не выбрано --- ';
$_['text_noname']                   = 'Без названия';
$_['text_all']                      = 'Все';
$_['text_select']                   = ' --- Выберите --- ';
$_['text_select_all']               = 'выбрать все';
$_['text_deselect_all']             = 'снять выделение';
$_['text_empty_value']              = '-- пусто --';
$_['text_pagination']               = 'Показано с %d по %d из %d (всего %d страниц)';
$_['text_loading']                  = 'Загрузка...';
$_['text_no_results']               = 'Нет данных!';
$_['text_condition_0']              = 'не выбрано';
$_['text_condition_equal']          = 'равно';
$_['text_condition_notequal']       = 'не равно';
$_['text_condition_more']           = 'больше';
$_['text_condition_moreequal']      = 'больше или равно';
$_['text_condition_less']           = 'меньше';
$_['text_condition_lessequal']      = 'меньше или равно';
$_['text_condition_contains']       = 'содержит';
$_['text_condition_notcontains']    = 'не содержит';
$_['text_condition_include']        = 'содержится в';
$_['text_condition_notinclude']     = 'не содержится в';
$_['text_document']                 = 'Документ';
$_['text_folder']                   = 'Журнал';
$_['text_field']                    = 'Поле';
$_['text_doctype']                  = 'Тип документа';
$_['text_template']                 = 'Шаблон';
$_['text_default_method']           = '==стандартный метод==';
$_['text_field_method']             = 'Параметры метода';
$_['text_system']                   = 'Documentov';
$_['text_currentdoc']               = 'ТЕКУЩИЙ ДОКУМЕНТ';
$_['text_addressdoc']               = 'ДОКУМЕНТ, В КОТОРЫЙ ПИШЕМ';
$_['text_by_link_in_field']         = 'по ссылке из поля';
$_['text_or']                       = 'или';
$_['text_and']                      = 'и';
$_['text_macros_field']             = array(
  'simple'        => 'обычные поля',
  'setting'       => 'настроечные поля',
  'placeholder'   => 'Выберите поле',
  'none'          => ' --- Не выбрано --- ',
  'default_method' => '==стандартный метод=='
);
$_['text_macros_doctype']                     = array(
  'placeholder'   => 'Выберите тип документа',
);
$_['text_navigator_documentov']             = array(
  'textNone'              => ' --- Не выбрано --- ',
  'buttonRemove'          => 'Удалить',
  'buttonClose'           => 'Закрыть',
  'buttonBugReport'       => 'Сообщить об ошибке',
  'buttonSendReport'      => 'Отправить сообщение',
  'textEmptyValue'        => '-- пусто --',
  'textBlockPopupWindow'  => 'Разрешите показывать всплывающие окна, чтоб загрузить файл',
  'textLoading'           => 'Загрузка...',
  'textLoadingError1'     => 'Подождите...',
  'textLoadingError2'     => 'Ждем ответ сервера...',
  'textLoadingError3'     => 'Сервер недоступен',
  'textError'             => '<b>Произошла ошибка</b>',
  'textHeaderError'       => 'Ошибка',
  'textBugReport'         => 'Будет отправлена нижеприведенная информация',
  'textVersion'           => 'Версия: ' . VERSION,
  'textURL'               => 'Адрес',
  'textBugReportAdd'      => 'Вы можете дополнить отправляемую информацию описанием текущей ситуации',
  'textBugReportEmail'    => 'Если Вы укажете свой e-mail, мы сможем связаться с Вами при необходимости',

);
$_['text_file_upload']            = [
  'upload' => 'Загрузить',
  'abort' => 'Остановить',
  'remove' =>  'Удалить',
  'complete' => 'Готово',
  'error' => 'Ошибка',
  'errorFilesLimit' => 'Количество выбранных файлов превышает лимит (%filesLimit%)',
  'errorSizeLimit' => 'Файл "%fileName%" превышает предельный размер (%sizeLimit%)',
  'errorFileType' => 'Файл "%fileName%" является некорректным',
  'errorOldBrowser' => 'Ваш браузер не может загружать файлы. Обновите его до последней версии'
];
$_['text_select_field']                     = 'Выберите поле';
$_['text_asc']                              = 'по возрастанию';
$_['text_desc']                             = 'по убыванию';
$_['text_search']                           = 'Искать...';
$_['text_field_deleted']                    = '<Поле удалено>';
$_['text_filter']                           = 'Фильтр';
$_['text_fields']                           = 'Поля';
$_['text_comparison_field']                 = 'полю';
$_['text_comparison_input']                 = 'значению';
$_['text_comparison_variable']              = 'переменной';
$_['text_source_type_document']             = 'из документа';
$_['text_source_type_doctype']              = 'из настроек типа документа';
$_['text_source_type_variable']             = 'из переменной';
$_['text_source_type_manual']               = 'ввести вручную';
$_['text_source_type_field']                = 'получить из поля';
$_['text_source_type']                      = 'Значение';
$_['text_source_doclink']                   = 'документ:';
$_['text_tab_source']                       = 'Что записывать';
$_['text_target_field']                     = 'в поле';
$_['text_source_value']                     = 'Значение';
$_['text_source_current']                   = 'Текущий документ';
$_['text_source_field']                     = 'Документ по ссылке из поля';
$_['text_source_variable']                  = 'Переменная';
$_['text_field_does_not_support_filter']    = 'Поле не поддерживает фильтрацию';
$_['text_var_author_uid']                   = 'СТРУКТУРНЫЙ ИДЕНТИФИКАТОР АВТОРА ДОКУМЕНТА';
$_['text_var_department_uid']               = 'СТРУКТУРНЫЙ ИДЕНТИФИКАТОР ПОДРАЗДЕЛЕНИЯ ДОКУМЕНТА';
$_['text_var_author_name']                  = 'ИМЯ АВТОРА';
$_['text_var_department_name']              = 'НАЗВАНИЕ ПОДРАЗДЕЛЕНИЯ';
$_['text_var_customer_uid']                 = 'СТРУКТУРНЫЙ ИДЕНТИФИКАТОР ТЕКУЩЕГО ПОЛЬЗОВАТЕЛЯ';
$_['text_var_customer_id']                  = 'СТРУКТУРНЫЙ ИДЕНТИФИКАТОР ТЕКУЩЕГО ПОЛЬЗОВАТЕЛЯ';
$_['text_var_customer_uids']                = 'ВСЕ СТРУКТУРНЫЕ ИДЕНТИФИКАТОРЫ ТЕКУЩЕГО ПОЛЬЗОВАТЕЛЯ';
$_['text_var_customer_user_uid']            = 'ПОЛЬЗОВАТЕЛЬСКИЙ ИДЕНТИФИКАТОР ТЕКУЩЕГО ПОЛЬЗОВАТЕЛЯ';
$_['text_var_customer_name']                = 'ИМЯ ПОЛЬЗОВАТЕЛЯ';
$_['text_var_current_time']                 = 'ТЕКУЩЕЕ ВРЕМЯ';
$_['text_var_time_added']                   = 'ВРЕМЯ СОЗДАНИЯ ДОКУМЕНТА';
$_['text_var_document_time_added']          = 'ВРЕМЯ СОЗДАНИЯ ДОКУМЕНТА';
$_['text_var_date_added']                   = 'ДАТА СОЗДАНИЯ ДОКУМЕНТА';
$_['text_var_current_date']                 = 'ТЕКУЩАЯ ДАТА';
$_['text_var_current_datetime']             = 'ТЕКУЩЕЕ ВРЕМЯ';
$_['text_var_current_document_uid']         = 'ИДЕНТИФИКАТОР ДОКУМЕНТА';
$_['text_var_current_button_uid']           = 'ИДЕНТИФИКАТОР НАЖАТОЙ КНОПКИ';
$_['text_var_change_field_uid']             = 'ИДЕНТИФИКАТОР ИЗМЕНЕННОГО ПОЛЯ';
$_['text_var_change_field_value']           = 'ПРЕЖНЕЕ ЗНАЧЕНИЕ ИЗМЕНЕННОГО ПОЛЯ';
$_['text_var_current_route_uid']            = 'ИДЕНТИФИКАТОР ТЕКУЩЕЙ ТОЧКИ МАРШРУТА ДОКУМЕНТА';
$_['text_var_current_route_name']           = 'НАЗВАНИЕ ТЕКУЩЕЙ ТОЧКИ МАРШРУТА ДОКУМЕНТА';
$_['text_var_current_route_description']    = 'ОПИСАНИЕ ТЕКУЩЕЙ ТОЧКИ МАРШРУТА ДОКУМЕНТА';
$_['text_var_current_doctype_uid']          = 'ИДЕНТИФИКАТОР ТИПА ДОКУМЕНТА';
$_['text_var_current_folder_uid']           = 'ИДЕНТИФИКАТОР ТЕКУЩЕГО ЖУРНАЛА';
$_['text_var_struid_access_document']       = 'СТРУКТУРНЫЕ ИДЕНТИФИКАТОРЫ С ДОСТУПОМ К ДОКУМЕНТУ';
$_['text_deputy']                           = '[зам]';
$_['text_type_sf_s']                        = 'структура';
$_['text_type_sf_sf']                       = 'настроечное поле';
$_['text_version']                          = 'Версия';
$_['text_license']                          = 'Лицензия';
$_['text_button_not_availabled']            = 'Кнопка недоступна';
$_['text_no_setting']                       = 'У поля нет настроек';

$_['technical_break_update']                = 'Выполняется обновление системы. Повторите операцию позднее<br><a href="/index.php?route=account/logout">Выйти</a>';

//Entry
$_['entry_target_doclink_field']            = 'Поле, содержащее ссылку';
$_['entry_target_doctype']                  = 'Тип документа-приёмника';
$_['entry_target_field']                    = 'Поле документа-приёмника';
$_['entry_target_field_setter']             = 'Метод поля';
$_['entry_source_type']                     = 'Источник';
$_['entry_source_doclink_field']            = 'Поле, содержащее ссылку';
$_['entry_source_doctype']                  = 'Тип документа-источника';
$_['entry_source_field']                    = 'из поля';
$_['entry_source_field_method']             = 'Метод поля';
$_['entry_source_value']                    = 'Значение';
$_['entry_source_variable']                 = 'Переменная';
$_['entry_source_manual']                   = 'введите значение';

//Help
$_['help_source_type']                      = 'Откуда получить значение';
$_['help_source_doclink_field']             = 'Поле текущего документа, которое содержит ссылку на документ-источник';
$_['help_source_doctype']                   = 'Выберите тип документа-источника данных';
$_['help_source_field']                     = 'Выберите поле, значение которого будет использовано в качестве данных для записи';
$_['help_source_field_getter']              = 'Выберите метод поля документа-источника данных, который будет использован для получения заначения этого поля';
$_['help_source_value']                     = 'Значение, которое будет записано в поле документа-приёмника';
$_['help_source_variable']                  = 'Переменная, которая будет записана в поле документа-приёмника';
$_['help_target_field_type']                = 'Запись можно выполнить в обычное поле (со вкладки Поля типа документа) или настроечное (со вкладки Настройки типа документа)';

// Buttons
$_['button_delete']                         = 'Удалить';
$_['button_download']                       = 'Скачать';
$_['button_edit']                           = 'Редактировать';
$_['button_change']                         = 'Изменить';
$_['button_open']                           = 'Открыть';
$_['button_filter']                         = 'Фильтровать';
$_['button_login']                          = 'Войти';
$_['button_update']                         = 'Обновить';
$_['button_remove']                         = 'Удалить';
$_['button_move']                           = 'Переместить';
$_['button_undo']                           = 'Отменить';
$_['button_search']                         = 'Поиск';
$_['button_up']                             = 'Вверх';
$_['button_down']                           = 'Вниз';
$_['button_add']                            = 'Добавить';
$_['button_save']                           = 'Сохранить';
$_['button_copy']                           = 'Копировать';
$_['button_cancel']                         = 'Отменить';
$_['button_disable_enable']                 = 'Отключить / Включить';
$_['button_close']                          = 'Закрыть';
$_['button_confirm']                        = 'Подтвердить';
$_['button_continue']                       = 'Продолжить';
$_['button_insert']                         = 'Вставить';

// Error
$_['error_access_denied']                   = 'Доступ запрещен';
$_['error_general']                         = 'Отказ в операции из-за ошибки';
$_['error_general_1']                       = 'Нет ни одного заполненного поля в сохраняемом документе';
$_['error_cycle']                           = 'Превышено количество вызываемых точек маршрута. Изменить это количество можно в настройках системы. Обратитесь к администратору';
$_['error_cycle_template']                  = 'Превышено количество рекурсивных вызовов формирования шаблона. Изменить это количество можно в настройках системы. Обратитесь к администратору';
$_['error_not_found_update_procedure']      = 'Ошибка! Не найдена процедура обновления';
$_['error_not_executable']                  = 'Ошибка! Файл %s не является исполняемым и не может быть запущен';
/* Когда нужен перевод скриптов, просто добавь код языка */

// Datepicker
$_['datepicker']                            = 'ru';

// Months
$_['January']                               = 'Январь';
$_['February']                              = 'Февраль';
$_['March']                                 = 'Март';
$_['April']                                 = 'Апрель';
$_['May']                                   = 'Май';
$_['June']                                  = 'Июнь';
$_['July']                                  = 'Июль';
$_['August']                                = 'Август';
$_['September']                             = 'Сентябрь';
$_['October']                               = 'Октябрь';
$_['November']                              = 'Ноябрь';
$_['December']                              = 'Декабрь';

//Day of week
$_['Sunday'] = 'Вс';
$_['Monday'] = 'Пн';
$_['Tuesday'] = 'Вт';
$_['Wednesday'] = 'Ср';
$_['Thursday'] =  'Чт';
$_['Friday'] = 'Пт';
$_['Saturday'] = 'Сб';

$_['tab_data']                      = 'Данные';
$_['tab_field']                     = 'Поля';
$_['tab_button']                    = 'Кнопка';
$_['tab_buttons']                   = 'Кнопки';
$_['tab_action']                    = 'Действие';
$_['tab_filter']                    = 'Фильтры';
$_['tab_route']                     = 'Маршрут';
$_['tab_template']                  = 'Шаблоны';
$_['tab_general']                   = 'Основное';
$_['tab_additional']                = 'Дополнительное';
$_['tab_setting']                   = 'Настройки';
$_['tab_delegate']                  = 'Доступ';
$_['text_route_jump_name']          = 'Переход';
$_['text_route_view_name']          = 'Активность';
$_['text_route_change_name']        = 'Изменение';
$_['text_route_setting_name']       = 'Настройки';
$_['text_route_create_name']        = 'Создание';
$_['text_route_delete_name']        = 'Удаление';

$_['column_name']                       = 'Название';
$_['column_description']                = 'Описание';
$_['column_created']                    = 'Создан';
$_['column_modified']                   = 'Изменен';

//text editor
$_['text_editor'] = [
  'font' =>  [
    'bold' =>  'Полужирный',
    'italic' =>  'Курсив',
    'underline' =>  'Подчёркнутый',
    'clear' =>  'Убрать стили шрифта',
    'height' =>  'Высота линии',
    'name' =>  'Шрифт',
    'strikethrough' =>  'Зачёркнутый',
    'subscript' =>  'Нижний индекс',
    'superscript' =>  'Верхний индекс',
    'size' =>  'Размер шрифта'
  ],
  'image' =>  [
    'image' =>  'Картинка',
    'insert' =>  'Вставить картинку',
    'resizeFull' =>  'Восстановить размер',
    'resizeHalf' =>  'Уменьшить до 50%',
    'resizeQuarter' =>  'Уменьшить до 25%',
    'floatLeft' =>  'Расположить слева',
    'floatRight' =>  'Расположить справа',
    'floatNone' =>  'Расположение по-умолчанию',
    'shapeRounded' =>  'Форма =>  Закругленная',
    'shapeCircle' =>  'Форма =>  Круг',
    'shapeThumbnail' =>  'Форма =>  Миниатюра',
    'shapeNone' =>  'Форма =>  Нет',
    'dragImageHere' =>  'Перетащите сюда картинку',
    'dropImage' =>  'Перетащите картинку',
    'selectFromFiles' =>  'Выбрать из файлов',
    'maximumFileSize' =>  'Максимальный размер файла',
    'maximumFileSizeError' =>  'Превышен максимальный размер файла',
    'url' =>  'URL картинки',
    'remove' =>  'Удалить картинку',
    'original' =>  'Оригинал'
  ],
  'video' =>  [
    'video' =>  'Видео',
    'videoLink' =>  'Ссылка на видео',
    'insert' =>  'Вставить видео',
    'url' =>  'URL видео',
    'providers' =>  '(YouTube, Vimeo, Vine, Instagram, DailyMotion или Youku)'
  ],
  'link' =>  [
    'link' =>  'Ссылка',
    'insert' =>  'Вставить ссылку',
    'unlink' =>  'Убрать ссылку',
    'edit' =>  'Редактировать',
    'textToDisplay' =>  'Отображаемый текст',
    'url' =>  'URL для перехода',
    'openInNewWindow' =>  'Открывать в новом окне'
  ],
  'table' =>  [
    'table' =>  'Таблица',
    'addRowAbove' =>  'Добавить строку выше',
    'addRowBelow' =>  'Добавить строку ниже',
    'addColLeft' =>  'Добавить столбец слева',
    'addColRight' =>  'Добавить столбец справа',
    'delRow' =>  'Удалить строку',
    'delCol' =>  'Удалить столбец',
    'delTable' =>  'Удалить таблицу'
  ],
  'hr' =>  [
    'insert' =>  'Вставить горизонтальную линию'
  ],
  'style' =>  [
    'style' =>  'Стиль',
    'p' =>  'Нормальный',
    'blockquote' =>  'Цитата',
    'pre' =>  'Код',
    'h1' =>  'Заголовок 1',
    'h2' =>  'Заголовок 2',
    'h3' =>  'Заголовок 3',
    'h4' =>  'Заголовок 4',
    'h5' =>  'Заголовок 5',
    'h6' =>  'Заголовок 6'
  ],
  'lists' =>  [
    'unordered' =>  'Маркированный список',
    'ordered' =>  'Нумерованный список'
  ],
  'options' =>  [
    'help' =>  'Помощь',
    'fullscreen' =>  'На весь экран',
    'codeview' =>  'Исходный код'
  ],
  'paragraph' =>  [
    'paragraph' =>  'Параграф',
    'outdent' =>  'Уменьшить отступ',
    'indent' =>  'Увеличить отступ',
    'left' =>  'Выровнять по левому краю',
    'center' =>  'Выровнять по центру',
    'right' =>  'Выровнять по правому краю',
    'justify' =>  'Растянуть по ширине'
  ],
  'color' =>  [
    'recent' =>  'Последний цвет',
    'more' =>  'Еще цвета',
    'background' =>  'Цвет фона',
    'foreground' =>  'Цвет шрифта',
    'transparent' =>  'Прозрачный',
    'setTransparent' =>  'Сделать прозрачным',
    'reset' =>  'Сброс',
    'resetToDefault' =>  'Восстановить умолчания'
  ],
  'shortcut' =>  [
    'shortcuts' =>  'Сочетания клавиш',
    'close' =>  'Закрыть',
    'textFormatting' =>  'Форматирование текста',
    'action' =>  'Действие',
    'paragraphFormatting' =>  'Форматирование параграфа',
    'documentStyle' =>  'Стиль документа',
    'extraKeys' =>  'Дополнительные комбинации'
  ],
  'help' =>  [
    'insertParagraph' =>  'Новый параграф',
    'undo' =>  'Отменить последнюю команду',
    'redo' =>  'Повторить последнюю команду',
    'tab' =>  'Tab',
    'untab' =>  'Untab',
    'bold' =>  'Установить стиль \"Жирный\"',
    'italic' =>  'Установить стиль \"Наклонный\"',
    'underline' =>  'Установить стиль \"Подчеркнутый\"',
    'strikethrough' =>  'Установить стиль \"Зачеркнутый\"',
    'removeFormat' =>  'Сборсить стили',
    'justifyLeft' =>  'Выровнять по левому краю',
    'justifyCenter' =>  'Выровнять по центру',
    'justifyRight' =>  'Выровнять по правому краю',
    'justifyFull' =>  'Растянуть на всю ширину',
    'insertUnorderedList' =>  'Включить/отключить маркированный список',
    'insertOrderedList' =>  'Включить/отключить нумерованный список',
    'outdent' =>  'Убрать отступ в текущем параграфе',
    'indent' =>  'Вставить отступ в текущем параграфе',
    'formatPara' =>  'Форматировать текущий блок как параграф (тег P)',
    'formatH1' =>  'Форматировать текущий блок как H1',
    'formatH2' =>  'Форматировать текущий блок как H2',
    'formatH3' =>  'Форматировать текущий блок как H3',
    'formatH4' =>  'Форматировать текущий блок как H4',
    'formatH5' =>  'Форматировать текущий блок как H5',
    'formatH6' =>  'Форматировать текущий блок как H6',
    'insertHorizontalRule' =>  'Вставить горизонтальную черту',
    'linkDialog.show' =>  'Показать диалог \"Ссылка\"'
  ],
  'history' =>  [
    'undo' =>  'Отменить',
    'redo' =>  'Повтор'
  ],
  'specialChar' =>  [
    'specialChar' =>  'Спецсимволы',
    'select' =>  'Выбрать спецсимволы'
  ],
  'documentov' =>  [
    'fields' =>  'Поля',
    'field' =>  'Поле',
    'id' =>  'ID',
    'variables' =>  'Переменные',
    'structure_fields' =>  'Структура',
    'elements' =>  'Элементы',
    'button' =>  'Кнопка',
    'save' =>  'Сохранить',
    'cancel' =>  'Отменить',
    'close' =>  'Закрыть',
    'actions' =>  'Действия',
    'conditions' =>  'Условия',
    'recordTemplate' =>  'Шаблон записи',
    'conditions_tooltip' =>  'Условия для отображения/скрытия блоков/полей',
    'equal' =>  'равно',
    'not_equal' =>  'не равно',
    'consist' =>  'содержит',
    'not_consist' =>  'не содержит',
    'equal_field' =>  'равно полю',
    'not_equal_field' =>  'не равно полю',
    'add_condition' =>  'Добавить условие',
    'del_condition' =>  'Удалить это условие',
    'add_action' =>  'Добавить действие',
    'del_action' =>  'Удалить это действие',
    'hide' =>  'Скрыть',
    'show' =>  'Показать',
    'record' =>  'Записать',
    'download' =>  'Загрузить',
    'download_document_field' => 'Поле с документом',
    'download_field' => 'Поле со значением',
    'or' =>  'или',
    'and' =>  'и',
    'textNone' =>  ' --- Не выбрано --- '
  ],
];
