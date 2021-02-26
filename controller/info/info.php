<?php

class ControllerInfoInfo extends Controller{
    
    public function getView($data) {
        $data['header'] = $this->load->controller('common/header');
        $data['footer'] = $this->load->controller('common/footer');
        return $this->load->view('info/info', $data);
    }    
}