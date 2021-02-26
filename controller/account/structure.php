<?php

/**
 * @package		Documentov
 * @author		Andrey V Surov
 * @copyright           Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.documentov.com
 */
class ControllerAccountStructure extends Controller {

    public function index() {
        if (!$this->customer->isLogged()) {
            $this->session->data['redirect'] = $this->url->link('account/account', '', true);
            $this->response->redirect($this->url->link('account/login', '', true));
        }
        $this->load->model('account/customer');
        
        if ($this->request->server['REQUEST_METHOD'] == "POST" && !empty($this->request->post['structure_uid']) && 
                $this->validate_structure_uid($this->request->post['structure_uid'])) {
            
            $this->customer->setStructureId($this->request->post['structure_uid']);
            
            //редирект на стартовую страницу            
            $this->response->redirect($this->model_account_customer->getStartPage() ?? $this->url->link('account/account', true));

        } else {
            $this->load->language('account/structure');            

            $this->document->setTitle($this->language->get('heading_title'));

            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

            $structures = $this->model_account_customer->getStructures($this->customer->getId());
            $deputies = array();
            foreach ($structures as $structure) {
                $deputies = array_merge($deputies, $this->model_account_customer->getDeputyStructures($structure['document_uid']));
            }
            
            $data['structures'] = array_merge($structures, $deputies);
            $data['structure_uid'] = $this->request->cookie['structure_uid'] ?? $this->customer->getStructureId();

            $data['action'] = $this->url->link('account/structure','',true);

            $this->response->setOutput($this->load->view('account/structure', $data));
            
        }

    }
    
    protected function validate_structure_uid($structure_uid) {
        $structures = $this->model_account_customer->getStructures($this->customer->getId());
        foreach ($structures as $structure) {
            if ($structure['document_uid'] == $structure_uid) {
                return true;
            } else {
                foreach ($this->model_account_customer->getDeputyStructures($structure['document_uid']) as $deputy) {
                    if ($deputy['document_uid'] == $structure_uid) {
                        return true;
                    }                    
                }
            }
        }
        return false;
    }

}
