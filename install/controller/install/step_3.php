<?php

class ControllerInstallStep3 extends Controller
{

  private $error = array();

  public function index()
  {
    $this->load->language('install/step_3');

    if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
      $this->load->model('install/install');

      $this->model_install_install->database($this->request->post);

      $output = '<?php' . "\n";
      $output .= '// HTTP' . "\n";
      $output .= 'define(\'HTTP_SERVER\', \'' . HTTP_DOCUMENTOV . '\');' . "\n\n";

      $output .= '// HTTPS' . "\n";
      $output .= 'define(\'HTTPS_SERVER\', \'' . HTTP_DOCUMENTOV . '\');' . "\n\n";

      $output .= '// DIR' . "\n";
      $output .= 'define(\'DIR_APPLICATION\', \'' . addslashes(DIR_DOCUMENTOV) . '\');' . "\n";
      $output .= 'define(\'DIR_SYSTEM\', \'' . addslashes(DIR_DOCUMENTOV) . 'system/\');' . "\n";
      $output .= 'define(\'DIR_IMAGE\', \'' . addslashes(DIR_DOCUMENTOV) . 'image/\');' . "\n";
      $output .= 'define(\'URL_IMAGE\', \'image/\');' . "\n";
      $output .= 'define(\'DIR_STORAGE\', DIR_SYSTEM . \'storage/\');' . "\n";
      $output .= 'define(\'DIR_LANGUAGE\', DIR_APPLICATION . \'language/\');' . "\n";
      $output .= 'define(\'DIR_TEMPLATE\', DIR_APPLICATION . \'view/theme/\');' . "\n";
      $output .= 'define(\'DIR_CONFIG\', DIR_SYSTEM . \'config/\');' . "\n";
      $output .= 'define(\'DIR_CACHE\', DIR_STORAGE . \'cache/\');' . "\n";
      $output .= 'define(\'DIR_DOWNLOAD\', DIR_STORAGE . \'download/\');' . "\n";
      $output .= 'define(\'DIR_LOGS\', DIR_STORAGE . \'logs/\');' . "\n";
      $output .= 'define(\'DIR_MODIFICATION\', DIR_STORAGE . \'modification/\');' . "\n";
      $output .= 'define(\'DIR_SESSION\', DIR_STORAGE . \'session/\');' . "\n";
      $output .= 'define(\'DIR_UPLOAD\', DIR_STORAGE . \'upload/\');' . "\n\n";

      $output .= '// DB' . "\n";
      $output .= 'define(\'DB_DRIVER\', \'' . addslashes($this->request->post['db_driver']) . '\');' . "\n";
      $output .= 'define(\'DB_HOSTNAME\', \'' . addslashes($this->request->post['db_hostname']) . '\');' . "\n";
      $output .= 'define(\'DB_USERNAME\', \'' . addslashes($this->request->post['db_username']) . '\');' . "\n";
      $output .= 'define(\'DB_PASSWORD\', \'' . addslashes(html_entity_decode($this->request->post['db_password'], ENT_QUOTES, 'UTF-8')) . '\');' . "\n";
      $output .= 'define(\'DB_DATABASE\', \'' . addslashes($this->request->post['db_database']) . '\');' . "\n";
      $output .= 'define(\'DB_PORT\', \'' . addslashes($this->request->post['db_port']) . '\');' . "\n";
      //            $output .= 'define(\'DB_PREFIX\', \'' . addslashes($this->request->post['db_prefix']) . '\');';
      $output .= 'define(\'DB_PREFIX\', \'\');';

      $file = fopen(DIR_DOCUMENTOV . 'config.php', 'w');

      fwrite($file, $output);

      fclose($file);

      $this->response->redirect($this->url->link('install/step_4'));
    }

    $this->document->setTitle($this->language->get('heading_title'));

    $data['heading_title'] = $this->language->get('heading_title');

    $data['text_step_3'] = $this->language->get('text_step_3');
    $data['text_db_connection'] = $this->language->get('text_db_connection');
    $data['text_db_administration'] = $this->language->get('text_db_administration');
    $data['text_mysqli'] = $this->language->get('text_mysqli');
    //        $data['text_mpdo'] = $this->language->get('text_mpdo');
    //        $data['text_pgsql'] = $this->language->get('text_pgsql');
    $data['text_db_storage'] = $this->language->get('text_db_storage');

    $data['entry_db_driver'] = $this->language->get('entry_db_driver');
    $data['entry_db_hostname'] = $this->language->get('entry_db_hostname');
    $data['entry_db_username'] = $this->language->get('entry_db_username');
    $data['entry_db_password'] = $this->language->get('entry_db_password');
    $data['entry_db_database'] = $this->language->get('entry_db_database');
    $data['entry_db_database_info'] = $this->language->get('entry_db_database_info');
    $data['entry_db_port'] = $this->language->get('entry_db_port');
    $data['entry_db_prefix'] = $this->language->get('entry_db_prefix');
    $data['entry_orgname'] = $this->language->get('entry_orgname');
    $data['entry_username'] = $this->language->get('entry_username');
    $data['entry_usersurname'] = $this->language->get('entry_usersurname');
    $data['entry_password'] = $this->language->get('entry_password');
    $data['entry_email'] = $this->language->get('entry_email');
    $data['entry_email_info'] = $this->language->get('entry_email_info');
    $data['entry_storage_location'] = $this->language->get('entry_storage_location');
    $data['button_continue'] = $this->language->get('button_continue');
    $data['button_back'] = $this->language->get('button_back');

    if (isset($this->error['warning'])) {
      $data['error_warning'] = $this->error['warning'];
    } else {
      $data['error_warning'] = '';
    }

    if (isset($this->error['db_hostname'])) {
      $data['error_db_hostname'] = $this->error['db_hostname'];
    } else {
      $data['error_db_hostname'] = '';
    }

    if (isset($this->error['db_username'])) {
      $data['error_db_username'] = $this->error['db_username'];
    } else {
      $data['error_db_username'] = '';
    }

    if (isset($this->error['db_database'])) {
      $data['error_db_database'] = $this->error['db_database'];
    } else {
      $data['error_db_database'] = '';
    }

    if (isset($this->error['db_port'])) {
      $data['error_db_port'] = $this->error['db_port'];
    } else {
      $data['error_db_port'] = '';
    }

    if (isset($this->error['db_prefix'])) {
      $data['error_db_prefix'] = $this->error['db_prefix'];
    } else {
      $data['error_db_prefix'] = '';
    }

    if (isset($this->error['orgname'])) {
      $data['error_orgname'] = $this->error['orgname'];
    } else {
      $data['error_orgname'] = '';
    }

    if (isset($this->error['username'])) {
      $data['error_username'] = $this->error['username'];
    } else {
      $data['error_username'] = '';
    }

    if (isset($this->error['usersurname'])) {
      $data['error_usersurname'] = $this->error['usersurname'];
    } else {
      $data['error_usersurname'] = '';
    }

    if (isset($this->error['password'])) {
      $data['error_password'] = $this->error['password'];
    } else {
      $data['error_password'] = '';
    }

    if (isset($this->error['email'])) {
      $data['error_email'] = $this->error['email'];
    } else {
      $data['error_email'] = '';
    }

    $data['action'] = $this->url->link('install/step_3');

    if (isset($this->request->post['db_driver'])) {
      $data['db_driver'] = $this->request->post['db_driver'];
    } else {
      $data['db_driver'] = '';
    }

    if (isset($this->request->post['db_hostname'])) {
      $data['db_hostname'] = $this->request->post['db_hostname'];
    } else {
      $data['db_hostname'] = 'localhost';
    }

    if (isset($this->request->post['db_username'])) {
      $data['db_username'] = $this->request->post['db_username'];
    } else {
      $data['db_username'] = 'root';
    }

    if (isset($this->request->post['db_password'])) {
      $data['db_password'] = $this->request->post['db_password'];
    } else {
      $data['db_password'] = '';
    }

    if (isset($this->request->post['db_database'])) {
      $data['db_database'] = $this->request->post['db_database'];
    } else {
      $data['db_database'] = '';
    }

    if (isset($this->request->post['db_port'])) {
      $data['db_port'] = $this->request->post['db_port'];
    } else {
      $data['db_port'] = 3306;
    }

    if (isset($this->request->post['db_prefix'])) {
      $data['db_prefix'] = $this->request->post['db_prefix'];
    } else {
      $data['db_prefix'] = '';
    }

    if (isset($this->request->post['username'])) {
      $data['username'] = $this->request->post['username'];
    } else {
      $data['username'] = '';
    }

    if (isset($this->request->post['usersurname'])) {
      $data['usersurname'] = $this->request->post['usersurname'];
    } else {
      $data['usersurname'] = '';
    }

    if (isset($this->request->post['password'])) {
      $data['password'] = $this->request->post['password'];
    } else {
      $data['password'] = '';
    }

    if (isset($this->request->post['email'])) {
      $data['email'] = $this->request->post['email'];
    } else {
      $data['email'] = '';
    }

    if (isset($this->request->post['orgname'])) {
      $data['orgname'] = $this->request->post['orgname'];
    } else {
      $data['orgname'] = '';
    }
    if (isset($this->request->post['storage_location'])) {
      $data['storage_location'] = $this->request->post['storage_location'];
    } else {
      $data['storage_location'] = '';
    }


    $data['mysqli'] = extension_loaded('mysqli');
    //        $data['mysql'] = extension_loaded('mysql');
    //        $data['pdo'] = extension_loaded('pdo');
    //        $data['pgsql'] = extension_loaded('pgsql');

    $data['back'] = $this->url->link('install/step_2');

    $data['footer'] = $this->load->controller('common/footer');
    $data['header'] = $this->load->controller('common/header');
    $data['column_left'] = $this->load->controller('common/column_left');

    $this->response->setOutput($this->load->view('install/step_3', $data));
  }

  private function validate()
  {
    if (!$this->request->post['db_hostname']) {
      $this->error['db_hostname'] = $this->language->get('error_db_hostname');
    }

    if (!$this->request->post['db_username']) {
      $this->error['db_username'] = $this->language->get('error_db_username');
    }

    if (!$this->request->post['db_database']) {
      $this->error['db_database'] = $this->language->get('error_db_database');
    }

    if (!$this->request->post['db_port']) {
      $this->error['db_port'] = $this->language->get('error_db_port');
    }

    if ($this->request->post['db_prefix'] && preg_match('/[^a-z0-9_]/', $this->request->post['db_prefix'])) {
      $this->error['db_prefix'] = $this->language->get('error_db_prefix');
    }

    if ($this->request->post['db_driver'] == 'mysqli' && $this->request->post['db_hostname'] && $this->request->post['db_username'] && $this->request->post['db_password'] && $this->request->post['db_database'] && $this->request->post['db_port']) {
      try {
        $db = new \DB\MySQLi($this->request->post['db_hostname'], $this->request->post['db_username'], html_entity_decode($this->request->post['db_password'], ENT_QUOTES, 'UTF-8'), $this->request->post['db_database'], $this->request->post['db_port']);

        if ($db->getServerVersion() < "50500") {
          $this->error['warning'] = $this->language->get('error_version_mysql');
        }
        if (is_resource($db)) {
          $db->close();
        }
      } catch (Exception $e) {
        $this->error['warning'] = $db->connect_error;
      }
    } elseif ($this->request->post['db_driver'] == 'mpdo') {
      try {
        $db = new \DB\mPDO($this->request->post['db_hostname'], $this->request->post['db_username'], html_entity_decode($this->request->post['db_password'], ENT_QUOTES, 'UTF-8'), $this->request->post['db_database'], $this->request->post['db_port']);

        if (is_resource($db)) {
          $db->close();
        }
      } catch (Exception $e) {
        $this->error['warning'] = $e->getMessage();
      }
    }

    if (!$this->request->post['username']) {
      $this->error['username'] = $this->language->get('error_username');
    }
    if (!$this->request->post['orgname']) {
      $this->error['orgname'] = $this->language->get('error_orgname');
    }

    if (!$this->request->post['usersurname']) {
      $this->error['usersurname'] = $this->language->get('error_usersurname');
    }

    if (!$this->request->post['password']) {
      $this->error['password'] = $this->language->get('error_password');
    }

    if ((utf8_strlen($this->request->post['email']) > 96) || !filter_var($this->request->post['email'], FILTER_VALIDATE_EMAIL)) {
      $this->error['email'] = $this->language->get('error_email');
    }

    if (!is_writable(DIR_DOCUMENTOV . 'config.php')) {
      $this->error['warning'] = $this->language->get('error_config') . DIR_DOCUMENTOV . 'config.php!';
    }

    /* $storage_location = $this->request->post['storage_location'];
          if ($storage_location !== "" && substr($storage_location, -1) !== "/") {
          $storage_location .= "/";
          }
          if ($storage_location !== "" && !(DIR_SYSTEM . 'storage/' === $storage_location)) {
          error_reporting(0);
          if (!is_writable($storage_location) || !is_dir($storage_location)) {
          $this->error['warning'] = $this->language->get('error_storage_location');
          } else {
          //перенос каталога storage

          if (!rename(DIR_SYSTEM . 'storage', $storage_location)) {
          $this->error['warning'] = $this->language->get('error_storage_location');
          }

          }
          error_reporting(E_ALL ^ E_WARNING);
          } */

    return !$this->error;
  }
}
