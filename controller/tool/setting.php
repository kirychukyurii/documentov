<?php

class ControllerToolSetting extends Controller
{

  public function index()
  {
    $this->load->model('tool/setting');

    $this->load->model('account/customer');
    $this->model_account_customer->setLastPage($this->url->link('tool/setting', '', true, true));

    $this->load->language('tool/setting');
    $this->document->setTitle($this->language->get('heading_title'));
    $data = array();
    $code_setting = array('dv_user', 'dv_zsystem');
    $data['settings'] = array();
    $settings = $this->model_tool_setting->getSettings($code_setting);
    foreach ($settings as $setting) {
      $value = $setting['value'];
      if ($setting['type'] == 'password' && $value) {
        $value = "*******";
      }
      $data['settings'][] = array(
        'setting_id'        => $setting['setting_id'],
        'code'              => $this->language->get("code_" . $setting['code']),
        'key'               => $this->language->get($setting['code'] . "_" . $setting['key']),
        'type'              => $setting['type'],
        'value'             => $value
      );
    }
    $this->load->model('localisation/language');
    $data['languages'] = $this->model_localisation_language->getLanguages('0,1');
    chdir(DIR_APPLICATION . 'language/');
    foreach (glob('*' . '*', GLOB_ONLYDIR) as $path) {
      if (!array_key_exists($path, $data['languages'])) {
        //есть неустановленный язык
        //проверим основной файл локализации
        $data['new_languages'][$path] = array('code' => $path);
        if (file_exists($path . "/" . $path . ".php")) {
          //ok
          //проверяем пиктограмму
          if (!file_exists($path . "/" . $path . ".png")) {
            //ok
            $data['new_languages'][$path]['error'] = sprintf($this->language->get('text_new_lang_error_picture'), $path);
          }
        } else {
          $data['new_languages'][$path]['error'] = sprintf($this->language->get('text_new_lang_error_main_file'), $path);
        }
      }
    }
    foreach ($data['languages'] as $language) {
      if (!file_exists($language['code'])) {
        $data['del_languages'][$language['language_id']] = $language['code'];
      }
    }

    $data['header'] = $this->load->controller('common/header');
    $data['footer'] = $this->load->controller('common/footer');
    $this->response->setOutput($this->load->view('tool/setting_list', $data));
  }

  /**
   * Возвращает настройку для Администрирование / Настройки
   * если type =password, скрывает значение настройки (например, пароль почты)
   */
  public function edit()
  {
    if (!empty($this->request->get['setting_id'])) {
      $this->load->model('tool/setting');
      $this->load->language('tool/setting');
      $setting_info = $this->model_tool_setting->getSetting($this->request->get['setting_id']);
      $value = $setting_info['value'];
      if ($setting_info['type'] == "password" && $value) {
        $value = "";
      }
      $data = array(
        'setting_id'        => $setting_info['setting_id'],
        'key'               => $this->language->get($setting_info['code'] . "_" . $setting_info['key']),
        'help'              => $this->language->get("help_" . $setting_info['code'] . "_" . $setting_info['key']),
        'type'              => $setting_info['type'] ? $setting_info['type'] : "text",
        'value'             => $value
      );
      $this->response->setOutput($this->load->view('tool/setting_form', $data));
    } elseif (!empty($this->request->get['language_id'])) {
      $this->load->model('localisation/language');
      $this->load->language('tool/setting');
      $language_info = $this->model_localisation_language->getLanguage($this->request->get['language_id']);
      $data = array(
        'language_id'       => $language_info['language_id'],
        'name'              => $language_info['name'],
        'code'              => $language_info['code'],
        'locale'            => $language_info['locale'],
        'image'             => $language_info['image'],
        'directory'         => $language_info['directory'],
        'sort_order'        => $language_info['sort_order'],
        'status'            => $language_info['status']
      );
      $this->response->setOutput($this->load->view('tool/language_form', $data));
    } elseif (!empty($this->request->get['language_code'])) {
      $this->load->language('tool/setting');
      $data = array(
        'language_id'       => 0,
        'name'              => "",
        'code'              => $this->request->get['language_code'],
        'locale'            => "",
        'image'             => "",
        'directory'         => "",
        'sort_order'        => "",
        'status'            => 0
      );
      $this->response->setOutput($this->load->view('tool/language_form', $data));
    } elseif (!empty($this->request->get['remove_language_id'])) {
      //удаляем язык
      $this->load->model('localisation/language');
      $this->model_localisation_language->deleteLanguage($this->request->get['remove_language_id']);
      $json = array();
      $json['success'] = $this->request->get['remove_language_id'];
      $this->response->addHeader("Content-type: application/json");
      $this->response->setOutput(json_encode($json));
    }
  }

  public function save()
  {
    if (!empty($this->request->get['setting_id']) && isset($this->request->post['value'])) {
      $this->load->model('tool/setting');
      $this->model_tool_setting->editSetting($this->request->get['setting_id'], $this->request->post['value']);
      $json = array();
      $json['success'] = 1;
      $this->response->addHeader("Content-type: application/json");
      $this->response->setOutput(json_encode($json));
    } elseif (!empty($this->request->get['language_id'])) {
      $this->load->model('localisation/language');
      $this->model_localisation_language->editLanguage($this->request->get['language_id'], $this->request->post);
      $json = array();
      $json['success'] = $this->request->get['language_id'];
      $this->response->addHeader("Content-type: application/json");
      $this->response->setOutput(json_encode($json));
    } elseif (isset($this->request->get['language_id']) && !empty($this->request->post['name'])) {
      $this->load->model('localisation/language');
      //        $this->model_localisation_language->addLanguage($this->request->post);
      $json = array();
      $json['success'] = $this->model_localisation_language->addLanguage($this->request->post);
      $this->response->addHeader("Content-type: application/json");
      $this->response->setOutput(json_encode($json));
    }
  }
}
