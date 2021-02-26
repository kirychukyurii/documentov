<?php
class ControllerMarketplaceExtension extends Controller
{
  private $error = array();

  public function index()
  {
    $this->load->model('account/customer');
    $this->model_account_customer->setLastPage($this->url->link('marketplace/extension', '', true, true));
    $this->load->language('marketplace/extension');

    $this->document->setTitle($this->language->get('heading_title'));

    if (isset($this->request->get['type'])) {
      $data['type'] = $this->request->get['type'];
    } else {
      $data['type'] = '';
    }

    $data['categories'] = [];

    $this->load->model("setting/extension");
    // $data = ['mods' => []];
    foreach (["field", "action", "folder", "service"] as $modType) {
      $avMods = $this->model_setting_extension->getInstalled($modType);
      if (count($avMods) == 0) {
        continue;
      }
      $this->load->language('extension/extension/' . $modType, 'extension');
      $data['categories'][] = array(
        'code' => $modType,
        'text' => $this->language->get('extension')->get('heading_title'),
        'href' => $this->url->link('extension/extension/' . $modType, '', true)
      );


      // $modTypeName = $this->language->get('extension')->get('heading_title');
      // $data['mods'][$modTypeName] = [];
      // foreach ($avMods as $mod) {
      //   $this->load->language('extension/' . $modType . "/" . $mod, $mod);
      //   if ($modType == "field") {
      //     $url = $this->url->link("extension/$modType/$mod/setting", '', true);
      //   } else if ($modType == "service") {
      //     $url = "redirect:" . $this->url->link("extension/$modType/$mod", '', true);
      //   } else {
      //     $url = $this->url->link("extension/$modType/$mod", '', true);
      //   }
      //   $data['categories'][] = array(
      //     'code' => $mod,
      //     'text' => $this->language->get($mod)->get('heading_title'),
      //     'href' => $url
      //   );

      // $data['mods'][$modTypeName][] = [
      //   'name' => $this->language->get($mod)->get('heading_title'),
      //   'href' => $url
      // ];
      // }
    }







    // $files = glob(DIR_APPLICATION . 'controller/extension/extension/*.php', GLOB_BRACE);

    // foreach ($files as $file) {
    //   $extension = basename($file, '.php');

    //   // Compatibility code for old extension folders
    //   $this->load->language('extension/extension/' . $extension, 'extension');

    //   $files = glob(DIR_APPLICATION . 'controller/extension/' . $extension . '/*.php', GLOB_BRACE);

    //   $data['categories'][] = array(
    //     'code' => $extension,
    //     'text' => $this->language->get('extension')->get('heading_title') . ' (' . count($files) . ')',
    //     'href' => $this->url->link('extension/extension/' . $extension, '', true)
    //   );
    // }

    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');
    $data['footer'] = $this->load->controller('common/footer');

    $this->response->setOutput($this->load->view('marketplace/extension', $data));
  }
}
