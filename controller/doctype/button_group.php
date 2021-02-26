<?php

class ControllerDoctypeButtonGroup extends Controller
{

  public function edit_button_group()
  {
    $data = array();
    $this->load->language('doctype/button_group');
    $this->load->model('tool/image');
    $this->load->model('localisation/language');
    $data['languages'] = $this->model_localisation_language->getLanguages();
    $button_group_uid = "";
    if (isset($this->request->get['button_group_uid'])) {
      $button_group_uid = $this->request->get['button_group_uid'];
    }


    if ($button_group_uid === '1') {
      //добавление новой группы кнопок
      if (isset($this->request->get['changed'])) {
        $descriptions = $this->request->post['btn_group_descriptions'] ?? array();
        $data['descriptions'] = $descriptions;
        $data['picture'] = $this->request->post['btn_group_picture'] ?? '';
        $data['color'] =   $this->request->post['btn_group_color'] ?? '';
        $data['background'] = $this->request->post['btn_group_background'] ?? '';
        $data['button_group_hide_name'] = $this->request->post['btn_group_hide_name'] ?? '';
        $data['thumb'] = $this->model_tool_image->resize($data['picture'] ? $data['picture'] : 'no_image.png', 25, 25);
      } else {
        $data['thumb'] = $this->model_tool_image->resize('no_image.png', 25, 25);
        $data['placeholder'] = $this->model_tool_image->resize('no_image.png', 25, 25);
        $data['route_uid'] = $this->request->get['route_uid'];
      }
    } else {
      //редактирование группы кнопок
      $button_group_info = $this->model_doctype_doctype->getButtonGroup($button_group_uid);
      $descriptions = $this->request->post['btn_group_descriptions'] ?? array();

      $new_descriptions = false;
      foreach ($descriptions as $lang_description) {
        if (!empty($lang_description) && $lang_description['name']) {
          $new_descriptions = true;
          break;
        }
      }
      if (!isset($this->request->get['changed']) || !$new_descriptions) {
        $descriptions = $button_group_info['descriptions'];
      }

      $data['descriptions'] = $descriptions;
      $data['picture'] = isset($this->request->get['changed']) ? $this->request->post['btn_group_picture'] : $button_group_info['picture'];
      $data['color'] = isset($this->request->get['changed']) ? $this->request->post['btn_group_color'] : $button_group_info['color'];
      $data['background'] = isset($this->request->get['changed']) ? $this->request->post['btn_group_background'] : $button_group_info['background'];
      $data['button_group_hide_name'] = isset($this->request->get['changed']) ? $this->request->post['btn_group_hide_name'] : $button_group_info['hide_group_name'];
      $data['thumb'] = $this->model_tool_image->resize($data['picture'] ? $data['picture'] : 'no_image.png', 25, 25);
    }
    $data['button_group_uid'] = $button_group_uid;
    $this->response->setOutput($this->load->view('doctype/button_group_form', $data));
  }

  public function autocomplete_button_group()
  {
    $json = array();
    $container_uid = '';
    if (isset($this->request->get['route_uid'])) {
      $container_uid = $this->request->get['route_uid'];
    }
    if (isset($this->request->get['folder_uid'])) {
      $container_uid = $this->request->get['folder_uid'];
    }
    $filter_data = array(
      'filter_name' => $this->request->get['filter_name'] ?? "",
      'container_uid' => $container_uid ?? 0,
    );

    foreach ($this->model_doctype_doctype->getButtonGroups($filter_data) as $button_group) {
      if (!empty($this->request->get['route_uid'])) {
        $json[] = $button_group;
      }
    }
    $this->response->addHeader('Content-Type: application/json');
    $this->response->setOutput(json_encode($json));
  }
}
