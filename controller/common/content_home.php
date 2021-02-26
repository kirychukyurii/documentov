<?php
class ControllerCommonContentHome extends Controller {
	public function index() {
            $this->load->language('common/content_home');
            $this->document->setTitle($this->language->get('text_title'));
            $data = array(
                'title' => sprintf($this->language->get('text_greeting'), VERSION)
            );
            $this->load->language('common/content_home');
            return $this->load->view('common/content_home', $data);
            
	}
}
