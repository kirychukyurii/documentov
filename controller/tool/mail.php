<?php

class ControllerToolMail extends Controller
{
  const KOEF_REPEAT = 5;
  const MAX_REPEAT_TIME = 5000;
  public function send($data)
  {
    $this->load->language('tool/mail');
    if (!$this->validate($data)) {
      echo " " . sprintf($this->language->get('error_validate_data'), print_r($data, true));
      return;
    }
    if (!getenv('HTTP_HOST')) {
      //используется для EHLO, при пустом значении - ошибка
      if ($this->request->server['HTTPS']) {
        $server = HTTPS_SERVER;
      } else {
        $server = HTTP_SERVER;
      }
      $domain = parse_url($server);
      putenv("HTTP_HOST=" . $domain['host']);
    }

    echo " " . sprintf($this->language->get('text_send_mail'), $data['to']);
    $mail = new Mail($this->config->get('config_mail_engine'));
    $mail->parameter = $this->config->get('config_mail_parameter');
    $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
    $mail->smtp_username = $this->config->get('config_mail_smtp_username');
    $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
    $mail->smtp_port = $this->config->get('config_mail_smtp_port');
    $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');
    $mail->setTo($data['to']);
    $mail->setFrom($this->config->get('config_email'));
    $mail->setSender($this->config->get('config_name'));
    if (!$data['subject']) {
      $data['subject'] = $this->config->get('config_name');
    }
    $mail->setSubject(html_entity_decode($data['subject'], ENT_QUOTES, 'UTF-8'));
    $mail->setHtml($data['message']);
    try {
      $mail->send();
    } catch (Exception $e) {
      echo " " . $e->getMessage();
      //дублируем задание
      if (empty($data['repeat'])) {
        $data['repeat'] = 1;
      } else {
        $data['repeat'] *= $this::KOEF_REPEAT;
      }
      if ($data['repeat'] < $this::MAX_REPEAT_TIME) {
        $this->load->model('daemon/queue');
        $date_start = new DateTime("now");
        $date_start->add(new DateInterval("PT" . $data['repeat'] . "M"));
        echo ". " . $this->language->get('text_resending') . ": " . $date_start->format($this->language->get('datetime_format'));
        $this->model_daemon_queue->addTask('tool/mail/send', $data, 0, $date_start->format('Y-m-d H:i:s'));
      }
    }
  }

  private function validate($data)
  {
    if (empty($data['to']) || empty($data['message'])) {
      return false;
    }
    return true;
  }
}
