<?php

class ControllerCommonHeader extends Controller
{

  const DIR_DEFAULT_CSS = "default/stylesheet/";

  public function index()
  {
    $this->load->model('setting/extension');

    if ($this->request->server['HTTPS']) {
      $server = $this->config->get('config_ssl');
    } else {
      $server = $this->config->get('config_url');
    }

    if (is_file(DIR_IMAGE . $this->config->get('config_icon'))) {
      $this->document->addLink($server . 'image/' . $this->config->get('config_icon'), 'icon');
    }

    $data['title'] = $this->document->getTitle();

    $data['base'] = $server;
    $data['description'] = $this->document->getDescription();
    $data['keywords'] = $this->document->getKeywords();
    $data['links'] = $this->document->getLinks();
    $data['styles'] = $this->document->getStyles();

    foreach (['field', 'action'] as $module) {
      $path = $this::DIR_DEFAULT_CSS . $module . "/";
      foreach (glob(DIR_TEMPLATE . $path . "*.css", GLOB_BRACE) as $file) {
        $pathinfo = pathinfo($file);
        $data['styles'][] = [
          'href'  => "/view/theme/" . $path . $pathinfo['basename'],
          'rel'   => 'stylesheet',
          'media' => 'screen'
        ];
      }
    }

    $data['scripts'] = $this->document->getScripts('header');
    $data['lang'] = $this->language->get('code');
    $data['direction'] = $this->language->get('direction');

    $data['name'] = $this->config->get('config_name');

    if ($this->config->get('config_logo')) {
      $data['logo'] = html_entity_decode($this->config->get('config_logo'));
    }
    $data['notification_pooling_period'] = $this->config->get('notification_pooling_period') ?? 0;
    if ($this->config->get('config_notification_icon')) {
      $this->load->model('tool/image');
      $data['notification_icon'] = $this->model_tool_image->resize($this->config->get('config_notification_icon'), $this->config->get('menu_image_width'), $this->config->get('menu_image_height'));
    }

    $this->load->language('common/header');

    $data['text_logged'] = sprintf($this->language->get('text_logged'), $this->url->link('account/account', '', true), $this->customer->getFirstName(), $this->url->link('account/logout', '', true));
    if ($this->config->get('default_start_page')) {
      $home = str_replace("&amp;", "&", $this->config->get('default_start_page'));
    } else {
      $this->load->model('account/customer');
      $home = $this->model_account_customer->getStartPage();
    }

    $data['home'] =  $home;
    $data['logged'] = $this->customer->isLogged();


    $data['disable_main_menu'] = $this->config->get('disable_main_menu') ?? 0;
    if ($this->daemon->getStatus() && $this->customer->isLogged() && !$data['disable_main_menu']) { //готовим меню, если пользователь не на странице входа в систему
      $this->load->model('menu/item');
      $this->load->model('tool/image');
      $data['menu_items'] = $this->getMenuItems($this->model_menu_item->getMenuItems(''));
    }

    if ($this->customer->isAdmin() && $this->variable->get('manual_update')) {
      $data['admin_message'] = sprintf($this->language->get('text_manual_update'), $this->url->link('tool/update/manualUpdate'));
    }
    $data['text'] = $this->language->all();
    $data['VERSION'] = $data['text']['VERSION'] = VERSION;
    return $this->load->view('common/header', $data);
  }

  private function getMenuItems($items)
  {
    $result = array();
    $data_template = array(
      'document_uid'      => $this->customer->getStructureId(),
      'doctype_uid'       => $this->config->get('structure_id'),
      'draft'             => FALSE,
      'mode'              => 'view'
    );
    foreach ($items as $item) {
      if ($item['type'] == "text") {
        if ($item['action'] == 'folder') {
          $link = $item['action_value'] ? $this->url->link('document/folder', '&folder_uid=' . $item['action_value'], true) : "";
        } else {
          $link = $item['action_value'];
        }
      } else {
        $link = "";
      }
      $data_template['template'] = $item['name'];
      $result[] = array(
        'name'      => $data_template['template'] ? strip_tags(htmlspecialchars_decode($this->load->controller('document/document/renderTemplate', $data_template))) : "",
        'type'      => $item['type'],
        'image'     => $item['image'] ? $this->model_tool_image->resize($item['image'], $this->config->get('menu_image_width'), $this->config->get('menu_image_height')) : "",
        'hide_name' => $item['hide_name'],
        'link'      => $link,
        'children'  => $this->getMenuItems($item['children'])
      );
    }
    return $result;
  }
}
