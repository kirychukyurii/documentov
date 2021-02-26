<?php
class ControllerCommonHome extends Controller
{
  public function index()
  {
    $this->load->model('account/customer');
    $startpage = $this->model_account_customer->getStartPage();
    if ($startpage) {
      $this->response->redirect($this->model_account_customer->getStartPage());
    } else {
      $this->response->redirect($this->url->link('document/search'));
    }

    // 		if (isset($this->request->get['route'])) {
    // 			$this->document->addLink($this->config->get('config_url'), 'canonical');
    // 		}

    // 		$data['content'] = $this->load->controller('common/content_home');

    // 		$data['footer'] = $this->load->controller('common/footer');
    // 		$data['header'] = $this->load->controller('common/header');
    // //                print_r($data);exit;
    // 		$this->response->setOutput($this->load->view('common/home', $data));
    // 	}
  }
}
