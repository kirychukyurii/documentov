<?php
class ModelSettingEvent extends Model {
	function getEvents() {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "event` WHERE status = '1' ORDER BY `sort_order` ASC");

		return $query->rows;
	}
}