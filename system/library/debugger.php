<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */
class Debugger {
    
    public function sh($message, $error_code="500", $error_text="Internal Server Error") {
        header(filter_input(INPUT_SERVER, 'SERVER_PROTOCOL') . " " . $error_code . " " . $error_text, true, $error_code);
        $message = "<br><br>" . str_replace("\n","<br>",print_r($message,true));
        echo str_replace(" ", "&nbsp;", $message);    
        exit;
    }
}
