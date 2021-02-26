<?php

class ControllerErrorCycle extends Controller {
    public function index() {
        $this->document->setTitle($this->language->get('error_cycle'));
        $data = array();
        if (empty($this->request->get['folder_uid'])) {
            $data['header'] = $this->load->controller('common/header');
            $data['footer'] = $this->load->controller('common/footer');
            $this->response->setOutput($this->load->view('error/cycle', $data));                                         
        } else {
            
            $this->response->addHeader("Content-type: application/json");
            $this->response->setOutput(json_encode(array(
                'toolbar'   => $this->load->view('document/view_button', $data), 
                'form' => $this->load->view('error/cycle', $data))
                ));            
        }
    }    
}
