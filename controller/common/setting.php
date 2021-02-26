<?php

class ControllerCommonSetting extends Controller
{

  public function getLanguageText()
  {
    $this->response($this->language->all());
  }

  public function getLanguages()
  {
    $this->load->model('localisation/language');
    $this->response($this->model_localisation_language->getLanguages());
  }

  private function response($data)
  {
    $this->response->addHeader("Content-type: application/json");
    $this->response->setOutput(json_encode($data));
  }
}
