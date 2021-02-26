<?php
class ControllerCommonFooter extends Controller
{
  public function index()
  {
    $this->load->language('common/footer');

    $data['notification_pooling_period'] = $this->config->get('notification_pooling_period') ?? 0;

    $data['scripts'] = [];

    $custom_js = DIR_APPLICATION . "view" . DIRECTORY_SEPARATOR . "javascript" . DIRECTORY_SEPARATOR . "custom.js";

    if (file_exists($custom_js)) {
      $data['scripts'][] = "/view/javascript/custom.js";
    }

    $version = VERSION;
    // если в версии на конце .0 (1.6.0.0) показываем три цифры версии (1.6.0)
    if (strlen($version) > 5 && $version[6] === "0") {
      $version = substr($version, 0, 5);
    }
    if ($this->config->get('config_name') == "Documentov") {
      $data['powered'] = sprintf($this->language->get('text_powered'), "", $this->config->get('config_name'), $version) .  $this->language->get('text_powered_addt');
    } else {
      $data['powered'] = strip_tags(sprintf($this->language->get('text_powered'), "", $this->config->get('config_name'), $version));
    }
    return $this->load->view('common/footer', $data);
  }
}
