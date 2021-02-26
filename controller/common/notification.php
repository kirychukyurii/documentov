<?php

class ControllerCommonNotification extends Controller
{

  public function get_notifications()
  {
    if (isset($this->request->get['token'])) {
      $this->load->model('account/customer');
      $token_info = $this->model_account_customer->getTokenInfo($this->request->get['token']);
      //$params = unserialize($token_info['params']);
      $structure_uid = $token_info['account_uid'];
      //$this->request->get = $params;
    } else {
      $structure_uid = $this->customer->getStructureId();
    }

    $this->load->model('document/document');
    if (isset($this->request->get['count'])) {
      $notification_count = $this->model_document_document->getNotificationCount($structure_uid);
      $this->response->setOutput(json_encode($notification_count));
    } else {
      $notifications = $this->model_document_document->getNotifications($structure_uid);
      if ($notifications) {
        $this->response->setOutput(json_encode($notifications));
      }
    }
  }

  public function notification_control()
  {
    if (isset($this->request->get['token'])) {
      $this->load->model('account/customer');
      $token_info = $this->model_account_customer->getTokenInfo($this->request->get['token']);
      $structure_uid = $token_info['account_uid'];
    } else {
      $structure_uid = $this->customer->getStructureId();
    }

    $this->load->model('document/document');
    if (isset($this->request->get['count_datepoint'])) {
      $date_point_ut = $this->request->get['count_datepoint'];
      $date_point = new DateTime();
      $date_point->setTimestamp($date_point_ut);
      $notification_count = $this->model_document_document->getNotificationCountDatePoint($structure_uid, $date_point);
      $this->response->setOutput(json_encode($notification_count));
      return;
    } elseif (isset($this->request->get['after_datepoint'])) {
      $date_point_ut  = intval($this->request->get['after_datepoint']);
      $date_point = new DateTime();
      $date_point->setTimestamp($date_point_ut);
      $notifications = $this->model_document_document->getNotificationsAfterDatePoint($structure_uid, $date_point);
    } elseif (isset($this->request->get['before_datepoint'])) {
      $date_point_ut  = intval($this->request->get['before_datepoint']);
      $date_point = new DateTime();
      $date_point->setTimestamp($date_point_ut);
      $notifications = $this->model_document_document->getNotificationsBeforeDatePoint($structure_uid, $date_point);
    } else {
      $notifications = $this->model_document_document->getAllNotifications($structure_uid);
    }
    if ($notifications) {
      $this->response->setOutput(json_encode($notifications));
    }
  }

  public function get_notification_count()
  {
    if (isset($this->request->get['token'])) {
      $this->load->model('account/customer');
      $token_info = $this->model_account_customer->getTokenInfo($this->request->get['token']);
      //$params = unserialize($token_info['params']);
      $structure_uid = $token_info['account_uid'];
      //$this->request->get = $params;
    } else {
      $structure_uid = $this->customer->getStructureId();
    }
    /* if (!$this->customer->getStructureId()) {
      return "";
      } */

    $this->load->model('document/document');
    $notification_count = $this->model_document_document->getNotificationCount($structure_uid);
    $this->response->setOutput(json_encode($notification_count));
  }

  public function remove_notification()
  {
    $this->load->model('document/document');
    $structure_uid = $this->customer->getStructureId();
    if ($structure_uid) {
      $this->model_document_document->removeNotifications($this->request->get['document_uid'], $structure_uid);
    }
    $this->response->setOutput("ok");
  }

  public function get_notifications_token()
  {
    $this->load->model('account/customer');
    $structure_uid = $this->customer->getStructureId();
    //$route = "common/notification/get_new_notifications";
    $route = "common/notification/notification_control";
    $params = array();
    $token = $this->model_account_customer->setToken($structure_uid, $route, serialize($params));
    $this->response->setOutput(json_encode($token, JSON_UNESCAPED_SLASHES));
  }
}
