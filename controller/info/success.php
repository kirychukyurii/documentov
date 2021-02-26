<?php

class ControllerInfoSuccess extends Controller {
    public function index() {
        $this->load->language('info/success');
        if (empty($this->request->get['folder_uid'])) {
            $data['header'] = $this->load->controller('common/header');
            $data['footer'] = $this->load->controller('common/footer');
            $this->response->setOutput($this->load->view('info/success', $data));                                         
        } else {
            $data = array();
            $this->response->addHeader("Content-type: application/json");
            $this->response->setOutput(json_encode(array(
                'toolbar'   => $this->load->view('document/view_button', $data), 
                'form' => $this->load->view('info/success', $data))
                ));            
        }
    }    
}
