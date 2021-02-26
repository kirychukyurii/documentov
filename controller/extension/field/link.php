<?php

/**
 * @package		LinkField
 * @author		Andrey V Surov
 * @copyright Copyright (c) 2018 Andrey V Surov, Roman V Zhukov (https://www.documentov.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		  https://www.documentov.com
 */

class ControllerExtensionFieldLink extends FieldController
{
  const FIELD_INFO = array(
    'methods' => array(
      array('type' => 'getter', 'name' => 'get_first_link'),
      array('type' => 'getter', 'name' => 'get_first_link_url'),
      array('type' => 'getter', 'name' => 'get_first_link_text'),
      array('type' => 'getter', 'name' => 'get_count_links'),
      array('type' => 'getter', 'name' => 'get_display_value_not_link'),
      array('type' => 'getter', 'name' => 'get_display_value_link'),
      array('type' => 'getter', 'name' => 'get_another_field_value', 'params' => array('another_field_uid')),
      array('type' => 'getter', 'name' => 'get_another_field_display', 'params' => array('another_field_uid')),
      array('type' => 'getter', 'name' => 'get_hierarchical_relationship', 'params' => array('parent_field_uid', 'type_relationship')),
      array('type' => 'getter', 'name' => 'get_doctype_name'),
      array('type' => 'getter', 'name' => 'get_doctype_uid'),
      array('type' => 'setter', 'name' => 'append_link', 'params' => array('standard_setter_param')),
      array('type' => 'setter', 'name' => 'remove_link', 'params' => array('standard_setter_param')),
      array('type' => 'setter', 'name' => 'remove_links', 'params' => array('orientation', 'count')),
    ),
    'MODULE_NAME' => 'FieldLink',
    'FILE_NAME'   => 'link'
  );

  public function setting()
  {
    $data['cancel'] = $this->url->link('marketplace/extension', 'type=field', true);
    $this->response->setOutput($this->load->view('extension/field/link', $data));
  }

  public function index()
  {
  }

  public function install()
  {
    $this->load->model('extension/field/link');
    $this->load->model('tool/utils');
    if ($this->model_tool_utils->isTable('field_value_link')) { // поле Текст есть?
      if ($this->request->server['REQUEST_METHOD'] == "POST") { // ответ о изменении типа Текст на Текст+ получили?
        $this->model_extension_field_link->install();
        if (!empty($this->request->post['change_type'])) {
          $this->model_tool_utils->changeFieldType('link', 'link');
        }
      } else {
        $data = array();
        $data['cancel'] = $this->url->link('marketplace/extension', 'type=field', true);
        $data['action'] = str_replace("&amp;", "&", $this->url->link('extension/extension/field/install', 'extension=link', true));
        return $this->load->view('extension/field/link_install', $data);
      }
    }
  }

  public function uninstall()
  {
    $this->load->model('extension/field/link');
    $this->model_extension_field_link->uninstall();
  }

  /**
   * Возвращает неизменяемую информацию о поле
   * @return array()
   */
  public function getFieldInfo()
  {
    return $this::FIELD_INFO;
  }

  /**
   * Метод возвращает название поля в соответствии с выбранным языком
   * @return type
   */
  public function getTitle()
  {
    $this->language->load('extension/field/link');
    return $this->language->get('heading_title');
  }

  /**
   * Метод возвращает описание параметров поля
   */
  public function getDescriptionParams($params)
  {
    $this->load->model('doctype/doctype');
    $result = array();
    if (!empty($params['doctype_uid'])) {
      $doctype_info = $this->model_doctype_doctype->getDoctype($params['doctype_uid']);
      if (isset($doctype_info['name'])) {
        $result[] = sprintf($this->language->get('text_description_doctype'), $doctype_info['name']);
      }
      if (!empty($params['doctype_field_uid'])) {
        $result[] = sprintf($this->language->get('text_description_field'), $this->model_doctype_doctype->getFieldName($params['doctype_field_uid']));
      }
    } else {
      return $this->language->get('text_none_type');
    }
    return implode("; ", $result);
  }

  /**
   * Возвращает форму поля для настройки администратором
   * @param type $data
   */
  public function getAdminForm($data)
  {
    $this->load->model('doctype/doctype');
    if (!empty($data['params']['doctype_uid'])) {
      $doctype_description = $this->model_doctype_doctype->getDoctypeDescriptions($data['params']['doctype_uid']);
      $data['doctype_name'] = isset($doctype_description[$this->config->get('config_language_id')]['name']) ? $doctype_description[$this->config->get('config_language_id')]['name'] : "";
    }
    if (!empty($data['params']['doctype_field_uid'])) {
      $field_info = $this->model_doctype_doctype->getField($data['params']['doctype_field_uid'], 1);
      $data['doctype_field_name'] = $field_info['name'] ?? "";
    }
    if (!empty($data['params']['source_type']) && $data['params']['source_type'] == "field") {
      //источник для автокомплита - поле
      if (!empty($data['params']['source_field_uid'])) {
        $source_field_info = $this->model_doctype_doctype->getField($data['params']['source_field_uid'], 1);
        $data['source_field_name'] = $source_field_info['name'] ?? "";
      }
    }
    if (!empty($data['params']['conditions'])) {
      $conditions = array();
      foreach ($data['params']['conditions'] as $condition) {
        $field_info_1 = $this->model_doctype_doctype->getField($condition['field_1_uid']);
        if (!empty($field_info_1['name'])) {
          $field_info_2 = $this->model_doctype_doctype->getField($condition['field_2_uid']);
          $conditions[] = array(
            'concat'            => $condition['concat'] ?? 0,
            'field_1_uid'        => $condition['field_1_uid'],
            'field_2_uid'        => $condition['field_2_uid'],
            'comparison'        => $condition['comparison'],
            'field_1_name'      => $field_info_1['name'],
            'field_2_name'      => $field_info_2['name'],
            'field_2_setting'   => $field_info_2['setting'],
            'method_name'       => $condition['method_name'] ?? ""
          );
        }
      }
      $data['params']['conditions'] = $conditions;
    }
    $data['MODULE_NAME'] = $this::FIELD_INFO['MODULE_NAME'];
    $data['FILE_NAME'] = $this::FIELD_INFO['FILE_NAME'];
    $data['text'] = $this->lang;
    return $this->load->view('field/link/link_form', $data)
      . $this->load->view('field/common_admin_form', array('data' => $data));
  }

  /**
   * Возвращает виджет поля для режима создания / редактирования поля
   *  $data = $field['params'], 'field_uid', 'document_uid'
   */
  public function getForm($data)
  {
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $field_uid = "";
    $field_info = [];
    if (empty($data['doctype_field_uid'])) {
      //не задано отображаемое поле
      if (!empty($data['doctype_uid'])) { // доктайп есть (не нетипизированная ссылка)
        //проверяем наличие заголовка
        $doctype_descriptions = $this->model_doctype_doctype->getDoctypeDescriptions($data['doctype_uid']);
        if (!empty($doctype_descriptions[$this->config->get('config_language_id')]['title_field_uid'])) {
          $field_uid = $doctype_descriptions[$this->config->get('config_language_id')]['title_field_uid'];
        } else {
          //заголовка нет, используем первое поле доктайпа            
          $data_f = array(
            'doctype_uid'   => $data['doctype_uid'],
            'setting'       => 0,
            'limit'         => 1
          );
          $fields = $this->model_doctype_doctype->getFields($data_f);
          $field_uid = $fields[0]['field_uid'] ?? "";
        }
      }
    } else {
      $field_uid = $data['doctype_field_uid'];
    }
    if ($field_uid) {
      $field_info = $this->model_doctype_doctype->getField($field_uid);

      if (!empty($data['list'])) {
        //документы отображаются в виде списка
        $filter_data = array(
          'filter_name' => "",
          'field_uids' => [['display' => $field_uid]]
        );
        if (!empty($data['source_type']) && $data['source_type'] == "field" && !empty($data['source_field_uid'])) {
          //источник данных - поле
          $source_field_value = $this->model_document_document->getFieldValue($data['source_field_uid'], $data['document_uid'] ?? 0);
          $filter_data['document_uids'] = explode(",", $source_field_value);
        } else {
          //все документы заданного доктайпа
          $filter_data['doctype_uid'] = $data['doctype_uid'];
        }
        //фильтры
        if (!empty($data['conditions'])) {
          foreach ($data['conditions'] as $condition) {
            if (!$condition['field_1_uid']) {
              continue;
            }
            $filter_value = "";
            if ($condition['field_2_uid']) {
              if (!empty($condition['method_name'])) {
                $field_2_info = $this->model_doctype_doctype->getField($condition['field_2_uid']);
                if ($field_2_info) {
                  $method_params = array(
                    'type' => $condition['type'],
                    'current_document_uid'  => $data['document_uid'] ?? '',
                    'field_uid' => $condition['field_2_uid'] ?? '',
                    'method_name' => $condition['method_name'],
                  );
                  $filter_value = $this->load->controller('extension/field/' . $field_2_info['type'] . '/executeMethod', $method_params);
                }
              } else {
                $filter_value = $this->model_document_document->getFieldValue($condition['field_2_uid'], $data['document_uid'] ?? 0);
              }
            }
            $filter_data['filter_names'][$condition['field_1_uid']][] = array(
              'value'     => $filter_value ?? "",
              'condition' => $condition['comparison']
            );
          }
        }

        $results = $this->model_document_document->getDocuments($filter_data);
        // print_r($results);
        // exit;

        $data['documents'] = array();
        $vfield = "v" . str_replace("-", "", $field_uid);
        foreach ($results as $result) {
          $data['documents'][] = array(
            'document_uid' => $result['document_uid'],
            'name' => strip_tags(html_entity_decode($result[$vfield], ENT_QUOTES, 'UTF-8')),

          );
        }
      }

      if (!empty($data['field_value']) || !empty($data['value'])) { //$data['value'] - значение может пробрасываться из виджета фильтра, например
        $vid = "";
        if (isset($data['field_value'])) {
          $vid = $data['field_value'];
        } else {
          $vid = $data['value'];
        }
        if (is_array($vid)) {
          $values = $vid;
        } else {
          $values = explode(",", $vid);
        }
        //проверка на несуществующие uid (например, док был удален)
        if ($values && $data['multi_select'] && empty($data['filter_form'])) {
          $data['values'] = array();
          foreach ($values as $value) {
            if (!$value) {
              continue;
            }
            $data['values'][] = array(
              'id'    => $value,
              'name'  => htmlentities(strip_tags(html_entity_decode($this->model_document_document->getFieldDisplay($field_uid, !$field_info['setting'] ? $value : 0))))
              //вырезаем теги, чтобы не показывать их при редактировании, если отображаемое поле, к примеру, текстовое с тегами
            );
          }
        } elseif ($values) {
          $data['value_id'] = $values[0];
          $data['value_name'] = htmlentities(strip_tags(html_entity_decode($this->model_document_document->getFieldDisplay($field_uid, !$field_info['setting'] ? $data['value_id'] : 0))));
          //вырезаем теги, чтобы не показывать их при редактировании, если отображаемое поле, к примеру, текстовое с тегами
        }
      }

      //фильтры
      if (!empty($data['conditions']) && empty($data['list'])) {
        //если фильтры и виджет в виде строки с автоподстановкой
        $filters = array();
        $i = 0;

        foreach ($data['conditions'] as $condition) {

          $filters[] = "filters[" . $i . "][concat]=" . (!empty($condition['concat']) ? "OR" : "AND") . "&filters[" . $i . "][field_uid]=" . $condition['field_1_uid'] . "&filters[" . $i . "][condition]=" . $condition['comparison'] . "&filters[" . $i++ . "][value]=" . $condition['field_2_uid'];
        }
        $data['filters'] = '&' . implode('&', $filters);
      }
    }



    $data = $this->setDefaultTemplateParams($data);

    $data['MODULE_NAME'] = $this::FIELD_INFO['MODULE_NAME'];
    $data['FILE_NAME'] = $this::FIELD_INFO['FILE_NAME'];
    $data['text'] = $this->lang;
    return $this->load->view('field/link/link_widget_form', $data)
      . $this->load->view('field/common_widget_form', array('data' => $data));
  }

  /**
   * Возвращает  поле для режима просмотра
   */
  public function getView($data)
  {

    $data = $this->setDefaultTemplateParams($data);
    $this->load->model('document/document');
    $this->load->model('extension/field/link');
    if (!empty($data['field_value'])) {
      if ($this->request->server['HTTPS']) {
        $server = $this->config->get('config_ssl');
      } else {
        $server = $this->config->get('config_url');
      }
      if (empty($data['doctype_uid'])) {
        //нетипизированная ссылка
        $this->load->model('tool/utils');
        $result = array();
        foreach (explode(',', $data['field_value']) as $value) {
          $document_uid = trim($value);
          if ($this->model_tool_utils->validateUID($document_uid)) {
            $document_title = $this->model_document_document->getDocumentTitle($document_uid);
            if ($document_title) {
              $data_link = array(
                'url'           => $this->url->link('document/document', 'document_uid=' . $document_uid),
                'document_uid'  => $document_uid,
                'text'          => $document_title
              );

              $result[] = $this->load->view('field/link/link_widget_view_link', $data_link);
            }
          }
        }
        $data['text'] = str_replace(["&#44", "&amp;#44;"], [",", ","], implode(", ", $result));

        return $this->load->view('field/link/link_widget_view', $data);
      }
      if (!empty($data['field_uid'])) {
        $field_value = $this->model_extension_field_link->getFieldValue($data['field_uid'], $data['document_uid'] ?? 0);
        if ($field_value && $data['field_value'] == $field_value['value'] && $field_value['full_display_value']) {
          $data['text'] = htmlspecialchars_decode(str_replace('href="index.php?route=', 'href="' . $server . 'index.php?route=', $field_value['full_display_value']));
          return $this->load->view('field/link/link_widget_view', $data);
        }
      }
      $data['text'] =  str_replace([", ", "&#44", "&amp;#44;"], [htmlspecialchars_decode($data['delimiter']), ",", ","], str_replace('href="index.php?route=', 'href="' . $server . 'index.php?route=', $this->model_extension_field_link->getDisplay($data['document_uid'] ?? "", $data['field_uid'] ?? "", $data['field_value'], array('params' => $data))));
    }
    return $this->load->view('field/link/link_widget_view', $data);
  }

  public function setParams($params)
  {
    $params['list'] = (int) $params['list'];
    $params['multi_select'] = (int) $params['multi_select'];
    $params['href'] = (int) $params['href'];
    $params['disabled_actualize'] = (int) ($params['disabled_actualize'] ?? 0);
    if (!empty($params['conditions'])) {
      foreach ($params['conditions'] as &$cond) {
        if (isset($cond['concat'])) {
          $cond['concat'] = (int) $cond['concat'];
        }
      }
      $params['conditions'] = array_values($params['conditions']);
    }
    if (!empty($params['source_type']) && $params['source_type'] != "field") {
      unset($params['source_field_uid']);
    }
    return $params;
  }

  //Метод возвращает форму настройки параметров метода
  public function getFieldMethodForm($data)
  {
    $this->language->load('extension/field/link');
    switch ($data['method_name']) {
      case "get_another_field_value":
        $this->load->model('doctype/doctype');
        $field_info = $this->model_doctype_doctype->getField($data['field_uid']);
        $data['doctype_uid'] = $field_info['params']['doctype_uid'];
        if (!empty($data['method_params']['another_field_uid'])) {
          $another_field_info = $this->model_doctype_doctype->getField($data['method_params']['another_field_uid']['value'], 1);
          $data['another_field_name'] = $another_field_info['name'];
        }
        return $this->load->view('field/link/method_another_field_form', $data);
      case "get_another_field_display":
        $this->load->model('doctype/doctype');
        $field_info = $this->model_doctype_doctype->getField($data['field_uid']);
        $data['doctype_uid'] = $field_info['params']['doctype_uid'];
        if (!empty($data['method_params']['another_field_uid'])) {
          $another_field_info = $this->model_doctype_doctype->getField($data['method_params']['another_field_uid']['value'], 1);
          $data['another_field_name'] = $another_field_info['name'];
        }
        return $this->load->view('field/link/method_another_field_form', $data);
      case "get_hierarchical_relationship":
        $this->load->model('doctype/doctype');
        $field_info = $this->model_doctype_doctype->getField($data['field_uid']);
        $data['doctype_uid'] = $field_info['params']['doctype_uid'];
        if (!empty($data['method_params']['parent_field_uid'])) {
          $data['parent_field_name'] = $this->model_doctype_doctype->getFieldName($data['method_params']['parent_field_uid']['value']);
        }
        return $this->load->view('field/link/method_hierarchical_relationship_form', $data);
      case "remove_links":
        return $this->load->view('field/link/method_remove_links_form', $data);
      default:
        return '';
    }
  }

  //геттеры
  public function get_first_link_url($params)
  {
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $value = explode(",", $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']))[0];
    $field_info = $this->model_doctype_doctype->getField($params['field_uid'], 0);
    $data_link = array(
      'url'           => str_replace('&amp;', '&', $this->url->link('document/document', 'document_uid=' . $value)),
      'text'          => $this->model_document_document->getFieldValue($field_info['params']['doctype_field_uid'], $value)
    );
    return $this->load->view('field/link/link_widget_view_link', $data_link);
  }

  public function get_first_link_text($params)
  {
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $value = explode(",", $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']))[0];
    $field_info = $this->model_doctype_doctype->getField($params['field_uid'], 0);
    $linktext = $this->model_document_document->getFieldValue($field_info['params']['doctype_field_uid'], $value);
    return $linktext;
  }

  public function get_first_link($params)
  {
    $this->load->model('document/document');
    $value = explode(",", $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']))[0];
    return $value;
  }

  public function get_count_links($params)
  {
    $this->load->model('document/document');
    $result = 0;
    foreach (explode(",", $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid'])) as $value) {
      if ($value) {
        $result++;
      }
    }
    return $result;
  }

  public function get_display_value_not_link($params)
  {
    $this->load->model('extension/field/link');
    $this->load->model('doctype/doctype');
    $field_info = $this->model_doctype_doctype->getField($params['field_uid']);
    $display = $this->model_extension_field_link->getDisplay($params['document_uid'], $params['field_uid'], "", $field_info);
    return trim(str_replace(", ", $field_info['params']['delimiter'], strip_tags($display)));
  }

  public function get_display_value_link($params)
  {
    $this->load->model('extension/field/link');
    $field_value = $this->model_extension_field_link->getFieldValue($params['field_uid'], $params['document_uid']);
    return $field_value['full_display_value'];
  }

  public function get_another_field_value($params)
  {
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    if (!empty($params['method_params']['another_field_uid'])) {
      $field_info = $this->model_doctype_doctype->getField($params['field_uid']);
      $values = explode(",", $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']));
      $result = array();
      foreach ($values as $document_uid) {
        $result[] = $this->model_document_document->getFieldValue($params['method_params']['another_field_uid'], $document_uid);
      }
      return implode($field_info['params']['delimiter'], $result);
    }
  }

  public function get_hierarchical_relationship($params)
  {
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    if (!empty($params['method_params']['parent_field_uid'])) {
      $field_info = $this->model_doctype_doctype->getField($params['field_uid']);
      $values = explode(",", $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']));
      $result = array();
      foreach ($values as $document_uid) {
        //получаем родственников по дереву для каждого document_uid
        switch ($params['method_params']['type_relationship']) {
          case 'brothers':
            $result = array_merge($result, $this->model_document_document->getBrothersDocuments($document_uid, $params['method_params']['parent_field_uid']));
            break;
          case 'descendants':
            $result = array_merge($result, $this->model_document_document->getDescendantsDocuments($document_uid, $params['method_params']['parent_field_uid']));
            break;
          default:
            $result = array_merge($result, $this->model_document_document->getAncestryDocuments($document_uid, $params['method_params']['parent_field_uid']));
        }
      }
      return implode($field_info['params']['delimiter'], $result);
    }
  }

  public function get_another_field_display($params)
  {
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    if (!empty($params['method_params']['another_field_uid'])) {
      $field_info = $this->model_doctype_doctype->getField($params['field_uid']);
      $values = explode(",", $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']));
      $result = array();
      foreach ($values as $document_uid) {
        $result[] = $this->model_document_document->getFieldDisplay($params['method_params']['another_field_uid'], $document_uid);
      }
      return implode($field_info['params']['delimiter'], $result);
    }
  }

  public function get_doctype_name($params)
  {
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $this->load->model('tool/utils');
    $value = explode(",", $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']))[0] ?? "";
    if ($value && $this->model_tool_utils->validateUID($value)) {
      $document_info = $this->model_document_document->getDocument($value, FALSE);
      if ($document_info) {
        $doctype_info = $this->model_doctype_doctype->getDoctype($document_info['doctype_uid']);
        return $doctype_info['name'];
      }
    }
    return "";
  }

  public function get_doctype_uid($params)
  {
    $this->load->model('document/document');
    $this->load->model('doctype/doctype');
    $this->load->model('tool/utils');
    $document_info = [];
    $value = explode(",", $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']))[0] ?? "";
    if ($value && $this->model_tool_utils->validateUID($value)) {
      $document_info = $this->model_document_document->getDocument($value, FALSE);
    }
    return $document_info['doctype_uid'] ?? "";
  }

  //сеттеры
  public function append_link($params)
  {
    $this->load->model('document/document');
    $val = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    $val = $val . "," . $params['method_params']['standard_setter_param'];
    $val = implode(",", array_unique(explode(",", $val)));
    return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], $val);
  }

  public function remove_link($params)
  {
    $this->load->model('document/document');
    $val = explode(",", $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']));
    $rmval = explode(",", $params['method_params']['standard_setter_param']);
    $newval = implode(",", array_unique(array_diff($val, $rmval)));
    return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], $newval);
  }

  public function remove_links($params)
  {
    $this->load->model('document/document');
    $value = $this->model_document_document->getFieldValue($params['field_uid'], $params['document_uid']);
    if (!$value) {
      return;
    }
    $values = explode(",", $value);
    if (!empty($params['method_params']['count']) && (int) $params['method_params']['count']) { //кол-во удаляемых ссылок      
      for ($i = 0; $i < (int) $params['method_params']['count'] && $values; $i++) {
        if ($params['method_params']['orientation'] === 'start') {
          array_shift($values);
        } else {
          array_pop($values);
        }
      }
    }
    return $this->model_document_document->editFieldValue($params['field_uid'], $params['document_uid'], $values);
  }
}
