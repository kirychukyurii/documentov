<?php

class ControllerStartupStartup extends Controller
{

  public function index()
  {

    $this->config->set('config_url', HTTP_SERVER);
    $this->config->set('config_ssl', HTTPS_SERVER);
    //		}
    // Settings
    // $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "setting` ");

    // foreach ($query->rows as $result) {
    //   if (!$result['serialized']) {
    //     $this->config->set($result['key'], $result['value']);
    //   } else {
    //     $this->config->set($result['key'], json_decode($result['value'], true));
    //   }
    // }

    // // Переменные
    // try {
    //   $query_v = $this->db->query("SELECT * FROM " . DB_PREFIX . "variable");

    //   foreach ($query_v->rows as $result) {
    //     if (!$result['serialized']) {
    //       $this->variable->set($result['name'], $result['value']);
    //     } else {
    //       $this->variable->set($result['name'], unserialize($result['value']));
    //     }
    //   }
    // } catch (Exception $exc) {
    //   //
    // }

    // проверка на изменение имени базы, dbname обнуляет демон
    if ($this->variable->get('dbname') !== DB_DATABASE) {
      // база была изменена, чистим весь кэш
      $this->cache->clear();
      $this->load->model('setting/variable');
      $this->model_setting_variable->setVar('dbname', DB_DATABASE);
    }


    // Theme
    // $this->config->set('template_cache', $this->config->get('developer_theme'));

    // Url
    $this->registry->set('url', new Url($this->config->get('config_url'), $this->config->get('config_ssl')));

    // Language
    $code = '';

    $this->load->model('localisation/language');

    $languages = $this->model_localisation_language->getLanguages();

    if (isset($this->session->data['language'])) {
      $code = $this->session->data['language'];
    }
    #Todo: убрать потом жесткую прошивку кода языка
    //----------
    $code = 'ru-ru';
    //-------------
    if (isset($this->request->cookie['language']) && !array_key_exists($code, $languages)) {
      $code = $this->request->cookie['language'];
    }

    // Language Detection
    if (!empty($this->request->server['HTTP_ACCEPT_LANGUAGE']) && !array_key_exists($code, $languages)) {
      $detect = '';

      $browser_languages = explode(',', $this->request->server['HTTP_ACCEPT_LANGUAGE']);

      // Try using local to detect the language
      foreach ($browser_languages as $browser_language) {
        foreach ($languages as $key => $value) {
          if ($value['status']) {
            $locale = explode(',', $value['locale']);

            if (in_array($browser_language, $locale)) {
              $detect = $key;
              break 2;
            }
          }
        }
      }

      if (!$detect) {
        // Try using language folder to detect the language
        foreach ($browser_languages as $browser_language) {
          if (array_key_exists(strtolower($browser_language), $languages)) {
            $detect = strtolower($browser_language);

            break;
          }
        }
      }

      $code = $detect ? $detect : '';
    }

    if (!array_key_exists($code, $languages)) {
      $code = $this->config->get('config_language');
    }

    $customer = new Cart\Customer($this->registry);

    if ($customer->isLogged()) {
      $this->load->model('account/customer');
      $language_id = $this->model_account_customer->getLanguageId($customer->getId());
      //            echo $language_id;
      foreach ($languages as $lang) {
        if ($lang['language_id'] == $language_id) {
          $code = $lang['code'];
          break;
        }
      }
    }

    if (!isset($this->session->data['language']) || $this->session->data['language'] != $code) {
      $this->session->data['language'] = $code;
    }

    if (!isset($this->request->cookie['language']) || $this->request->cookie['language'] != $code) {
      setcookie('language', $code, (ini_get('session.cookie_lifetime') ? time() + ini_get('session.cookie_lifetime') : ini_get('session.cookie_lifetime')), ini_get('session.cookie_path'), ini_get('session.cookie_domain'));
    }

    // Overwrite the default language object
    $language = new Language($code);
    $language->load($code);

    $this->registry->set('language', $language);

    // Set the config language_id
    $this->config->set('config_language_id', $languages[$code]['language_id']);
    $this->config->set('config_language_name', explode("-", $code)[0]); // название языка для передачи демону

    // Customer
    $this->registry->set('customer', $customer);

    //проверяем обновления
    $route = $this->request->get['route'] ?? "";
    if ($route && empty($this->request->exception_route[$route])) {
      $this->load->model('tool/update');
      $this->model_tool_update->update();
    }
  }
}
