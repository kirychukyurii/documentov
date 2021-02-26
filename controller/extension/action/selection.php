<?php

/**
 * @package		SelectionAction
 * @author		Roman V Surov
 * @copyright Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		  https://www.documentov.com
 */

class ControllerExtensionActionSelection extends ActionController
{
  const ACTION_INFO = array(
    'name' => 'selection',
    'inRouteContext' => true,
  );

  public function index()
  {
    $this->load->language('extension/action/selection');

    $data['cancel'] = $this->url->link('marketplace/extension', 'type=action', true);

    $this->response->setOutput($this->load->view('extension/action/selection', $data));
  }

  public function install()
  {
  }

  public function uninstall()
  {
  }

  /**
   * Метод позволяет изменить сохраняемые в базу параметры действия (при необходимости)
   * @param type $data
   * @return type
   */
  public function setParams($data)
  {
    if (!empty($data['params']['action']['conditions'])) {
      foreach ($data['params']['action']['conditions'] as &$cond) {
        if (isset($cond['concat'])) {
          $cond['concat'] = empty($cond['concat']) ? 0 : 1;
        }
      }
      $data['params']['action']['conditions'] = array_values($data['params']['action']['conditions']);
    }
    return $data['params']['action'];
  }

  /**
   * Метод возвращает описание действия, исходя из параметров
   */
  public function getDescription($params)
  {
    $this->load->language('action/selection');
    $this->load->model('doctype/doctype');
    $description = array();
    if (!empty($params['document_source'])) {
      switch ($params['document_source']) {
        case "document_field":
          if (!empty($params['document_field_uid'])) {
            $field_info = $this->model_doctype_doctype->getField($params['document_field_uid'], 1);
            $description[] = sprintf($this->language->get('text_description_document_field'), $field_info['name'] ?? "");
          }
          break;
        case "doctype_list":
          if (!empty($params['doctype_uid'])) {
            $doctype_info = $this->model_doctype_doctype->getDoctype($params['doctype_uid']);
            $description[] = sprintf($this->language->get('text_description_doctype_list'), $doctype_info['name'] ?? "");
            $params['action']['doctype_name'] = $doctype_info['name'] ?? "";
          }
          break;
        case "doctype_field":
          if (!empty($params['doctype_field_uid'])) {
            $field_info = $this->model_doctype_doctype->getField($params['doctype_field_uid'], 1);
            $description[] = sprintf($this->language->get('text_description_doctype_field'), $field_info['name'] ?? "");
          }
          break;
      }
    }
    if ($description) {
      if (!empty($params['field_result_uid'])) {
        $field_info = $this->model_doctype_doctype->getField($params['field_result_uid']);
        $description[] = sprintf($this->language->get('text_description_field_result'), $field_info['name']);
      }

      return implode("; ", $description);
    }
    return $this->language->get('text_description_without_field_document');
  }


  /**
   * Метод возвращает форму действия для типа документа
   * @param type $data - массив, включающий doctype_uid, route_uid
   */
  public function getForm($data)
  {
    $this->load->language('action/selection');
    $this->load->language('doctype/doctype');
    $this->load->model('doctype/doctype');
    if (!empty($data['action']['document_source'])) {
      switch ($data['action']['document_source']) {
        case "document_field":
          if (!empty($data['action']['document_field_uid'])) {
            $field_info = $this->model_doctype_doctype->getField($data['action']['document_field_uid'], 1);
            $data['action']['document_field_name'] = $field_info['name'] ?? "";
          }
          break;
        case "doctype_list":
          if (!empty($data['action']['doctype_uid'])) {
            $doctype_info = $this->model_doctype_doctype->getDoctype($data['action']['doctype_uid']);
            $data['action']['doctype_name'] = $doctype_info['name'] ?? "";
          }
          break;
        case "doctype_field":
          if (!empty($data['action']['doctype_field_uid'])) {
            $field_info = $this->model_doctype_doctype->getField($data['action']['doctype_field_uid'], 1);
            $data['action']['doctype_field_name'] = $field_info['name'] ?? "";
          }
          break;
      }

      if (!empty($data['action']['conditions'])) {
        foreach ($data['action']['conditions'] as &$condition) {
          // раньше использовался id, патча не было
          $condition = $this->patchCondition($condition);

          if (empty($condition['field_uid']) && !empty($condition['field_id'])) {
            $condition['field_uid'] = $condition['field_id'];
          }
          $field_info_1 = $this->model_doctype_doctype->getField($condition['field_1_uid'] ?? $condition['field_1_uid']);
          $doctype_info_1 = $this->model_doctype_doctype->getDoctype($field_info_1['doctype_uid']);
          $doctype_name_1 = "";
          if (isset($doctype_info_1['name'])) {
            $doctype_name_1 = $doctype_info_1['name'] . " - ";
          }
          $field_info_2 = $this->model_doctype_doctype->getField($condition['field_2_uid'] ?? $condition['field_2_uid']);
          if ($data['action']['document_source'] !== "doctype_list") {
            $condition['field_1_name'] = $doctype_name_1;
          } else {
            $condition['field_1_name'] = "";
          }
          $condition['field_1_name'] .= isset($field_info_1['name']) ? $field_info_1['name'] : "";
          $condition['field_2_name'] = isset($field_info_2['name']) ? $field_info_2['name'] : "";
        }
      }
    }
    if (!empty($data['action']['type_result'])) {
      switch ($data['action']['type_result']) {
        case "documents":
          if (!empty($data['action']['sort_field_uid'])) {
            $sort_field_info = $this->model_doctype_doctype->getField($data['action']['sort_field_uid'], 1);
            $data['sort_field_name'] = $sort_field_info['name'] ?? "";
          }
          if (!empty($data['action']['limit_total_field_uid'])) {
            $total_field_info = $this->model_doctype_doctype->getField($data['action']['limit_total_field_uid'], 1, true);
            $data['action']['limit_total_field_name'] = $total_field_info['name'];
            // if (!empty($total_field_info['setting']) && !empty($total_field_info['doctype_uid'])) {
            //   $doctype_info = $this->model_doctype_doctype->getDoctype($total_field_info['doctype_uid']);
            // }
            // $data['action']['limit_total_field_name'] = ($doctype_info['name'] . " - " ?? "") . ($total_field_info['name'] ?? "");
          }
          if (!empty($data['action']['limit_start_field_uid'])) {
            $start_field_info = $this->model_doctype_doctype->getField($data['action']['limit_start_field_uid'], 1, true);
            $data['action']['limit_start_field_name'] = $start_field_info['name'];
            // if (!empty($start_field_info['setting']) && !empty($start_field_info['doctype_uid'])) {
            //   $doctype_info = $this->model_doctype_doctype->getDoctype($start_field_info['doctype_uid']);
            // }
            // $data['action']['limit_start_field_name'] = ($doctype_info['name'] . " - " ?? "") . ($start_field_info['name'] ?? "");
          }
          break;
        case "sum";
          if (!empty($data['action']['sum_field_uid'])) {
            $sum_field_info = $this->model_doctype_doctype->getField($data['action']['sum_field_uid'], 1);
            $data['sum_field_name'] = $sum_field_info['name'] ?? "";
          }
          break;
        case "max";
          if (!empty($data['action']['max_field_uid'])) {
            $max_field_info = $this->model_doctype_doctype->getField($data['action']['max_field_uid'], 1);
            $data['max_field_name'] = $max_field_info['name'] ?? "";
          }
          break;
        case "min":
          if (!empty($data['action']['min_field_uid'])) {
            $min_field_info = $this->model_doctype_doctype->getField($data['action']['min_field_uid'], 1);
            $data['min_field_name'] = $min_field_info['name'] ?? "";
          }
          break;
      }
    }
    if (!empty($data['action']['field_result_uid'])) {
      $field_info = $this->model_doctype_doctype->getField($data['action']['field_result_uid']);
      $data['action']['field_result_name'] = $field_info['name'];
    }
    return $this->load->view('action/selection/selection_form', $data);
  }

  /**
   * Возвращает неизменяемую информацию о действии
   * @return array()
   */
  public function getActionInfo()
  {
    return $this::ACTION_INFO;
  }


  /**
   * 
   * @param type $data  = array('document_uid', 'button_uid', 'params');
   */
  public function executeButton($data)
  {
  }


  public function executeRoute($data)
  {
    $data_daemon = [
      'uid' => $data['params']['uid'],
      'document_uid' => $data['document_uid'],
      'session' => [
        'user_uid'  => $this->customer->getStructureId(),
        'language_id'   => (int) $this->config->get('config_language_id'),
        'pressed_button_uid' => $this->session->data['current_button_uid'] ?? "",
        'changed_field_uid' => $this->request->get['field_uid'] ?? "",
        'changed_field_value' => $this->request->get['field_value'] ?? "",
        'folder_uid' => $this->request->get['folder_uid'] ?? "",
      ],
    ];

    $result = $this->daemon->exec("ExecuteAction", $data_daemon);
    return $result;
    ///////////////// the end /////////////////

    $this->load->language('action/selection');
    if (!empty($data['params']['document_source']) && !empty($data['params']['field_result_uid'])) {
      $this->load->model('document/document');
      $this->load->model('doctype/doctype');
      $document_uid = $data['document_uid'];
      switch ($data['params']['document_source']) {
        case "document_field":
          $value = $this->model_document_document->getFieldValue($data['params']['document_field_uid'], $document_uid);
          $data_docs = array(
            'filter_names'  => array(),
            'document_uids'  => explode(",", $value)
          );
          break;
        case "doctype_list":
          $data_docs = array(
            'filter_names'  => array(),
            'doctype_uids'  => array($data['params']['doctype_uid'])
          );
          break;
        case "doctype_field":
          $value = $this->model_document_document->getFieldValue($data['params']['doctype_field_uid'], $document_uid);
          $data_docs = array(
            'filter_names'  => array(),
            'doctype_uids'  => explode(",", $value)
          );
          break;
      }

      if (!empty($data['params']['conditions'])) {
        foreach ($data['params']['conditions'] as $condition) {
          $condition = $this->patchCondition($condition);
          $field_2_value = $this->model_document_document->getFieldValue($condition['field_2_uid'], $document_uid);
          $data_docs['filter_names'][$condition['field_1_uid']][] = array(
            'value'         => $field_2_value ? $field_2_value : "", //условие нужно, чтобы не передавать NULL
            'comparison'    => $condition['comparison'],
            'concat'        => !empty($condition['concat']) ? "or" : "and"
          );
        }
      }
      if ($data['params']['type_result'] == "documents") {
        if (!empty($data['params']['sort_field_uid'])) {
          $data_docs['sort'] = $data['params']['sort_field_uid'];
          $data_docs['order'] = $data['params']['sort_order'] ?? "asc";
        }
        if (!empty($data['params']['limit_total_field_uid'])) {
          $data_docs['limit'] = $this->model_document_document->getFieldValue($data['params']['limit_total_field_uid'], $document_uid);
          if (!empty($data['params']['limit_start_field_uid'])) {
            $data_docs['start'] = $this->model_document_document->getFieldValue($data['params']['limit_start_field_uid'], $document_uid);
          } else {
            $data_docs['start'] = 0;
          }
        }
      } elseif ($data['params']['type_result'] == "count") {
        $data_docs['function'] = "COUNT";
      } elseif ($data['params']['type_result'] == "sum") {
        $data_docs['function'] = "SUM";
        $data_docs['function_join'] = $data['params']['sum_field_uid'];
      } elseif ($data['params']['type_result'] == "max") {
        $data_docs['function'] = "MAX";
        $data_docs['function_join'] = $data['params']['max_field_uid'];
      } elseif ($data['params']['type_result'] == "min") {
        $data_docs['function'] = "MIN";
        $data_docs['function_join'] = $data['params']['min_field_uid'];
      }

      $result = $this->model_document_document->getDocumentIds($data_docs);
      if (is_array($result)) {
        $result = implode(",", $result);
      } else {
        if ($result === NULL) {
          $result = "";
        }
      }
      $result = $this->model_document_document->editFieldValue($data['params']['field_result_uid'], $document_uid, $result);
      return $result;
    } else {
      return array(
        'log'   => $this->language->get('error_document_not_found')
      );
    }
  }

  // раньше использовался field_(1|2)_id, патча не было
  private function patchCondition($condition)
  {
    if (empty($condition['field_1_uid']) && !empty($condition['field_1_uid'])) {
      $condition['field_1_uid'] = $condition['field_1_uid'];
    }
    if (empty($condition['field_2_uid']) && !empty($condition['field_2_uid'])) {
      $condition['field_2_uid'] = $condition['field_2_uid'];
    }
    return $condition;
  }
}
