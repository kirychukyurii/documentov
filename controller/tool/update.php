<?php

class ControllerToolUpdate extends Controller {
    public function manualUpdate() {
        $this->load->model('tool/update');
        $this->load->model('account/customer');
        $this->model_tool_update->manualUpdate();
        $this->response->redirect($this->model_account_customer->getStartPage());
    }
}

