<?php
/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */


class Variable {
    private $data = array();

    public function get($key) {
        return $this->data[$key] ?? null;
    }
	
    public function set($key, $value) {
        $this->data[$key] = $value;
    }

}